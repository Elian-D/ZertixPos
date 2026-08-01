# Política de Borrado — SoftDeletes, Papelera y Estados de Ciclo de Vida

**Fecha:** 2026-07-30
**Motivo:** al usar `SoftDeletesTrait` (`app/Traits/SoftDeletesTrait.php`) casi por reflejo en cada módulo nuevo, el sistema terminó con tres patrones de "borrado" mezclados sin criterio: papelera real y funcional, papelera con UI pero sin forma de llegar a ella, y trait completo sin ninguna ruta que lo use. Este documento fija el criterio para decidir, entidad por entidad, cuál de los patrones aplica — y documenta lo que ya existe en el código hoy, con evidencia concreta, para no repetir el problema al refactorizar.

No es una crítica al código original — el patrón se copió antes de tener el criterio claro. Es la base para decidir con criterio de ahora en adelante.

---

## 1. El error de fondo: "borrar" no es una sola operación

El sistema trata "eliminar" como si fuera siempre lo mismo: mover a `deleted_at`, mostrar en una papelera, restaurar o purgar. Pero en la práctica hay **tres operaciones distintas** que se confunden bajo ese único verbo:

1. **Borrar un registro de catálogo/maestro** (un producto, un cliente, una categoría) — el usuario se equivocó o ya no lo usa. Aquí sí aplica "mover a la papelera y poder restaurar".
2. **Invalidar un documento transaccional/fiscal** (una venta, un pago, un asiento contable, una factura) — esto **no es un borrado**, es un cambio de estado (`cancelado`/`anulado`/`voided`) que además dispara reversiones reales (stock, contabilidad, NCF). Esta operación ya existe en el código como `cancel()`/`anular()` en varios controladores — es la correcta.
3. **Un registro de bitácora/ledger** (un movimiento de inventario, un movimiento de caja, una sesión de caja, una línea de un asiento) — esto **nunca debería ser borrable**, ni siquiera con soft-delete. Si algo estuvo mal, se corrige con un movimiento compensatorio (como ya hace `SaleService::cancel()` con `InventoryMovement::TYPE_ADJUSTMENT`), no eliminando la fila que dejó la huella.

`SoftDeletesTrait` solo modela bien el caso 1. Cuando se le aplicó a los casos 2 y 3 "porque el molde ya estaba hecho", empezaron los problemas que estás sintiendo ahora.

---

## 2. El framework: 3 preguntas para decidir la política de cualquier entidad

Antes de agregar `SoftDeletes` a un modelo nuevo, respondé esto:

**P1 — ¿Es un catálogo/maestro que se referencia desde transacciones históricas, o es la transacción/documento en sí?**
Si es catálogo (Producto, Cliente, Categoría) → soft-delete tiene sentido: no se puede hard-delete porque ventas viejas referencian su `id`, y el usuario sí quiere poder "deshacer" un borrado por error.
Si es el documento/transacción (Venta, Pago, Asiento) → **no es soft-delete lo que necesita, es un estado de ciclo de vida** (`cancelado`).

**P2 — ¿Existe ya (o debería existir) un estado de ciclo de vida propio que invalide el registro con reversión real?**
Si sí (Sale: `completed`/`canceled`; JournalEntry: `draft`/`posted`/`cancelled`; Payment: `active`/`cancelled`; Receivable: `unpaid`/`partial`/`paid`/`cancelled`; Quote: `draft`/`approved`/`converted`/`expired`/`cancelled`) → ese estado es la operación real de "borrar". El soft-delete, si existe, es un paso **posterior y opcional** de archivado histórico, nunca un atajo que se salte la reversión.

**P3 — ¿Es un registro que existe solo para dejar rastro de que algo pasó (bitácora/ledger), sin identidad propia editable?**
Si sí (InventoryMovement, PosCashMovement, PosSession, JournalItem, SaleItem, QuoteItem, SalePayment) → **no debería tener ni soft-delete ni papelera**. Si algo salió mal, se corrige con un registro compensatorio nuevo, nunca borrando ni ocultando el original — eso es lo que garantiza que la suma siga siendo auditable.

De estas tres preguntas salen las **4 categorías** siguientes.

---

## 3. Las 4 categorías

