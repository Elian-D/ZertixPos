<?php

namespace App\Exports\Sales\Ncf;

use Maatwebsite\Excel\Concerns\{FromQuery, WithHeadings, WithMapping, WithStyles};
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class NcfLogsExport implements FromQuery, WithHeadings, WithMapping, WithStyles
{
    protected $query;

    public function __construct($query) { $this->query = $query; }

    public function query()
    {
        // Importante cargar relaciones para evitar N+1 — sale.items para el desglose
        // real de ITBIS (Fase 5, auditoría post-REQ-5.12).
        return $this->query->with(['sale.client', 'sale.items', 'type', 'user']);
    }

    public function headings(): array
    {
        return ['Fecha', 'NCF', 'Tipo', 'Venta #', 'RNC/Cédula', 'Cliente', 'Monto', 'ITBIS', 'Estado'];
    }

    public function map($log): array
    {
        // ITBIS real, no un 18% fijo recalculado a mano sobre el bruto (ignoraba
        // exenciones, ISC y las tasas reales del sistema multi-tasa de la Fase 5) —
        // se suma solo el/los renglón(es) del grupo 'itbis' del breakdown congelado
        // por línea, dejando fuera ISC/otros impuestos aditivos.
        $itbis = $log->sale->items
            ->pluck('tax_breakdown')
            ->filter()
            ->flatten(1)
            ->filter(fn ($line) => str_starts_with($line['key'], 'itbis'))
            ->sum('amount');

        return [
            $log->created_at->format('d/m/Y'),
            $log->full_ncf,
            $log->type->name,
            $log->sale->number,
            $log->sale->client->tax_id ?? 'N/A',
            $log->sale->client->name,
            $log->sale->grand_total, // neto + impuesto, no el bruto
            $itbis,
            $log->status == 'used' ? 'Utilizado' : 'Anulado'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}