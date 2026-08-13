# ZertixPOS — Roadmap v1.2.0 → v1.5.0

**Fecha:** 2026-08-06
**Contexto:** Este documento ordena lo que sigue **después** de `v1.1.0.md` (desacople de Contabilidad + arquitectura de módulos base/satélite). Nace porque `docs/promts.md` es la libreta de trabajo del día a día — cambia constantemente y no es el lugar para fijar un orden de versiones. Este archivo sí lo es: una vez escrito, no debería reordenarse salvo que cambie una dependencia real, no una prioridad de humor.

> **Principio del orden:** no es una lista de prioridades de negocio, es un mapa de dependencias reales. Cada versión existe donde está porque algo de una versión anterior la bloquea técnicamente — no porque "es lo más importante". Donde dos cosas no se bloquean entre sí, se agrupan por área de código tocada, para no reabrir los mismos archivos en versiones separadas.

---

## Resumen — una fila por versión

| Versión | Qué resuelve | Por qué va ahí y no antes/después |
| :--- | :--- | :--- |
| v1.2.0 | Impuestos (bug de raíz), Cobros CxC desde el TPV, tokens de color + componentes Orvian ligeros | Impuestos es la dependencia raíz de todo lo que mueve dinero de aquí en adelante. Los componentes se adoptan antes de construir módulos nuevos para no repintarlos después |
| v1.3.0 | Ciclo de venta completo: Devoluciones + Nota de Crédito (B04), rename Producto/Servicio | Depende del monto de impuesto correcto (v1.2.0) para saber cuánto revertir |
| v1.4.0 | Ciclo de compra: Proveedores/Órdenes de Compra, Inventario avanzado (Transferencias, Tomas Físicas/Mermas) | Depende de CxP operativa (ya base desde v1.1.0) y del modelo de impuestos correcto para no duplicar el mismo bug en Compras |
| v1.5.0 | Multi-tenant (`stancl/tenancy` + PostgreSQL), rediseño de Roles/Permisos | Se paga el costo de la migración una sola vez, sobre un modelo de datos ya estable (base + satélites tempranos ya construidos) |
| Sin versión fija | Identidad Corporativa (logo), migración gradual de las 24 `x-data-table` a `Orvian\Kit\Livewire\Base\DataTable` | Ninguna depende de código propio ni bloquea nada — se hacen cuando corresponda, en paralelo |

---

## v1.2.0 — Corrección Fiscal + Cobros TPV + Tokens de Marca

### Por qué primero

El bug de impuestos no es cosmético, es la dependencia raíz de todo lo que mueve dinero de aquí en adelante. Confirmado en `docs/promts.md`: `sales` no tiene columna `tax_amount`/`net_amount`, el ticket impreso muestra ITBIS en `$0.00` en producción ahora mismo (`ticket.blade.php:33` lee un atributo que no existe en el schema), y `SaleService::generateSaleAccountingEntry()` descuadra el asiento porque credita por el bruto y debita por lo que realmente se cobró (con impuesto).

Todo lo que se construya después hereda este hueco si no se corrige antes:
- **Devoluciones/B04** (v1.3.0) no puede calcular cuánto revertir en impuesto si la venta original nunca guardó cuánto impuesto cobró.
- El **reporte 607 de NCF** (ya construido en `NcfReportService`) reportaría datos incorrectos a la DGII apenas se active NCF en serio.
- **Compras** (v1.4.0), si se construye antes, repetiría el mismo patrón de "booleano global sin persistir" para el ITBIS de compra en vez de heredar el modelo correcto.

### Alcance

