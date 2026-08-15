<?php

namespace App\Console\Commands;

use App\DTOs\Sales\PosContext;
use App\Models\Accounting\AccountingAccountRole;
use App\Models\Accounting\ClientCollection;
use App\Models\Accounting\JournalEntry;
use App\Models\Accounting\Receivable;
use App\Models\Clients\Client;
use App\Models\Clients\Equipment;
use App\Models\Clients\PointOfSale;
use App\Models\Configuration\TipoPago;
use App\Models\Inventory\InventoryMovement;
use App\Models\Inventory\Warehouse;
use App\Models\Products\Product;
use App\Models\Sales\Pos\PosCashMovement;
use App\Models\Sales\Pos\PosSession;
use App\Models\Sales\Pos\PosTerminal;
use App\Models\Sales\Sale;
use App\Models\User;
use App\Services\Accounting\Collection\CollectionService;
use App\Services\Sales\SalesServices\SaleService;
use Carbon\Carbon;
use Database\Seeders\Demo\CategorySeeder;
use Database\Seeders\Demo\EquipmentSeeder;
use Database\Seeders\Demo\EquipmentTypeSeeder;
use Database\Seeders\Demo\InventoryStockSeeder;
use Database\Seeders\Demo\PointOfSaleSeeder;
use Database\Seeders\Demo\ProductSeeder;
use Database\Seeders\Demo\UserSeeder;
use Database\Seeders\Demo\WarehouseSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Throwable;

class SeedDemoData extends Command
{
    /**
     * Marca cualquier Sale/PosSession generado por este comando — permite
     * detectar si ya corrió antes (idempotencia sin --fresh) y localizar
     * exactamente qué borrar con --fresh, sin tocar nunca datos reales
     * cargados a mano por el usuario.
     */
    private const DEMO_MARKER = '[zertix:seed-demo]';

    protected $signature = 'zertix:seed-demo
                                {--sales=100 : Cantidad de ventas históricas a generar}
                                {--days=30 : Días de historial a generar (ventas, sesiones, movimientos)}
                                {--clients=30 : Clientes ficticios a crear si no existen}
                                {--fresh : Elimina el historial transaccional demo anterior antes de generar}';

    protected $description = 'Genera una instancia de demostración: catálogo de ejemplo + historial realista de ventas/caja/inventario';

    public function handle(SaleService $saleService, CollectionService $collectionService): int
    {
        $this->authenticateAsSeedUser();

        $salesTarget = max(0, (int) $this->option('sales'));
        $days = max(1, (int) $this->option('days'));
        $clientsTarget = max(0, (int) $this->option('clients'));

        if ($this->option('fresh')) {
            $this->info('--fresh: limpiando historial transaccional demo anterior...');
            $this->clearPreviousDemoHistory();
        }

        $this->info('[1/5] Catálogo demo (categorías, productos, almacenes)...');
        $this->seedCatalog();

        $this->info('[2/5] Clientes, puntos de venta y equipos ficticios...');
        $this->seedClientsAndFieldOps($clientsTarget);

        $this->info('[3/5] Stock inicial...');
        (new InventoryStockSeeder)->run();

        if ($salesTarget === 0) {
            $this->info('--sales=0: se omite el historial transaccional. Demo generada correctamente.');

            return self::SUCCESS;
        }

        if (! $this->option('fresh') && $this->demoHistoryExists()) {
            $this->warn('Ya existe historial demo generado por este comando. Usa --fresh para regenerarlo.');

            return self::SUCCESS;
        }

        $this->info('[4/5] Terminales POS demo, sesiones de caja y ventas...');
        $terminals = $this->ensureDemoTerminals();
        $this->seedTransactionalHistory($saleService, $terminals, $salesTarget, $days);

        $this->info('[5/5] Cobros dispersos sobre ventas a crédito...');
        $this->seedScatteredCollections($collectionService);

        $this->info('Demo generada correctamente.');

        return self::SUCCESS;
    }

    /**
     * SaleService/CollectionService/InventoryMovementService dependen de Auth::id()
     * (igual que en producción, donde corre bajo una sesión HTTP autenticada) —
     * un comando de consola no tiene usuario logueado por defecto, así que se
     * autentica aquí para el resto del proceso.
     *
     * `UserSeeder` ya no corre en `core` (REQ-07.13) — la instalación real crea
     * su administrador desde el Wizard (Fase 8). Si este comando corre sobre
     * una base recién migrada, sin pasar por el Wizard ni por un `User` real
     * todavía, se siembra el usuario de fábrica de `Demo\UserSeeder` para tener
     * a quién autenticar — nunca si ya existe alguien (real o de fábrica).
     */
    private function authenticateAsSeedUser(): void
    {
        if (User::query()->doesntExist()) {
            (new UserSeeder)->run();
        }

        $user = User::where('email', 'admin@local.com')->first() ?? User::query()->firstOrFail();
        Auth::login($user);
    }

