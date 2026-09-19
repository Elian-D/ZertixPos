<?php

namespace App\Livewire\App\Finance;

use App\Livewire\Base\DataTable;
use App\Models\Sales\Ncf\NcfSequence;
use App\Services\Sales\Ncf\NcfCatalogService;
use Illuminate\Database\Eloquent\Builder;

/**
 * Categoría B "sin papelera visible" (docs/analisis/politica-soft-deletes.md §4.5)
 * — caso ya bien resuelto, no se toca el criterio: soft-delete real vía
 * NcfSequenceService::delete() (guardado: solo si `current < from`, ningún NCF
 * emitido todavía), pero sin restaurar expuesto al usuario — decisión explícita
 * para un dato fiscal sensible, no un olvido. destroy() se queda como ruta real.
 */
class NcfSequenceTable extends DataTable
{
    public array $filters = [
        'search'      => '',
        'ncf_type_id' => '',
        'status'      => '',
    ];

    protected function columns(): array
    {
        return [
            'type_id'         => ['label' => 'Tipo de Comprobante', 'default' => true, 'mobile' => true],
            'series'          => ['label' => 'Serie'],
            'range'           => ['label' => 'Rango (Desde - Hasta)', 'default' => true],
            'current'         => ['label' => 'Último Usado', 'default' => true, 'mobile' => true],
            'available'       => ['label' => 'Disponibles', 'default' => true],
            'usage_percent'   => ['label' => '% de Uso', 'default' => true],
            'expiry_date'     => ['label' => 'Vencimiento', 'default' => true],
            'alert_threshold' => ['label' => 'Umbral Alerta'],
            'status'          => ['label' => 'Estado', 'default' => true, 'mobile' => true],
            'created_at'      => ['label' => 'Fecha Registro'],
        ];
    }

    protected function filterMap(): array
    {
        return [
            'search'      => fn (Builder $q, $v) => $q->where('series', 'like', "%{$v}%"),
            'ncf_type_id' => fn (Builder $q, $v) => $q->where('ncf_type_id', $v),
            'status'      => fn (Builder $q, $v) => match ($v) {
                // Activos: Status DB es active Y no ha vencido Y quedan números
                NcfSequence::STATUS_ACTIVE => $q->where('status', NcfSequence::STATUS_ACTIVE)
                    ->whereDate('expiry_date', '>', now()->format('Y-m-d'))
                    ->whereRaw('(ncf_sequences.to - ncf_sequences.current) > 0'),
                // Agotados: La resta de to - current es 0 o menos
                NcfSequence::STATUS_EXHAUSTED => $q->whereRaw('(ncf_sequences.to - ncf_sequences.current) <= 0'),
                // Vencidos: La fecha de vencimiento ya pasó
                NcfSequence::STATUS_EXPIRED => $q->whereDate('expiry_date', '<=', now()->format('Y-m-d')),
                default => $q->where('status', $v),
            },
        ];
    }

    protected function filterOptions(): array
    {
        return app(NcfCatalogService::class)->getForSequences();
    }

    protected function formatFilterValue(string $key, mixed $value): string
    {
        $options = $this->filterOptions();

        return match ($key) {
            'ncf_type_id' => $options['ncf_types'][$value] ?? $value,
            'status'      => $options['statuses'][$value] ?? $value,
            default       => parent::formatFilterValue($key, $value),
        };
    }

    protected function baseQuery(): Builder
    {
        return $this->applyFilters(NcfSequence::query()->with('type'));
    }

    public function render()
    {
        $sequences = $this->baseQuery()->latest()->paginate($this->perPage);

        return view('livewire.app.finance.ncf-sequence-table', array_merge(
            ['items' => $sequences],
            $this->filterOptions()
        ));
    }
}
