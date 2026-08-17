# ZertixPOS — Roadmap v1.2.0 → v1.5.0

**Fecha:** 2026-08-06
**Contexto:** Este documento ordena lo que sigue **después** de `v1.1.0.md` (desacople de Contabilidad + arquitectura de módulos base/satélite). Nace porque `docs/promts.md` es la libreta de trabajo del día a día — cambia constantemente y no es el lugar para fijar un orden de versiones. Este archivo sí lo es: una vez escrito, no debería reordenarse salvo que cambie una dependencia real, no una prioridad de humor.

> **Principio del orden:** no es una lista de prioridades de negocio, es un mapa de dependencias reales. Cada versión existe donde está porque algo de una versión anterior la bloquea técnicamente — no porque "es lo más importante". Donde dos cosas no se bloquean entre sí, se agrupan por área de código tocada, para no reabrir los mismos archivos en versiones separadas.

**Corrección (2026-08-15) — Multi-tenant se adelanta de v1.5.0 a v1.3.0, Devoluciones y Compras corren un número hacia atrás.** El orden original ponía Multi-tenant al final porque asumía migración a PostgreSQL con aislamiento por esquema — bajo ese modelo, cada tabla que existiera al momento de migrar se congelaba como contrato permanente por esquema, así que convenía esperar a que Devoluciones (v1.3.0 original) y Compras (v1.4.0 original) ya existieran para no rediseñar el esquema dos veces. Análisis cruzado a fondo (arquitectura de aislamiento, ventajas/desventajas reales de producción, no solo de desarrollo) determinó que **PostgreSQL no hace falta para esto** — se adopta `stancl/tenancy` en modo **database-per-tenant sobre MySQL**, el motor que ya está en producción. Con bases separadas por tenant, esa dependencia de fondo desaparece: las migraciones de Devoluciones/Compras simplemente van a correr contra cada base de tenant más adelante, exactamente igual que hoy corren contra la única base — no hay ningún esquema compartido que se "congele". Ver el desglose completo de esta decisión, con la evidencia técnica que la sostiene, en la sección de v1.3.0 más abajo. Esto es un cambio de dependencia real, no de prioridad — se documenta el reorden completo, no se esconde.

---

## Resumen — una fila por versión

| Versión | Qué resuelve | Por qué va ahí y no antes/después |
| :--- | :--- | :--- |
| v1.2.0 | Detalle completo en [`v1.2.0.md`](v1.2.0.md): limpieza confirmada de `docs/promts.md` (estados muertos, `unique`/doble-submit, Consumidor Final, código de almacén), reestructuración de rutas/sidebar (`admin`→`app`, `accounting.*`→`finance.*`), rename "Pagos"→"Cobros", Impuestos (bug de raíz), Cobros CxC desde el TPV, tokens de color + componentes Orvian ligeros | Impuestos es la dependencia raíz de todo lo que mueve dinero de aquí en adelante. La limpieza y la reestructuración de navegación van primero para no construir lo nuevo sobre rutas/vistas que van a cambiar de nombre o de lugar a mitad de camino. Los componentes de marca se adoptan antes de construir módulos nuevos para no repintarlos después |
| v1.3.0 | **(Adelantada desde v1.5.0 original)** Multi-tenant vía `stancl/tenancy`, modo **database-per-tenant sobre MySQL** (sin migrar motor): separación landlord/tenant, wizard de aprovisionamiento (reusa el Install Wizard de v1.1.0 Fase 8), panel de Súper Admin liviano, DNS comodín `*.zertixpos.com`, límites por Plan, y Roles/Permisos pendientes de `docs/promts.md` | Ya no depende de que Devoluciones/Compras existan primero — esa dependencia era específica de un modelo con PostgreSQL+esquema compartido, descartado. Sí depende de que la Fase 3 de v1.2.0 (rutas `admin`→`app`) ya haya cerrado, para no provisionar tenants nuevos sobre rutas que están por moverse. Se prioriza sobre Devoluciones/Compras porque hay clientes reales esperando poder entrar por su propio subdominio, y la infraestructura base (`installation_modules`, `Plan`, Wizard) ya está lista desde v1.1.0 |
| v1.4.0 | **(Antes v1.3.0)** Ciclo de venta completo: Devoluciones + Nota de Crédito (B04), rename Producto/Servicio | Depende del monto de impuesto correcto (v1.2.0) para saber cuánto revertir |
| v1.5.0 | **(Antes v1.4.0)** Ciclo de compra: Proveedores/Órdenes de Compra, Inventario avanzado (Transferencias, Tomas Físicas/Mermas) | Depende de CxP operativa (ya base desde v1.1.0) y del modelo de impuestos correcto para no duplicar el mismo bug en Compras |
| Sin versión fija | Identidad Corporativa (logo), migración gradual de las 24 `x-data-table` a `Orvian\Kit\Livewire\Base\DataTable` | Ninguna depende de código propio ni bloquea nada — se hacen cuando corresponda, en paralelo |