    private function seedCatalog(): void
    {
        // Category/Product ya son idempotentes (updateOrCreate por slug/sku).
        (new CategorySeeder)->run();
        (new ProductSeeder)->run();

        // WarehouseSeeder no lo es (Warehouse::create() plano) — se guarda a mano.
        if (Warehouse::where('name', 'Planta de Producción Principal')->doesntExist()) {
            (new WarehouseSeeder)->run();
        }
    }

    private function seedClientsAndFieldOps(int $clientsTarget): void
    {
        $hasDemoClients = Client::where('tax_id', '!=', '00000000000')->exists();

        if (! $hasDemoClients && $clientsTarget > 0) {
            Client::factory()->count($clientsTarget)->create();
        }

        // Puntos de Venta de Ruta y Activos en Campo son módulos satélite — no
        // tiene sentido generar demo de algo que esta instalación no vende.
        if (module_enabled('sales.delivery_points') && PointOfSale::query()->doesntExist()) {
            (new PointOfSaleSeeder)->run();
        }

        if (module_enabled('clients.field_assets')) {
            (new EquipmentTypeSeeder)->run(); // idempotente (updateOrCreate)

            if (PointOfSale::query()->exists() && Equipment::query()->doesntExist()) {
                (new EquipmentSeeder)->run();
            }
        }
    }

    /**
     * No existe un PosTerminalSeeder en el proyecto (confirmado en la Fase 2) —
     * cualquier terminal se crea a mano desde la UI. El historial demo necesita
     * al menos una para poder abrir sesiones de caja, así que aquí se garantiza
     * una por cada almacén tipo POS/estático, idempotente por nombre.
     *
     * @return Collection<int, PosTerminal>
     */
    private function ensureDemoTerminals(): Collection
    {
        return Warehouse::whereIn('type', [Warehouse::TYPE_POS, Warehouse::TYPE_STATIC])
            ->get()
            ->map(fn (Warehouse $warehouse) => PosTerminal::firstOrCreate(
                ['warehouse_id' => $warehouse->id, 'name' => "Caja Demo — {$warehouse->name}"],
                [
                    'is_active' => true,
                    'requires_pin' => false,
                    'allow_item_discount' => true,
                    'allow_global_discount' => true,
                    'max_item_discount_percentage' => 20,
                    'max_global_discount_percentage' => 10,
                ]
            ));
    }

    private function demoHistoryExists(): bool
    {
        return Sale::where('notes', self::DEMO_MARKER)->exists();
    }

