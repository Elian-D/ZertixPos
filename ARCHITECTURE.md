# Arquitectura de Módulos y Guía de Desarrollo (ERP Pattern)

Este proyecto utiliza una arquitectura desacoplada basada en servicios y capas de responsabilidad para garantizar **"Skinny Controllers"** (controladores delgados), código altamente reutilizable y facilidad para realizar pruebas unitarias.

**Vigencia (2026-08-27):** este documento describe el patrón real con el motor de tablas **Livewire** (`App\Livewire\Base\DataTable`, v1.3.0 Fase 0). El motor AJAX viejo (`<x-data-table>` con `window.filterSources`/`AjaxDataTable`, formularios GET) sigue vivo en los módulos todavía no migrados (Inventario, Finanzas, Sistema) — para esos, seguir usando ese patrón hasta que les toque su sub-fase. Para **cualquier tabla nueva**, usar el patrón Livewire de este documento; nunca crear una tabla nueva sobre el motor viejo.

---

## Tabla de Contenido

1. [Arquitectura de Módulos y Guía de Desarrollo (ERP Pattern)](#arquitectura-de-módulos-y-guía-de-desarrollo-erp-pattern)
2. [Estructura de Capas y Responsabilidades](#estructura-de-capas-y-responsabilidades)
   - [1. Capa de Datos (Modelo)](#1-capa-de-datos-modelo)
   - [2. Capa de Tabla (Livewire DataTable)](#2-capa-de-tabla-livewire-datatable)
   - [3. Capa de Filtrado (`filterMap()` + clases solo si hace falta)](#3-capa-de-filtrado-filtermap--clases-solo-si-hace-falta)
   - [4. Capa de Validación y Seguridad (Form Requests)](#4-capa-de-validación-y-seguridad-form-requests)
   - [5. Capa de Servicios (Business Logic)](#5-capa-de-servicios-business-logic)
   - [6. Controlador (Orquestador, ya casi sin `index()`)](#6-controlador-orquestador-ya-casi-sin-index)
   - [7. Rutas](#7-rutas)
   - [8. Papelera como tab, no como vista aparte](#8-papelera-como-tab-no-como-vista-aparte)
   - [9. Feedback de acciones (toasts)](#9-feedback-de-acciones-toasts)
3. [Checklist de Implementación para Nuevos Módulos](#checklist-de-implementación-para-nuevos-módulos)
4. [Ejemplo de Flujo Estándar (Store)](#ejemplo-de-flujo-estándar-store)
5. [Referencia detallada](#referencia-detallada)

---

## Estructura de Capas y Responsabilidades

### 1. Capa de Datos (Modelo)

- **Ubicación:** `app/Models/[Grupo]/[Module].php`
- **Responsabilidad:** Gestionar la persistencia y relaciones base.
- **Tarea:** Definir siempre un `scopeWithIndexRelations($query)` para centralizar el *Eager Loading* que usará tanto la tabla Livewire como las exportaciones a Excel. Cualquier método del modelo que se llame por fila en la vista (`$model->esAlgo()`, `$model->calcularX()`) y dispare una query, debe resolverse desde ahí con `withExists()`/`withCount()`/`withSum()` — nunca en vivo por fila (ver [Checklist de migración](docs/ui/datatable-migration-checklist.md) §4 para el patrón exacto de fallback).
- **Soft deletes:** solo si el modelo es un catálogo real con identidad borrable (Categoría A de `docs/analisis/politica-soft-deletes.md`). Un modelo que es bitácora/ledger (`InventoryMovement`, `PosCashMovement`, `PosSession`) o que ya tiene su propio ciclo de vida por `status` (`Sale`, `Quote`, `JournalEntry`) **no** necesita `SoftDeletes` — "borrar" ahí es una transición de estado (`cancel()`), no una fila oculta.

### 2. Capa de Tabla (Livewire DataTable)

- **Ubicación:** `app/Livewire/App/[Grupo]/[Module]Table.php`, extiende `App\Livewire\Base\DataTable`.
  - El namespace/carpeta espeja el **prefijo real de la ruta**, no la agrupación de Clientes/Ventas del sidebar viejo — ej. `app/sales/pos/terminals/*` → `App\Livewire\App\Sales\PosTerminalTable`, no `App\Livewire\App\Clients\...`.
- **Vista:** `resources/views/livewire/app/[grupo]/[modulo]-table.blade.php`, construida sobre `<x-data-table.base-table>` + `<x-data-table.cell>` por columna.
- **Ya no existe** la clase `app/Tables/[Module]Table.php` del patrón viejo (`allColumns()`/`defaultDesktop()`/`defaultMobile()` estáticos) — eso ahora es el método `columns()` del componente:
  ```php
  protected function columns(): array
  {
      return [
          'name'   => ['label' => 'Nombre', 'default' => true, 'mobile' => true],
          'status' => ['label' => 'Estado', 'default' => true],
          'notes'  => ['label' => 'Notas'], // ni default ni mobile → solo aparece si el usuario la activa
      ];
  }
  ```
  `'default' => true` reemplaza al viejo `defaultDesktop()` curado; `'mobile' => true` reemplaza a `defaultMobile()`. Si ninguna columna declara `'default'`, el motor trata **todas** como visibles por defecto — confirmar que eso es realmente lo que se quiere para un módulo con pocas columnas.
- **La ruta `index` del módulo ya no arma la tabla** — solo renderiza un wrapper Blade con el componente:
  ```blade
  {{-- resources/views/[modulo]/index.blade.php --}}
  <x-app-layout>
      <div class="p-4 md:p-6 flex flex-col gap-6">
          <livewire:app.[grupo].[modulo]-table />
      </div>
  </x-app-layout>
  ```
- **Selección masiva: por decisión explícita, ningún módulo la activa por ahora**, aunque el motor la soporta (`bulkActions()`, `type: 'select'` para acciones con valor) para cuando se retome en bloque. No agregarla módulo por módulo sin confirmar con el usuario primero.

### 3. Capa de Filtrado (`filterMap()` + clases solo si hace falta)

- **Regla:** un filtro que es un `where()` de una línea se define directo como closure en `filterMap()` del componente — **no** se crea una clase para eso.
  ```php
  protected function filterMap(): array
  {
      return [
          'search' => fn (Builder $q, $v) => $q->where('name', 'like', "%{$v}%"),
          'status' => fn (Builder $q, $v) => $q->where('status', $v),
      ];
  }
  ```
- **Solo se queda como clase** (`app/Filters/[Grupo]/[Module]/XxxFilter.php`, implementa `FilterInterface::apply(Builder $query, mixed $value): Builder`) un filtro con joins o lógica condicional real que ensuciaría el `filterMap()`. Si **todos** los filtros de un módulo colapsan a closures, se borra el directorio `app/Filters/[Grupo]/[Module]/` completo — no queda como referencia muerta.
- `App\Filters\Base\QueryFilter` (el orquestador AJAX viejo) sigue existiendo para los módulos todavía no migrados — no se toca al migrar un módulo nuevo, solo se deja de usar para ese módulo.
- El buscador (`<x-data-table.search>`) se renderiza siempre en el toolbar del motor nuevo, aunque el módulo viejo no tuviera ninguno — si no había un campo obvio que buscar, se agrega uno básico (ej. por `name`/`notes`) en vez de dejar la barra de búsqueda visualmente presente pero sin efecto.

### 4. Capa de Validación y Seguridad (Form Requests)

- **Ubicación:** `app/Http/Requests/[Grupo]/[Module]/`
- **Responsabilidad:** Validar datos y verificar permisos de Spatie antes de que el controlador ejecute cualquier lógica. Sin cambios respecto al patrón viejo — `create`/`store`/`edit`/`update`/`destroy` siguen siendo rutas y `FormRequest` reales, solo el **listado** (`index`) se migró a Livewire.
- **Archivos:** `Store[Module]Request.php`, `Update[Module]Request.php`. `Bulk[Module]Request.php` solo si el módulo ya tenía selección masiva activa antes de migrar y se decide mantenerla — si no, se borra junto con el resto del código de bulk viejo.
- **Importante:** cuando una acción se mueve de ruta a método Livewire (`restore()`, `forceDelete()`, `toggleActivo()`), el permiso que antes vivía en el middleware de la ruta borrada **hay que replicarlo a mano dentro del método**:
  ```php
  public function restore(int $id): void
  {
      abort_unless(auth()->user()->can('modulo restore'), 403);
      // ...
  }
  ```

### 5. Capa de Servicios (Business Logic)

El controlador nunca debe hacer `Model::create()` ni gestionar `DB::transaction()`. Sin cambios respecto al patrón viejo:

- **Catalog Service:** `app/Services/[Grupo]/[Module]CatalogService.php`. Suministra datos para los `<select>` de los filtros (`getForFilters()`) y de los formularios (`getForForm()`).
- **Business Service:** `app/Services/[Grupo]/[Module]Service.php`. Ejecuta acciones de escritura, cálculos complejos y procesos masivos (`performBulkAction`, aunque hoy ningún módulo lo invoca desde la tabla — ver §2).

### 6. Controlador (Orquestador, ya casi sin `index()`)

- **Responsabilidad:** solo lo que sigue siendo una ruta real — `create`, `store`, `edit`, `update`, `destroy`, y cualquier acción de negocio propia del módulo (`cancel`, `close`, `print`, etc.). `index()` se reduce a devolver el wrapper Blade (más `$this->authorize(...)` si la ruta vieja lo tenía):
  ```php
  public function index()
  {
      $this->authorize('ver modulo'); // si aplicaba antes
      return view('modulo.index');
  }
  ```
- Lo que antes vivía en `index()` — parámetros de columnas, `Filters->apply()`, paginación, catálogos para los `<select>` — ahora vive dentro del componente Livewire (§2/§3).
- El borrado normal (soft-delete) de un módulo Categoría A **sigue siendo una ruta real** con `<x-ui.confirm-deletion-modal :route="...">` — no se convierte a método Livewire. Solo `restore()`/`forceDelete()` (los que antes vivían en una vista de papelera aparte) se mueven al componente.

### 7. Rutas

- Rutas de `index`/`eliminados`/`restore`/`force-delete`/`bulk`/`export` del patrón viejo se reducen: `eliminados`/`restore`/`force-delete` desaparecen (reemplazadas por el tab Papelera + métodos Livewire, ver §8); `bulk` desaparece si el módulo no tenía selección masiva real que se decidiera mantener; `export`, si existía, se mueve a un método `export()` del propio componente Livewire (`Excel::download(...)` puede devolverse directo desde una acción Livewire) en vez de una ruta GET aparte.
- `create`/`store`/`edit`/`update`/`destroy` quedan intactas tal cual estaban.

### 8. Papelera como tab, no como vista aparte

Para un módulo **Categoría A** (catálogo con soft-delete real — ver `docs/analisis/politica-soft-deletes.md` §6): ya no existe una ruta/vista `eliminados` dedicada. La papelera es un tab **dentro del mismo índice**, controlado por una clave `trashed` en `$filters` que **no** es un filtro real (cambia el scope completo de la query en `baseQuery()`, no agrega un `where`):

```php
public array $filters = ['trashed' => '', /* ... */];

protected function nonChipFilterKeys(): array { return ['trashed']; }

protected function baseQuery(): Builder
{
    $query = $this->filters['trashed'] === 'only'
        ? Modulo::onlyTrashed()
        : Modulo::query();

    return $this->applyFilters($query->withIndexRelations());
}

public function restore(int $id): void { /* abort_unless + restore() + $this->notify(...) */ }
public function forceDelete(int $id): void { /* abort_unless + forceDelete() + $this->notify(...) */ }
```

En la vista, dos `<x-ui.button wire:click="$set('filters.trashed', '...')">` como tabs (Activos/Papelera). El borrado permanente usa `<x-ui.confirm-deletion-modal :wireConfirm="'forceDelete(' . $id . ')'">` (nunca `:route` para esa acción específica). Un módulo **sin** soft-delete real (bitácora, o con su propio `status`) no tiene tab de papelera — su "borrado" es la transición de estado que ya tenía (`cancel()`, etc.), sin tocar.

### 9. Feedback de acciones (toasts)

`session()->flash('success', ...)` **no** produce un toast en una acción Livewire — `x-ui.toasts` lo lee vía Blade en `app-layout.blade.php`, que solo se evalúa en una carga completa de página, nunca en un `wire:click`. Toda acción del componente (`restore()`, `forceDelete()`, `toggleActivo()`, `export()`, etc.) usa el helper heredado de `DataTable`:

```php
$this->notify('success', "Módulo \"{$item->name}\" actualizado correctamente.");
```

`session()->flash()` se sigue usando tal cual en acciones que **sí** redirigen de verdad (`store`, `update`, `destroy` por ruta) — el toast en esos casos funciona porque hay una carga de página real detrás.

Regla adicional: un toggle activar/desactivar siempre vive **dentro de `x-ui.action-menu`** como item, nunca como botón suelto en la fila.

---

## Checklist de Implementación para Nuevos Módulos

> Para una tabla nueva, la referencia operativa completa (con los "porqué" de cada punto, sacados de errores reales) es **`docs/ui/datatable-migration-checklist.md`** — este checklist es el resumen para copiar en la tarea.

### Base de Datos y Seguridad

- [ ] Migración creada y ejecutada — con `SoftDeletes` **solo si** el modelo es Categoría A (catálogo con identidad borrable, no bitácora ni con `status` propio).
- [ ] Seeder de Permisos creado y ejecutado (`[Modulo]PermissionsSeeder`).
- [ ] Modelo con `scopeWithIndexRelations()` completo (incluye cualquier `withExists()`/`withCount()` que evite N+1 por fila).

### Backend y Lógica

- [ ] `App\Livewire\App\[Grupo]\[Modulo]Table` con `columns()`/`filterMap()`/`filterOptions()`/`baseQuery()`/`render()`.
- [ ] Filtros: closures en `filterMap()` salvo joins/lógica real (clase aparte, `FilterInterface` nuevo con `apply(Builder $query, mixed $value)`).
- [ ] CatalogService implementado (`getForFilters()`, `getForForm()`).
- [ ] Service de negocio con `create()`, `update()` (y `performBulkAction()` solo si el módulo va a tener selección masiva, confirmado con el usuario).
- [ ] Si es Categoría A: `restore()`/`forceDelete()` en el componente, con `abort_unless(auth()->user()->can('...'), 403)` replicando el permiso que antes tenía la ruta.

### HTTP y Rutas

- [ ] FormRequests creados (Store, Update) con validación de permisos.
- [ ] Rutas registradas: Index (solo wrapper), Create, Store, Edit, Update, Destroy. Sin Eliminados/Restore/ForceDelete/Bulk dedicadas si se migró a Livewire.
- [ ] Controlador con servicios inyectados en el constructor; `index()` reducido a `authorize()` (si aplica) + `return view(...)`.

### Frontend

- [ ] Vista wrapper `[modulo]/index.blade.php` con `<livewire:app.[grupo].[modulo]-table />`.
- [ ] Vista del componente sobre `<x-data-table.base-table>` + `<x-data-table.cell>`, tab Papelera si aplica (§8), `<x-ui.action-menu>` para acciones de fila (nunca botones sueltos para editar/eliminar/toggle).
- [ ] Toda acción Livewire con feedback usa `$this->notify(...)`, nunca `session()->flash()`.
- [ ] Formularios de Create/Edit alimentados por el CatalogService — sin cambios respecto al patrón viejo.

---

## Ejemplo de Flujo Estándar (Store)

El flujo de `store` no cambió con la migración — sigue siendo ruta + `FormRequest` + `Service`, nunca Livewire:

```php
/**
 * Almacenar un nuevo registro
 * El StoreModuloRequest se encarga de:
 * 1. Validar permisos (authorize)
 * 2. Validar datos (rules)
 */
public function store(StoreModuloRequest $request, ModuloService $service)
{
    // El Service centraliza la creación y lógica de base de datos
    $model = $service->create($request->validated());

    return redirect()
        ->route('modulo.index')
        ->with('success', "Registro {$model->name} creado correctamente.");
}
```

Lo que cambió es el **listado** — ya no hay un método `index()` que arme filtros/paginación/columnas; eso vive en el componente Livewire (§2/§6).

---

## Referencia detallada

- `docs/ui/datatable-migration-checklist.md` — checklist operativo completo, con el porqué de cada punto (permisos Docker/Sail, N+1, papelera, toasts, etc.).
- `docs/ui/datatables.md` / `docs/ui/datatable-components.md` — documentación de los componentes Blade del motor (`base-table`, `filter-select`, `action-menu`, etc.).
- `docs/analisis/politica-soft-deletes.md` — cómo decidir si un modelo nuevo necesita `SoftDeletes`/papelera o no.
- `docs/features/v1.3.0.md` (Fase 0, REQ-0) — historia y alcance completo de la migración del motor de tablas.
