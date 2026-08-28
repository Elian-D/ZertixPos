# Checklist de migración de tabla al motor Livewire

Checklist interno a seguir **cada vez** que se migra un módulo del sistema AJAX viejo (`x-data-table` con formularios GET) al motor nuevo (`App\Livewire\Base\DataTable`, Fase 0 de `docs/features/v1.3.0.md`). Nace de errores reales cometidos migrando Clientes (REQ-0.7) — cada punto tiene su porqué, no es burocracia.

---

## 0. Entorno (Sail/Docker)

- [ ] **Nunca** correr `php artisan` dentro del contenedor con `docker exec` a secas — entra como `root` y deja archivos compilados (`storage/framework/views/*.php`) con ese dueño. El proceso que sirve la app corre como `sail`, así que la próxima vez que Laravel necesite recompilar una vista, falla con `Permission denied`.
  - Correcto: `docker exec --user=sail <container> php artisan ...`
  - Si ya se rompió: `docker exec <container> chown -R sail:sail storage bootstrap/cache`

---

## 1. Antes de escribir código

- [ ] Leer el controlador AJAX viejo completo (`index()`, `bulk()`, `export()`) — no asumir el patrón, cada módulo tiene mínimas variaciones.
- [ ] Leer el modelo: `scopeWithIndexRelations()` actual y **cualquier método que se llame por fila en la vista** (`$model->algoQueHagaQuery()`). Ver punto 4.
- [ ] Leer la Table class vieja (`app/Tables/XxxTable.php` si existe): `allColumns()` vs `defaultDesktop()` — casi siempre `defaultDesktop()` es un **subconjunto curado**, no todas las columnas. Ver punto 3.
- [ ] Leer el `XxxFilters.php` orquestador y cada filtro hoja que referencia — decidir cuáles colapsan a closures (REQ-0.4) y cuáles se quedan como clase (joins/lógica real). Si es el caso como clientes que todos son closures se puede borrar el directorio.
- [ ] Confirmar con el usuario el alcance real si el documento de la fase es ambiguo (ej. "grupo del sidebar" vs "3 módulos literales") — no asumir el alcance más grande ni el más chico sin preguntar. **Pasó dos veces (CRM/Ventas y de nuevo en Inventario, REQ-0.7/0.8): el texto del REQ listaba menos módulos de los que el sidebar real tiene.** Antes de dar una sub-fase por cerrada, releer `resources/views/layouts/sidebar.blade.php` para el grupo completo (`<x-sidebar.subitem>` dentro de ese `<x-sidebar.group>`), no solo el texto de `docs/features/v1.3.0.md` — ese documento puede quedar desactualizado, el sidebar no.

## 2. Selección masiva — todo o nada

Por ahora ninguno tendrá selecion masivas.

- [ ] Si el módulo **no** va a tener selección masiva: **no** pasar `:selectable`/`:bulkActions` a `x-data-table.base-table` **y tampoco** dejar `<x-data-table.row-checkbox>` en el `@forelse` de filas — ese componente lo pone la vista a mano, no está condicionado por `$selectable` de `base-table`. Dejar uno sin el otro deja checkboxes de fila sin cabecera y sin barra de acciones.
- [ ] Si el módulo **sí** la tiene: agregar ambos (`:selectable="true"`, `:bulkActions="$this->bulkActions()"`) + `<x-data-table.row-checkbox>` en cada fila + implementar `performBulkAction()`/`currentPageIds()` en el componente.
- [ ] El `colspan` del `@empty` cambia según esto: `count($visibleColumns) + 1` sin checkbox de fila, `+ 2` con él (checkbox + acciones).

## 3. Columnas — `default` vs `mobile`

- [ ] Si la Table class vieja curaba un `defaultDesktop()` más chico que `allColumns()`, cada columna que debe verse por defecto en desktop necesita `'default' => true` en `columns()`. Si ninguna columna declara `'default'`, el motor asume que **todas** son default-visible — confirmar que eso es lo que se quiere antes de omitirlo.
- [ ] `'mobile' => true` solo en las columnas que deben sobrevivir en la vista mobile curada (normalmente un subconjunto aún más chico que `default`).

## 4. N+1 — todo método por fila que toque la BD