    /**
     * Reparte $salesTarget ventas a lo largo de $days días (hoy hacia atrás),
     * abriendo/cerrando una PosSession por terminal en cada día que tenga
     * ventas, con PosCashMovement de apertura/cierre. Usa SaleService::create()
     * real (no inserts crudos) para que inventario, CxC y asientos contables
     * queden consistentes con las reglas de negocio reales, solo con fecha
     * retroactiva.
     */
    private function seedTransactionalHistory(SaleService $saleService, Collection $terminals, int $salesTarget, int $days): void
    {
        if ($terminals->isEmpty()) {
            $this->warn('No hay almacenes tipo POS/estático para abrir cajas demo — se omite el historial.');

            return;
        }

        $products = Product::where('is_active', true)->get();
        if ($products->isEmpty()) {
            $this->warn('No hay productos para vender — se omite el historial.');

            return;
        }

        $clients = Client::all();
        $users = User::all();
        $cashTipoPago = TipoPago::activo()->where('slug', TipoPago::EFECTIVO)->first()
            ?? TipoPago::activo()->first();

        $remaining = $salesTarget;
        $bar = $this->output->createProgressBar($salesTarget);
        $bar->start();

        for ($dayOffset = $days - 1; $dayOffset >= 0 && $remaining > 0; $dayOffset--) {
            $date = Carbon::now()->subDays($dayOffset);
            // Más ventas en los últimos días que en los primeros — una demo con
            // actividad pareja todos los días se ve menos real que una con
            // variación entre días buenos/malos.
            $salesToday = min($remaining, random_int(1, (int) ceil(($salesTarget / $days) * 2) + 1));

            foreach ($terminals as $terminal) {
                if ($salesToday <= 0 || $remaining <= 0) {
                    break;
                }

                $forThisTerminal = min($salesToday, random_int(1, max(1, intdiv($salesToday, $terminals->count()) + 2)));
                $session = $this->openDemoSession($terminal, $users->random(), $date);

                for ($i = 0; $i < $forThisTerminal && $remaining > 0; $i++) {
                    $client = $clients->random();
                    Auth::login($users->random());

                    $items = $this->randomSaleItems($products);
                    $total = collect($items)->sum('subtotal');
                    $isCredit = $client->tax_id !== '00000000000' && fake()->boolean(20);

                    $context = new PosContext(
                        terminal_id: $terminal->id,
                        session_id: $session->id,
                        cash_account_id: $terminal->cash_account_id,
                        warehouse_id: $terminal->warehouse_id,
                        sale_origin: 'pos',
                        is_walkin_customer: $client->tax_id === '00000000000',
                    );

                    $saleTime = $date->copy()->setTime(random_int(8, 20), random_int(0, 59));

                    try {
                        $sale = $saleService->create([
                            'client_id' => $client->id,
                            'warehouse_id' => $terminal->warehouse_id,
                            'sale_date' => $saleTime->toDateTimeString(),
                            'items' => $items,
                            'total_amount' => $total,
                            'discount_total' => 0,
                            'payment_type' => $isCredit ? 'credit' : 'cash',
                            'tipo_pago_id' => $isCredit ? null : $cashTipoPago?->id,
                        ], $context);

                        $sale->update(['notes' => self::DEMO_MARKER]);

                        $remaining--;
                        $bar->advance();
                    } catch (Throwable $e) {
                        // Stock insuficiente u otra regla de negocio real (ej. tope de
                        // descuento) — se salta esta venta puntual en vez de tumbar
                        // todo el lote. Los datos demo son 100% desechables.
                        continue;
                    }
                }

                $this->closeDemoSession($session);
            }
        }

        $bar->finish();
        $this->newLine();

        if ($remaining > 0) {
            $this->warn('Se generaron '.($salesTarget - $remaining)." de {$salesTarget} ventas solicitadas (el resto falló por reglas de negocio, ej. stock agotado).");
        }
    }

    private function openDemoSession(PosTerminal $terminal, User $cashier, Carbon $date): PosSession
    {
        $openingBalance = fake()->randomElement([500, 1000, 1500, 2000]);
        $openedAt = $date->copy()->setTime(7, random_int(30, 59));

        $session = PosSession::create([
            'terminal_id' => $terminal->id,
            'user_id' => $cashier->id,
            'opened_by_user_id' => $cashier->id,
            'status' => PosSession::STATUS_OPEN,
            'opened_at' => $openedAt,
            'opening_balance' => $openingBalance,
            'notes' => self::DEMO_MARKER,
        ]);

        PosCashMovement::create([
            'pos_session_id' => $session->id,
            'user_id' => $cashier->id,
            // pos_cash_movements.accounting_account_id es NOT NULL a nivel de
            // esquema (no depende de module_enabled('accounting.advanced')) —
            // se usa el rol 'cash_default', sembrado siempre sin importar el
            // flag (ver docs/features/v1.1.0.md Fase 2).
            'accounting_account_id' => AccountingAccountRole::resolve('cash_default'),
            'type' => PosCashMovement::TYPE_IN,
            'amount' => $openingBalance,
            'reason' => 'Fondo inicial de caja',
        ]);

        return $session;
    }

    private function closeDemoSession(PosSession $session): void
    {
        $session->refresh();
        $closedAt = $session->opened_at->copy()->addHours(random_int(6, 10));

        // Arqueo cuadrado por diseño (fondo inicial + ventas en efectivo de la
        // sesión, ya reflejadas en sale_payments por SaleService) — una demo no
        // necesita simular descuadres para verse real.
        $expected = $session->calculateExpected();

        $session->update([
            'status' => PosSession::STATUS_CLOSED,
            'closed_at' => $closedAt,
            'closed_by_user_id' => $session->opened_by_user_id,
            'expected_balance' => $expected,
            'closing_balance' => $expected,
            'difference' => 0,
        ]);
    }

