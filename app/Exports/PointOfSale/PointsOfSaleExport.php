<?php

namespace App\Exports\PointOfSale;

use App\Models\Clients\BusinessType;
use App\Models\Clients\Client;
use App\Models\Geo\Province;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithDefaultStyles;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Style;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PointsOfSaleExport implements FromQuery, WithColumnWidths, WithDefaultStyles, WithHeadings, WithMapping, WithStyles
{
    protected $query;

    private $statesCache = [];

    private $businessTypesCache = [];

    private $clientsCache = [];

    public function __construct($query)
    {
        $this->query = $query;
        $this->loadCaches();
    }

    private function loadCaches()
    {
        $this->statesCache = Province::pluck('name', 'id')->toArray();
        $this->businessTypesCache = BusinessType::pluck('nombre', 'id')->toArray();
        $this->clientsCache = Client::pluck('name', 'id')->toArray();
    }

    public function query()
    {
        // Seleccionamos los campos exactos de tu tabla de POS
        return $this->query
            ->select([
                'id', 'client_id', 'business_type_id', 'name', 'provincia_id',
                'city', 'address', 'latitude', 'longitude', 'contact_name',
                'contact_phone', 'active', 'created_at',
            ])
            ->withoutGlobalScopes()
            ->orderBy('id');
    }

    public function headings(): array
    {
        return [
            'ID', 'Cliente Propietario', 'Tipo de Negocio', 'Nombre POS',
            'Provincia', 'Ciudad', 'Dirección', 'Latitud', 'Longitud',
            'Contacto', 'Teléfono', 'Estado', 'Fecha Registro',
        ];
    }

    public function map($pos): array
    {
        $data = is_array($pos) ? $pos : $pos->getAttributes();

        return [
            $data['id'],
            $this->clientsCache[$data['client_id']] ?? 'N/A',
            $this->businessTypesCache[$data['business_type_id']] ?? 'N/A',
            $data['name'],
            $this->statesCache[$data['provincia_id']] ?? 'N/A',
            $data['city'],
            $data['address'] ?? '',
            $data['latitude'],
            $data['longitude'],
            $data['contact_name'] ?? '',
            $data['contact_phone'] ?? '',
            $data['active'] ? 'Activo' : 'Inactivo',
            \Carbon\Carbon::parse($data['created_at'])->format('d-m-Y'),
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 8, 'B' => 30, 'C' => 20, 'D' => 35, 'E' => 20,
            'F' => 20, 'G' => 40, 'H' => 15, 'I' => 15, 'J' => 25,
            'K' => 15, 'L' => 12, 'M' => 18,
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
                    'startColor' => ['argb' => '10B981'], // Verde Emerald-600 para diferenciar de Clientes
                ],
            ],
        ];
    }
}
