<?php

namespace App\Exports\Clients;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ClientsTemplateExport implements FromCollection, ShouldAutoSize, WithHeadings
{
    public function collection()
    {
        return collect([
            [
                'Individual',
                'Juan Pérez',
                'Pérez Servicios',
                'juan@example.com',
                '809-000-0000',
                'Monseñor Nouel',
                'Bonao',
                'Av. Libertad #123',
                'Cédula',
                '001-0000000-0',
                'Activo',
            ],
            [
                'Empresa',
                'Constructora S.A.',
                'CSA Dominicana',
                'contacto@csa.com',
                '809-555-5555',
                'La Vega',
                'La Vega',
                'Calle Principal #456',
                'RNC',
                '130123456',
                'Prospecto',
            ],
        ]);
    }

    public function headings(): array
    {
        return [
            'tipo', 'nombre_o_razon_social', 'nombre_comercial', 'email',
            'telefono', 'provincia_estado', 'ciudad', 'direccion', 'tipo_identificacion',
            'rnc_cedula', 'estado_cliente',
        ];
    }
}
