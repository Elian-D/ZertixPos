<?php

namespace App\Services\PointOfSale;

use App\Models\Clients\PointOfSale;
use Illuminate\Support\Facades\DB;

class POSService
{
    public function createPOS(array $data): PointOfSale
    {
        return PointOfSale::create($data);
    }

    public function updatePOS(PointOfSale $pos, array $data): bool
    {
        return $pos->update($data);
    }

    public function performBulkAction(array $ids, string $action, $value = null): int
    {
        return DB::transaction(function () use ($ids, $action, $value) {
            $query = PointOfSale::whereIn('id', $ids);
            $count = count($ids);

            match ($action) {
                'delete' => $query->delete(),
                'change_active' => $query->update(['active' => $value]),
                'change_geo_state' => $query->update(['provincia_id' => $value]),
                'change_client' => $query->update(['client_id' => $value]),
                default => throw new \InvalidArgumentException('Acción no soportada'),
            };

            return $count;
        });
    }

    public function getActionLabel(string $action): string
    {
        return match ($action) {
            'delete' => 'eliminado',
            'change_active' => 'actualizado el estado operativo',
            'change_geo_state' => 'actualizado la ubicación',
            'change_client' => 'actualizado el cliente asociado',
            default => 'procesado',
        };
    }
}
