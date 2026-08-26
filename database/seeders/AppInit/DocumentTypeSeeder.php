<?php

namespace Database\Seeders\AppInit;

use App\Models\Accounting\DocumentType;
use Illuminate\Database\Seeder;

class DocumentTypeSeeder extends Seeder
{
    public function run(): void
    {
        $docs = [
            [
                'name' => 'Factura de Venta',
                'code' => 'FAC',
                'prefix' => 'FAC',
            ],
            [
                // REQ-4.2, Opción A: el code/prefix 'PAG' se mantiene tal cual — es
                // información legal/histórica ya impresa en recibos reales (PAG-000102,
                // etc.) — solo el name mostrado cambia a "Recibo de Cobro".
                'name' => 'Recibo de Cobro',
                'code' => 'PAG',
                'prefix' => 'PAG',
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
