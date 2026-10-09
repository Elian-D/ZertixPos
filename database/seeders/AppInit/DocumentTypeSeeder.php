<?php

namespace Database\Seeders\AppInit;

use App\Models\Accounting\DocumentType;
use Illuminate\Database\Seeder;

class DocumentTypeSeeder extends Seeder
{
    public function run(): void
    {
        // v1.4.0 REQ-3.19 — cada documento lleva su propio correlativo: la venta
        // (VTA) es la operación, la factura (FAC) el comprobante entregado al
        // cliente y la CxC (CXC) la deuda si fue a crédito. El NCF sigue siendo
        // el número fiscal; estos son internos.
        $docs = [
            [
                'name' => 'Venta',
                'code' => 'VTA',
                'prefix' => 'VTA',
            ],
            [
                'name' => 'Factura',
                'code' => 'FAC',
                'prefix' => 'FAC',
            ],
            [
                'name' => 'Cuenta por Cobrar',
                'code' => 'CXC',
                'prefix' => 'CXC',
            ],
            [
                'name' => 'Cotización',
                'code' => 'COT',
                'prefix' => 'COT',
            ],
            [
                'name' => 'Turno POS',
                'code' => 'TRN',
                'prefix' => 'TRN',
            ],
            [
                // REQ-4.2, Opción A: el code/prefix 'PAG' se mantiene tal cual — es
                // información legal/histórica ya impresa en recibos reales (PAG-000102,
                // etc.) — solo el name mostrado cambia a "Recibo de Cobro".
                'name' => 'Recibo de Cobro',
                'code' => 'PAG',
                'prefix' => 'PAG',
            ],
            [
                // Correlativo interno de Devoluciones (v1.4.0 Fase 2) — no fiscal.
                'name' => 'Devolución',
                'code' => 'DEV',
                'prefix' => 'DEV',
            ],
            [
                // Toma física (v1.5.0 REQ-2.1) — documento interno para firmar.
                'name' => 'Toma física',
                'code' => 'TFS',
                'prefix' => 'TFS',
            ],
            [
                // Merma (v1.5.0 REQ-2.2) — baja de mercancía perdida con motivo.
                'name' => 'Merma',
                'code' => 'MER',
                'prefix' => 'MER',
            ],
            [
                // Transferencia entre almacenes (v1.5.0 REQ-2.4).
                'name' => 'Transferencia',
                'code' => 'TRA',
                'prefix' => 'TRA',
            ],
        ];

        foreach ($docs as $doc) {
            // current_number es un correlativo real, no un valor de catálogo — nunca
            // se pisa en un re-run del seeder (un updateOrCreate ingenuo con
            // 'current_number' => 0 en el payload resetea el correlativo de
            // instalaciones ya en producción cada vez que este seeder corre de nuevo).
            $type = DocumentType::firstOrNew(['code' => $doc['code']]);
            $type->fill($doc);
            if (! $type->exists) {
                $type->current_number = 0;
            }
            $type->save();
        }

        // REC, MAN y NC nunca se consultan en ningún punto del código (confirmado por
        // grep) — se retiran de instalaciones existentes que los sembraron antes de
        // esta limpieza. NC (Nota de Crédito) se elimina porque el módulo en sí no se
        // construye en esta versión — queda para cuando se aborde B04 (v1.1.0.md Fase 4.4).
        DocumentType::whereIn('code', ['REC', 'MAN', 'NC'])->forceDelete();
    }
}
