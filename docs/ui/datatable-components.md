# Componentes de DataTable (`x-data-table.*`)

Suite de componentes Blade para construir interfaces de tabla en ZertixPOS (Fase 0, `docs/features/v1.3.0.md`). Todos son componentes anónimos (sin clase PHP) y se comunican con el Livewire padre vía `$wire`. Ver [`docs/ui/datatables.md`](./datatables.md) para la guía de implementación completa.

---

## Arquitectura General

Los componentes se dividen en cuatro grupos funcionales:

**Toolbar** — controles siempre visibles sobre la tabla:
`search` · `per-page-selector` · `filter-container` · `column-selector`

**Filtros internos** — viven dentro de `filter-container`:
`filter-select` · `filter-toggle` · `filter-date-range` · `filter-range`. Pueden agruparse con `filter-group`.

**Feedback reactivo** — responden al estado de los filtros o la selección:
`filter-chips` · `cell` · `bulk-actions-bar`

**Selección masiva (REQ-0.5)** — `row-checkbox` (por fila) + el checkbox de cabecera embebido en `base-table`.

### Contrato con el componente Livewire

Todos los componentes asumen que el Livewire padre extiende `App\Livewire\Base\DataTable` y tiene:
- `public array $filters` — array de filtros con claves string
- `public array $visibleColumns` — columnas actualmente visibles
- `public int $perPage` — registros por página
- `public array $selected` / `public bool $selectAll` — selección masiva
- Métodos: `clearFilter()`, `clearAllFilters()`, `toggleColumn()`, `resetColumns()`, `runBulkAction()`, `clearSelection()`

---

## x-data-table.search

Input de búsqueda con debounce de 300ms y botón para limpiar.

### Props

| Prop | Tipo | Default | Descripción |
|---|---|---|---|
| `placeholder` | `string` | `'Buscar...'` | Texto del placeholder |
| `filterKey` | `string` | `'search'` | Clave en `$filters` del componente Livewire |

### Comportamiento

- `wire:model.live.debounce.300ms` — espera 300ms tras el último keystroke antes de disparar
- El botón × limpia el filtro con `$wire.set('filters.search', '')`
- El botón × usa Alpine `x-show` para aparecer solo si hay texto

### Ejemplo

```html
<x-data-table.search placeholder="Buscar por nombre o email..." />
<x-data-table.search filterKey="query" placeholder="Buscar factura..." />
```

---

## x-data-table.per-page-selector

Select pill compacto para controlar cuántos registros muestra la tabla.

### Props

| Prop | Tipo | Default | Descripción |
|---|---|---|---|
| `options` | `array` | `[10, 25, 50, 100]` | Opciones del select |

### Comportamiento

- `wire:model.live="perPage"` — sincroniza con `$perPage` del DataTable
- Cuando cambia, `updatedPerPage()` en `DataTable` llama `resetPage()` automáticamente

### Ejemplo

```html
<x-data-table.per-page-selector />
<x-data-table.per-page-selector :options="[5, 10, 20, 50]" />
```

---

## x-data-table.filter-container

Contenedor dropdown para agrupar los filtros del módulo. En mobile se convierte en un drawer desde abajo.

### Props

| Prop | Tipo | Default | Descripción |
|---|---|---|---|
| `activeCount` | `int` | `0` | Número de filtros activos (muestra badge) |

### Comportamiento

- Desktop: dropdown con `@click.away` para cerrar
- Mobile: overlay + panel deslizante desde abajo con botón "Aplicar filtros"
- "Limpiar todo" llama `wire:click="clearAllFilters"`

### Ejemplo

```html
<x-data-table.filter-container
    :activeCount="count(array_filter($filters))">

    <x-data-table.filter-select
        label="Rol"
        filterKey="role"
        :options="$roleOptions"
    />

    <x-data-table.filter-toggle
        label="Solo activos"
        filterKey="only_active"
    />

</x-data-table.filter-container>
```

> [!TIP]
> Para excluir un filtro interno (ej. `trashed`, gestionado por tabs fuera de la toolbar) del conteo de `activeCount`, usa `array_diff_key($filters, ['trashed' => ''])` antes de `array_filter()`.

---

## x-data-table.filter-group

Agrupación colapsable para módulos con alta densidad de filtros.

### Props

