<?php

namespace App\Services\Inventory\WarehouseService;

use App\Models\Inventory\Warehouse;
use Illuminate\Support\Facades\DB;

class WarehouseService
{
    public function store(array $data): Warehouse
    {
        return DB::transaction(function () use ($data) {
            return Warehouse::create($data);
            // Nota: El modelo booted() ya se encarga de crear la cuenta y el código
        });
    }

    public function update(Warehouse $warehouse, array $data): Warehouse
    {
        return DB::transaction(function () use ($warehouse, $data) {
            $nombreAnterior = $warehouse->name;
            $warehouse->update($data);

            if ($nombreAnterior !== $warehouse->name) {
                $warehouse->generateCode();
                // Opcional: Actualizar el nombre de la cuenta contable
                if ($warehouse->accountingAccount) {
                    $warehouse->accountingAccount->update([
                        'name' => 'Inventario: '.$warehouse->name,
                    ]);
                }
            }

            return $warehouse;
        });
    }

    public function toggle(Warehouse $warehouse): bool
    {
        return (bool) $warehouse->toggleActivo();
    }
}