- [ ] Grep en la vista vieja por `$model->algo()` dentro del `@forelse` — cualquier método que dispare `->exists()`, `->count()`, una relación no eager-loaded, etc. es una query por fila.
- [ ] Para cada uno: mover el cálculo a `scopeWithIndexRelations()` con `withExists()`/`withCount()`/`withSum()` (subquery correlacionada, una sola query para toda la página), y hacer que el método del modelo **prefiera el atributo ya cargado** antes de disparar la query en vivo:
  ```php
  public function esAlgo(): bool
  {
      if (array_key_exists('es_algo', $this->attributes)) {
          return (bool) $this->attributes['es_algo'];
      }
      // fallback: query en vivo para cuando no se usó withIndexRelations()
      return $this->relacion()->where(...)->exists();
  }
  ```
- [ ] Verificar con Debugbar (pestaña Queries) que el conteo de queries **no escala** con el número de filas visibles antes de dar la migración por terminada — no basta con que la página cargue, tiene que no tener duplicados.

## 5. Componentes UI — qué NO asumir

- [ ] **Nunca** un `<button>`/`<div>` a mano con clases hardcodeadas cuando existe un componente del sistema para eso — tabs, botones, badges, etc. siempre van con `x-ui.button` (variant/appearance por estado, ver `docs/ui/buttons.md`) u otro componente de `docs/ui/*`, no reinventados con Tailwind suelto.
- [ ] **Nunca** un `wire:confirm` (alert nativo del navegador) para una confirmación destructiva — el sistema ya tiene `x-ui.confirm-deletion-modal` para eso. Para acciones Livewire (sin ruta HTTP dedicada) usa su prop `:wireConfirm="'metodo(' . $id . ')'"` en vez de `:route` — el botón de confirmar dispara `wire:click` en lugar de un `<form method="POST">`. `wire:confirm` solo se justifica para acciones masivas genéricas sin copy específico (ver `bulk-actions-bar.blade.php`), nunca para "eliminar X permanentemente".
- [ ] `x-ui.button` **no tiene lógica propia para abrir modales** — el `$dispatch('open-modal', 'nombre-{{ $id }}')` se pasa siempre como atributo Alpine externo (`@click="..."`), igual en cualquier botón del sistema. Si el elemento no está ya dentro de un `x-data` ancestro, agregar `x-data` vacío al mismo elemento.
- [ ] Acciones de fila: la acción más prominente (ej. "Ver detalles") puede ir como botón ícono suelto (`x-ui.button` modo ícono); acciones secundarias (Editar/Eliminar) van dentro de `x-ui.action-menu` + `x-ui.action-menu.item` (REQ-0.6), no como botones sueltos adicionales.
- [ ] **Activar/Desactivar (toggle de estado)** — aparece seguido (BusinessType, EquipmentType, Warehouse, Category, Unit, etc. todos tienen `is_active`/`activo` con un toggle). Va **siempre** dentro de `x-ui.action-menu` como un ítem más (no como botón suelto al lado de Ver/Editar) — `wire:click="toggleActivo({{ $id }})"` directo en el `x-ui.action-menu.item`, sin confirmación (no es destructivo). Ícono condicional según el estado actual (`heroicon-o-check-circle` si va a activar, `heroicon-o-x-circle` si va a desactivar).
- [ ] **`session()->flash()` NO muestra toast en una acción Livewire.** `x-ui.toasts` lee `session('success')`/etc. con Blade dentro de `app-layout.blade.php` — ese bloque solo se evalúa en la carga completa de la página (un `wire:click` no navega). Toda acción del componente que deba dar feedback (`restore()`, `forceDelete()`, `toggleActivo()`, `performBulkAction()`, cualquier cosa sin redirect) usa `$this->notify('success'|'error'|'info'|'warning', $mensaje)` — helper ya en `App\Livewire\Base\DataTable`, dispara `$this->dispatch('notify', type:, title:, message:)` que `x-ui.toasts` sí escucha en vivo (`@notify.window`). La tabla ya se refresca sola (Livewire re-renderiza tras el `wire:click`) — el toast es solo el feedback visual encima de eso, no hace falta forzar un reload de página.
  - `session()->flash()` sigue siendo correcto en los métodos del controlador que sí hacen `redirect()` (ej. `store()`/`update()` de un formulario real fuera de Livewire) — el problema es específico de acciones Livewire sin navegación.
- [ ] Los modales por fila (`<x-modal name="algo-{{ $id }}">` en un `@foreach` aparte) siguen funcionando igual dentro de una vista Livewire — no hace falta reescribirlos, solo `@include` la partial vieja de modales si ya existía.