### Categoría A — Catálogo/maestro con papelera real (SoftDeletes + `SoftDeletesTrait` completo)
Aplica cuando P1 = catálogo, y no hay estado de ciclo de vida propio que ya lo cubra. El usuario legítimamente puede querer recuperar el registro.

### Categoría B — Documento con ciclo de vida propio (estado de cancelación, NO trait genérico)
Aplica cuando P2 = sí. La operación de "borrar" es `cancel()`/`anular()`, con su propia lógica de reversión (contabilidad, inventario, NCF). El soft-delete, si se mantiene, es solo un archivado posterior — y **debe exigir que el estado ya esté cancelado antes de permitirlo** (ver `ReceivableController::destroy()` más abajo, que sí lo hace bien).

### Categoría C — Bitácora/ledger (nunca soft-delete, nunca papelera)
Aplica cuando P3 = sí. Ni el modelo debería tener `SoftDeletes`, ni debería existir ninguna ruta de borrado. La única forma de "corregir" es un registro compensatorio nuevo.

### Categoría D — Catálogo simple sin impacto histórico (hard-delete directo, sin trait)
Catálogos de configuración que casi no cambian y no se referencian desde el historial transaccional de forma que importe conservar el registro borrado (países, estados geográficos, tipos de identificación fiscal). Aquí ni siquiera hace falta soft-delete — un `delete()` simple con validación de relaciones (`$relationCheck` del propio trait, o una a medida) alcanza.

---

## 4. Auditoría completa — cómo está HOY cada modelo, y en qué categoría debería estar

### 4.1 Categoría A — correctos tal cual (catálogo con papelera funcional)

| Modelo | Controlador | Estado |
|---|---|---|
| `Product` | `ProductController` | ✅ Papelera completa y usada (destroy + eliminadas + restaurar) |
| `Category` | `CategoryController` | ✅ Igual |
| `Unit` | `UnitController` | ✅ Igual |
| `Client` | `ClientController` | ✅ Igual |
| `Warehouse` | `WarehouseController` | ✅ Igual |
| `PosTerminal` | `PosTerminalController` | ✅ Igual, además con guard propio: no se puede eliminar con sesión de caja abierta ([PosTerminalController.php:126](app/Http/Controllers/Sales/Pos/PosTerminalController.php:126)) — este es el patrón correcto de guard antes de soft-delete, igual que Categoría B |
| `Equipment`, `EquipmentType`, `PointOfSale`, `BusinessType` | Sus controladores en `Clients/` | ✅ Papelera completa (aunque estos 4 modelos son candidatos a salir del núcleo de Clientes hacia el módulo satélite "Activos en Campo" — ver `modulos-base-satelite.md`, no cambia su categoría de borrado) |
| `EstadosCliente`, `TipoPago` | `EstadosClienteController`, `TipoPagoController` | ✅ Papelera completa |
| `AccountingAccount`, `DocumentType` | Sus controladores en `Accounting/` | ✅ Papelera completa — correcto porque son catálogo (plan de cuentas, tipos de documento), no transacciones |

Estos 14 modelos están bien como están — no tocar.

### 4.2 Categoría B — documentos con ciclo de vida propio, implementados de forma **inconsistente**

Este es el núcleo del problema que estás sintiendo. Los cuatro casos siguientes son conceptualmente idénticos (documento financiero con estado de cancelación) pero cada uno quedó implementado distinto:

