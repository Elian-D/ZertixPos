<?php

namespace Database\Seeders\AccountingSeeders;

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
                'current_number' => 0,
            ],
            [
                'name' => 'Comprobante de Pago',
                'code' => 'PAG',
                'prefix' => 'PAG',
                'current_number' => 0,
            ],
        ];

        foreach ($docs as $doc) {
            DocumentType::updateOrCreate(['code' => $doc['code']], $doc);
        }

        // REC, MAN y NC nunca se consultan en ningún punto del código (confirmado por
        // grep) — se retiran de instalaciones existentes que los sembraron antes de
        // esta limpieza. NC (Nota de Crédito) se elimina porque el módulo en sí no se
        // construye en esta versión — queda para cuando se aborde B04 (v1.1.0.md Fase 4.4).
        DocumentType::whereIn('code', ['REC', 'MAN', 'NC'])->forceDelete();
    }
}