1. **Impuestos** — agregar `tax_amount`/`net_amount` a `sales` (y evaluar `sale_items` si se quiere impuesto por línea a futuro), calcularlos y persistirlos en `SaleService::create()` en vez de descartarlos tras la validación, corregir `generateSaleAccountingEntry()` para que credite por el neto+impuesto real, y que `ticket.blade.php`/`full.blade.php` lean la columna real. Evaluar si el ITBIS por producto (exención específica) es necesario ahora o se queda global vía `config/tax.php` — no es parte del bug bloqueante, es una mejora aparte.
2. **Cobros/Pagos CxC desde el TPV** — depende directo de REQ-02.8 (abono operativo separado del asiento contable, ya construido en v1.1.0). Es la pieza que falta para que "pagar en caja" y "el sistema" cuadren, tal como lo pide `docs/promts.md`.
3. **`PosTerminal.is_active` sin enforcement real** — marcado `[x]` en `docs/promts.md` pero el texto describe el bug sin resolver (`OpenSessionRequest`, `CheckTerminalAccess`, `PosCheckoutController` no lo validan). Confirmar si ya se corrigió en otra sesión antes de re-trabajarlo; si no, aplicar aquí por ser del mismo área (POS).
4. **Tokens de color (verde, blanco como base) + `x-ui.badge`/`x-ui.button`/`x-ui.toasts`/`x-ui.forms.*` adaptados de Orvian** — deliberadamente aquí, no después. Son de bajo costo (estimado: ~1-2 días el color, componentes casi drop-in ya que no requieren Livewire) y adoptarlos *antes* de construir Devoluciones/Compras/Inventario avanzado evita que esos módulos nuevos nazcan en la paleta vieja y haya que repintarlos después. Es la única forma de que el rebranding no sea trabajo duplicado.
5. Excluido deliberadamente de esta versión: la migración de `DataTable` (ver sección "Sin versión fija" — requiere Livewire y es un proyecto propio).

---

## v1.3.0 — Ciclo de Venta Completo: Devoluciones + Nota de Crédito (B04)

### Dependencias

- Depende de **Impuestos (v1.2.0)** — sin el monto de impuesto real persistido en la venta original, no hay forma correcta de calcular cuánto revertir en una devolución.
- `sales.ncf` y su infraestructura de módulos (v1.1.0 Fase 4) ya están listas — el B04 se construye como parte de Devoluciones, sin un flag propio (revisión v1.1.0 §10.9, ver nota abajo).

### Alcance

1. **Rename `is_stockable` → campo `type` enum** (Producto/Servicio) en el modelo, clases, rutas y UI — se hace primero dentro de esta versión porque es barato y Devoluciones ya tiene un bug conocido (revierte stock de un servicio que nunca tuvo stock real, ver `docs/promts.md` sección Logística) que se resuelve limpio si el enum existe antes de tocar esa lógica.
2. **Flujo de Devoluciones y Reembolsos** — módulo base (confirmado en `modulos-base-satelite.md`), funciona con o sin NCF activo.
3. **Nota de Crédito Fiscal (B04)** — **ya no es el satélite `sales.credit_notes_b04`** (esa entrada se eliminó de `config/modules.php` en v1.1.0 §10.9: no es una funcionalidad independiente, es el comprobante fiscal de esta misma acción de Devoluciones). Se construye como una rama de este mismo flujo — "emitir devolución con B04" — que valida `module_enabled('sales.ncf')` directo, sin flag intermedio.
4. Vistas `show` específicas para desglose de venta (ítems, pagos, descuentos aplicados) — pedido explícito en `docs/promts.md`, mismo módulo.

---

## v1.4.0 — Ciclo de Compra + Inventario Avanzado

### Dependencias

- **Compras (`purchases.vendors`)** depende de CxP operativa, que ya es base desde v1.1.0 (REQ-03.8) — y aquí hereda el modelo de impuestos correcto (v1.2.0) en vez de duplicar el mismo bug para el ITBIS de compra.
- No depende de Devoluciones/B04 (v1.3.0), pero se agrupa después por área de código: ambas tocan `InventoryMovementService` y conviene no reabrirlo en versiones separadas sin necesidad.

### Alcance

1. **Proveedores y Órdenes de Compra** (`purchases.vendors`) — pantallas y lógica completa, según `docs/promts.md`.
2. **Transferencias entre Almacenes** — submódulo con estados `Creación`/`Recepción`, documentos firmables no editables tras aprobar.
3. **Tomas Físicas (auditorías de stock) y Pérdidas/Mermas.**
4. **Bugs de validación servicio-stock** (mismo área de código): no permitir asignar stock a un producto tipo Servicio, no permitir transferir un Servicio, y corregir la cancelación de venta para que no intente devolver stock de un Servicio.
5. Estos módulos son candidatos naturales para nacer directo en `Orvian\Kit\Livewire\Base\DataTable` en vez del `x-data-table` viejo — son pantallas 100% nuevas, cero legado que migrar. Es el punto de partida real de la migración gradual del DataTable, sin que sea su propia versión dedicada.

