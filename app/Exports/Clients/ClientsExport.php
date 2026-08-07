<?php

namespace App\Exports\Clients;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithDefaultStyles;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Style;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ClientsExport implements FromQuery, WithColumnWidths, WithDefaultStyles, WithHeadings, WithMapping, WithStyles
{
    protected $query;

    private $provincesCache = [];

    private $municipalitiesCache = [];

    private $estadosCache = [];

    public function __construct($query)
    {
        $this->query = $query;
        $this->loadCaches();
    }

    private function loadCaches()
    {
        $this->provincesCache = \App\Models\Geo\Province::pluck('name', 'id')->toArray();
        $this->municipalitiesCache = \App\Models\Geo\Municipality::pluck('name', 'id')->toArray();
        $this->estadosCache = \App\Models\Configuration\EstadosCliente::pluck('nombre', 'id')->toArray();
    }

    public function query()
    {
        return $this->query
            ->select([
                'id', 'type', 'name', 'commercial_name', 'email', 'phone',
                'provincia_id', 'municipio_id', 'address', 'tax_identifier_type', 'tax_id',
                'estado_cliente_id', 'created_at', 'updated_at',
            ])
            ->withoutGlobalScopes() // Desactiva scopes globales si tienes
            ->orderBy('id');
    }

    public function headings(): array
    {
        return [
            'tipo', 'nombre_o_razon_social', 'nombre_comercial', 'email',
            'telefono', 'provincia_estado', 'ciudad', 'direccion', 'tipo_identificacion',
            'rnc_cedula', 'estado_cliente', 'fecha_registro', 'ultima_actualizacion',
        ];
    }

    public function map($client): array
    {
        // ✅ CRÍTICO: Convertir a array para evitar accessors
        $data = is_array($client) ? $client : $client->getAttributes();

        return [
            $data['type'] === 'company' ? 'Empresa' : 'Individual',
            $data['name'],
            $data['commercial_name'] ?? '',
            $data['email'] ?? '',
            $data['phone'] ?? '',
            $this->provincesCache[$data['provincia_id']] ?? '',
            $this->municipalitiesCache[$data['municipio_id']] ?? '',
            $data['address'] ?? '',
            $data['tax_identifier_type'] ?? '',
            $data['tax_id'],
            $this->estadosCache[$data['estado_cliente_id']] ?? '',
            \Carbon\Carbon::parse($data['created_at'])->format('d-m-Y H:i'),
            \Carbon\Carbon::parse($data['updated_at'])->format('d-m-Y H:i'),
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 12, 'B' => 35, 'C' => 30, 'D' => 30, 'E' => 15,
            'F' => 20, 'G' => 20, 'H' => 40, 'I' => 15, 'J' => 18,
            'K' => 20, 'L' => 20, 'M' => 20,
        ];
    }

    public function defaultStyles(Style $defaultStyle)
    {
        return ['font' => ['name' => 'Arial', 'size' => 11]];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => '4F46E5'],
                ],
            ],
        ];
    }
}
