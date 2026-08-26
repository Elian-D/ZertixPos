<?php

namespace App\Services\Sales\Pos\PosSessionServices;

use App\Models\Accounting\ClientCollection;
use App\Models\Sales\Pos\PosSession;
use App\Models\Sales\Sale;
use App\Models\Sales\SalePayment;

class PosSessionReportService
{
    /**
     * Arma los datos del reporte de turno (PDF carta y ticket 80mm comparten esta
     * misma estructura, solo cambia la vista que la renderiza).
     */
    public function getReportData(PosSession $session): array
    {
        $session->load(['terminal', 'openedBy', 'closedBy']);

        $sales = $session->sales()
            ->where('status', Sale::STATUS_COMPLETED)
            ->with(['client', 'user', 'items', 'payments.tipoPago'])
            ->orderBy('created_at')
            ->get();

        // Venta a Crédito nunca tiene filas en sale_payments — SaleService::processPayments()
        // corta antes y manda directo a Cuentas por Cobrar (createReceivableEntry), no hay
        // "método de pago" físico que registrar todavía. Sin este chequeo explícito, tanto
        // el detalle como el desglose la mostraban como "N/A" y desaparecía sin explicación
        // de cualquier total. Ojo: esto es de EXPOSICIÓN/etiquetado — no toca el cuadre real,
        // el crédito nunca entró a `cash_sales`/`calculateExpected()` (correcto, no es dinero
        // físico) ni a $methodTotals/$columns de abajo (solo pagos reales por método).
        $salesDetail = $sales->map(function (Sale $sale) {
            if ($sale->payment_type === Sale::PAYMENT_CREDIT) {
                $paymentLabel = 'Crédito';
            } else {
                $paymentLabel = $sale->payments->count() > 1
                    ? 'Mixto'
                    : ($sale->payments->first()?->tipoPago?->nombre ?? 'N/A');
            }

            return [
                'hora'     => $sale->created_at->format('h:i A'),
                'numero'   => $sale->number,
                'cliente'  => $sale->client->name ?? 'Consumidor Final',
                'cajero'   => $sale->user->name ?? 'N/A',
                'cantidad' => (float) $sale->items->sum('quantity'),
                'metodo'   => $paymentLabel,
                // grand_total (net_amount + tax_amount) — el efectivo que entra a la
                // gaveta incluye el impuesto cobrado, sin excepción (Fase 5, REQ-5.10).
                // Antes usaba total_amount - discount_total (bruto sin impuesto), lo
                // que desalineaba esta fila contra "Ventas en Efectivo" del resumen.
                'total'    => (float) $sale->grand_total,
            ];
        });

        // Desglose de dinero: Concepto (fila) x Forma de Pago (columna).
        // Hoy el único concepto real es "Ventas POS", pero se estructura como
        // lista de filas a propósito — cuando exista CxC/Pagos/Servicios en el
        // turno, cada uno se agrega como una fila nueva sin tocar la forma de
        // la tabla ni las vistas que la pintan.
        $creditSales = $sales->where('payment_type', Sale::PAYMENT_CREDIT);
        $cashSales = $sales->reject(fn (Sale $sale) => $sale->payment_type === Sale::PAYMENT_CREDIT);

        $payments = SalePayment::whereIn('sale_id', $cashSales->pluck('id'))
            ->with('tipoPago')
            ->get();

        $methodTotals = $payments
            ->groupBy(fn ($p) => $p->tipoPago->nombre ?? 'Sin método')
            ->map(fn ($group) => (float) $group->sum('amount'));

        // Total de ventas a crédito: no viene de sale_payments (no aplica), viene del
        // total facturado de las ventas marcadas payment_type = credit. Mismo fix que
        // la línea de arriba (REQ-5.10): grand_total, no el bruto sin impuesto — es lo
        // que el cliente realmente adeuda.
        $creditTotal = (float) $creditSales->sum(fn (Sale $sale) => $sale->grand_total);

        $breakdownRows = [
            [
                'concepto' => 'Ventas POS',
                'methods'  => $methodTotals->all(),
                'total'    => (float) $methodTotals->sum(),
            ],
        ];

        // Cobros de CxC hechos desde esta terminal/turno (Fase 6, REQ-6.7) — es dinero
        // físico entrando a la gaveta, igual que una venta de contado, por eso SÍ suma
        // a $grandTotal (a diferencia del crédito, que se excluye a propósito arriba).
        // Fila nueva sin tocar la forma de $breakdownRows ni las vistas que lo pintan
        // (ya iteran @foreach($breakdownRows as $row) de forma genérica desde que se
        // escribieron) — solo se agrega si de verdad hubo cobros en el turno, para no
        // ensuciar el ticket de una terminal que nunca cobra CxC.
        $collections = ClientCollection::where('pos_session_id', $session->id)
            ->where('status', ClientCollection::STATUS_ACTIVE)
            ->with('tipoPago')
            ->get();

        $collectionTotals = $collections
            ->groupBy(fn ($p) => $p->tipoPago->nombre ?? 'Sin método')
            ->map(fn ($group) => (float) $group->sum('amount'));

        if ($collectionTotals->isNotEmpty()) {
            $breakdownRows[] = [
                'concepto' => 'Cobros CxC',
                'methods'  => $collectionTotals->all(),
                'total'    => (float) $collectionTotals->sum(),
            ];
        }

        // Columnas de método de pago (solo dinero real cobrado, lo que ya usa el
        // arqueo) — unión de los métodos usados en ventas Y en cobros, para que un
        // método usado solo en un cobro no quede invisible en $columnTotals.
        $columns = $methodTotals->keys()->merge($collectionTotals->keys())->unique()->values()->all();

        $columnTotals = collect($columns)->mapWithKeys(fn ($col) => [
            $col => collect($breakdownRows)->sum(fn ($row) => $row['methods'][$col] ?? 0),
        ])->all();

        $grandTotal = (float) $methodTotals->sum() + (float) $collectionTotals->sum();

        return [
            'session'             => $session,
            'salesDetail'         => $salesDetail,
            'breakdownRows'       => $breakdownRows,
            'columns'             => $columns,
            'columnTotals'        => $columnTotals,
            // Total cobrado en caja (efectivo/tarjeta/transferencia/etc.) — esto es lo
            // que respalda el arqueo, no incluye crédito a propósito.
            'grandTotal'          => $grandTotal,
            // Crédito (CxC) va aparte, nunca sumado a $grandTotal ni a $columnTotals,
            // para no confundir "vendido" con "cobrado en caja" — pero sí visible, no
            // debe desaparecer sin explicación. Ver nota arriba.
            'creditTotal'         => $creditTotal,
            // Total de ventas real del turno (cobrado + a crédito), para el renglón
            // "Ventas: $X — de las cuales $Y fueron a crédito, no exigible en caja".
            'totalSalesWithCredit' => $grandTotal + $creditTotal,
        ];
    }
}
