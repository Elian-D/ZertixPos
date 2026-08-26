<?php

namespace App\Livewire\Base;

use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Lazy;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Motor base de las tablas de ZertixPOS (REQ-0.1) — porte de
 * Orvian\Kit\Livewire\Base\DataTable, sin instalar el paquete (Opción C).
 *
 * Los componentes hijos extienden esta clase e implementan solo
 * columns() / filterMap() / filterOptions() y su propio render().
 */
#[Lazy]
abstract class DataTable extends Component
{
    use WithPagination;

    public array $visibleColumns = [];
    public array $filters        = [];
    public int   $perPage        = 15;

    /** Selección masiva (REQ-0.5) — sobrevive toda la vida del componente. */
    public array $selected   = [];
    public bool  $selectAll  = false;

    // =========================================================================
    // BOOT
    // =========================================================================

    public function mount(): void
    {
        $this->visibleColumns = $this->resolveDefaultColumns(isMobile: false);
    }

    // =========================================================================
    // API DEL HIJO
    // =========================================================================

    /**
     * Columnas del módulo.
     * Formato: ['clave' => ['label' => 'Texto', 'mobile' => bool]]
     */
    protected function columns(): array
    {
        return [];
    }

    /**
     * Mapa de filtros.
     * Valor: Closure (simple) o ::class de FilterInterface (complejo).
     */
    protected function filterMap(): array
    {
        return [];
    }

    /**
     * Opciones para los selects de filtros.
     * El render() del hijo lo pasa a la vista.
     */
    protected function filterOptions(): array
    {
        return [];
    }

    /**
     * Acciones masivas disponibles sobre la selección actual (REQ-0.5).
     * Formato: [['key' => 'activate', 'label' => 'Activar', 'variant' => 'primary', 'icon' => 'heroicon-o-check', 'confirm' => bool]]
     */
    protected function bulkActions(): array
    {
        return [];
    }

    // =========================================================================
    // HOOKS
    // =========================================================================

    public function updatedFilters(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    // =========================================================================
    // FILTROS
    // =========================================================================

    protected function applyFilters(Builder $query): Builder
    {
        foreach ($this->filterMap() as $key => $filter) {
            if (!array_key_exists($key, $this->filters)) {
                continue;
            }
            if (!filled($this->filters[$key])) {
                continue;
            }

            $value = $this->filters[$key];

            if ($filter instanceof \Closure) {
                $filter($query, $value);
            } else {
                (new $filter())->apply($query, $value);
            }
        }

        return $query;
    }

    // =========================================================================
    // COLUMNAS
    // =========================================================================

    private function resolveDefaultColumns(bool $isMobile): array
    {
        return collect($this->columns())
            ->when(
                $isMobile,
                fn ($c) => $c->filter(fn ($def) => ($def['mobile'] ?? false) === true),
                fn ($c) => $c
            )
            ->keys()
            ->all();
    }

    public function toggleColumn(string $column): void
    {
        if (in_array($column, $this->visibleColumns)) {
            $remaining = array_values(array_diff($this->visibleColumns, [$column]));
            if (count($remaining) === 0) {
                $this->visibleColumns = $this->resolveDefaultColumns(isMobile: false);
                return;
            }
            $this->visibleColumns = $remaining;
        } else {
            $this->visibleColumns[] = $column;
        }
    }

    public function resetColumns(bool $mobile = false): void
    {
        $this->visibleColumns = $this->resolveDefaultColumns(isMobile: $mobile);
    }

    // =========================================================================
    // CHIPS
    // =========================================================================

    public function getActiveChips(): array
    {
        $labels = collect($this->columns())
            ->mapWithKeys(fn ($def, $key) => [$key => $def['label']])
            ->all();

        $chips = [];
        foreach ($this->filters as $key => $value) {
            if ($value === '' || $value === null || $value === false || $value === 0) {
                continue;
            }
            $chips[] = [
                'key'   => $key,
                'label' => $labels[$key] ?? $key,
                'value' => $this->formatFilterValue($key, $value),
            ];
        }

        return $chips;
    }

    /**
     * Hook: los hijos sobreescriben para traducir IDs a nombres legibles.
     */
    protected function formatFilterValue(string $key, mixed $value): string
    {
        return is_bool($value) ? 'Sí' : (string) $value;
    }

    public function clearFilter(string $key): void
    {
        if (array_key_exists($key, $this->filters)) {
            $current = $this->filters[$key];
            $this->filters[$key] = match (true) {
                is_bool($current) => false,
                is_int($current)  => 0,
                default            => '',
            };
        }
        $this->resetPage();
    }

    public function clearAllFilters(): void
    {
        $this->filters = array_map(function ($value) {
            return match (true) {
                is_bool($value) => false,
                is_int($value)  => 0,
                default           => '',
            };
        }, $this->filters);

        $this->resetPage();
    }

    // =========================================================================
    // SELECCIÓN MASIVA (REQ-0.5)
    // =========================================================================

    public function updatedSelectAll(bool $value): void
    {
        $this->selected = $value ? $this->currentPageIds() : [];
    }

    public function updatedSelected(): void
    {
        $this->selectAll = count($this->selected) > 0
            && empty(array_diff($this->currentPageIds(), $this->selected));
    }

    /**
     * IDs de la página actualmente renderizada — el hijo lo implementa
     * a partir del paginator que ya construyó en render().
     */
    protected function currentPageIds(): array
    {
        return [];
    }

    public function clearSelection(): void
    {
        $this->selected  = [];
        $this->selectAll = false;
    }

    public function runBulkAction(string $action): void
    {
        if (empty($this->selected)) {
            return;
        }

        $this->performBulkAction($action, $this->selected);
        $this->clearSelection();
    }

    /**
     * El hijo delega en su Service (performBulkAction() ya existente).
     */
    protected function performBulkAction(string $action, array $ids): void
    {
        // No-op por defecto — el hijo lo sobreescribe.
    }

    // =========================================================================
    // LIVEWIRE
    // =========================================================================

    public function placeholder(): \Illuminate\Contracts\View\View
    {
        return view('components.ui.skeleton', ['type' => 'table', 'rows' => $this->perPage]);
    }

    public function paginationView(): string
    {
        return 'pagination.zertix-compact';
    }

    public function paginationSimpleView(): string
    {
        return $this->paginationView();
    }

    abstract public function render();
}
