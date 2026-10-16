<?php

namespace App\Models\Products;

use App\Traits\GuardsForceDelete;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Categoría de productos en dos niveles fijos (v1.5.1 REQ-2):
 * - principal (`parent_id` null): solo agrupa, nunca recibe productos;
 * - subcategoría (`parent_id` con valor): la única que recibe productos. No tiene hijas.
 */
class Category extends Model
{
    use GuardsForceDelete, SoftDeletes;

    protected $table = 'categories';

    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'description',
        'is_active',
    ];

    /* ===========================
     |  RELACIONES
     =========================== */

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    /* ===========================
     |  SCOPES DEL CATÁLOGO
     =========================== */

    public function scopeActivos($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInactivos($query)
    {
        return $query->where('is_active', false);
    }

    public function scopeParents(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    /* ===========================
     |  JERARQUÍA
     =========================== */

    public function isParent(): bool
    {
        return $this->parent_id === null;
    }

    /**
     * Principales activas con sus subcategorías activas, para selectores, filtros y el TPV.
     * Una principal inactiva oculta a sus hijas (REQ-2.2). Las principales sin hijas no salen
     * si $onlyWithChildren.
     */
    public static function tree(bool $onlyWithChildren = true): Collection
    {
        return static::parents()->activos()
            ->with(['children' => fn ($q) => $q->activos()->orderBy('name')])
            ->orderBy('name')
            ->get()
            ->when($onlyWithChildren, fn ($tree) => $tree->filter(fn ($p) => $p->children->isNotEmpty())->values());
    }

    /**
     * Ids de categoría que abarca un filtro (REQ-2.4): una principal equivale a todas sus
     * subcategorías; una subcategoría, a sí misma.
     *
     * @return array<int>
     */
    public static function coveredIds(int|string|null $categoryId): array
    {
        if (! $categoryId) {
            return [];
        }

        $childIds = DB::table('categories')->where('parent_id', $categoryId)->pluck('id')->all();

        return $childIds ?: [(int) $categoryId];
    }

    /**
     * Opciones de filtro en árbol: la principal en negrita y sus hijas con sangría.
     *
     * @return array{options: array<int, string>, strong: array<int>}
     */
    public static function filterOptions(): array
    {
        $tree = static::tree();
        $options = [];
        foreach ($tree as $parent) {
            $options[$parent->id] = $parent->name;
            foreach ($parent->children as $child) {
                $options[$child->id] = "\u{00A0}\u{00A0}\u{00A0}\u{00A0}".$child->name;
            }
        }

        return ['options' => $options, 'strong' => $tree->pluck('id')->all()];
    }

    /**
     * Regla de validación del producto (REQ-2.2): solo una subcategoría recibe productos.
     */
    public static function productRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) {
            $category = static::find($value);

            if (! $category) {
                $fail('Elige una categoría.');
            } elseif ($category->isParent()) {
                $fail("\"{$category->name}\" es una categoría principal y solo agrupa: elige una de sus subcategorías.");
            }
        };
    }

    /** "Agua › Botellones" para una subcategoría; el nombre solo para una principal. */
    public function getFullNameAttribute(): string
    {
        return $this->parent ? "{$this->parent->name} › {$this->name}" : $this->name;
    }

    /* ===========================
     |  COMPORTAMIENTO
     =========================== */

    public function toggleActivo(): void
    {
        $this->is_active = ! $this->is_active;
        $this->save();
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($category) {
            $category->slug = Str::slug($category->name);
        });
    }

    /**
     * Qué impide mandarla a la papelera (REQ-2.2): una principal con subcategorías o una
     * subcategoría con productos. Solo cuenta lo que no está en la papelera.
     */
    public function trashBlockers(): array
    {
        return [
            'subcategoría|subcategorías' => $this->children()->count(),
            'producto|productos' => $this->products()->count(),
        ];
    }

    /** Qué impide el borrado definitivo (v1.5.1 REQ-1.1). Cuenta también lo de la papelera. */
    public function deletionBlockers(): array
    {
        return [
            'subcategoría|subcategorías' => DB::table('categories')->where('parent_id', $this->id)->count(),
            'producto|productos' => DB::table('products')->where('category_id', $this->id)->count(),
        ];
    }
}
