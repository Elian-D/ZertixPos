# Sistema de Filtrado de Datos (Pipeline Pattern)

Este proyecto utiliza un sistema de filtrado desacoplado basado en el patrón **Pipeline**. La lógica de filtrado no reside en el Controlador, sino en clases dedicadas que aplican criterios a un `Builder` de Eloquent.


## Tabla de Contenido

  - [Estructura de Archivos](#estructura-de-archivos)
  - [Guía de Implementación (Paso a Paso)](#guía-de-implementación-paso-a-paso)
    - [1. Crear el Filtro Específico](#1-crear-el-filtro-específico)
    - [2. Registrar el filtro en la Clase Orquestadora](#2-registrar-el-filtro-en-la-clase-orquestadora)
    - [3. Uso en el Controlador](#3-uso-en-el-controlador)
  - [Frontend e Interfaz de Usuario](#frontend-e-interfaz-de-usuario)
    - [Componentes Blade](#componentes-blade)
    - [Gestión de Chips de Filtro (JS)](#gestión-de-chips-de-filtro-js)
  - [Beneficios del Sistema](#beneficios-del-sistema)
  - [Notas Adicionales](#notas-adicionales)


---

## Estructura de Archivos

```plaintext
app/
├── Filters/
│   ├── Contracts/
│   │   └── FilterInterface.php      # El contrato para cada filtro individual
│   ├── Base/
│   │   └── QueryFilter.php          # La orquestación de los filtros
│   └── [Module]/
│       ├── [Module]Filters.php      # El registro de filtros del módulo
│       └── [FilterName]Filter.php   # La lógica de un filtro específico
```

---

## Guía de Implementación (Paso a Paso)

### 1. Crear el Filtro Específico

Cada filtro debe implementar `FilterInterface` y definir la lógica de la consulta dentro del método `apply`.

**Ejemplo**: `ClientStatusFilter.php`

```php
namespace App\Filters\Client;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use App\Filters\Contracts\FilterInterface;

class ClientStatusFilter implements FilterInterface 
{
    public function __construct(protected Request $request) {}

    public function apply(Builder $query): Builder 
    {
        $value = $this->request->input('is_active');
        return $value !== null ? $query->where('is_active', $value) : $query;
    }
}
```

### 2. Registrar el filtro en la Clase Orquestadora

En la clase `Filters` de tu módulo, añade el filtro al array `filters()`. La llave del array debe coincidir con el nombre del parámetro en el `Request`.

```php
class ClientFilters extends QueryFilter {
    protected function filters(): array {
        return [
            'search'    => ClientSearchFilter::class,
            'is_active' => ClientStatusFilter::class, // 'is_active' es el name del toggle
        ];
    }
}
```

### 3. Uso en el Controlador

Instancia la clase de filtros y pásale el `query` del modelo.

```php
public function index(Request $request) {
    $results = (new ClientFilters($request))
        ->apply(Client::query()->with(['relaciones']))
        ->paginate($perPage)
        ->withQueryString();

    return view('modulo.index', compact('results'));
}
```
---

## Frontend e Interfaz de Usuario

### 1. Componentes Blade

Para mantener la consistencia visual, utiliza los componentes de `data-table`:

```html
<x-data-table.filter-dropdown>
    <x-data-table.filter-toggle label="Estado" name="is_active"
        :options="['' => 'Todos', '1' => 'Activos', '0' => 'Inactivos']"
        formId="main-filters" />
</x-data-table.filter-dropdown>
```

### 2. Gestión de Chips de Filtro (JS)

Para que el sistema de "Chips" (etiquetas de filtros activos) muestre nombres legibles en lugar de IDs, debemos exponer un diccionario al objeto global `window`. Solo hace falta para filtros basados en catálogo (`<select>` con IDs numéricos) — un `filter-toggle` como el de arriba ya se autoexplica y no necesita entrada en `filterSources`.

En tu vista parcial de filtros:

```js
window.filterSources = {
    // nombre_del_parametro: { id: "Nombre Legible" }
    state: JSON.parse('{!! json_encode($states->pluck("name", "id")) !!}'),
};
```
---

## Beneficios del Sistema

- **Single Responsibility:** El controlador no conoce los detalles de la base de datos.

- **Reusabilidad:** Puedes usar el mismo `SearchFilter` en diferentes partes de la app.

- **Escalabilidad:** Agregar un filtro nuevo es tan simple como crear una clase y registrarla en un array.

- **Limpieza:** Evita los bloques gigantes de `if($request->has(...))` en los controladores.

---

## Notas Adicionales

- Asegúrate de que los nombres de los inputs en el HTML coincidan exactamente con las llaves definidas en la clase `[Module]Filters`.

- Siempre usa `withQueryString()` en la paginación para que los filtros persistan al cambiar de página.