---

## v1.5.0 — Multi-tenant (Fase SaaS)

### Por qué aquí y no antes

`stancl/tenancy` + PostgreSQL con aislamiento por esquema significa que cada tabla que exista en el momento de migrar se vuelve el contrato permanente por esquema. Migrar antes de que Compras/Inventario avanzado existan no ahorra nada — esas tablas nacerían después de todos modos y habría que diseñarlas tenant-aware igual. Migrar después de que el modelo de datos ya no se mueva tanto (base + satélites tempranos ya construidos y estables) significa pagar el costo de la migración **una sola vez**, en vez de tener que replicar cada cambio de esquema en N tenants si algo del modelo de Impuestos o Devoluciones cambiara después de migrar.

El registro de módulos y `Plan` (`installation_modules`/`plan_module`, construidos en v1.1.0 Fase 3 y 5) ya están diseñados exactamente para este momento — es la reutilización 1:1 que se documentó desde el inicio: mismo shape de dato, solo se le agrega `tenant_id`.

### Alcance

1. **Migración a PostgreSQL** + aislamiento por esquema vía `stancl/tenancy`.
2. **Interfaz de Súper Administrador** — gestión de tenants, planes asignados, altas/bajas.
3. **Rediseño de Roles y Permisos** (pendientes en `docs/promts.md`: rol obligatorio al crear usuario, permisos extra seleccionables, traducción de permisos, organización en tabs/categorías) — se agrupa aquí porque el multi-tenant introduce la capa de Súper Admin sobre el sistema de permisos existente; conviene rediseñar roles/permisos una sola vez, no dos.

---

## Sin versión fija — en paralelo, sin dependencias de código

| Tarea | Por qué no tiene versión dedicada |
| :--- | :--- |
| **Identidad Corporativa** — vectorizar el logo oficial en Figma | Es trabajo de diseño, no de código. No bloquea ni depende de nada — se integra a los tokens de color el día que esté listo, sin importar qué versión esté en curso |
| **Migración gradual de las 24 tablas `x-data-table` existentes** a `Orvian\Kit\Livewire\Base\DataTable` | El propio paquete está diseñado para convivencia gradual (namespaces separados `x-data-table.*` vs `x-orvian.data-table.*`). Migrar las 24 de una es un proyecto en sí mismo — se migra módulo por módulo cuando se toque por otra razón, empezando naturalmente por los módulos nuevos de v1.4.0 |

---

## Notas de Implementación

- **El orden de este documento asume que `v1.1.0.md` se completa primero.** Ninguna fase de aquí empieza antes de que el registro de módulos, `Plan`, y el desacople de Contabilidad estén cerrados — son la base sobre la que se apoya todo lo demás (CxC/CxP operativas sin Contabilidad, `sales.ncf` como flag, etc.).
- **Impuestos (v1.2.0) es el único punto real de bloqueo duro** en toda esta cadena — todo lo demás son dependencias de "conviene" (orden por área de código, evitar retrabajo), no de "no compila si no está". Si en algún momento hay que reordenar por urgencia de negocio, Impuestos es lo único que no se puede saltar sin arrastrar el mismo bug a los módulos siguientes.
- **Los componentes Orvian pesados (DataTable) se excluyen a propósito de cualquier versión con fecha fija** — es deuda técnica real, pero forzarla a un sprint específico rompe el criterio de "migración gradual" que el propio paquete fue diseñado para permitir.
- Este documento no reemplaza `docs/promts.md` — ahí siguen viviendo los hallazgos nuevos, bugs sueltos y notas de trabajo diario. Cuando algo de `promts.md` madure lo suficiente para tener una versión asignada, se refleja aquí; `promts.md` no se vacía por eso, sigue siendo la libreta.