---

## v1.2.0 — Corrección Fiscal + Cobros TPV + Tokens de Marca

### Por qué primero

El bug de impuestos no es cosmético, es la dependencia raíz de todo lo que mueve dinero de aquí en adelante. Confirmado en `docs/promts.md`: `sales` no tiene columna `tax_amount`/`net_amount`, el ticket impreso muestra ITBIS en `$0.00` en producción ahora mismo (`ticket.blade.php:33` lee un atributo que no existe en el schema), y `SaleService::generateSaleAccountingEntry()` descuadra el asiento porque credita por el bruto y debita por lo que realmente se cobró (con impuesto).

Todo lo que se construya después hereda este hueco si no se corrige antes:
- **Devoluciones/B04** (v1.4.0) no puede calcular cuánto revertir en impuesto si la venta original nunca guardó cuánto impuesto cobró.
- El **reporte 607 de NCF** (ya construido en `NcfReportService`) reportaría datos incorrectos a la DGII apenas se active NCF en serio.
- **Compras** (v1.5.0), si se construye antes, repetiría el mismo patrón de "booleano global sin persistir" para el ITBIS de compra en vez de heredar el modelo correcto.

### Alcance

**Detalle completo, fase por fase (tabla de Requerimientos + desglose), en [`v1.2.0.md`](v1.2.0.md).** Resumen:

1. **Limpieza confirmada de `docs/promts.md`** (Fases 1-2 de `v1.2.0.md`): estados muertos de `DocumentType`, "Mínimo Activos", `x-cloak`, `unique` (bug ya reproducido con 69 almacenes duplicados), código de almacén sin uso real, protección de Consumidor Final, ticket a crédito mostrando forma de pago.
2. **Reestructuración de rutas y sidebar** (Fase 3): agrupación CRM/Ventas/Inventario/Finanzas/Reportes/Sistema, prefijo `admin`→`app`, rename `accounting.*`→`finance.*` y `clients.pos.*`→`clients.delivery_points.*`. Va antes de lo demás para no construir código nuevo sobre nombres de ruta que están por cambiar.
3. **Rename "Pagos"→"Cobros"** (Fase 4), fase propia con verificación dedicada — `Payment` es exclusivamente el abono de CxC, nunca dinero saliendo del negocio; el nombre correcto libera "Pagos" para cuando exista CxP operativa.
4. **Impuestos** (Fase 5) — modelo multi-tasa por línea (`config/impuestos.php` + pivote `product_taxes`), persistir `net_amount`/`tax_amount` en `sales`/`sale_items`, corregir `generateSaleAccountingEntry()` para que credite por el neto+impuesto real, y que `ticket.blade.php`/`full.blade.php` lean la columna real.
5. **Cobros CxC desde el TPV** (Fase 6) — depende directo de REQ-02.8 (abono operativo separado del asiento contable, ya construido en v1.1.0). Es la pieza que falta para que "pagar en caja" y "el sistema" cuadren, tal como lo pide `docs/promts.md`.
6. **Tokens de color + componentes Orvian ligeros** (Fase 7) — deliberadamente al final, no antes. Son de bajo costo y adoptarlos *antes* de construir Multi-tenant/Devoluciones/Compras evita que esos módulos nuevos nazcan en la paleta vieja y haya que repintarlos después.
7. Excluido deliberadamente de esta versión: la migración de `DataTable` (ver sección "Sin versión fija" — requiere Livewire y es un proyecto propio).

