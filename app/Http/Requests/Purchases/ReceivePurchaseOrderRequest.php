<?php

namespace App\Http\Requests\Purchases;

use App\Models\Purchases\PurchaseReception;
use App\Models\Purchases\PurchaseReceptionItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Recibir una entrega de una orden (v1.5.0 REQ-5.2). Lo pendiente por línea y que haya algo
 * que recibir o rechazar se validan en PurchaseReceptionService::receive(), con la orden
 * bloqueada.
 */
class ReceivePurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('purchase_receptions.create');
    }

    public function rules(): array
    {
        return [
            'reception_date' => 'required|date|before_or_equal:today',
            'supplier_invoice' => 'nullable|string|max:50',
            'supplier_ncf' => 'nullable|string|max:19',
            'financial_account_id' => ['nullable', Rule::exists('financial_accounts', 'id')->where('is_active', true)],
            'notes' => 'nullable|string|max:500',
            'rating' => ['nullable', Rule::in(array_keys(PurchaseReception::RATINGS))],
            'rating_note' => 'nullable|string|max:255',

            'lines' => 'required|array',
            'lines.*.received' => 'nullable|numeric|min:0',
            'lines.*.rejected' => 'nullable|numeric|min:0',
            'lines.*.reason' => ['nullable', Rule::in(array_keys(PurchaseReceptionItem::getRejectReasons()))],
            'lines.*.unit_cost' => 'required|numeric|min:0',
        ];
    }

    public function attributes(): array
    {
        return [
            'reception_date' => 'fecha',
            'supplier_invoice' => 'factura del proveedor',
            'supplier_ncf' => 'NCF del proveedor',
            'financial_account_id' => 'cuenta de pago',
            'notes' => 'nota',
            'rating' => 'calificación',
            'rating_note' => 'nota de la calificación',
            'lines.*.received' => 'cantidad recibida',
            'lines.*.rejected' => 'cantidad rechazada',
            'lines.*.reason' => 'motivo del rechazo',
            'lines.*.unit_cost' => 'costo unitario',
        ];
    }

    public function messages(): array
    {
        return [
            'reception_date.before_or_equal' => 'La fecha de recepción no puede ser futura.',
        ];
    }
}