    /**
     * @return array<int, array{product_id: int, quantity: int, price: float, subtotal: float}>
     */
    private function randomSaleItems(Collection $products): array
    {
        $lineCount = random_int(1, 5);
        $items = [];

        foreach ($products->random(min($lineCount, $products->count())) as $product) {
            $quantity = random_int(1, 4);
            $subtotal = $quantity * (float) $product->price;

            $items[] = [
                'product_id' => $product->id,
                'quantity' => $quantity,
                'price' => (float) $product->price,
                'discount_amount' => 0,
                'discount_percentage' => 0,
                'subtotal' => $subtotal,
            ];
        }

        return $items;
    }

    /**
     * Cobros dispersos en el tiempo sobre las CxC generadas por las ventas a
     * crédito de este lote — reemplaza el efecto secundario que antes vivía
     * escondido en ClientFactory::configure() (siempre `payment_date => now()`,
     * sin importar cuándo se emitió la factura). Usa CollectionService::createCollection()
     * real, así que respeta el mismo camino que un abono manual desde la UI
     * (actualiza balance, genera asiento si accounting.advanced está activo).
     */
    private function seedScatteredCollections(CollectionService $collectionService): void
    {
        $demoSaleIds = Sale::where('notes', self::DEMO_MARKER)->pluck('id');

        $receivables = Receivable::where('reference_type', Sale::class)
            ->whereIn('reference_id', $demoSaleIds)
            ->get();

        if ($receivables->isEmpty()) {
            return;
        }

        $tipoPagos = TipoPago::activo()->get();

        foreach ($receivables as $receivable) {
            // No todas las facturas a crédito demo tienen abono — algunas quedan
            // pendientes a propósito, para que CxC/reportes de vencidas tengan
            // algo real que mostrar.
            if (! fake()->boolean(65)) {
                continue;
            }

            $isFullPayment = fake()->boolean(40);
            $amount = $isFullPayment
                ? $receivable->total_amount
                : round($receivable->total_amount * fake()->randomFloat(2, 0.2, 0.8), 2);

            $paymentDate = fake()->dateTimeBetween($receivable->emission_date, 'now');

            try {
                Auth::login(User::query()->inRandomOrder()->firstOrFail());

                $collectionService->createCollection([
                    'receivable_id' => $receivable->id,
                    'tipo_pago_id' => $tipoPagos->random()->id,
                    'amount' => $amount,
                    'payment_date' => Carbon::instance($paymentDate)->toDateString(),
                ]);
            } catch (Throwable $e) {
                continue;
            }
        }
    }

    /**
     * Borra el historial transaccional demo generado por corridas anteriores de
     * este comando (ventas, ítems, pagos, sesiones de caja, movimientos, CxC y
     * los asientos contables que generaron) — nunca toca catálogo, clientes ni
     * almacenes ya sembrados, y nunca toca una fila sin la marca de este comando.
     *
     * El stock no se revierte movimiento por movimiento (los InventoryMovement
     * demo simplemente se borran) — se recalcula de nuevo en el paso [3/5]
     * (InventoryStockSeeder). Es aceptable porque este historial es 100%
     * desechable, no contabilidad real que deba cuadrar en el tiempo.
     */
    private function clearPreviousDemoHistory(): void
    {
        $saleIds = Sale::where('notes', self::DEMO_MARKER)->pluck('id');
        $sessionIds = PosSession::where('notes', self::DEMO_MARKER)->pluck('id');

        if ($saleIds->isEmpty() && $sessionIds->isEmpty()) {
            return;
        }

        $receivableIds = Receivable::where('reference_type', Sale::class)
            ->whereIn('reference_id', $saleIds)
            ->pluck('id');

        $journalEntryIds = ClientCollection::whereIn('receivable_id', $receivableIds)
            ->whereNotNull('journal_entry_id')
            ->pluck('journal_entry_id')
            ->merge(
                JournalEntry::whereIn('reference', Sale::whereIn('id', $saleIds)->pluck('number'))->pluck('id')
            )
            ->unique();

        InventoryMovement::where('reference_type', Sale::class)->whereIn('reference_id', $saleIds)->delete();
        Receivable::whereIn('id', $receivableIds)->forceDelete(); // cascada: payments
        JournalEntry::whereIn('id', $journalEntryIds)->forceDelete(); // cascada: journal_items
        Sale::whereIn('id', $saleIds)->forceDelete(); // cascada: sale_items, sale_payments, invoices
        PosCashMovement::whereIn('pos_session_id', $sessionIds)->delete();
        PosSession::whereIn('id', $sessionIds)->forceDelete();
    }
}
