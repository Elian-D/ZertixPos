<?php

namespace App\Models\Inventory;

use App\Models\Accounting\AccountingAccount;
use App\Traits\GuardsForceDelete;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Warehouse extends Model
{
    use GuardsForceDelete, SoftDeletes;

    protected $table = 'warehouses';

    protected $fillable = [
        'name',
        'type',
        'address',
        'description',
        'is_active',
        'accounting_account_id', // Nuevo campos
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /* ===========================
     |  CONSTANTES DE TIPOS
     =========================== */

    const TYPE_STATIC = 'static';

    const TYPE_MOBILE = 'mobile';

    const TYPE_POS = 'pos';

    public static function getTypes(): array
    {
        return [
            self::TYPE_STATIC => 'Estático (Bodega/Fábrica)',
            self::TYPE_POS => 'Estático (Tienda/Mostrador)',
            self::TYPE_MOBILE => 'Móvil (Camión/Ruta)',
        ];
    }

    /**
     * El almacén que se usa cuando Inventario está apagado (v1.5.1 REQ-3.3): la terminal y
     * la venta lo guardan sin pedirlo, porque sin inventario no se toca ninguna existencia.
     * Una instalación nueva no trae almacenes, así que si no hay ninguno lo crea.
     */
    public static function fallback(): self
    {
        return static::where('is_active', true)->orderBy('id')->first()
            ?? static::create(['name' => 'Almacén principal', 'type' => self::TYPE_POS, 'is_active' => true]);
    }

    /**
     * Eventos del Modelo
     */
    protected static function booted()
    {
        static::created(function (Warehouse $warehouse) {
            // La subcuenta contable solo se crea si la instalación tiene contabilidad
            // formal activa — CxC/CxP/Cotizaciones son base y no deben depender de esto.
            if (module_enabled('accounting.advanced')) {
                $warehouse->createAccountingAccount();
            }
        });
    }

    /**
     * Crea la subcuenta contable automáticamente bajo la cuenta de Inventarios (1.1.03)
     */
    public function createAccountingAccount(): void
    {
        if ($this->accounting_account_id) {
            return;
        }

        DB::transaction(function () {
            $parent = AccountingAccount::where('code', '1.1.03')->first();

            if (! $parent) {
                return;
            }

            // Buscar el último correlativo de este padre
            $lastChild = AccountingAccount::where('parent_id', $parent->id)
                ->orderBy('code', 'desc')
                ->first();

            if (! $lastChild) {
                $newCode = $parent->code.'.01';
            } else {
                $parts = explode('.', $lastChild->code);
                $lastPart = (int) end($parts);
                $newCode = $parent->code.'.'.str_pad($lastPart + 1, 2, '0', STR_PAD_LEFT);
            }

            $account = AccountingAccount::create([
                'parent_id' => $parent->id,
                'code' => $newCode,
                'name' => 'Inventario: '.$this->name,
                'type' => AccountingAccount::TYPE_ASSET,
                'level' => $parent->level + 1,
                'is_selectable' => true,
            ]);

            $this->updateQuietly(['accounting_account_id' => $account->id]);
        });
    }

    /* ===========================
     |  RELACIONES
     =========================== */

    /**
     * Relación con la cuenta contable de activo que representa este almacén
     */
    public function accountingAccount(): BelongsTo
    {
        return $this->belongsTo(AccountingAccount::class, 'accounting_account_id');
    }

    /**
     * Relación: Un almacén tiene muchos balances de stock
     */
    public function stocks()
    {
        return $this->hasMany(InventoryStock::class);
    }

    /* ===========================
     |  SCOPES
     =========================== */

    /**
     * Centraliza el Eager Loading del índice — sin esto, la vista de detalle
     * llamaba $item->stocks()->count() por fila (N+1 real, ver migración a
     * Livewire de REQ-0.8).
     */
    public function scopeWithIndexRelations($query)
    {
        return $query->with('accountingAccount')->withCount('stocks');
    }

    public function scopeActivos($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /* ===========================
     |  COMPORTAMIENTO Y LÓGICA
     =========================== */

    /**
     * Alternar el estado activo/inactivo
     */
    public function toggleActivo(): bool
    {
        $this->is_active = ! $this->is_active;
        $this->save();

        return $this->is_active; // Ahora devuelve el nuevo estado (true/false)
    }

    /**
     * Formatear el nombre del tipo para la UI
     */
    protected function typeLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => self::getTypes()[$this->type] ?? $this->type,
        );
    }

    /** Qué impide el borrado definitivo (v1.5.1 REQ-1.1). */
    public function deletionBlockers(): array
    {
        return [
            'producto con existencia|productos con existencia' => DB::table('inventory_stocks')->where('warehouse_id', $this->id)->where('quantity', '!=', 0)->count(),
            'movimiento de inventario|movimientos de inventario' => DB::table('inventory_movements')->where('warehouse_id', $this->id)->orWhere('to_warehouse_id', $this->id)->count(),
            'venta|ventas' => DB::table('sales')->where('warehouse_id', $this->id)->count(),
            'terminal TPV|terminales TPV' => DB::table('pos_terminals')->where('warehouse_id', $this->id)->count(),
            'orden de compra|órdenes de compra' => DB::table('purchase_orders')->where('warehouse_id', $this->id)->count(),
            'toma, merma o transferencia|tomas, mermas o transferencias' => DB::table('inventory_counts')->where('warehouse_id', $this->id)->count() + DB::table('inventory_wastes')->where('warehouse_id', $this->id)->count() + DB::table('inventory_transfers')->where('from_warehouse_id', $this->id)->orWhere('to_warehouse_id', $this->id)->count(),
        ];
    }
}