| Modelo | ¿Tiene `cancel()`/`anular()` real? | ¿Ruta `destroy` registrada? | ¿Ruta `eliminadas`/`restaurar` registrada? | Veredicto |
|---|---|---|---|---|
| **`Receivable`** | No tiene `cancel()` propio — el guard vive en `destroy()` mismo | ✅ [receivables.php:22](routes/admin/accounting/receivables.php:22) | ✅ [receivables.php:17-27](routes/admin/accounting/receivables.php:17) | ✅ **El único de los cuatro totalmente correcto.** `ReceivableController::destroy()` ([ReceivableController.php:63-73](app/Http/Controllers/Accounting/ReceivableController.php:63)) exige `status === STATUS_CANCELLED` antes de permitir el soft-delete — la papelera es un paso *posterior* a la anulación, nunca un atajo. |
| **`Payment`** | ✅ `cancel()` ([PaymentController.php:116](app/Http/Controllers/Accounting/PaymentController.php:116)), con el mismo guard correcto en `destroy()` (línea 130: rechaza si `status === STATUS_ACTIVE`) | ❌ **No registrada** en `payments.php` | Solo `eliminados` (ver, línea 12) — **sin `destroy` no hay forma de que algo llegue ahí, y tampoco hay `restaurar`** | ⚠️ **Papelera fantasma.** El código del controlador está bien escrito, pero la ruta que la alimenta nunca se registró. Es un menú/pantalla que siempre estará vacío. |
| **`JournalEntry`** | ✅ `cancel()` ([JournalEntryController.php:150](app/Http/Controllers/Accounting/JournalEntryController.php:150)), con el mismo guard correcto en `destroy()` (línea 167: rechaza si `status === STATUS_POSTED`) | ❌ **No registrada** en `journalEntries.php` | ❌ **Tampoco registradas** (ni `eliminadas` ni `restaurar`) | ⚠️ **Todo el `SoftDeletesTrait` de este controlador es código muerto** — bien escrito, cero forma de alcanzarlo por HTTP. |
| **`Sale`** | ✅ `cancel()` en `SaleService::cancel()` (reversión completa: stock, NCF, asiento contable) | ❌ **No registrada** en `sales.php` (solo existe la ruta `cancel`) | ❌ **Tampoco registradas** | ⚠️ `SaleController` sigue implementando `use SoftDeletesTrait` y sus métodos abstractos (`getModelClass`, `getViewFolder`, etc.) sin que ninguna ruta los use — boilerplate muerto, pero al menos no hay riesgo de usarlo mal porque es inalcanzable. |

**Por qué esto es peligroso, no solo desprolijo:** en los tres casos con guard (`Receivable`, `Payment`, `JournalEntry`) el código en sí está bien — nadie puede soft-eliminar un `Payment` activo o un `JournalEntry` asentado sin pasar primero por `cancel()`. El riesgo real es el opuesto: como `Payment` y `JournalEntry` cargan con todo el aparato del trait (vistas `eliminadas.blade.php`, rutas parcialmente registradas, permisos) sin estar completos, es fácil que alguien "termine de cablear" la ruta que falta sin darse cuenta de que el guard ya existe y sin revisar si aplica igual — o peor, que copie el patrón de `Payment`/`JournalEntry` (medio cableado) para un módulo nuevo en vez del de `Receivable` (completo y correcto).

### 4.3 Categoría C — bitácora/ledger: `SoftDeletes` presente pero **nunca usado**, y así debe quedarse (sin destroy)

Confirmado con grep en todo `app/Services` y `app/Http/Controllers`: ningún lugar del código llama `->delete()` sobre estos modelos.

| Modelo | Tiene `SoftDeletes` | Tiene ruta de borrado | Veredicto |
|---|---|---|---|
| `InventoryMovement` | Sí | No | Correcto que no haya ruta — un movimiento de inventario es un asiento de Kardex, nunca se borra, se corrige con `TYPE_ADJUSTMENT` (como ya hace `SaleService::cancel()`). **El `SoftDeletes` del modelo es vestigial** — se puede quitar sin que nada cambie funcionalmente. |
| `PosCashMovement` | Sí | No | Igual razonamiento — es la bitácora de entradas/salidas de caja. `SoftDeletes` vestigial. |
| `PosSession` | Sí | No | Igual — es el registro de auditoría de un turno de caja (quién, cuándo, con cuánto). Nunca debería poder borrarse, ni con soft-delete. `SoftDeletes` vestigial. |
| `Quote` | Sí | No | Caso distinto pero mismo resultado: `Quote` ya tiene su propio ciclo de vida (`draft`/`approved`/`converted`/`expired`/`cancelled`) — la forma de "invalidar" una cotización es cambiar su estado, no borrarla. `SoftDeletes` vestigial, nunca debió agregarse aquí; si se quiere "papelera" de cotizaciones, el filtro correcto es por `status = cancelled`, no por `deleted_at`. |
| `JournalItem`, `SaleItem`, `QuoteItem`, `SalePayment` | No (correctamente) | No | Estos ya están bien — son líneas hijas de un documento padre, no tienen identidad propia para borrarse por separado. Nada que cambiar. |

