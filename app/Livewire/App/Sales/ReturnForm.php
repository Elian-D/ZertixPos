<?php

namespace App\Livewire\App\Sales;

use App\Models\Products\Product;
use App\Models\Sales\Returns\SaleReturn;
use App\Models\Sales\Sale;
use App\Services\Sales\Returns\ReturnService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Throwable;

/**
 * Modal "Devolver" del listado de Ventas (v1.4.0 REQ-2.4 + ajustes post-prueba).
 * Se abre con el evento `open-return` ({ saleId }) desde la acción de fila, solo
 * para ventas que ya no se pueden anular (Sale::canBeCanceled() === false).
 * Una devolución puede incluir varias líneas de la venta.
 */
class ReturnForm extends Component
{
    public const MODAL = 'return-sale';

    public ?int $saleId = null;

    public string $reason = '';

    public string $method = SaleReturn::METHOD_CASH;

    /** @var array<int, array{selected: bool, quantity: mixed, restock: bool}> keyed por sale_item_id */
    public array $lines = [];

    public string $replacementProductId = '';

    public $replacementQuantity = 1;

    public string $notes = '';

    #[On('open-return')]
    public function openFor(int $saleId): void
    {
        abort_unless(auth()->user()->can('returns.create'), 403);

        $this->resetValidation();
        $this->reset(['reason', 'method', 'lines', 'replacementProductId', 'replacementQuantity', 'notes']);
        $this->saleId = $saleId;
        unset($this->sale, $this->returnableItems);

        $sale = $this->sale;
        if (! $sale || $sale->status !== Sale::STATUS_COMPLETED || $sale->canBeCanceled()) {
            $this->saleId = null;
            $this->dispatch('notify', type: 'error', title: 'Error', message: 'Esta venta no admite devolución.');

            return;
        }

        // Cantidad por defecto: todo lo que queda por devolver de cada línea.
        $returnable = $this->returnableItems;
        foreach ($returnable as $item) {
            $this->lines[$item->id] = [
                'selected' => $returnable->count() === 1,
                'quantity' => $this->fmtQty($item->returnable),
                'restock' => true,
            ];
        }

        $this->js("window.dispatchEvent(new CustomEvent('open-modal', { detail: '".self::MODAL."' }))");
    }

    /**
     * La cantidad nunca pasa del máximo disponible (tope en el campo, no un error
     * después). $key llega null cuando el toggle (@entangle) reescribe el arreglo.
     */
    public function updatedLines($value, ?string $key = null): void
    {
        unset($this->selectedIds, $this->lineValues, $this->total);

        // Cambio de producto solo con una línea: al marcar una segunda, vuelve a efectivo/deuda.
        if (count($this->selectedIds) > 1 && $this->method === SaleReturn::METHOD_EXCHANGE) {
            $this->method = SaleReturn::METHOD_CASH;
            $this->reset(['replacementProductId', 'replacementQuantity']);
        }

        if ($key === null) {
            return;
        }

        [$id, $field] = array_pad(explode('.', $key), 2, null);

        if ($field !== 'quantity' || ! isset($this->lines[$id])) {
            return;
        }

        $max = (float) ($this->returnableItems->firstWhere('id', (int) $id)?->returnable ?? 0);
        if ($value !== '' && $value !== null && (float) $value > $max) {
            $this->lines[$id]['quantity'] = $this->fmtQty($max);
        }
    }

    #[Computed]
    public function canExchange(): bool
    {
        return count($this->selectedIds) <= 1;
    }

    #[Computed]
    public function sale(): ?Sale
    {
        return $this->saleId
            ? Sale::with(['items.product', 'client:id,name', 'receivable', 'posSession'])->find($this->saleId)
            : null;
    }

    /** Líneas con unidades todavía por devolver, con `returnable` precalculado. */
    #[Computed]
    public function returnableItems(): Collection
    {
        if (! $this->sale) {
            return collect();
        }

        return $this->sale->items
            ->each(fn ($item) => $item->returnable = $item->returnableQuantity())
            ->filter(fn ($item) => $item->returnable > 0)
            ->values();
    }

    #[Computed]
    public function isCredit(): bool
    {
        return $this->sale?->payment_type === Sale::PAYMENT_CREDIT;
    }

    /** Snapshot por línea (valores de la venta original) para la cantidad elegida. */
    #[Computed]
    public function lineValues(): array
    {
        $values = [];

        foreach ($this->returnableItems as $item) {
            $qty = (float) ($this->lines[$item->id]['quantity'] ?? 0);
            $unitNet = (float) $item->subtotal / (float) $item->quantity;
            $unitTax = (float) $item->tax_amount / (float) $item->quantity;

            $values[$item->id] = [
                'unit_net' => $unitNet,
                'unit_tax' => $unitTax,
                'total' => $qty > 0 ? round($unitNet * $qty, 2) + round($unitTax * $qty, 2) : 0,
            ];
        }

        return $values;
    }