---

## v1.3.0 — Multi-tenant (Fase SaaS, adelantada)

### Por qué se adelanta desde v1.5.0, y por qué es seguro hacerlo ahora

El plan original ponía esto al final porque asumía PostgreSQL con aislamiento por esquema (`stancl/tenancy` en modo schema) — bajo ese modelo, cada tabla que existiera al momento de migrar se convierte en el contrato permanente del esquema compartido, así que esperar a que el modelo de datos se estabilizara (Devoluciones + Compras ya construidos) tenía sentido real, no solo prudencia.

Esa dependencia **ya no aplica** porque la decisión de arquitectura cambió, con evidencia concreta detrás, no solo preferencia:

**Decisión final: `stancl/tenancy`, modo *database-per-tenant*, sobre MySQL. No se migra el motor a PostgreSQL para esto.**

Razones, en orden de peso:

1. **El escenario que motiva todo esto — sacar a un cliente grande a su propia base — se resuelve solo con database-per-tenant.** Cada tenant ya nace en su propia base física; "graduarlo" a servidor dedicado es un cambio de credenciales de conexión con un `mysqldump`/restore de bajo riesgo, no una migración de datos. Con esquemas compartidos en Postgres, ese mismo evento exige extraer el esquema de una instancia compartida (`pg_dump -n` + restore + reconfiguración), con downtime a planificar para el cliente que menos margen de error tolera.
2. **No apilar dos incógnitas nuevas al mismo tiempo bajo presión real de fecha.** Multi-tenancy ya es nuevo. Migrar a PostgreSQL en producción, después de 10 meses construidos sobre MySQL, también sería nuevo — y tiene un costo verificado, no estimado: **~23 fragmentos de SQL específico de MySQL** (`DATE_FORMAT`, `MONTH()`) repartidos en 5 controladores de dashboard (`InventoryDashboardController`, `SalesDashboardController`, `FinancialOverviewController`, `AccountingDashboardController`, `NcfDashboardController`) que no corren tal cual en Postgres y fallan **en silencio** (fechas mal agrupadas, gráficos vacíos), no con un error que se note al desplegar.
3. **Blast radius.** Con esquemas compartidos, una caída o corrupción de la única instancia de Postgres tumba a todos los tenants a la vez. Con bases separadas, un incidente queda contenido a un solo cliente. Para un ERP que guarda datos fiscales/contables reales, ese es el criterio que más pesa.
4. **Madurez del paquete.** Database-per-tenant es el modo original y más documentado de `stancl/tenancy` — más soporte real de comunidad para cuando algo falle un fin de semana.

**Corrección de premisa, importante para no construir mal esto:** no es una estrategia de columna `tenant_id` compartida — es aislamiento por **conexión**. Cada tenant tiene su propia base de datos física; ninguna tabla de negocio (`sales`, `products`, `users`, etc.) lleva `tenant_id`. El aislamiento no depende de que ningún desarrollador se acuerde de filtrar por tenant en cada query — es estructuralmente imposible que una consulta de un tenant vea datos de otro, porque la conexión activa ya apunta a la base correcta antes de correr cualquier query.

