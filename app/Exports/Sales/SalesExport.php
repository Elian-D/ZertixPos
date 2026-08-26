<?php

namespace App\Exports\Sales;

use App\Models\Clients\Client;
use App\Models\Inventory\Warehouse;
use App\Models\Sales\Pos\PosTerminal;
use App\Models\Sales\Sale;
use App\Models\User;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithDefaultStyles;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Style;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SalesExport implements FromQuery, WithColumnWidths, WithDefaultStyles, WithHeadings, WithMapping, WithStyles
{
    protected $query;

    private $clientsCache = [];

    private $warehousesCache = [];

    private $terminalsCache = [];

    private $usersCache = [];

    private $statusLabels = [];

    private $paymentLabels = [];

    public function __construct($query)
    {
        $this->query = $query;

        // Carga de caches para optimizar performance
        $this->clientsCache = Client::pluck('name', 'id')->toArray();
        $this->warehousesCache = Warehouse::pluck('name', 'id')->toArray();
        $this->terminalsCache = PosTerminal::pluck('name', 'id')->toArray();
        $this->usersCache = User::pluck('name', 'id')->toArray();
        $this->statusLabels = Sale::getStatuses();
        $this->paymentLabels = Sale::getPaymentTypes();
    }

    /**
     * Consulta base con relaciones necesarias para evitar N+1
     */
    public function query()
    {
        return $this->query->with(['payments.tipoPago'])
            ->select([
                'id', 'number', 'created_at', 'sale_date', 'client_id',
                'warehouse_id', 'pos_terminal_id', 'pos_session_id',
                'payment_type', 'total_amount', 'net_amount', 'tax_amount',
                'status', 'user_id', 'notes',
            ])->latest('sale_date');
    }

    /**
     * Encabezados actualizados con campos POS y Métodos de Pago
     */
    public function headings(): array
    {
        return [
            'ID',
            'Número Doc',
            'Fecha Venta',
            'Cliente',
            'Almacén',
            'Terminal POS',
            'Tipo Condición',
            'Método(s) de Pago',
            'Total Venta',
            'Estado',
            'Vendedor',
            'Observaciones',
        ];
    }

    /**
     * Mapeo de datos incluyendo lógica de pagos múltiples
     */
    public function map($sale): array
    {
        // Lógica para mostrar métodos de pago (Ej: "Efectivo, Tarjeta")
        $paymentMethods = $sale->payments->map(function ($p) {
            return $p->tipoPago->nombre ?? 'N/A';
        })->unique()->implode(', ');

        return [
            $sale->id,
            $sale->number,
            $sale->sale_date->format('d/m/Y'),
            $this->clientsCache[$sale->client_id] ?? 'N/A',
            $this->warehousesCache[$sale->warehouse_id] ?? 'N/A',
            $this->terminalsCache[$sale->pos_terminal_id] ?? 'Oficina/Web',
            $this->paymentLabels[$sale->payment_type] ?? $sale->payment_type,
            $paymentMethods ?: 'No registrado',
            // grand_total (neto + impuesto), no total_amount (bruto — ver auditoría
            // post-REQ-5.12 en docs/features/v1.2.0.md).
            $sale->grand_total,
            $this->statusLabels[$sale->status] ?? $sale->status,
            $this->usersCache[$sale->user_id] ?? 'Sistema',
            $sale->notes ?? '',
        ];
    }

    /**
     * Anchos de columna optimizados para las nuevas columnas
     */
    public function columnWidths(): array
    {
        return [
            'A' => 8,  'B' => 15, 'C' => 15, 'D' => 30, 'E' => 20,
            'F' => 20, 'G' => 15, 'H' => 25, 'I' => 18, 'J' => 15,
            'K' => 20, 'L' => 40,
        ];
    }

    public function defaultStyles(Style $defaultStyle)
    {
        return [
            'font' => ['name' => 'Segoe UI', 'size' => 10],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ];
    }

    /**
     * Formato de moneda, colores y bordes (Ajustado a L columnas)
     */
    public function styles(Worksheet $sheet)
    {
        $lastRow = $sheet->getHighestRow();
        $range = 'A1:L1';
        $fullRange = 'A1:L'.$lastRow;

        // Encabezado Indigo 600
        $sheet->getStyle($range)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => '4F46E5']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Formato Moneda para la columna de Total (I)
        $sheet->getStyle('I2:I'.$lastRow)->getNumberFormat()
            ->setFormatCode('"'.config('regional.currency_symbol').'"#,##0.00');

        // Bordes de tabla
        $sheet->getStyle($fullRange)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'E5E7EB'],
                ],
            ],
        ]);

        return [];
    }
}