## 6. Papelera — tab del mismo índice, no vista/ruta aparte

Estándar fijado en Clientes (REQ-0.7), aplica a toda entidad **Categoría A** de `docs/analisis/politica-soft-deletes.md` (catálogo con papelera real — la mayoría de los módulos de esta fase). Reemplaza el patrón viejo (`eliminadas()` del `SoftDeletesTrait`, ruta y vista aparte, partial de tabla duplicado) por un tab "Activos"/"Papelera" que reusa la misma tabla, columnas, filtros y paginación.

- [ ] Antes de tocar nada: confirmar en `docs/analisis/politica-soft-deletes.md` §4 que el modelo es Categoría A. Si es B (documento con `cancel()`) o C (bitácora), **no** se le agrega tab de papelera — ver §7 de ese documento.
- [ ] `$filters['trashed'] => ''` en el componente — **no** entra en `filterMap()` (no es un `where`, reemplaza el scope global). Se resuelve en `baseQuery()`: `$filters['trashed'] === 'only' ? Model::onlyTrashed() : Model::query()`.
- [ ] Sobreescribir `nonChipFilterKeys(): array { return ['trashed']; }` — si no, "trashed" aparece como chip removible y cuenta como filtro activo. Usar `$this->activeFilterCount()` (ya excluye `nonChipFilterKeys()`) en vez de `count(array_filter($filters))` a mano en la vista, para `:hasFilters` y `:activeCount` del `filter-container`.
- [ ] Tabs con `x-ui.button` (ver punto 5) alternando `variant`/`appearance` según `$filters['trashed']`, arriba de `base-table` — mismo patrón que `ComponentsTEMP/index.blade.php`.
- [ ] Acciones de fila condicionadas a `$model->trashed()`: trashed → Restaurar (ícono, `wire:click="restore({{ $id }})"`) + Eliminar definitivo (abre `x-ui.confirm-deletion-modal` con `:wireConfirm`, nunca `wire:confirm`); no-trashed → el flujo normal (Ver/Editar/Eliminar).
- [ ] Implementar `restore(int $id)` y `forceDelete(int $id)` en el componente — **cada uno con su propio chequeo de permiso** (`abort_unless(auth()->user()->can('modulo restore'), 403)` / `'modulo delete'`), replicando el `permission:` que tenía la ruta vieja — al mover la acción a un método público de Livewire, el middleware de ruta que la protegía desaparece, así que el gate hay que ponerlo a mano adentro del método. Mismo cuidado para `performBulkAction()` si el módulo tiene bulk (ver ruta `.bulk` vieja).
- [ ] Borrar la ruta `Xxx.eliminados`/`.restore`/`.borrarDefinitivo`, la vista `eliminadas.blade.php` y su partial de tabla (`eliminados-table.blade.php`) — grep primero por si algo más las referencia.
- [ ] El método `getRouteEliminadas()` que pide `SoftDeletesTrait` queda vestigial (el trait lo sigue exigiendo para `destroyTrait()`, que sí se sigue usando en `destroy()`) — dejarlo con un comentario, no hace falta quitar el trait completo.

## 7. Limpieza al cerrar el módulo

- [ ] Borrar del controlador viejo los métodos que el Livewire component reemplaza (`bulk()`, `export()` si ahora es `wire:click="export"` con `Excel::download()` devuelto directo desde el componente — Livewire soporta descargas de archivo desde una acción).
- [ ] Borrar imports que quedaron sin uso en el controlador.
- [ ] Borrar rutas AJAX que ya nadie llama (`Xxx.bulk`, `Xxx.export` si se reemplazaron) — `grep -rn "route('modulo.bulk')"` antes de borrar para confirmar que nada más la usa.
- [ ] Borrar la Table class vieja (`app/Tables/XxxTable.php`) y el `XxxFilters.php` orquestador + filtros hoja colapsados — `grep` primero para confirmar que ningún otro archivo (Export, test) los sigue usando.
- [ ] Borrar los partials AJAX viejos (`partials/table.blade.php`, `partials/filters.blade.php`, `partials/filter-sources.blade.php`) una vez que la vista Livewire los reemplaza — dejar intactos los que se siguen reusando (modales).
- [ ] Borrar el archivo JS del módulo en `resources/js/pages/` (ej. `clients.js`) — es el `AjaxDataTable({tableId, formId, chips: {...}})` del sistema viejo, apunta a IDs de `<table>`/`<form>` que ya no existen. Quitar también su `import './pages/xxx'` en `resources/js/app.js`. Confirmar primero que el archivo es *solo* del index viejo (a veces un módulo comparte JS con su página de importación u otra vista que sigue viva).
- [ ] La papelera **ya no es una página aparte** — ver punto 6. Si el módulo es Categoría A, la vista/ruta de papelera se borra como parte de este mismo cierre, no queda "fuera del alcance".