| Prop | Tipo | Default | Descripción |
|---|---|---|---|
| `title` | `string` | — | Título del grupo (uppercase) |
| `collapsed` | `bool` | `false` | Si arranca cerrado |
| `activeCount` | `int` | `0` | Filtros activos dentro de este grupo |

### Ejemplo

```html
<x-data-table.filter-group
    title="Ubicación"
    :activeCount="(!empty($filters['province']) ? 1 : 0) + (!empty($filters['municipality']) ? 1 : 0)"
>
    <x-data-table.filter-select label="Provincia" filterKey="province" :options="$provinces" />
    <x-data-table.filter-select label="Municipio" filterKey="municipality" :options="$municipalities" />
</x-data-table.filter-group>
```

---

## x-data-table.filter-select

Select de filtro con label encima. Solo funciona dentro de `filter-container`.

### Props

| Prop | Tipo | Default | Descripción |
|---|---|---|---|
| `label` | `string` | `''` | Label encima del select |
| `filterKey` | `string` | `''` | Clave en `$filters` |
| `options` | `array` | `[]` | Array asociativo `['valor' => 'Label visible']` |
| `placeholder` | `string` | `'Todos'` | Opción vacía por defecto |

### Comportamiento

- `wire:model.live="filters.{filterKey}"`
- Con valor seleccionado: el select cambia a color de marca

### Ejemplo

```html
<x-data-table.filter-select
    label="Estado"
    filterKey="status"
    :options="[
        'active'   => 'Activo',
        'inactive' => 'Inactivo',
    ]"
    placeholder="Todos los estados"
/>
```

---

## x-data-table.filter-toggle

Toggle booleano con sincronización en tiempo real.

### Props

| Prop | Tipo | Default | Descripción |
|---|---|---|---|
| `label` | `string` | `''` | Texto descriptivo del toggle |
| `filterKey` | `string` | `''` | Clave en `$filters` (debe ser booleano) |
| `description` | `string\|null` | `null` | Subtexto bajo el label |

### Comportamiento

- Usa `$wire.entangle().live` — cualquier cambio dispara la actualización de la tabla sin botón "Aplicar".
- Si el filtro se limpia desde un chip o "Limpiar todo", el toggle se apaga automáticamente.

> [!IMPORTANT]
> Inicializa la clave en `$filters` del componente Livewire como `false` (booleano), no como `''` (string), para que la reactividad funcione bien.

### Ejemplo

```html
<x-data-table.filter-toggle
    label="Solo con deuda"
    filterKey="has_debt"
    description="Muestra solo clientes con balance pendiente"
/>
```

---

## x-data-table.filter-date-range

Rango de fechas con dos inputs `date`.

### Props

| Prop | Tipo | Default | Descripción |
|---|---|---|---|
| `label` | `string` | `''` | Título del grupo |
| `fromKey` | `string` | `'date_from'` | Clave en `$filters` para la fecha inicio |
| `toKey` | `string` | `'date_to'` | Clave en `$filters` para la fecha fin |

### Ejemplo

```html
<x-data-table.filter-date-range
    label="Fecha de venta"
    fromKey="sale_from"
    toKey="sale_to"
/>
```

---

## x-data-table.filter-range

Rango numérico con dos inputs (mínimo / máximo).

### Props

| Prop | Tipo | Default | Descripción |
|---|---|---|---|
| `label` | `string` | `''` | Título del grupo |
| `fromKey` | `string` | `'range_min'` | Clave del mínimo en `$filters` |
| `toKey` | `string` | `'range_max'` | Clave del máximo en `$filters` |
| `prefix` | `string\|null` | `null` | Prefijo visual (ej: `'RD$'`) |
| `suffix` | `string\|null` | `null` | Sufijo visual (ej: `'%'`, `'kg'`) |
| `min` | `int` | `0` | Valor mínimo del input |
| `step` | `int` | `1` | Paso del input |

### Ejemplo

```html
<x-data-table.filter-range
    label="Precio"
    fromKey="price_min"
    toKey="price_max"
    prefix="RD$"
/>
```

---

## x-data-table.filter-chips

Chips de filtros activos. Se renderiza automáticamente dentro de `base-table`.

### Props

| Prop | Tipo | Default | Descripción |
|---|---|---|---|
| `chips` | `array` | `[]` | Viene de `getActiveChips()` |
| `hasFilters` | `bool` | `false` | Si hay filtros activos |

### Formato del array `chips`

