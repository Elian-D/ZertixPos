<?php

namespace App\Exports\Sales;

use App\Models\Clients\Client;
use App\Models\Sales\Invoice;
use App\Models\Sales\Sale;
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

class InvoicesExport implements FromQuery, WithColumnWidths, WithDefaultStyles, WithHeadings, WithMapping, WithStyles
{
    protected $query;

    private $clientsCache = [];

    private $statusLabels = [];

    private $formatLabels = [];

    private $paymentLabels = [];

    public function __construct($query)
    {
        $this->query = $query;

        // Optimización: Cacheamos catálogos para evitar N+1 en el mapeo
        $this->clientsCache = Client::pluck('name', 'id')->toArray();
        $this->statusLabels = Invoice::getStatuses();
        $this->formatLabels = Invoice::getFormats();
        $this->paymentLabels = [
            Sale::PAYMENT_CASH => 'Contado',
            Sale::PAYMENT_CREDIT => 'Crédito',
        ];
    }

    public function query()
    {
        // Traemos los campos necesarios incluyendo la relación sale para el cliente y monto
        return $this->query->with(['sale'])->latest();
    }

    public function headings(): array
    {
        return [
            'ID',
            'N° Factura',
            'Fecha Emisión',
            'Cliente',
            'Tipo Venta',
            'Formato',
            'Monto Total',
            'Estado',
            'Vencimiento',
            'Generado Por',
        ];
    }

    public function map($invoice): array
    {
        // Aseguramos que los objetos existan antes de llamar a sus propiedades
        $sale = $invoice->sale;

        return [
            $invoice->id,
            $invoice->invoice_number,
            $invoice->created_at ? $invoice->created_at->format('d/m/Y H:i') : 'N/A',
            $this->clientsCache[$sale->client_id ?? null] ?? 'N/A',
            $this->paymentLabels[$sale->payment_type ?? ''] ?? ($sale->payment_type ?? 'N/A'),
            $this->formatLabels[$invoice->format_type] ?? $invoice->format_type,
            $sale->total_amount ?? 0,
            $this->statusLabels[$invoice->status] ?? $invoice->status,
            $invoice->due_date ? \Carbon\Carbon::parse($invoice->due_date)->format('d/m/Y') : 'N/A',
            $invoice->generated_by,
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 8,  'B' => 15, 'C' => 20, 'D' => 35, 'E' => 15,
            'F' => 15, 'G' => 18, 'H' => 15, 'I' => 15, 'J' => 20,
        ];
    }

    public function defaultStyles(Style $defaultStyle)
    {
        return [
            'font' => ['name' => 'Segoe UI', 'size' => 10],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Encabezado Pro (Color Esmeralda para diferenciarlo de Ventas)
        $sheet->getStyle('A1:J1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => '059669']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $lastRow = $sheet->getHighestRow();

        // Formato Moneda
        $sheet->getStyle('G2:G'.$lastRow)->getNumberFormat()
            ->setFormatCode('"'.config('regional.currency_symbol').'"#,##0.00');

        // Bordes
        $sheet->getStyle('A1:J'.$lastRow)->applyFromArray([
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