**Acción sugerida (baja prioridad, cosmética):** quitar `SoftDeletes` de `InventoryMovement`, `PosCashMovement`, `PosSession` y `Quote` — no cambia el comportamiento actual (nunca se usa), pero deja de sugerir, al leer el modelo, que "borrar" es una operación válida ahí.

### 4.4 Categoría D — catálogo simple, correctamente sin `SoftDeletes`

`Country`, `State`, `DiaSemana`, `TaxIdentifierType`, `ClientStateCategory`, `Impuesto`, `NcfType`, `InventoryStock`, `User`, `ConfiguracionGeneral`, `PosSetting` — ninguno tiene `SoftDeletes`, y para todos aplica el razonamiento de la Categoría D (configuración/catálogo de bajo movimiento, o singleton que nunca se borra como `ConfiguracionGeneral`/`PosSetting`).

**Única excepción a revisar:** `NcfType` (B01, B02, etc.) no tiene `SoftDeletes` pero sí se referencia desde `ncf_sequences` y desde ventas históricas (`sale.ncf_type_id` vía `NcfLog`). Si algún día se permite borrar un `NcfType` ya usado, un hard-delete rompería esa referencia histórica. Mientras no exista una ruta de borrado para `NcfType` esto es teórico — pero si se llega a construir un CRUD completo para tipos de NCF, debería nacer en Categoría A (con `SoftDeletes` + papelera), no en D.

### 4.5 Caso aparte, ya bien resuelto: `NcfSequence`

`NcfSequence` tiene `SoftDeletes`, tiene ruta `destroy` real (vía `NcfSequenceService::delete()`), pero **no tiene papelera/restaurar expuesta al usuario**. A diferencia de `Payment`/`JournalEntry`, esto no es un error — es la categoría correcta para un dato fiscal sensible: se permite invalidar (soft-delete, nunca hard-delete, para no perder el rastro DGII), pero restaurar una secuencia fiscal ya abandonada no es un botón de UI, es una decisión que amerita intervención directa de soporte/admin si alguna vez hace falta. Es un buen ejemplo de "Categoría B sin papelera visible" para replicar en casos similares (nada que cambiar aquí).

---

## 5. El problema del nombre hardcodeado (`eliminadas`)

El trait fuerza la convención de vista `{carpeta}.eliminadas` ([SoftDeletesTrait.php:45](app/Traits/SoftDeletesTrait.php:45)), pero cada controlador define su propio `getRouteEliminadas()` con nombres que no siempre coinciden en género/número: `receivables.eliminados`, `accounting.payments.eliminados`, mientras las vistas Blade se llaman `eliminadas.blade.php` en casi todos los módulos. Funciona porque cada controlador resuelve su propio nombre de ruta, pero es una fuente de errores de copy-paste (renombrar mal una ruta al clonar un controlador nuevo) y no ayuda a que el patrón sea autoexplicativo. Al estandarizar (sección 6), conviene fijar una sola convención de nombre en español neutro — por ejemplo `.papelera` en vez de `.eliminadas`/`.eliminados` — para no depender de la concordancia de género de cada entidad.

---

## 6. Alternativa a la vista de papelera dedicada: filtro "Papelera" en el mismo índice

Para toda la **Categoría A** (catálogo con papelera real), en vez de mantener el patrón actual — controlador con método `eliminadas()`, ruta propia, vista `eliminadas.blade.php` y un partial de tabla duplicado (`eliminados-table.blade.php`, casi idéntico al `table.blade.php` normal en cada módulo) — conviene resolverlo con un **filtro más en el mismo índice**, tipo tab/chip "Papelera" que reutiliza la misma tabla, columnas, orden y paginación ya existentes.

**Qué elimina esto:**
- El método `eliminadas()` del trait y su ruta dedicada.
- La vista `eliminadas.blade.php` de cada módulo.
- El partial de tabla duplicado (`eliminados-table.blade.php`) — la causa de la misma clase de duplicación que ya se encontró en `pos-workspace.blade.php` (Fase 7.10 de `POS-Interfaz.md`), aquí repetida módulo por módulo en vez de desktop/móvil.

