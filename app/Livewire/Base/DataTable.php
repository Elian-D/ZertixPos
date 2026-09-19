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

    /**
     * Desktop-default es un subconjunto curado de columns() (marcado con
     * 'default' => true), no "todas las columnas que existen" — igual que
     * el viejo TableConfig::defaultDesktop() distinguía de allColumns().
     * Si el hijo no marca 'default' en ninguna columna, todas cuentan como
     * visibles por defecto (comportamiento simple para tablas chicas).
     */
    private function resolveDefaultColumns(bool $isMobile): array
    {
        $columns = collect($this->columns());
        $hasCuratedDefaults = $columns->contains(fn ($def) => array_key_exists('default', $def));

        return $columns
            ->filter(function ($def) use ($isMobile, $hasCuratedDefaults) {
                if ($isMobile) {
                    return ($def['mobile'] ?? false) === true;
                }

                return $hasCuratedDefaults ? ($def['default'] ?? false) === true : true;
            })
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
            if (in_array($key, $this->nonChipFilterKeys(), true)) {
                continue;
            }
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
     * Claves de $filters que controlan el estado de la tabla (tabs, etc.)
     * pero no son "un filtro" desde la perspectiva del usuario — no deben
     * contarse como filtro activo ni aparecer como chip removible. Caso de
     * uso principal: 'trashed' del patrón Papelera (docs/analisis/politica
     * -soft-deletes.md §6) — cambia el scope global de la query, no agrega
     * una condición, y ya tiene su propia UI de tabs.
     */
    protected function nonChipFilterKeys(): array
    {
        return [];
    }

    /**
     * Número de filtros "reales" activos, excluyendo nonChipFilterKeys().
     * Las vistas lo usan para :hasFilters y :activeCount en vez de repetir
     * count(array_filter($filters)) con un array_diff_key manual.
     */
    public function activeFilterCount(): int
    {
        return count(array_filter(
            array_diff_key($this->filters, array_flip($this->nonChipFilterKeys()))
        ));
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

    /**
     * $value es opcional — solo lo usan las acciones declaradas con
     * 'type' => 'select' en bulkActions() (ej. "cambiar a esta provincia").
     */
    public function runBulkAction(string $action, mixed $value = null): void
    {
        if (empty($this->selected)) {
            return;
        }

        $this->performBulkAction($action, $this->selected, $value);
        $this->clearSelection();
    }

    /**
     * El hijo delega en su Service (performBulkAction() ya existente).
     */
    protected function performBulkAction(string $action, array $ids, mixed $value = null): void
    {
        // No-op por defecto — el hijo lo sobreescribe.
    }

    // =========================================================================
    // FEEDBACK (toasts)
    // =========================================================================

    /**
     * Toast de feedback tras una acción del componente (restore(), forceDelete(),
     * toggleActivo(), performBulkAction(), etc.).
     *
     * OJO: `session()->flash('success', ...)` NO alcanza acá — x-ui.toasts lee
     * session('success') con Blade dentro de app-layout.blade.php, que solo se
     * evalúa en la carga completa de la página. Un wire:click no navega, así que
     * ese flash nunca llega a mostrarse. El toast real de una acción Livewire se
     * dispara como evento de navegador (que x-ui.toasts sí escucha en
     * @notify.window), no por sesión.
     */
    protected function notify(string $type, string $message, ?string $title = null): void
    {
        $titles = [
            'success' => '¡Éxito!',
            'error'   => 'Error',
            'info'    => 'Información',
            'warning' => 'Advertencia',
        ];

        $this->dispatch('notify', type: $type, title: $title ?? ($titles[$type] ?? 'Aviso'), message: $message);
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
