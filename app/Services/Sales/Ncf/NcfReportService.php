<?php

namespace App\Services\Sales\Ncf;

use App\Models\Sales\Ncf\NcfLog;
use Illuminate\Support\Collection;

class NcfReportService
{
    /**
     * Genera el contenido del archivo TXT para el reporte 607 (Ventas)
     * Basado en la estructura técnica de la DGII.
     */
    public function generate607Txt(Collection $logs, string $periodo): string
    {
        // Obtener RNC de la empresa desde tu helper personalizado
        $rncEmpresa = general_config()?->tax_id ?? '000000000'; 
        
        // Limpiar el periodo por si viene con guiones (ej: 2024-05 -> 202405)
        $periodoClean = str_replace('-', '', $periodo);

        // Encabezado: Tipo de Archivo|RNC|Periodo|Cantidad Registros
        $header = "607|{$rncEmpresa}|{$periodoClean}|" . $logs->count();
        
        $lines = [$header];

        foreach ($logs as $log) {
            $sale = $log->sale;
            $client = $sale->client;

            // Lógica fiscal para montos
            $totalMonto = $sale->total ?? 0;
            // Si manejas el impuesto por separado en DB usa ese, sino calculamos base 18%
            $montoFacturado = $totalMonto / 1.18;
            $itbisFacturado = $totalMonto - $montoFacturado;

            $data = [
                // 1. RNC o Cédula del Cliente
                $client->tax_id ?? '',

                // 2. Tipo de Identificación (1: RNC, 2: Cédula, 3: Pasaporte/Otro)
                $this->getTaxIdType($client->tax_id ?? ''),
                
                // 3. Número de Comprobante Fiscal (NCF / e-NCF)
                $log->full_ncf,
                
                // 4. NCF Modificado (Sólo si es Nota de Crédito/Débito)
                '', 
                
                // 5. Fecha de Comprobante (YYYYMMDD)
                $sale->created_at->format('Ymd'),
                
                // 6. Fecha de Retención (Vacio si no aplica)
                '', 
                
                // 7. Monto Facturado (Base Imponible)
                number_format($montoFacturado, 2, '.', ''), 
                
                // 8. ITBIS Facturado
                number_format($itbisFacturado, 2, '.', ''),
                
                // Nota: El 607 real pide más columnas (Retenciones, Otros Impuestos)
                // Se agregan vacíos para cumplir con la longitud de campos si es necesario.
            ];

            $lines[] = implode('|', $data);
        }

        // DGII exige CRLF y el archivo suele codificarse en ANSI/ISO-8859-1 para su validador
        return implode("\r\n", $lines);
    }

    /**
     * Determina el tipo de ID según la longitud del tax_id
     */
    private function getTaxIdType(?string $taxId): string
    {
        if (!$taxId) return '3'; // Consumidor Final / Otros
        
        $cleanId = preg_replace('/[^0-9]/', '', $taxId);
        
        if (strlen($cleanId) === 9) return '1';  // RNC
        if (strlen($cleanId) === 11) return '2'; // Cédula
        
        return '3'; // Otros
    }
}