**Qué cambia respecto a los filtros actuales del pipeline:**
El filtro "Papelera" no es un `where` más como `ProductsSearchFilter`/`ProductsCategoryFilter` — tiene que **reemplazar el scope global de Eloquent** (`withTrashed()` o `onlyTrashed()`) en vez de agregar una condición sobre el scope por defecto (que ya excluye trashed automáticamente). Es un tipo de filtro distinto al resto del pipeline, pero encaja en el mismo patrón `QueryFilter` sin problema — solo su `apply()` hace algo distinto (cambiar el scope, no añadir un `where`).

**Qué cambia en la vista:** las acciones de fila (Restaurar / Borrar definitivo vs. Editar / Eliminar) se condicionan según si la fila viene trashed o no — trivial con la misma tabla, sin partial aparte.

**Encaja directo con el roadmap ya existente:** esto es una aplicación puntual del ítem "Refactorización de Tablas: Datatable unificado" de `docs/promts.md` — no es un plan nuevo y paralelo, es el mismo Datatable unificado resolviendo también el caso de la papelera de una vez, en lugar de dejarlo para después.

## 7. El estándar a seguir de ahora en adelante

Para cualquier módulo nuevo (y al refactorizar los existentes), antes de escribir `use SoftDeletes` en un modelo:

1. **Aplicar las 3 preguntas de la sección 2.** Si el resultado es Categoría B o C, **no uses `SoftDeletesTrait` en el controlador** — no hace falta la papelera.
2. **Si es Categoría A:** usa `SoftDeletesTrait` completo, con las 4 rutas (`destroy`, `eliminadas`/`restaurar`/`force-delete`) siempre registradas juntas — nunca a medias como pasó con `Payment` y `JournalEntry`. Si en algún punto decidís no dar `restaurar`, que sea una decisión explícita (como en `NcfSequence`), no un olvido.
3. **Si es Categoría B:** el soft-delete (si se mantiene) va **siempre detrás de un guard que exija el estado cancelado**, replicando exactamente `ReceivableController::destroy()` — es el ejemplo de referencia. La operación principal que expone la UI es `cancel()`/`anular()`, no "eliminar".
4. **Si es Categoría C:** no agregues `SoftDeletes` al modelo, no crees `destroy()`, no hay ruta de borrado. Punto.
5. **Renombrar la convención de rutas/vistas** de `eliminadas`/`eliminados` a algo neutro (`papelera`) al tocar cada módulo — no hace falta un refactor masivo de una sola vez, se corrige módulo por módulo cuando se refactorice cada uno.

---

## 8. Roadmap de limpieza sugerido

Orden de menor a mayor riesgo, para ir resolviendo sin bloquear el trabajo urgente de POS:

1. **Cablear o eliminar la papelera fantasma de `Payment`** — decidir si de verdad se quiere permitir archivar pagos anulados (entonces registrar las rutas `destroy`/`eliminados`/`restaurar` que faltan, siguiendo el patrón de `Receivable`) o si directamente se quita el `SoftDeletesTrait`/vista de ese controlador por no aportar nada hoy.
2. **Igual decisión para `JournalEntry`** — ahí ni siquiera hay vista de trash alcanzable, es 100% código muerto.
3. **Quitar el `use SoftDeletesTrait` y los métodos abstractos muertos de `SaleController`** — no se usa, y mantenerlo sugiere (falsamente) que hay un flujo de borrado de ventas fuera de `cancel()`.
4. **Quitar `SoftDeletes` de `InventoryMovement`, `PosCashMovement`, `PosSession` y `Quote`** — cosmético, cero riesgo, pero deja el modelo honesto sobre lo que realmente se puede hacer con él.
5. **Migrar la Categoría A al filtro "Papelera" en el mismo índice (sección 6)** cuando se aborde el Datatable unificado de `promts.md` — ahí es donde se elimina de raíz la vista/ruta/partial dedicados en todos los módulos de una vez, en vez de mantenerlos módulo por módulo.
6. **Estandarizar el nombre de rutas/vistas** (`papelera` en vez de `eliminadas`/`eliminados`) — se resuelve solo al hacer el paso 5, no hace falta como refactor aislado si el filtro unificado ya lo reemplaza.
7. **Documentar `NcfSequence` y `Receivable` como los dos ejemplos de referencia** en el propio `SoftDeletesTrait` (comentario de cabecera) para que el próximo módulo que se escriba copie el patrón correcto por defecto, no el de `Payment`/`JournalEntry`.
