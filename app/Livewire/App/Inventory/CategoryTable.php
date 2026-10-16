<?php

namespace App\Livewire\App\Inventory;

use App\Livewire\Base\DataTable;
use App\Models\Products\Category;
use App\Services\Products\CategoryService;
use DomainException;
use Illuminate\Database\Eloquent\Builder;

/**
 * Categorías en árbol (v1.5.1 REQ-2.3): cada principal y debajo sus subcategorías, con el
 * conteo de productos. Filtrar por una principal muestra ella y sus subcategorías.
 */
class CategoryTable extends DataTable
{
    public array $filters = [
        'search' => '',
        'parent_id' => '',
        'is_active' => '',
        'trashed' => '',
    ];

    protected function columns(): array
    {
        return [
            'id' => ['label' => 'ID'],
            'name' => ['label' => 'Nombre', 'default' => true, 'mobile' => true],
            'products_count' => ['label' => 'Productos', 'default' => true, 'mobile' => true],
            'is_active' => ['label' => 'Estado', 'default' => true],
            'description' => ['label' => 'Descripción', 'default' => true],
            'created_at' => ['label' => 'Fecha Creación'],
            'updated_at' => ['label' => 'Última Actualización'],
        ];
    }

    /** En qué busca el filtro 'search' de abajo — debe coincidir con su closure. */
    protected function searchFields(): array
    {
        return ['nombre', 'ID'];
    }

    protected function filterMap(): array
    {
        return [
            'search' => fn (Builder $q, $v) => $q->where(fn (Builder $qq) => $qq
                ->where('categories.name', 'like', "%{$v}%")
                ->orWhere('categories.id', 'like', "%{$v}%")),
            'parent_id' => fn (Builder $q, $v) => $q->where(fn (Builder $qq) => $qq
                ->where('categories.id', $v)
                ->orWhere('categories.parent_id', $v)),
            'is_active' => fn (Builder $q, $v) => $q->where('categories.is_active', (bool) $v),
        ];
    }

    protected function filterOptions(): array
    {
        return ['parents' => Category::parents()->orderBy('name')->pluck('name', 'id')->all()];
    }

    protected function nonChipFilterKeys(): array
    {
        return ['trashed'];
    }

    protected function formatFilterValue(string $key, mixed $value): string
    {
        return match ($key) {
            'is_active' => filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'Activas' : 'Inactivas',
            'parent_id' => $this->filterOptions()['parents'][$value] ?? $value,
            default => parent::formatFilterValue($key, $value),
        };
    }

    protected function baseQuery(): Builder
    {
        $query = $this->filters['trashed'] === 'only'
            ? Category::onlyTrashed()
            : Category::query();

        // Orden de árbol: cada principal y debajo sus subcategorías, por nombre.
        $query->with(['parent' => fn ($q) => $q->withTrashed()->select('id', 'name', 'deleted_at')])
            ->withCount(['products', 'children'])
            ->orderByRaw('COALESCE((SELECT p.name FROM categories p WHERE p.id = categories.parent_id), categories.name)')
            ->orderByRaw('categories.parent_id IS NOT NULL')
            ->orderBy('categories.name');

        return $this->applyFilters($query);
    }

    public function toggleActivo(int $id): void
    {
        abort_unless(auth()->user()->can('categories.manage'), 403);

        $category = Category::findOrFail($id);
        $category->toggleActivo();

        $this->notify('success', 'Estado actualizado para "'.$category->name.'".');
    }

    public function restore(int $id): void
    {
        abort_unless(auth()->user()->can('categories.manage'), 403);

        $category = Category::onlyTrashed()->findOrFail($id);

        try {
            app(CategoryService::class)->restore($category);
        } catch (DomainException $e) {
            $this->notify('error', $e->getMessage());

            return;
        }

        $this->notify('success', "Categoría \"{$category->name}\" restaurada correctamente.");
    }

    public function forceDelete(int $id): void
    {
        abort_unless(auth()->user()->can('categories.manage'), 403);

        $category = Category::onlyTrashed()->findOrFail($id);
        $name = $category->name;

        try {
            $category->forceDeleteGuarded("La categoría \"{$name}\"");
        } catch (DomainException $e) {
            $this->notify('error', $e->getMessage());

            return;
        }

        $this->notify('success', "Categoría \"{$name}\" eliminada definitivamente.");
    }

    public function render()
    {
        return view('livewire.app.inventory.category-table', [
            'categories' => $this->baseQuery()->paginate($this->perPage),
            'parents' => $this->filterOptions()['parents'],
        ]);
    }
}