## 8. Casos reales encontrados en Inventario (REQ-0.8)

- [ ] **`<x-data-table.search>` siempre se renderiza en el toolbar de `base-table`, tenga o no el módulo viejo un buscador real.** Terminales POS, Turnos POS, Almacenes y Categorías/Unidades no tenían campo de búsqueda en el AJAX viejo — dejar `filters.search` fuera del componente deja el buscador visible pero sin efecto (silenciosamente roto). Agregar un `search` mínimo y razonable (por `name` u otro campo obvio) aunque el módulo viejo no lo tuviera, y anotarlo como desviación intencional al reportar el módulo.
- [ ] **`Route::resource()` sin `->only([...])` registra `create`/`edit`/`show` aunque el controlador no tenga esos métodos** (patrón CRUD-por-modal: Categorías, Unidades, Almacenes, Tipos de Negocio/Equipo). Antes de tocar la ruta, confirmar qué métodos existen realmente en el controlador — si `create()`/`edit()`/`show()` no están, recortar a `->only(['index', 'store', 'update', 'destroy'])`.
- [ ] **No asumir que un catálogo de opciones (`CatalogService::getForFilters()`) está sincronizado con el filtro real.** `InventoryStockCatalogService::getForFilters()['statuses']` traía la clave `'low_stock'`, pero `InventoryStockStatusFilter`/el `match()` del filtro siempre esperó `'low'` — la vista AJAX vieja nunca lo notó porque hardcodeaba las opciones del `<select>` a mano en vez de usar ese array. Al migrar, si vas a usar `$options` del catálogo directo en `x-data-table.filter-select`, verificar que sus claves calzan con `filterMap()`; si no calzan y el catálogo se comparte con otra pantalla (ej. un dashboard), no "corregir" el catálogo compartido — hardcodear las opciones correctas en la vista Livewire, igual que hacía el AJAX viejo, y dejar un comentario explicando por qué no se tocó el catálogo.
- [ ] **Un toggle activar/desactivar que en el AJAX viejo era un `<form>`/botón suelto (no dentro de un `action-menu`) igual se convierte** al moverlo a Livewire — la regla del punto 5 (`toggleActivo` dentro de `x-ui.action-menu`) no depende de cómo estaba antes, aplica siempre que el módulo tenga ese patrón.
- [ ] Un módulo Categoría B/C sin rutas de papelera (`InventoryMovement`, `InventoryStock`) no necesita nada del punto 6 — confirmarlo en `docs/analisis/politica-soft-deletes.md` antes de asumir que "todo módulo de catálogo tiene papelera".

## 9. Casos reales encontrados en Finanzas (REQ-0.9)

