<?php

namespace App\Http\Requests\Inventory;

use App\Models\Inventory\InventoryMovement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInventoryMovementRequest extends FormRequest
{
    /**
     * Solo usuarios con permiso de ajustes pueden crear movimientos manuales
     */
    public function authorize(): bool
    {
        return $this->user()->can('inventory_movements.create_adjustment');
    }

    public function rules(): array
    {
        return [
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'product_id'   => ['required', 'exists:products,id'],
            'to_warehouse_id' => [
                Rule::requiredIf($this->type === InventoryMovement::TYPE_TRANSFER),
                'nullable',
                'exists:warehouses,id',
                'different:warehouse_id', // No se puede transferir al mismo almacén
            ],
            
            // Permitimos números negativos si el tipo es ajuste, 
            // pero quantity siempre debe ser numérico.
            'quantity'     => ['required', 'numeric', 'not_in:0'],
            
            // El tipo para creación manual suele ser 'adjustment', 
            // pero lo dejamos abierto a los tipos definidos en el modelo.
            'type'         => ['required', Rule::in(array_keys(InventoryMovement::getTypes()))],
            
            'description'  => ['required', 'string', 'min:5', 'max:255'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $type = $this->type;
            $qty = $this->quantity;
            
            // 1. Si es ENTRADA, prohibir negativos o cero
            if ($type === InventoryMovement::TYPE_INPUT && $qty <= 0) {
                $validator->errors()->add('quantity', 'Para una entrada, la cantidad debe ser un número positivo.');
            }

            // 2. Obtener stock actual para validaciones de saldo
            $stockModel = \App\Models\Inventory\InventoryStock::where('warehouse_id', $this->warehouse_id)
                ->where('product_id', $this->product_id)
                ->first();
            $currentQty = $stockModel ? $stockModel->quantity : 0;

            // 3. Si es SALIDA o TRANSFERENCIA
            if (in_array($type, [InventoryMovement::TYPE_OUTPUT, InventoryMovement::TYPE_TRANSFER])) {
                $absQty = abs($qty);
                if ($currentQty < $absQty) {
                    $validator->errors()->add('quantity', "Stock insuficiente para realizar la operación. Disponible: {$currentQty}.");
                }
            }

            // 4. Si es AJUSTE (Validar que el ajuste negativo no supere el stock actual)
            if ($type === InventoryMovement::TYPE_ADJUSTMENT) {
                if (($currentQty + $qty) < 0) {
                    $validator->errors()->add('quantity', "El ajuste dejaría el stock en negativo ({$currentQty} + {$qty} = " . ($currentQty + $qty) . ").");
                }
            }
        });
    }
    
    /**
     * Mensajes personalizados para que el Dashboard sea claro
     */
    public function messages(): array
    {
        return [
            'quantity.not_in' => 'La cantidad debe ser distinta de cero.',
            'description.required' => 'Debes explicar el motivo del ajuste por motivos de auditoría.',
        ];
    }
}