    #[Computed]
    public function selectedIds(): array
    {
        return collect($this->lines)->filter(fn ($l) => ! empty($l['selected']))->keys()->map(fn ($id) => (int) $id)->all();
    }

    #[Computed]
    public function total(): float
    {
        return round(collect($this->selectedIds)->sum(fn ($id) => $this->lineValues[$id]['total'] ?? 0), 2);
    }

    #[Computed]
    public function products(): Collection
    {
        return Product::activo()->orderBy('name')->get(['id', 'name', 'price', 'type']);
    }

    /** ¿El cambio es por un producto distinto (genera venta nueva)? */
    #[Computed]
    public function isDifferentProduct(): bool
    {
        if ($this->method !== SaleReturn::METHOD_EXCHANGE || $this->replacementProductId === '') {
            return false;
        }

        $ids = $this->selectedIds;
        $item = count($ids) === 1 ? $this->returnableItems->firstWhere('id', $ids[0]) : null;

        return $item && (int) $this->replacementProductId !== $item->product_id;
    }

    /** Resumen del cambio por un producto distinto: a favor / nuevo / cobrar o entregar. */
    #[Computed]
    public function exchangeSummary(): ?array
    {
        if (! $this->isDifferentProduct || $this->total <= 0) {
            return null;
        }

        $product = $this->products->firstWhere('id', (int) $this->replacementProductId);
        $qty = (float) $this->replacementQuantity;
        if (! $product || $qty <= 0) {
            return null;
        }

        $new = app(ReturnService::class)->quoteReplacement($product, $qty)['total'];
        $diff = round($new - $this->total, 2);

        return ['credit' => $this->total, 'new' => $new, 'collect' => max(0, $diff), 'give' => max(0, -$diff)];
    }

    protected function rules(): array
    {
        $rules = [
            'reason' => ['required', 'in:'.implode(',', array_keys(SaleReturn::getReasons()))],
            'method' => ['required', 'in:'.SaleReturn::METHOD_CASH.','.SaleReturn::METHOD_EXCHANGE, function ($attribute, $value, $fail) {
                if ($value === SaleReturn::METHOD_EXCHANGE && ! $this->canExchange) {
                    $fail('El cambio de producto es de uno en uno. Con varios productos, la devolución es en efectivo o reduce la deuda.');
                }
            }],
            'lines' => [function ($attribute, $value, $fail) {
                if (empty($this->selectedIds)) {
                    $fail('Marca al menos un producto a devolver.');
                }
            }],
            'replacementProductId' => ['nullable', 'exists:products,id'],
            'replacementQuantity' => [$this->isDifferentProduct ? 'required' : 'nullable', 'numeric', 'gt:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];

        foreach ($this->selectedIds as $id) {
            $max = (float) ($this->returnableItems->firstWhere('id', $id)?->returnable ?? 0);
            $rules["lines.{$id}.quantity"] = ['required', 'numeric', 'gt:0', 'max:'.$max];
        }

        return $rules;
    }

    protected function messages(): array
    {
        return [
            'reason.required' => 'Selecciona el motivo.',
            'lines.*.quantity.required' => 'Indica la cantidad.',
            'lines.*.quantity.gt' => 'Debe ser mayor a cero.',
            'lines.*.quantity.max' => 'Máximo :max.',
            'replacementQuantity.gt' => 'La cantidad debe ser mayor a cero.',
        ];
    }

    public function save(ReturnService $service): void
    {
        abort_unless(auth()->user()->can('returns.create'), 403);

        $this->validate();

        $lines = collect($this->selectedIds)->map(fn ($id) => [
            'sale_item_id' => $id,
            'quantity' => (float) $this->lines[$id]['quantity'],
            'restock' => (bool) ($this->lines[$id]['restock'] ?? true),
        ])->all();

        try {
            $return = $service->create($this->sale, [
                'lines' => $lines,
                'reason' => $this->reason,
                'refund_method' => $this->method,
                'replacement_product_id' => $this->isDifferentProduct ? (int) $this->replacementProductId : null,
                'replacement_quantity' => (float) $this->replacementQuantity,
                'notes' => $this->notes ?: null,
            ]);
        } catch (Throwable $e) {
            report($e);
            $this->dispatch('notify', type: 'error', title: 'No se pudo registrar', message: $e->getMessage());

            return;
        }

        $printUrl = route('sales.returns.print', $return);

        $this->dispatch('notify',
            type: 'success',
            title: '¡Éxito!',
            message: "Devolución {$return->number} registrada.",
            link: ['url' => $printUrl, 'label' => 'Imprimir ticket'],
        );
        $this->dispatch('return-created');
        $this->js("window.dispatchEvent(new CustomEvent('close-modal', { detail: '".self::MODAL."' })); window.open('{$printUrl}', '_blank');");

        $this->saleId = null;
    }

    private function fmtQty(float $qty): string
    {
        return rtrim(rtrim(number_format($qty, 2, '.', ''), '0'), '.');
    }

    public function render()
    {
        return view('livewire.app.sales.return-form', [
            'reasons' => SaleReturn::getReasons(),
        ]);
    }
}