**Corrección de alcance, importante para no sobre-construir esto:** un cliente con sucursales **no es un tenant con tenants anidados** — `stancl/tenancy` no tiene ese concepto y forzarlo sería un error de modelo. Un cliente con varias sucursales sigue siendo **un solo tenant**; las sucursales son `Warehouse`/`PosTerminal` dentro de esa misma base, exactamente como ya funciona hoy — es el mismo modelo que profundiza "Transferencias entre Almacenes" en v1.5.0. Multi-tenant resuelve "el Colmado Pérez y la Farmacia Ana no deben verse entre sí", no "las sucursales del Colmado Pérez no deben verse entre sí" (al revés: sí deben, es el mismo negocio, y aislarlas rompería el reporte consolidado que un dueño de cadena espera).

### Alcance

1. **Separación landlord/tenant.** Base central ("landlord"): tabla `tenants` (subdominio, plan, estado, límites), tabla `domains`, y un `users`/`admins` propio y chico para el staff de ZertixPOS — completamente separado de los `users` de cada cliente, que viven dentro de la base de ese tenant (la misma tabla que ya existe hoy, sin cambios de estructura).
2. **Wizard de aprovisionamiento** en el panel de Súper Admin — no reconstruye el flujo de alta, **envuelve** el Install Wizard ya construido en `v1.1.0.md` Fase 8 (Admin/Empresa/Plan/Finalizar): crea la fila `Tenant` + `Domain`, corre `tenants:migrate`/`tenants:seed` contra la base nueva, y dispara ese mismo wizard la primera vez que el cliente entra a su subdominio.
3. **Panel de Súper Admin liviano** — alta/baja de tenants, plan asignado, límites por plan (ya identificado como faltante en `v1.1.0.md` §Fase 5: "nada delimita cuántos usuarios puede crear una instalación según su plan"), estado de cada instalación. Reusa `installation_modules`/`Plan` tal cual, sin rediseño — esas tablas ya nacieron pensadas para este momento (`modulos-base-satelite.md:160`).
4. **DNS comodín** `*.zertixpos.com` — un registro `A`/`CNAME`, un certificado wildcard vía DNS-01 challenge (no HTTP-01, no valida wildcards), y una lista de subdominios reservados (`admin`, `app`, `api`, `www`) que el wizard rechaza antes de crear un tenant.
5. **Fix obligatorio, no opcional:** activar `CacheTenancyBootstrapper` de `stancl/tenancy` para que el caché de permisos de `spatie/laravel-permission` (24h por defecto, clave global si no se ajusta) no se filtre entre tenants que comparten el mismo store de caché. Aplica sin importar el modo de aislamiento elegido — no es una ventaja exclusiva de database-per-tenant, hay que resolverlo igual.
6. **Roles y Permisos** (pendientes de `docs/promts.md`: rol obligatorio al crear usuario, permisos extra seleccionables, traducción de permisos, organización en tabs/categorías) — se mantiene agrupado acá porque el panel de Súper Admin introduce por primera vez el concepto de roles a nivel landlord, aunque ya no depende técnicamente de la migración a Postgres como se pensaba originalmente. Evaluar al llegar a esta fase si conviene desacoplarlo en una sub-fase propia según el volumen de trabajo ya acumulado.

### Descartado explícitamente, y por qué

- **PostgreSQL + aislamiento por esquema** — evaluado a fondo, descartado. Fuerza una migración de motor completa antes de poder tocar multi-tenancy, con un costo verificado (no estimado) de ~23 puntos de SQL MySQL-específico que fallan en silencio si se migran mal, apilado sobre aprender multi-tenancy por primera vez, bajo fecha real con clientes esperando.
- **`tenant_id` sobre esquema compartido** — descartado desde el inicio de la discusión. Es una arquitectura distinta (aislamiento por fila, no por conexión) que no aprovecha lo que `stancl/tenancy` realmente resuelve, y deja la seguridad de los datos dependiendo de que ningún `where` se olvide nunca.

### Dependencias reales