- [ ] **Un `route()`/enlace copiado tal cual del AJAX viejo puede estar usando el id equivocado sin que se note en desarrollo.** `sales/ncf/logs`: el modal de detalle enlazaba `route('finance.invoices.print', $log->sale_id)` — esa ruta espera el id de `Invoice`, no el de `Sale` (modelos distintos, ids independientes). Como en datos de seeder `Sale::id` e `Invoice::id` suelen coincidir 1:1, el bug es invisible hasta que la numeración diverge en datos reales (ventas sin factura, reimportaciones, etc. — reportado como 404 real en producción). Al migrar un link que pasa un id, verificar contra qué modelo hace el route-model-binding la ruta destino, no asumir que el id "de al lado" sirve — acá el fix real fue navegar la relación (`$log->sale->invoice`, eager-loaded en `baseQuery()` como `'sale.invoice'`) en vez de pasar `sale_id`.
- [ ] **Un `<a>` a mano con clases Tailwind copiadas es candidato a `x-ui.button` con `href`** (ver `docs/ui/buttons.md`, sección Polimorfismo) aunque el enlace ya funcionara — no es exclusivo de migrar la tabla en sí, aplica a cualquier partial que se toque de paso (acá, el modal de detalle de NCF Log).
- [ ] Cuando un controlador retiene un método real (ej. `exportTxt()` con su propio formulario GET, independiente del estado de la tabla) que sigue usando el pipeline `Request`-based viejo (`NcfLogFilters`), **no borrar esas clases de filtro** aunque el resto del módulo ya haya colapsado a closures en el `filterMap()` de Livewire — son dos consumidores distintos del mismo directorio de filtros.
- [ ] **`<x-data-table.cell column="..." :visible="$visibleColumns">contenido</x-data-table.cell>` evalúa `contenido` en PHP siempre, exista o no la columna en `$visibleColumns`.** `cell.blade.php` envuelve `{{ $slot }}` en su propio `@if(in_array($column, $visible))`, pero Blade ya renderizó el contenido del slot (ejecutó cualquier `$item->relacion->campo` ahí adentro) antes de pasárselo al componente — el `@if` interno solo decide si se imprime el `<td>`, no si el PHP corrió. Si una celda condiciona su propio eager load a un flag (ej. `module_enabled('accounting.advanced')`, como `ReceivableTable::baseQuery()`), **ese mismo flag tiene que envolver la celda entera en la vista**, no confiar en `:visible` — de lo contrario, con el flag apagado, la relación ya no está eager-loaded pero la vista la sigue tocando por fila = N+1 real (encontrado en Debugbar: 2 queries duplicadas se volvieron 8 al "optimizar" solo el `baseQuery()` sin tocar la vista). Aplica a cualquier columna condicional, no solo a esta.

## 10. Verificación final

- [ ] `php -l` en cada archivo PHP tocado.
- [ ] `php artisan view:cache` (como `sail`, ver punto 0) compila sin errores — detecta blade roto sin necesidad de abrir el navegador.
- [ ] Probar en el navegador (o dejárselo al usuario): búsqueda, cada filtro, orden de columnas, selector de columnas, paginación, y — si aplica — selección masiva.
- [ ] Revisar Debugbar: conteo de queries razonable, sin duplicados que escalen con filas.

## 11. Tablas que quedan pendientes (fuera de Fase 0, motor AJAX viejo)

**Purga de `resources/js/pages/` + `resources/js/components/ajax-datatable/` (2026-08-27, cierre de REQ-0.10).** Se borró todo el directorio de wiring `AjaxDataTable({tableId, formId, chips})` — el motor Livewire ya cubre todos los módulos que van a migrar. Estas 4 tablas se quedan en el patrón AJAX viejo **a propósito**, sin su JS (su UI de filtros ya estaba rota desde que `x-data-table.*` se reclamó para Livewire, ver `CLAUDE.md`) — no se migran en Fase 0 por decisiones ya tomadas, documentadas cada una en su lugar real:

| Módulo | Vista | Por qué no migra ahora | Documentado en |
|---|---|---|---|
| Cuentas Contables (`AccountingAccount`) | `resources/views/accounting/accounts/` | Vive detrás de `module:accounting.advanced` — decisión de producto de no competir contra software de contabilidad dedicado (Alegra), nadie la pide hoy | `docs/features/v1.3.0.md`, REQ-0.9 |
| Asientos Contables (`JournalEntry`) | `resources/views/accounting/journal_entries/` | Mismo motivo que Cuentas Contables — mismo flag `accounting.advanced` | `docs/features/v1.3.0.md`, REQ-0.9 |
| Tipos NCF (`NcfType`) | `resources/views/sales/ncf/types/` | Va a dejar de ser CRUD (catálogo fijo sembrado por seeder + toggle `is_active`) — migrarla ahora con create/edit sería el mismo trabajo dos veces | `docs/features/v1.3.0.md`, Fase 7 (REQ-7.1-7.3) |
| Movimientos de Caja (`PosCashMovement`) | `resources/views/sales/pos/cash-movements/` | Función deshabilitada en el sidebar desde Fase 9.1 — exige `accounting_account_id` (acoplamiento a Contabilidad) y permite salidas de efectivo genéricas que no reflejan la operación real; se reintroducirá simplificada | `routes/app/sales.php` (rutas comentadas), `docs/features/POS-Interfaz.md` |

Cuando a alguna de estas le toque su turno: seguir este mismo checklist desde el punto 1, no asumir que por no tener JS ya está a medio migrar.
