# Sistema de Tablas Adaptativas (DataTable Pattern)

> **Antes de migrar un módulo:** seguir [`docs/ui/datatable-migration-checklist.md`](./datatable-migration-checklist.md) — checklist de errores reales cometidos (N+1, permisos de Docker, selección masiva a medias, etc.), a repasar en cada sub-fase (0.7-0.10).

Motor de tablas de ZertixPOS (Fase 0, `docs/features/v1.3.0.md`, REQ-0). Porte del patrón de tablas de Orvian Kit — **sin instalar el paquete** (`Opción C`, decisión 2026-08-26): el código vive directo en `app/` de ZertixPOS, con los namespaces y tokens de color reales del proyecto, no `Orvian\Kit\*`. Combina el **Pipeline Pattern** (filtros), el motor reactivo de **Livewire 4**, y componentes Blade especializados para tablas con búsqueda, filtros, paginación propia, columnas configurables por dispositivo, selección masiva y carga asíncrona — sin repetir código entre módulos.

---

## Tabla de Contenido

- [Estructura de Archivos](#estructura-de-archivos)
- [Guía de Implementación](#guía-de-implementación)
  - [1. Definir las columnas — `columns()`](#1-definir-las-columnas--columns)
  - [2. Crear el componente Livewire](#2-crear-el-componente-livewire)
    - [2.5 Formateo Inteligente de Filtros (Hook Method)](#25-formateo-inteligente-de-filtros-hook-method)
  - [3. Uso en Blade](#3-uso-en-blade)
- [Componentes de la Suite (`x-data-table.*`)](#componentes-de-la-suite-x-data-table)
- [Jerarquía Visual y Page Header](#jerarquía-visual-y-page-header)
- [Sistema de Filtros — `FilterInterface` (REQ-0.3, REQ-0.4)](#sistema-de-filtros--filterinterface-req-03-req-04)
- [Selección Masiva y Barra de Acciones (REQ-0.5)](#selección-masiva-y-barra-de-acciones-req-05)
- [Menú de Acciones — `x-ui.action-menu` (REQ-0.6)](#menú-de-acciones--x-uiaction-menu-req-06)
- [Sistema de Paginación ZertixPOS](#sistema-de-paginación-zertixpos)
- [Carga Asíncrona y Skeleton](#carga-asíncrona-y-skeleton)
- [Responsividad y Control de Columnas](#responsividad-y-control-de-columnas)
- [Eliminar N+1 con scopeWithIndexRelations](#eliminar-n1-con-scopewithindexrelations)
- [Componente Empty State](#componente-empty-state)
- [Notas Adicionales](#notas-adicionales)

---

## Estructura de Archivos

```plaintext
app/
├── Livewire/
│   └── Base/
│       └── DataTable.php                       # Clase abstracta base (App\Livewire\Base\DataTable)
├── Filters/
│   ├── Contracts/
│   │   └── FilterInterface.php                 # apply(Builder $query, mixed $value): Builder
│   ├── Base/
│   │   └── QueryFilter.php                     # Orquestador legacy (controladores AJAX no migrados aún)
│   └── [Modulo]/
│       └── [Campo]Filter.php                   # Un filtro por campo — closure o clase
resources/
└── views/
    ├── components/
    │   ├── data-table/
    │   │   ├── base-table.blade.php            # Esqueleto visual + selección masiva
    │   │   ├── cell.blade.php                  # Celda inteligente
    │   │   ├── row-checkbox.blade.php          # Checkbox de fila (REQ-0.5)
    │   │   ├── bulk-actions-bar.blade.php      # Barra flotante de acciones masivas (REQ-0.5)
    │   │   ├── search.blade.php
    │   │   ├── per-page-selector.blade.php
    │   │   ├── filter-container.blade.php
    │   │   ├── filter-group.blade.php
    │   │   ├── filter-select.blade.php
    │   │   ├── filter-toggle.blade.php
    │   │   ├── filter-date-range.blade.php
    │   │   ├── filter-range.blade.php
    │   │   ├── filter-chips.blade.php
    │   │   └── column-selector.blade.php
    │   └── ui/
    │       ├── empty-state.blade.php
    │       ├── skeleton.blade.php
    │       ├── action-menu.blade.php            # Menú "···" genérico (REQ-0.6)
    │       └── action-menu/
    │           └── item.blade.php
    └── pagination/
        ├── zertix-compact.blade.php             # Default de todo el sistema
        ├── zertix-full.blade.php                # Módulos de alta densidad (auditoría, logs)
        └── zertix-ledger.blade.php               # Datasets masivos o mobile
```

> No existe `app/Tables/*TableConfig.php` como clase separada — a diferencia del kit original de Orvian, en ZertixPOS las columnas se definen directo en el método `columns()` del componente Livewire hijo. Es una fuente de verdad menos que mantener sincronizada.

---

## Guía de Implementación

### 1. Definir las columnas — `columns()`

A diferencia del kit original de Orvian (que usaba una clase `TableConfig` separada), en ZertixPOS las columnas se declaran directo en el componente Livewire hijo, en el método `columns()`:

```php
protected function columns(): array
{
    return [
        'name'          => ['label' => 'Nombre',          'mobile' => true],
        'email'         => ['label' => 'Correo Electrónico', 'mobile' => false],
        'role'          => ['label' => 'Rol',              'mobile' => true],
        'status'        => ['label' => 'Estado',           'mobile' => false],
        'last_login_at' => ['label' => 'Último Acceso',    'mobile' => false],
    ];
}
```

`mobile: true` marca las columnas que quedan visibles por defecto en pantallas pequeñas — `App\Livewire\Base\DataTable` calcula `defaultDesktop()` (todas las claves de `columns()`) y `defaultMobile()` (solo las marcadas `mobile: true`) automáticamente, sin una clase aparte.

---

### 2. Crear el Componente Livewire

`App\Livewire\Base\DataTable` orquesta paginación, visibilidad de columnas, filtros y selección masiva. Los hijos implementan solo lo específico del módulo: `columns()`, `filterMap()`, `filterOptions()`, `bulkActions()`, `render()`, y los métodos de negocio (CRUD, etc.).

```php
namespace App\Livewire\Products;

use App\Livewire\Base\DataTable;
use App\Models\Products\Product;
use App\Services\Products\ProductService;

class ProductTable extends DataTable
{
    public array $filters = [
        'search'    => '',
        'is_active' => '',
        'category'  => '',
    ];

    protected function columns(): array
    {
        return [
            'name'     => ['label' => 'Producto',  'mobile' => true],
            'category' => ['label' => 'Categoría',  'mobile' => false],
            'price'    => ['label' => 'Precio',     'mobile' => true],
            'stock'    => ['label' => 'Stock',      'mobile' => false],
        ];
    }

    protected function filterMap(): array
    {
        return [
            'search'    => fn ($q, $v) => $q->where('name', 'like', "%{$v}%"),
            'is_active' => fn ($q, $v) => $q->where('is_active', (bool) $v),
            'category'  => fn ($q, $v) => $q->where('category_id', $v),
        ];
    }

    protected function bulkActions(): array
    {
        return [
            ['key' => 'activate',   'label' => 'Activar',   'icon' => 'heroicon-o-check',
                'variant' => 'default'],
            ['key' => 'deactivate', 'label' => 'Desactivar', 'icon' => 'heroicon-o-x-mark',
                'variant' => 'default'],
            ['key' => 'delete',     'label' => 'Eliminar',   'icon' => 'heroicon-o-trash',
                'variant' => 'error', 'confirm' => true,
                'confirmMessage' => '¿Eliminar los productos seleccionados?'],
        ];
    }

    protected function performBulkAction(string $action, array $ids): void
    {
        app(ProductService::class)->performBulkAction($ids, $action);
    }

    protected function currentPageIds(): array
    {
        return $this->query()->paginate($this->perPage)->pluck('id')->all();
    }

    protected function query()
    {
        return $this->applyFilters(Product::query()->withIndexRelations());
    }

    public function render()
    {
        $products = $this->query()->paginate($this->perPage);

        return view('livewire.products.product-table', compact('products'));
    }
}
```

> [!IMPORTANT]
> Nunca uses `->paginate(10)` con un literal. Siempre `->paginate($this->perPage)`.

> [!TIP]
> El atributo `#[Lazy]` viene heredado de `DataTable`. Si un módulo no debe cargar diferido, sobreescribe con `#[Lazy(enabled: false)]` en el hijo.

### 2.5 Formateo Inteligente de Filtros (Hook Method)

El componente base es "ciego" respecto a la base de datos para mantenerse reutilizable. Por defecto, si un filtro usa un ID, el chip muestra ese ID literal (ej: `Categoría: 4`). Para mostrar nombres reales, sobreescribe `formatFilterValue()` en el hijo:

```php
protected function formatFilterValue(string $key, mixed $value): string
{
    return match ($key) {
        'category' => \App\Models\Products\Category::find($value)?->name ?? $value,
        'is_active' => $value === '1' ? 'Activos' : 'Inactivos',
        default     => parent::formatFilterValue($key, $value),
    };
}
```

---

### 3. Uso en Blade

```html
<x-ui.page-header
    title="Productos"
    description="Catálogo de productos del sistema."
    :count="$products->total()"
    countLabel="productos"
>
    <x-slot:actions>
        <x-ui.button variant="primary" size="sm" iconLeft="heroicon-s-plus"
            wire:click="create">
            Nuevo Producto
        </x-ui.button>
    </x-slot:actions>
</x-ui.page-header>

<x-data-table.base-table
    :items="$products"
    :columns="$this->columns()"
    :visibleColumns="$visibleColumns"
    :activeChips="$this->getActiveChips()"
    :hasFilters="count(array_filter($filters)) > 0"
    :selectable="true"
    :bulkActions="$this->bulkActions()"
>
    <x-slot:filterSlot>
        <x-data-table.filter-container
            :activeCount="count(array_filter($filters))">
            <x-data-table.filter-select
                label="Categoría"
                filterKey="category"
                :options="$categoryOptions"
                placeholder="Todas las categorías"
            />
            <x-data-table.filter-toggle
                label="Solo activos"
                filterKey="is_active"
            />
        </x-data-table.filter-container>
    </x-slot:filterSlot>

    @forelse($products as $product)
        <tr class="hover:bg-slate-50 transition-colors duration-150">
            <x-data-table.row-checkbox :id="$product->id" />

            <x-data-table.cell column="name" :visible="$visibleColumns">
                {{ $product->name }}
            </x-data-table.cell>

            <x-data-table.cell column="category" :visible="$visibleColumns">
                {{ $product->category?->name ?? '—' }}
            </x-data-table.cell>

            <td class="px-4 py-3.5 text-right">
                <x-ui.action-menu>
                    <x-ui.action-menu.item wire:click="edit({{ $product->id }})" icon="heroicon-o-pencil-square">
                        Editar
                    </x-ui.action-menu.item>
                    <x-ui.action-menu.item wire:click="confirmDelete({{ $product->id }})" icon="heroicon-o-trash" variant="danger">
                        Eliminar
                    </x-ui.action-menu.item>
                </x-ui.action-menu>
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="{{ count($visibleColumns) + 2 }}" class="px-6 py-12">
                <x-ui.empty-state variant="simple" title="Sin resultados"
                    description="No encontramos productos con los filtros aplicados." />
            </td>
        </tr>
    @endforelse

</x-data-table.base-table>
```

> El `colspan` del empty state es `count($visibleColumns) + 2` cuando la tabla es `selectable` (columna de checkbox + columna de acciones); `+ 1` cuando no lo es.

---

## Componentes de la Suite (`x-data-table.*`)

| Componente | Rol |
|---|---|
| `x-data-table.search` | Input de búsqueda con debounce y botón × |
| `x-data-table.per-page-selector` | Select pill para registros por página |
| `x-data-table.filter-container` | Dropdown/drawer que agrupa los filtros del módulo |
| `x-data-table.filter-group` | Grupo colapsable de filtros relacionados |
| `x-data-table.filter-select` | Select dentro del filter-container |
| `x-data-table.filter-toggle` | Toggle booleano dentro del filter-container |
| `x-data-table.filter-date-range` | Rango de fechas (desde/hasta) |
| `x-data-table.filter-range` | Rango numérico (mín/máx) con prefix/suffix |
| `x-data-table.filter-chips` | Chips de filtros activos con × individual |
| `x-data-table.column-selector` | Checkboxes reactivos para columnas visibles |
| `x-data-table.cell` | `<td>` condicional que se auto-oculta si la columna no está visible |
| `x-data-table.row-checkbox` | Checkbox de fila para selección masiva (REQ-0.5) |
| `x-data-table.bulk-actions-bar` | Barra flotante de acciones sobre la selección (REQ-0.5) |

Ver [`docs/ui/datatable-components.md`](./datatable-components.md) para props y comportamiento detallado de cada uno.

---

## Jerarquía Visual y Page Header

Las **acciones primarias** (Nuevo, Exportar) viven **fuera** de la tabla, en `x-ui.page-header`. `base-table` no tiene slot de acciones — todo lo que va dentro de la tabla son controles de filtrado, visualización y la barra de selección masiva.

---

## Sistema de Filtros — `FilterInterface` (REQ-0.3, REQ-0.4)

`App\Filters\Contracts\FilterInterface` recibe el valor ya resuelto, sin `Request`:

```php
interface FilterInterface
{
    public function apply(Builder $query, mixed $value): Builder;
}
```

En el `filterMap()` de un `DataTable` hijo, cada entrada es una **closure** (caso común) o el nombre de una **clase** que implementa `FilterInterface` (solo cuando hay joins, subqueries o lógica condicional real):

```php
protected function filterMap(): array
{
    return [
        'search'   => fn ($q, $v) => $q->where('name', 'like', "%{$v}%"), // closure — caso simple
        'province' => \App\Filters\Client\ClientStateFilter::class,        // clase — filtro complejo reusado
    ];
}
```

**Compatibilidad con los controladores AJAX que aún no migraron a Livewire:** `App\Filters\Base\QueryFilter` sigue siendo el orquestador de esos módulos (`(new ProductsFilters($request))->apply($query)` en el controlador, sin cambios en esa línea). Internamente ya adapta cada filtro hijo al contrato nuevo — construye cada `FilterClass` sin `Request` y le pasa `$request->input($key)` como `$value`. Los ~90 filtros individuales de `app/Filters/**` ya están en la firma nueva.

**Colapso a closures (REQ-0.4):** cuando un módulo migra su tabla a este motor (sub-fases `feature/v1.3.0-datatable-*`), los filtros de una sola línea (`fn($q, $v) => $q->where(...)`) se colapsan directo en el `filterMap()` del `DataTable` hijo y su archivo de clase se elimina — no tiene sentido mantener una clase completa para un `where()`. Solo los filtros con joins o lógica condicional real (ej. `ClientStateFilter`) se quedan como clase, ya con la firma nueva, y se reusan igual desde `filterMap()`.

---

## Selección Masiva y Barra de Acciones (REQ-0.5)

**No existe en el kit de Orvian — construido de cero para ZertixPOS.** `App\Livewire\Base\DataTable` expone:

- `public array $selected` / `public bool $selectAll` — propiedades de servidor que sobreviven toda la vida del componente Livewire. Selección entre páginas es gratis: no hace falta el hack de `sessionStorage` que el motor AJAX viejo necesitaba para lo mismo.
- Checkbox de cabecera (`wire:model.live="selectAll"`, en `base-table`) + checkbox de fila (`x-data-table.row-checkbox`, declarado por el módulo igual que `x-data-table.cell`).
- `bulkActions(): array` — el hijo declara qué acciones ofrece. Cada botón de la barra dispara `runBulkAction('key')`, que el `DataTable` base delega en `performBulkAction()` del hijo — el mismo `performBulkAction()` que ya existe en los Services del proyecto (`ProductService`, etc.). La barra **no reimplementa lógica de negocio**, solo la invoca.
- `currentPageIds(): array` — el hijo lo implementa a partir del paginator que ya construyó en `render()`; es lo que usa el checkbox "seleccionar todos" para saber qué IDs marcar en la página actual.

Para activar la barra en un módulo: pasa `:selectable="true"` y `:bulkActions="$this->bulkActions()"` a `x-data-table.base-table`, y agrega `<x-data-table.row-checkbox :id="$item->id" />` como primera celda de cada fila.

---

## Menú de Acciones — `x-ui.action-menu` (REQ-0.6)

Menú "···" de acciones de fila — **componente genérico, no exclusivo de la tabla**. Dropdown en desktop, bottom sheet en mobile. Se construyó una sola vez en Fase 0 para que la Fase 7.6 de `v1.2.0.md` (kebab de acciones secundarias de `x-ui.page-header`, pendiente de construir) lo reuse en vez de implementarlo dos veces.

```html
<x-ui.action-menu>
    <x-ui.action-menu.item wire:click="edit({{ $item->id }})" icon="heroicon-o-pencil-square">
        Editar
    </x-ui.action-menu.item>
    <x-ui.action-menu.item wire:click="confirmDelete({{ $item->id }})" icon="heroicon-o-trash" variant="danger">
        Eliminar
    </x-ui.action-menu.item>
</x-ui.action-menu>
```

`x-ui.action-menu.item` renderiza `<button>` o `<a>` (si recibe `href`), igual que `x-ui.button`. `variant="danger"` tiñe el ítem de rojo — úsalo para eliminar/desactivar.

---

## Sistema de Paginación ZertixPOS

Vistas propias, registradas en `AppServiceProvider::boot()` como default tanto para Livewire como para paginación de controladores fuera de Livewire:

```php
Paginator::defaultView('pagination.zertix-compact');
Paginator::defaultSimpleView('pagination.zertix-compact');
```

| Vista | Uso recomendado |
|---|---|
| `pagination.zertix-compact` | **Default** — todos los DataTable. Botones numéricos compactos. |
| `pagination.zertix-full` | Módulos de alta densidad (auditoría, logs). Incluye "Ir a página". |
| `pagination.zertix-ledger` | Datasets masivos o mobile. Pill compacto con input. |

`DataTable::paginationView()` retorna `'pagination.zertix-compact'` por defecto — no hace falta declarar nada en los módulos hijos. Para usar una vista distinta en un módulo puntual: `{{ $items->links('pagination.zertix-full') }}`.

---

## Carga Asíncrona y Skeleton

`DataTable` tiene `#[Lazy]` y `placeholder()` en la clase base. Todos los módulos que hereden de ella cargan en dos fases: (1) el navegador recibe el layout completo de inmediato con un skeleton donde irá la tabla, (2) Livewire ejecuta `render()` en una segunda petición AJAX y la tabla reemplaza el skeleton. El número de filas del skeleton coincide con `$perPage`.

Para desactivar en un módulo específico: `#[Lazy(enabled: false)]` en el hijo.

---

## Responsividad y Control de Columnas

**Defaults por dispositivo** — `columns()` define, vía la clave `mobile`, qué columnas quedan visibles en cada dispositivo. Alpine detecta el viewport en `init()` del `column-selector` y llama `$wire.resetColumns(isMobile)` si hace falta corregir.

**Control del usuario** — checkboxes reactivos vía Alpine (`:checked="isVisible(key)"`), nunca se desincronizan del estado del servidor.

**Guard de mínimo** — el sistema nunca deja la tabla sin columnas. Si el usuario intenta quitar la última visible, se restauran las columnas por defecto del dispositivo.

**Overflow controlado** — `overflow-x-auto custom-scroll`. Si el usuario activa más columnas de las que caben, la tabla permite scroll horizontal sin romper el layout.

---

## Eliminar N+1 con scopeWithIndexRelations

Cada modelo usado en una tabla **debe** definir `scopeWithIndexRelations()` — centraliza el eager loading tanto para el listado como para las exportaciones Excel (ver `CLAUDE.md`, Arquitectura → Eager Loading Centralization).

```php
// app/Models/Products/Product.php
public function scopeWithIndexRelations($query)
{
    return $query->with('category', 'unit');
}

// En el render() del DataTable
$products = Product::query()->withIndexRelations()->paginate($this->perPage);
```

---

## Componente Empty State

`x-ui.empty-state` se usa dentro del `@empty` del `@forelse`. No es automático — cada módulo lo declara en su vista.

```html
@empty
    <tr>
        <td colspan="{{ count($visibleColumns) + 2 }}" class="px-6 py-12">
            <x-ui.empty-state
                variant="simple"
                title="Sin resultados"
                description="No encontramos registros que coincidan con los filtros aplicados."
            />
        </td>
    </tr>
```

---

## Notas Adicionales

- Nunca pases un literal a `paginate()`. Siempre `$this->perPage`.
- Nunca pongas acciones primarias (Nuevo, Exportar) dentro de `base-table`. Van en `x-ui.page-header`.
- Nunca omitas `->withIndexRelations()` si el modelo tiene relaciones que la vista consume.
- El `colspan` del empty state es `count($visibleColumns) + 1` (sin selección masiva) o `+ 2` (con `x-data-table.row-checkbox` activo).
- Sin clases `dark:*` en ningún componente de esta suite — el dark mode está preparado (tokens `.dark` reservados) pero no construido todavía (`CLAUDE.md`). No se inventan valores de una paleta que no existe.
- Para añadir una columna nueva: agrégala a `columns()`, marca `mobile: true` si aplica, y añade el `<x-data-table.cell>` correspondiente en la vista del módulo.