- Depende de que la **Fase 3 de v1.2.0** (prefijo `admin`→`app`, rutas finales) ya haya cerrado — no tiene sentido provisionar tenants nuevos sobre rutas que están a mitad de moverse.
- **No** depende de v1.4.0 (Devoluciones) ni v1.5.0 (Compras) — esa dependencia solo existía bajo el modelo de PostgreSQL+esquema ya descartado. Con bases separadas, las migraciones de esas versiones futuras simplemente corren contra cada base de tenant cuando lleguen, igual que hoy corren contra la única base existente.
- Puede correr en **paralelo** a las Fases 4-7 de v1.2.0 (Cobros, Impuestos, Marca) — es código nuevo y aislado (landlord + wizard de aprovisionamiento), sin choque de archivos con esas fases.

**Verificación:** crear un tenant nuevo desde el panel de Súper Admin provisiona una base MySQL nueva, corre el Install Wizard existente de punta a punta, y el subdominio `{tenant}.zertixpos.com` resuelve al tenant correcto sin configuración manual de DNS por cliente. Un usuario logueado en el subdominio del Tenant A no tiene sesión válida en el subdominio del Tenant B. Los roles/permisos de un tenant no se filtran a otro tenant que comparta el mismo store de caché (confirmado con `CacheTenancyBootstrapper` activo, probado con dos tenants reales en simultáneo). Un subdominio no registrado en `domains` responde 404, no expone ningún tenant por error. Sacar un tenant a un servidor dedicado es un cambio de credenciales de conexión, verificado sin pérdida de datos.

---

## v1.4.0 — Ciclo de Venta Completo: Devoluciones + Nota de Crédito (B04)

*(Antes v1.3.0 — corre un número hacia atrás por el adelanto de Multi-tenant, ver corrección al inicio del documento)*

### Dependencias

- Depende de **Impuestos (v1.2.0)** — sin el monto de impuesto real persistido en la venta original, no hay forma correcta de calcular cuánto revertir en una devolución.
- `sales.ncf` y su infraestructura de módulos (v1.1.0 Fase 4) ya están listas — el B04 se construye como parte de Devoluciones, sin un flag propio (revisión v1.1.0 §10.9, ver nota abajo).

### Alcance

1. **Rename `is_stockable` → campo `type` enum** (Producto/Servicio) en el modelo, clases, rutas y UI — se hace primero dentro de esta versión porque es barato y Devoluciones ya tiene un bug conocido (revierte stock de un servicio que nunca tuvo stock real, ver `docs/promts.md` sección Logística) que se resuelve limpio si el enum existe antes de tocar esa lógica.
2. **Flujo de Devoluciones y Reembolsos** — módulo base (confirmado en `modulos-base-satelite.md`), funciona con o sin NCF activo.
3. **Nota de Crédito Fiscal (B04)** — **ya no es el satélite `sales.credit_notes_b04`** (esa entrada se eliminó de `config/modules.php` en v1.1.0 §10.9: no es una funcionalidad independiente, es el comprobante fiscal de esta misma acción de Devoluciones). Se construye como una rama de este mismo flujo — "emitir devolución con B04" — que valida `module_enabled('sales.ncf')` directo, sin flag intermedio.
4. Vistas `show` específicas para desglose de venta (ítems, pagos, descuentos aplicados) — pedido explícito en `docs/promts.md`, mismo módulo.

---

## v1.5.0 — Ciclo de Compra + Inventario Avanzado

*(Antes v1.4.0 — corre un número hacia atrás por el adelanto de Multi-tenant, ver corrección al inicio del documento)*

### Dependencias

- **Compras (`purchases.vendors`)** depende de CxP operativa, que ya es base desde v1.1.0 (REQ-03.8) — y aquí hereda el modelo de impuestos correcto (v1.2.0) en vez de duplicar el mismo bug para el ITBIS de compra.
- No depende de Devoluciones/B04 (v1.4.0), pero se agrupa después por área de código: ambas tocan `InventoryMovementService` y conviene no reabrirlo en versiones separadas sin necesidad.

