<?php

namespace App\Exports\Equipment;

use App\Models\Clients\EquipmentType;
use App\Models\Clients\PointOfSale;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithDefaultStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Style;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class EquipmentsExport implements
    FromQuery,
    WithHeadings,
    WithMapping,
    WithStyles,
    WithDefaultStyles,
    WithColumnWidths
{
    protected $query;

    private array $equipmentTypesCache = [];
    private array $pointsOfSaleCache = [];

    public function __construct($query)
    {
        $this->query = $query;
        $this->loadCaches();
    }

    /**
     * Cargamos catálogos para evitar N+1
     */
    private function loadCaches(): void
    {
        $this->equipmentTypesCache = EquipmentType::pluck('nombre', 'id')->toArray();
        $this->pointsOfSaleCache  = PointOfSale::pluck('name', 'id')->toArray();
    }

    public function query()
    {
        return $this->query
            ->select([
                'id',
                'equipment_type_id',
                'point_of_sale_id',
                'serial_number',
                'name',
                'model',
                'active',
                'created_at',
            ])
            ->withoutGlobalScopes()
            ->orderBy('id');
    }

    public function headings(): array
    {
        return [
            'ID',
            'Tipo de Equipo',
            'Punto de Venta',
            'Serial',
            'Nombre',
            'Modelo',
            'Estado',
            'Fecha Registro',
        ];
    }

    public function map($equipment): array
    {
        $data = is_array($equipment)
            ? $equipment
            : $equipment->getAttributes();

        return [
            $data['id'],
            $this->equipmentTypesCache[$data['equipment_type_id']] ?? 'N/A',
            $this->pointsOfSaleCache[$data['point_of_sale_id']] ?? 'N/A',
            $data['serial_number'] ?? '',
            $data['name'] ?? '',
            $data['model'] ?? '',
            $data['active'] ? 'Activo' : 'Inactivo',
            \Carbon\Carbon::parse($data['created_at'])->format('d-m-Y'),
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 8,   // ID
            'B' => 18,  // Código
            'C' => 25,  // Tipo de Equipo
            'D' => 30,  // Punto de Venta
            'E' => 20,  // Serial
            'F' => 30,  // Nombre
            'G' => 20,  // Modelo
            'H' => 12,  // Estado
            'I' => 18,  // Fecha
        ];
    }

    public function defaultStyles(Style $defaultStyle)
    {
        return [
            'font' => [
                'name' => 'Arial',
                'size' => 11,
            ],
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => [
                    'bold'  => true,
                    'color' => ['argb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['argb' => '2563EB'], // Azul (diferente a POS)
                ],
            ],
        ];
    }
}