```php
[
    ['key' => 'role',   'label' => 'Rol',    'value' => 'Cajero'],
    ['key' => 'status', 'label' => 'Estado', 'value' => 'Activo'],
]
```

---

## x-data-table.column-selector

Selector de columnas visibles. Dropdown en desktop, drawer en mobile.

### Props

| Prop | Tipo | Default | Descripción |
|---|---|---|---|
| `columns` | `array` | `[]` | Mapa resuelto `['key' => 'Label']` |
| `visibleColumns` | `array` | `[]` | Columnas actualmente visibles |
| `desktopDefaults` | `array` | `[]` | Columnas por defecto en desktop |
| `mobileDefaults` | `array` | `[]` | Columnas por defecto en mobile |

`base-table` calcula y pasa estos cuatro props automáticamente a partir de `columns()` — no hace falta declararlo a mano en la vista del módulo.

### Por qué los checkboxes son Alpine y no PHP

`@checked($isVisible)` es PHP estático — se evalúa una sola vez en el render inicial. Cuando Livewire hace morfing parcial del DOM tras `resetColumns()`, PHP no re-corre el template. Alpine con `:checked` lee `$wire.visibleColumns` en runtime y siempre queda en sync.

---

## x-data-table.cell

Celda de tabla (`<td>`) que se auto-oculta si la columna no está en las columnas visibles.

### Props

| Prop | Tipo | Default | Descripción |
|---|---|---|---|
| `column` | `string` | — | Clave de la columna |
| `visible` | `array` | — | `$visibleColumns` del componente |
| `class` | `string` | `'px-4 py-3.5'` | Clases de padding del `<td>` |

### Ejemplo

```html
<x-data-table.cell column="name" :visible="$visibleColumns">
    {{ $product->name }}
</x-data-table.cell>
```

---

## x-data-table.row-checkbox

Checkbox de fila para selección masiva (REQ-0.5). No es automático — cada módulo lo declara como primera celda de su `<tr>`, igual que `cell`.

### Props

| Prop | Tipo | Default | Descripción |
|---|---|---|---|
| `id` | `int\|string` | — | Id de la fila, se agrega/quita de `$wire.selected` |

### Ejemplo

```html
<tr>
    <x-data-table.row-checkbox :id="$product->id" />
    <x-data-table.cell column="name" :visible="$visibleColumns">...</x-data-table.cell>
</tr>
```

---

## x-data-table.bulk-actions-bar

Barra flotante de acciones sobre la selección masiva (REQ-0.5). La renderiza automáticamente `base-table` cuando `:selectable="true"` — no se usa suelta en un módulo.

### Props

| Prop | Tipo | Default | Descripción |
|---|---|---|---|
| `count` | `int` | `0` | `count($selected)` |
| `actions` | `array` | `[]` | Viene de `bulkActions()` del hijo |

### Formato del array `actions`

```php
[
    ['key' => 'activate', 'label' => 'Activar', 'icon' => 'heroicon-o-check', 'variant' => 'default'],
    ['key' => 'delete',   'label' => 'Eliminar', 'icon' => 'heroicon-o-trash', 'variant' => 'error',
        'confirm' => true, 'confirmMessage' => '¿Eliminar los registros seleccionados?'],
]
```

Cada botón llama `wire:click="runBulkAction('{{ $action['key'] }}')"`. Si `confirm: true`, se agrega `wire:confirm` con `confirmMessage` antes de ejecutar. `variant: 'error'` tiñe el botón de rojo dentro de la barra (que en sí es de fondo oscuro, `zertix-secondary-dark`).

---

## x-ui.action-menu / x-ui.action-menu.item

Menú "···" de acciones — genérico, no exclusivo de la tabla (REQ-0.6). Documentado en detalle en [`docs/ui/datatables.md`](./datatables.md#menú-de-acciones--x-uiaction-menu-req-06).

---

## Responsividad de los Dropdowns

Todos los componentes dropdown (`filter-container`, `column-selector`, `action-menu`) usan el mismo patrón de detección de dispositivo en Alpine:

```js
isMobile: window.innerWidth < 768,
init() {
    window.addEventListener('resize', () => {
        this.isMobile = window.innerWidth < 768;
    });
}
```

En mobile (`< 768px`) el dropdown se reemplaza por un **drawer/bottom sheet** desde abajo, con overlay y botón de cierre. En desktop es un dropdown estándar con `@click.away`. Este comportamiento es automático — no requiere configuración por módulo.
