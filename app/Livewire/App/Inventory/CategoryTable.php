<?php

namespace App\Livewire\App\Inventory;

use App\Livewire\Base\DataTable;
use App\Models\Products\Category;
use Illuminate\Database\Eloquent\Builder;

class CategoryTable extends DataTable
{
    public array $filters = [
        'search'    => '',
        'is_active' => '',
        'trashed'   => '',
    ];

    protected function columns(): array
    {
        return [
            'id'          => ['label' => 'ID', 'mobile' => true],
            'name'        => ['label' => 'Nombre', 'default' => true, 'mobile' => true],
            'is_active'   => ['label' => 'Estado', 'default' => true],
            'description' => ['label' => 'Descripción', 'default' => true],
            'created_at'  => ['label' => 'Fecha Creación'],
            'updated_at'  => ['label' => 'Última Actualización'],
        ];
    }

    protected function filterMap(): array
    {
        return [
            'search' => fn (Builder $q, $v) => $q->where(fn (Builder $qq) => $qq
                ->where('name', 'like', "%{$v}%")
                ->orWhere('id', 'like', "%{$v}%")),
            'is_active' => fn (Builder $q, $v) => $q->where('is_active', (bool) $v),
        ];
    }

    protected function nonChipFilterKeys(): array
    {
        return ['trashed'];
    }

    protected function formatFilterValue(string $key, mixed $value): string
    {
        return match ($key) {
            'is_active' => filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'Activos' : 'Inactivos',
            default     => parent::formatFilterValue($key, $value),
        };
    }

    protected function baseQuery(): Builder
    {
        $query = $this->filters['trashed'] === 'only'
            ? Category::onlyTrashed()
            : Category::query();

        return $this->applyFilters($query);
    }

    public function toggleActivo(int $id): void
    {
        abort_unless(auth()->user()->can('configure categories'), 403);

        $category = Category::findOrFail($id);
        $category->toggleActivo();

        $this->notify('success', 'Estado actualizado para "'.$category->name.'".');
    }

    public function restore(int $id): void
    {
        abort_unless(auth()->user()->can('configure categories'), 403);

        $category = Category::onlyTrashed()->findOrFail($id);
        $category->restore();

        $this->notify('success', "Categoría \"{$category->name}\" restaurada correctamente.");
    }

    public function forceDelete(int $id): void
    {
        abort_unless(auth()->user()->can('configure categories'), 403);

        $category = Category::onlyTrashed()->findOrFail($id);
        $name = $category->name;
        $category->forceDelete();

        $this->notify('success', "Categoría \"{$name}\" eliminada definitivamente.");
    }

    public function render()
    {
        $categories = $this->baseQuery()->paginate($this->perPage);

        return view('livewire.app.inventory.category-table', [
            'categories' => $categories,
        ]);
    }
}
