<?php

namespace App\Exports\Accounting;

use App\Models\Accounting\ClientCollection;
use Maatwebsite\Excel\Concerns\{FromQuery, WithHeadings, WithMapping, WithStyles, WithDefaultStyles, WithColumnWidths};
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\{Style, Fill, Alignment};

class CollectionsExport implements FromQuery, WithHeadings, WithMapping, WithStyles, WithDefaultStyles, WithColumnWidths
{
    protected $query;
    protected $statuses;

    public function __construct($query)
    {
        $this->query = $query;
        $this->statuses = ClientCollection::getStatuses();
    }

    public function query()
    {
        // Cargamos relaciones para optimizar el rendimiento (Eager Loading)
        return $this->query->with(['client', 'receivable', 'tipoPago', 'creator']);
    }

    public function headings(): array
    {
        return [
            'ID',
            'Fecha Cobro',
            'No. Recibo',
            'Cliente',
            'Factura Aplicada',
            'Método de Pago',
            'Referencia',
            'Monto Cobrado',
            'Estado',
            'Registrado por',
            'Nota'
        ];
    }

    public function map($payment): array
    {
        return [
            $payment->id,
            $payment->payment_date->format('d/m/Y'),
            $payment->receipt_number,
            $payment->client->name,
            $payment->receivable->document_number ?? 'N/A',
            $payment->tipoPago->nombre ?? 'N/A',
            $payment->reference ?? 'Sin referencia',
            $payment->amount,
            $this->statuses[$payment->status] ?? $payment->status,
            $payment->creator->name ?? 'Sistema',
            $payment->note ?? '',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 8,  'B' => 15, 'C' => 18, 'D' => 35, 'E' => 18,
            'F' => 18, 'G' => 20, 'H' => 15, 'I' => 15, 'J' => 20, 'K' => 40
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
        // Estilo Encabezado (Indigo 600 para consistencia)
        $sheet->getStyle('A1:K1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => '4F46E5']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Formato moneda para la columna H (Monto)
        $lastRow = $sheet->getHighestRow();
        if ($lastRow > 1) {
            $sheet->getStyle('H2:H' . $lastRow)->getNumberFormat()
                ->setFormatCode('#,##0.00');
        }

        return [];
    }
}
