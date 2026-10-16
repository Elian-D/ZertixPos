<?php

namespace App\Exports\Inventory;

use App\Models\Inventory\Warehouse;
use App\Models\Inventory\InventoryMovement;
use App\Models\Products\Product;
use App\Models\User;
use Maatwebsite\Excel\Concerns\{FromQuery, WithHeadings, WithMapping, WithStyles, WithDefaultStyles, WithColumnWidths};
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\{Style, Fill, Alignment, Border};

class MovementsExport implements FromQuery, WithHeadings, WithMapping, WithStyles, WithDefaultStyles, WithColumnWidths
{
    protected $query;
    private $warehousesCache = [];
    private $productsCache = [];
    private $usersCache = [];
    private $typesLabels = [];

    public function __construct($query)
    {
        $this->query = $query;
        
        // Cargamos caches para evitar consultas repetitivas durante el mapeo
        $this->warehousesCache = Warehouse::pluck('name', 'id')->toArray();
        $this->productsCache = Product::pluck('name', 'id')->toArray();
        $this->usersCache = User::pluck('name', 'id')->toArray();
        $this->typesLabels = InventoryMovement::getTypes();
    }

    /**
     * Consulta base para el reporte
     */
    public function query()
    {
        return $this->query->select([
            'id', 
            'created_at', 
            'product_id', 
            'warehouse_id', 
            'to_warehouse_id',
            'type', 
            'quantity', 
            'previous_stock', 
            'current_stock', 
            'user_id', 
            'description',
            'reference_type'
        ])->orderBy('created_at', 'desc');
    }

    /**
     * Encabezados del Excel
     */
    public function headings(): array
    {
        return [
            'ID', 
            'Fecha/Hora', 
            'Producto', 
            'Almacén Origen', 
            'Almacén Destino', 
            'Tipo Operación', 
            'Cant. Movida', 
            'Stock Anterior', 
            'Stock Nuevo', 
            'Responsable', 
            'Notas/Observaciones'
        ];
    }

    /**
     * Mapeo de cada fila
     */
    public function map($movement): array
    {
        // Columna de Destino: solo transferencias (el otro almacén del documento)
        $destino = '---';

        if (in_array($movement->type, ['transfer_out', 'transfer_in']) && $movement->to_warehouse_id) {
            $destino = $this->warehousesCache[$movement->to_warehouse_id] ?? 'N/A';
        }

        return [
            $movement->id,
            $movement->created_at->format('d/m/Y H:i'),
            $this->productsCache[$movement->product_id] ?? 'N/A',
            $this->warehousesCache[$movement->warehouse_id] ?? 'N/A',
            $destino,
            $this->typesLabels[$movement->type] ?? $movement->type,
            $movement->quantity,
            $movement->previous_stock,
            $movement->current_stock,
            $this->usersCache[$movement->user_id] ?? 'Sistema',
            $movement->description ?? '',
        ];
    }

    /**
     * Configuración de anchos de columna
     */
    public function columnWidths(): array
    {
        return [
            'A' => 8,  // ID
            'B' => 18, // Fecha
            'C' => 35, // Producto
            'D' => 22, // Origen
            'E' => 22, // Destino
            'F' => 18, // Tipo
            'G' => 12, // Cantidad
            'H' => 15, // Prev
            'I' => 15, // Curr
            'J' => 20, // Usuario
            'K' => 45, // Notas
        ];
    }

    /**
     * Estilos globales por defecto
     */
    public function defaultStyles(Style $defaultStyle)
    {
        return [
            'font' => [
                'name' => 'Segoe UI',
                'size' => 10,
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ];
    }

    /**
     * Estilos específicos (Encabezado y bordes)
     */
    public function styles(Worksheet $sheet)
    {
        // Estilo para la fila de encabezado
        $sheet->getStyle('A1:K1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['argb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => '4F46E5'], // Indigo 600
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ]);

        // Aplicar bordes delgados a toda la tabla de datos (ejemplo hasta fila 1000)
        $sheet->getStyle('A1:K' . ($this->query->count() + 1))->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'E5E7EB'], // Gray 200
                ],
            ],
        ]);

        return [];
    }
}