### Alcance

1. **Proveedores y Órdenes de Compra** (`purchases.vendors`) — pantallas y lógica completa, según `docs/promts.md`.
2. **Transferencias entre Almacenes** — submódulo con estados `Creación`/`Recepción`, documentos firmables no editables tras aprobar. Es el mismo modelo que ya sostiene "sucursales dentro de un tenant" en v1.3.0 — se profundiza acá, no se rediseña.
3. **Tomas Físicas (auditorías de stock) y Pérdidas/Mermas.**
4. **Bugs de validación servicio-stock** (mismo área de código): no permitir asignar stock a un producto tipo Servicio, no permitir transferir un Servicio, y corregir la cancelación de venta para que no intente devolver stock de un Servicio.
5. Estos módulos son candidatos naturales para nacer directo en `Orvian\Kit\Livewire\Base\DataTable` en vez del `x-data-table` viejo — son pantallas 100% nuevas, cero legado que migrar. Es el punto de partida real de la migración gradual del DataTable, sin que sea su propia versión dedicada.

---

## Sin versión fija — en paralelo, sin dependencias de código

| Tarea | Por qué no tiene versión dedicada |
| :--- | :--- |
| **Identidad Corporativa** — vectorizar el logo oficial en Figma | Es trabajo de diseño, no de código. No bloquea ni depende de nada — se integra a los tokens de color el día que esté listo, sin importar qué versión esté en curso |
| **Migración gradual de las 24 tablas `x-data-table` existentes** a `Orvian\Kit\Livewire\Base\DataTable` | El propio paquete está diseñado para convivencia gradual (namespaces separados `x-data-table.*` vs `x-orvian.data-table.*`). Migrar las 24 de una es un proyecto en sí mismo — se migra módulo por módulo cuando se toque por otra razón, empezando naturalmente por los módulos nuevos de v1.5.0 |

---

## Notas de Implementación

- **El orden de este documento asume que `v1.1.0.md` se completa primero.** Ninguna fase de aquí empieza antes de que el registro de módulos, `Plan`, y el desacople de Contabilidad estén cerrados — son la base sobre la que se apoya todo lo demás (CxC/CxP operativas sin Contabilidad, `sales.ncf` como flag, y ahora también `installation_modules`/`Plan` como el dato que Multi-tenant reutiliza 1:1 en v1.3.0).
- **Impuestos (v1.2.0) sigue siendo el único punto real de bloqueo duro** sobre Devoluciones y Compras — nada de eso cambia con el adelanto de Multi-tenant. Multi-tenant, en cambio, ya no bloquea ni es bloqueado por ninguna versión de negocio (v1.4.0/v1.5.0) — es infraestructura ortogonal, adelantada por presión real de clientes esperando, no por prioridad de humor (ver corrección al inicio del documento, con la evidencia técnica que la sostiene).
- **Los componentes Orvian pesados (DataTable) se excluyen a propósito de cualquier versión con fecha fija** — es deuda técnica real, pero forzarla a un sprint específico rompe el criterio de "migración gradual" que el propio paquete fue diseñado para permitir.
- **PostgreSQL queda descartado como prerequisito de Multi-tenant, no descartado para siempre.** Si en el futuro aparece una razón concreta y específica (no "se ve más profesional") — full-text search avanzado, un tipo de dato que MySQL no cubra bien — esa conversación se da en ese momento, con esa justificación puntual, no como parte de esta decisión.
- Este documento no reemplaza `docs/promts.md` — ahí siguen viviendo los hallazgos nuevos, bugs sueltos y notas de trabajo diario. Cuando algo de `promts.md` madure lo suficiente para tener una versión asignada, se refleja aquí; `promts.md` no se vacía por eso, sigue siendo la libreta.
