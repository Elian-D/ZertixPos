<?php

namespace App\Services\Equipment;

use App\Models\Clients\Equipment;
use Illuminate\Support\Facades\DB;

class EquipmentService
{

    public function create(array $data): Equipment
    {
        return DB::transaction(function () use ($data) {
            return Equipment::create($data);
        });
    }

    public function update(Equipment $equipment, array $data): bool
    {
        return DB::transaction(function () use ($equipment, $data) {
            return $equipment->update($data);
        });
    }

    /**
     * Acciones masivas
     */
    public function performBulkAction(array $ids, string $action, $value = null): int
    {
        return DB::transaction(function () use ($ids, $action, $value) {

            $query = Equipment::whereIn('id', $ids);
            $count = count($ids);

            match ($action) {
                'delete'          => $query->delete(),
                'change_active'   => $query->update(['active' => $value]),
                'change_type'     => $query->update(['equipment_type_id' => $value]),
                'change_pos'      => $query->update(['point_of_sale_id' => $value]),
                default           => throw new \InvalidArgumentException('Acción no soportada'),
            };

            return $count;
        });
    }

    /**
     * Etiquetas humanas para mensajes flash
     */
    public function getActionLabel(string $action): string
    {
        return match ($action) {
            'delete'        => 'eliminado',
            'change_active' => 'actualizado el estado',
            'change_type'   => 'actualizado el tipo de equipo',
            'change_pos'    => 'actualizado el punto de venta',
            default         => 'procesado',
        };
    }

}
