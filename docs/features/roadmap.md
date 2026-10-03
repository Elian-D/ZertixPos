# ZertixPOS — Roadmap de versiones

**Nombre genérico a propósito (2026-10-02):** antes era `roadmap-v1.2-v1.5.md`. Es **el** roadmap vivo: cada versión nueva se agrega aquí, sin renombrar el archivo cada vez que el rango crece.

**Contexto:** Este documento ordena lo que sigue **después** de `v1.1.0.md` (desacople de Contabilidad + arquitectura de módulos base/satélite). Nace porque `docs/promts.md` es la libreta de trabajo del día a día — cambia constantemente y no es el lugar para fijar un orden de versiones. Este archivo sí lo es: una vez escrito, no debería reordenarse salvo que cambie una dependencia real, no una prioridad de humor.

**Trabajo previo, ya completado antes de v1.2.0 (fuera del alcance de este documento, mencionado solo como contexto):** el sistema de POS/Terminales completo (diseño responsivo, sesiones multi-usuario, PIN y bloqueo de terminal, independencia de configuración/descuentos por caja, integración DGII para RNC/Cédula) está construido y documentado fase por fase en `docs/features/POS-Interfaz.md` — ese documento es la referencia de ese trabajo, no se repite acá.

> **Principio del orden:** no es una lista de prioridades de negocio, es un mapa de dependencias reales. Cada versión existe donde está porque algo de una versión anterior la bloquea técnicamente — no porque "es lo más importante". Donde dos cosas no se bloquean entre sí, se agrupan por área de código tocada, para no reabrir los mismos archivos en versiones separadas.

**Corrección (2026-08-15) — Multi-tenant se adelanta de v1.5.0 a v1.3.0, Devoluciones y Compras corren un número hacia atrás.** El orden original ponía Multi-tenant al final porque asumía migración a PostgreSQL con aislamiento por esquema — bajo ese modelo, cada tabla que existiera al momento de migrar se congelaba como contrato permanente por esquema, así que convenía esperar a que Devoluciones (v1.3.0 original) y Compras (v1.4.0 original) ya existieran para no rediseñar el esquema dos veces. Análisis cruzado a fondo (arquitectura de aislamiento, ventajas/desventajas reales de producción, no solo de desarrollo) determinó que **PostgreSQL no hace falta para esto** — se adopta `stancl/tenancy` en modo **database-per-tenant sobre MySQL**, el motor que ya está en producción. Con bases separadas por tenant, esa dependencia de fondo desaparece: las migraciones de Devoluciones/Compras simplemente van a correr contra cada base de tenant más adelante, exactamente igual que hoy corren contra la única base — no hay ningún esquema compartido que se "congele". Ver el desglose completo de esta decisión, con la evidencia técnica que la sostiene, en la sección de v1.3.0 más abajo. Esto es un cambio de dependencia real, no de prioridad — se documenta el reorden completo, no se esconde.

---

## Resumen — una fila por versión

| Versión | Qué resuelve | Por qué va ahí y no antes/después |
| :--- | :--- | :--- |
| v1.2.0 | **Completada.** Detalle completo en [`v1.2.0.md`](v1.2.0.md): limpieza de deuda técnica menor (estados muertos, `unique`/doble-submit, Consumidor Final, código de almacén, depuración geográfica a solo RD), reestructuración de rutas/sidebar (`admin`→`app`, `accounting.*`→`finance.*`), rename "Pagos"→"Cobros", Impuestos (bug de raíz), Cobros CxC desde el TPV, Identidad Corporativa (logo vectorizado) + tokens de color + componentes Orvian ligeros | Impuestos es la dependencia raíz de todo lo que mueve dinero de aquí en adelante. La limpieza y la reestructuración de navegación van primero para no construir lo nuevo sobre rutas/vistas que van a cambiar de nombre o de lugar a mitad de camino. Los componentes de marca (y el logo, que se integró en la misma pasada) se adoptan antes de construir módulos nuevos para no repintarlos después |
| v1.3.0 | **Completada.** **(Adelantada desde v1.5.0 original)** Multi-tenant vía `stancl/tenancy`, modo **database-per-tenant sobre MySQL** (sin migrar motor): separación landlord/tenant, wizard de aprovisionamiento (reusa el Install Wizard de v1.1.0 Fase 8), panel de Súper Admin liviano, DNS comodín `*.zertixpos.com`, límites por Plan, Roles/Permisos renombrados a `recurso.accion`, y migración completa de las 22 tablas del sistema al motor Livewire (`App\Livewire\Base\DataTable`) — absorbió de una sola vez lo que originalmente se planeó como migración gradual sin versión fija | Ya no depende de que Devoluciones/Compras existan primero — esa dependencia era específica de un modelo con PostgreSQL+esquema compartido, descartado. Sí depende de que la Fase 3 de v1.2.0 (rutas `admin`→`app`) ya haya cerrado, para no provisionar tenants nuevos sobre rutas que están por moverse. Se prioriza sobre Devoluciones/Compras porque hay clientes reales esperando poder entrar por su propio subdominio, y la infraestructura base (`installation_modules`, `Plan`, Wizard) ya está lista desde v1.1.0. La migración del motor de tablas se adelantó a Fase 0 de esta misma versión porque el Panel de Súper Admin y cualquier vista nueva de multi-tenant nacían directo en el motor nuevo — construirlas en el motor viejo hubiera significado migrarlas después de todos modos |
| v1.4.0 | **(Antes v1.3.0)** Detalle en [`v1.4.0.md`](v1.4.0.md). Hecho:<br>• Rename Producto/Servicio y guard de Anulación por turno.<br>• **Devoluciones y Cambios solo desde backoffice** con ejecución inmediata. El flujo Solicitar/Aprobar del TPV se diseñó, se descartó y no se construyó.<br>• Vistas `show` estilo Filament de casi todos los módulos.<br>• Numeración propia por documento: `VTA`/`FAC`/`CXC`/`COT`/`TRN`.<br>• Anulación con motivo en la venta.<br>• Rediseño de los PDF carta.<br>• Ajustes de UI de las tablas.<br><br>Nota de Crédito (B04) descartada en esta versión | Depende del monto de impuesto correcto (v1.2.0) para saber cuánto revertir |
| v1.5.0 | **(Antes v1.4.0)** Detalle en [`v1.5.0.md`](v1.5.0.md):<br>• Inventario sano: tipos de movimiento, código de barras, inventario inicial y costo promedio.<br>• Toma física, mermas y transferencias.<br>• **Tesorería** (cuentas de caja/banco y conciliación), adelantada desde v1.7.0.<br>• Proveedores, CxP genérica (compras y gastos directos) con reversión.<br>• Órdenes de compra y recepciones.<br><br>Compras pasa a núcleo en todos los planes | Depende de la numeración por documento y de los componentes de vista de v1.4.0. Hereda el modelo de impuestos de v1.2.0, aunque el ITBIS de compra queda fuera de esta versión |
| v1.6.0 | **(Sin pedido confirmado — no empezar hasta que un cliente real lo pida, ver sección propia).** Variantes de producto (talla/color/etc.) — `product_variants` con SKU/código de barras/stock propios, `Product` pasa a ser el estilo padre | No depende de nada anterior técnicamente, pero **si se confirma antes de que v1.4.0/v1.5.0 arranquen, hay que adelantarla** (mismo criterio que adelantó Multi-tenant) — `SaleItem`/`QuoteItem`/`InventoryStock` hoy asumen `product_id` único, y Devoluciones/Compras/Transferencias construidas sobre esa asunción tendrían que reabrirse para agregar la dimensión de variante |
| v1.7.0 | **(Propuesta, por validar).** Límites de almacenes y cajas por Plan, y operación multi-sucursal sobre los almacenes y terminales que ya existen:<br>• Identidad de sucursal en `Warehouse`.<br>• Usuarios ligados a una sucursal, con datos filtrados.<br>• Selector de sucursal activa.<br>• Cuentas de tesorería ligadas a una sucursal (la tesorería en sí ya se construye en v1.5.0).<br>• Reporte consolidado por sucursal. | Los **límites del Plan** no dependen de nada y pueden adelantarse solos: hoy se vende sin techo de almacenes ni cajas. La parte **multi-sucursal** depende de **Transferencias (v1.5.0)**, que es lo que conecta el inventario entre sucursales |

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
5. **Cobros CxC desde el TPV** (Fase 6) — depende directo de REQ-02.8 (abono operativo separado del asiento contable, ya construido en v1.1.0). Es la pieza que falta para que "pagar en caja" y "el sistema" cuadren.
6. **Identidad Corporativa (logo vectorizado en Figma) + Tokens de color y componentes Orvian ligeros** (Fase 7) — deliberadamente al final, no antes. Son de bajo costo y adoptarlos *antes* de construir Multi-tenant/Devoluciones/Compras evita que esos módulos nuevos nazcan en la marca/paleta vieja y haya que repintarlos después.
7. Excluido deliberadamente de esta versión: la migración de `DataTable` a Livewire — requiere un motor propio, se termina adelantando a Fase 0 de v1.3.0 (ver esa sección).

---

## v1.3.0 — Multi-tenant (Fase SaaS, adelantada)

**Detalle completo, fase por fase (tabla de Requerimientos + desglose por dependencia, orden real de ramas), en [`v1.3.0.md`](v1.3.0.md).** Este documento reemplaza el resumen que sigue abajo — se deja igual como contexto de por qué se adelantó, pero el alcance real y ejecutable vive en `v1.3.0.md`.

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
6. **Roles y Permisos** — rol obligatorio al crear usuario (con permisos extra seleccionables), renombrado completo a la convención `recurso.accion`, traducción de permisos y organización en tabs/categorías. Se mantiene agrupado acá porque el panel de Súper Admin introduce por primera vez el concepto de roles a nivel landlord, aunque ya no depende técnicamente de la migración a Postgres como se pensaba originalmente.
7. **Migración del motor de tablas a Livewire** (`App\Livewire\Base\DataTable`) — 22 módulos migrados en total. Originalmente planeada sin versión fija, migración módulo por módulo "cuando tocara". Se adelantó a Fase 0, antes de todo lo demás, porque el Panel de Súper Admin y cualquier vista nueva de esta versión nacían directo en el motor nuevo — construirlas primero en el motor AJAX viejo hubiera significado migrarlas después de todos modos. Con esto, la migración gradual que originalmente no tenía versión fija quedó completamente absorbida acá, de una sola vez.

### Descartado explícitamente, y por qué

- **PostgreSQL + aislamiento por esquema** — evaluado a fondo, descartado. Fuerza una migración de motor completa antes de poder tocar multi-tenancy, con un costo verificado (no estimado) de ~23 puntos de SQL MySQL-específico que fallan en silencio si se migran mal, apilado sobre aprender multi-tenancy por primera vez, bajo fecha real con clientes esperando.
- **`tenant_id` sobre esquema compartido** — descartado desde el inicio de la discusión. Es una arquitectura distinta (aislamiento por fila, no por conexión) que no aprovecha lo que `stancl/tenancy` realmente resuelve, y deja la seguridad de los datos dependiendo de que ningún `where` se olvide nunca.

### Dependencias reales

- Depende de que la **Fase 3 de v1.2.0** (prefijo `admin`→`app`, rutas finales) ya haya cerrado — no tiene sentido provisionar tenants nuevos sobre rutas que están a mitad de moverse.
- **No** depende de v1.4.0 (Devoluciones) ni v1.5.0 (Compras) — esa dependencia solo existía bajo el modelo de PostgreSQL+esquema ya descartado. Con bases separadas, las migraciones de esas versiones futuras simplemente corren contra cada base de tenant cuando lleguen, igual que hoy corren contra la única base existente.
- Puede correr en **paralelo** a las Fases 4-7 de v1.2.0 (Cobros, Impuestos, Marca) — es código nuevo y aislado (landlord + wizard de aprovisionamiento), sin choque de archivos con esas fases.

**Verificación:** crear un tenant nuevo desde el panel de Súper Admin provisiona una base MySQL nueva, corre el Install Wizard existente de punta a punta, y el subdominio `{tenant}.zertixpos.com` resuelve al tenant correcto sin configuración manual de DNS por cliente. Un usuario logueado en el subdominio del Tenant A no tiene sesión válida en el subdominio del Tenant B. Los roles/permisos de un tenant no se filtran a otro tenant que comparta el mismo store de caché (confirmado con `CacheTenancyBootstrapper` activo, probado con dos tenants reales en simultáneo). Un subdominio no registrado en `domains` responde 404, no expone ningún tenant por error. Sacar un tenant a un servidor dedicado es un cambio de credenciales de conexión, verificado sin pérdida de datos.

---

## v1.4.0 — Devoluciones y Cambios (Ciclo de Venta Completo)

*(Antes v1.3.0 — corre un número hacia atrás por el adelanto de Multi-tenant, ver corrección al inicio del documento)*

**Detalle completo, fase por fase (tabla de Requerimientos + desglose, incluyendo el hallazgo real de que el guard de Anulación hoy no cubre una venta de contado con turno cerrado, y por qué no se reactiva `PosCashMovement`), en [`v1.4.0.md`](v1.4.0.md).** Resumen:

**Realidad de negocio que gobierna el diseño:** en un colmado/surtidora dominicana, 97-99% de una devolución es un **cambio de producto dañado**, casi nunca reembolso de dinero puro — el diseño prioriza eso, no un reembolso genérico.

### Lo que se hizo realmente

El plan original de abajo ("Alcance") se dejó como registro. Lo construido difiere en puntos importantes:

- **Fase 1 — Prerequisitos:**
  - `is_stockable` pasó a `type` (Producto/Servicio).
  - Anular ahora solo es posible mientras el turno de la venta sigue abierto (`Sale::canBeCanceled()`).
  - Se quitó el gate `invoices.print`, que nunca se había sembrado y bloqueaba toda impresión.
- **Fase 2 — Devoluciones y Cambios (replanteada):**
  - **Primer intento descartado:** era un motor pensado para el TPV (aprobaciones, ventana de días, selección de caja y almacén, ajuste del efectivo del turno). Se respaldó en un stash y se revirtió.
  - **Lo construido** sigue las reglas reales del negocio:
    - Solo backoffice, con ejecución inmediata.
    - "Anular" o "Devolver" según el turno.
    - Reembolso en efectivo (monto fijo) o cambio de producto. Si el reemplazo cuesta más, se crea una venta nueva pagada con "Devolución" + efectivo.
    - Varias líneas por devolución, aunque el cambio se hace de a una.
    - Toggle de reingreso a inventario.
    - En una venta a crédito, la devolución baja la CxC (no es un abono).
  - **Lo que lo acompaña:**
    - Numeración `DEV`.
    - Ticket corto con PDF.
    - Tabla Livewire propia.
    - Badges "Devuelta" / "Devuelta parcial" en Ventas.
    - Permisos `returns.create` y `returns.void`.
- **Fase 3 — Vistas show y ajustes:**
  - **Vistas `show` estilo Filament**, sin instalar Filament (`x-ui.infolist.*`, skill `/filament-show`), en lugar de modales e iframes. Cubre clientes, cotizaciones, ventas, devoluciones, terminales, turnos, productos, almacenes, CxC, cobros, facturas, secuencias NCF, roles y usuarios.
  - **Configuración General** rehecha con `/filament-form`.
  - **Fix de seguridad:** las rutas de Terminales POS no tenían permisos.
  - **Anulación de ventas:** motivo obligatorio, quién anuló y cuándo, guardados en la venta. Antes el motivo se perdía si la venta no tenía NCF.
  - **Numeración por tipo de documento:** cada uno con su correlativo atómico (trait `HasDocumentNumber`).
    - `VTA` para la venta.
    - `FAC` para la factura, ahora con secuencia propia.
    - `CXC` para la cuenta por cobrar.
    - `COT` para la cotización.
    - `TRN` para el turno.
  - **Ajustes de UI:**
    - Paginación adaptable.
    - Menú de acciones en móvil.
    - Badges que no se parten.
    - Modales cortos dentro de su tabla.
    - El buscador de cada tabla dice en qué busca (`searchFields()`).
  - **Rediseño de los 4 PDF carta** (turno, factura, recibo y cotización) sobre componentes `x-pdf.*` (skill `/filament-pdf`).
- **Descartado:**
  - Devoluciones desde el TPV y su flujo de aprobación.
  - Nota de Crédito Fiscal (B04).
  - Saldo a favor (store credit).
  - Asientos contables de la devolución.
  - Mermas formales, que pasan a v1.5.0.

### Dependencias

- Depende de **Impuestos (v1.2.0)** — sin el monto de impuesto real persistido en la venta original, no hay forma correcta de calcular cuánto revertir en una devolución.
- `sales.ncf` y su infraestructura de módulos (v1.1.0 Fase 4) ya están listas para cuando se active el B04 — ver nota de prioridad abajo.

### Alcance (plan original, antes de construir)

1. **Rename `is_stockable` → campo `type` enum** (Producto/Servicio) — barato, y corrige de paso un bug conocido (revierte stock de un servicio que nunca tuvo stock real).
2. **Endurecer el guard de Anulación** — hallazgo real de auditoría: hoy una venta 100% en efectivo se puede anular sin restricción aunque su turno de caja ya haya cerrado.
3. **Flujo de Devoluciones y Cambios** — módulo base (confirmado en `modulos-base-satelite.md`), con un cambio de producto modelado como una entrada (producto dañado) y una salida (reemplazo) en el mismo registro. Reembolso en efectivo exige una `PosSession` abierta en el negocio (sin contabilidad, es la única fuente de verdad de que hay efectivo real disponible) — se registra desacoplado de `PosCashMovement` (ese módulo está parado a medias, no se reactiva en esta versión).
4. **Flujo Solicitar (TPV) → Aprobar/Ejecutar** — opcional por negocio vía toggle, con permiso propio de aprobación separado de solicitar, y posibilidad de anular una devolución ya ejecutada mientras su sesión siga abierta.
5. **Nota de Crédito Fiscal (B04)** — **bajada de prioridad a propósito** (el objetivo inmediato es vender el sistema; puede no pedirse nunca). Se deja la base reservada (columna NCF, gate ya resuelto), pero no se construye el consumo real de secuencia/reporte 607 en esta versión.
6. Vista `show` real de Ventas/Facturas (reemplaza el modal, ahora solo como historial informativo — ya no es el punto de entrada de Devolución) y el mismo arreglo para Cotizaciones (sus líneas hoy solo existen dentro del iframe de preview, no como contenido real de la página).

---

## v1.5.0 — Ciclo de Compra + Inventario Avanzado

*(Antes v1.4.0 — corre un número hacia atrás por el adelanto de Multi-tenant, ver corrección al inicio del documento)*

### Dependencias

- **Compras (`purchases.vendors`)** depende de CxP operativa, que ya es base desde v1.1.0 (REQ-03.8) — y aquí hereda el modelo de impuestos correcto (v1.2.0) en vez de duplicar el mismo bug para el ITBIS de compra.
- No depende de Devoluciones/B04 (v1.4.0), pero se agrupa después por área de código: ambas tocan `InventoryMovementService` y conviene no reabrirlo en versiones separadas sin necesidad.

**Especificación definitiva, fase por fase, en [`v1.5.0.md`](v1.5.0.md).** El alcance cambió respecto al plan original de abajo:

- **Inventario:**
  - Se quita la entrada manual libre.
  - Se agrega el inventario inicial en el formulario del producto.
  - Tipos de movimiento con nombre (`sale`, `purchase`, `count`, `waste`…).
  - Código de barras y costo promedio ponderado.
- **Tesorería adelantada desde v1.7.0:** cuentas de caja/banco, kardex inmutable y conciliación. El cierre de turno no se toca; los depósitos se hacen a mano y el turno queda marcado como "Depositado".
- **CxP genérica:** una sola tabla para deudas de compras y gastos directos (luz, agua, alquiler), con pagos y anulación con reverso.
- **Compras en todos los planes**, como núcleo `base_flexible` (antes satélite solo de Pro).
- **Impuestos de compra (606) fuera** de esta versión: el precio de compra se registra tal como se pagó.

### Alcance (plan original, antes de especificar)

1. **Proveedores y Órdenes de Compra** (`purchases.vendors`) — pantallas y lógica completa.
2. **Transferencias entre Almacenes** — submódulo con estados `Creación`/`Recepción`, documentos firmables no editables tras aprobar. Es el mismo modelo que ya sostiene "sucursales dentro de un tenant" en v1.3.0 — se profundiza acá, no se rediseña.
3. **Tomas Físicas (auditorías de stock) y Pérdidas/Mermas.**
4. **Bugs de validación servicio-stock** (mismo área de código): no permitir asignar stock a un producto tipo Servicio y no permitir transferir un Servicio. La cancelación de venta que devolvía stock de un Servicio ya se corrigió en v1.4.0 (REQ-1.1).
5. Estos módulos nacen directo en el motor Livewire (`App\Livewire\Base\DataTable`) — ya es el único motor vigente para tablas nuevas desde que v1.3.0 Fase 0 migró el sistema completo, no hay motor viejo que evitar.

---

## v1.6.0 — Variantes de Producto (Talla/Color)

**Sin pedido confirmado todavía.** Origen: reenvío de un tercero (2026-09-21) sobre un posible cliente de tienda de ropa — no es un requerimiento activo, es reconocimiento de terreno para no llegar desprevenido si se confirma. **No empezar a construir hasta que haya un cliente real esperando esto**, mismo criterio que el resto del documento.

### Por qué el sistema no lo soporta hoy, ni parcialmente

Auditoría del código confirma que ZertixPOS es "single-SKU" de punta a punta, no solo le falta un campo:

- **`Product`** (`app/Models/Products/Product.php`, migración `2026_01_30_192804_create_products_table.php`): `sku` único **por producto**. Cero columna `barcode`. Cero tabla de atributos (Talla, Color) en ningún lado del sistema.
- **`InventoryStock`**: constraint único `(warehouse_id, product_id)` — estructuralmente imposible separar stock por talla/color sin migrar el schema, no es un límite de UI.
- **`SaleItem`/`QuoteItem`**: `foreignId('product_id')` directo, sin tabla intermedia de variante.
- **POS Workspace**: tocar la tarjeta del producto lo agrega al carrito de una vez — no hay paso de "elegir opciones" en el flujo.
- **Código de barras**: no existe en absoluto, ni para producto simple.
- Grep completo de `docs/analisis/*.md` y `docs/features/*.md` por "variante"/"talla"/"atributo" (de producto): cero menciones. Nunca se discutió ni de pasada.

### Alcance (si se confirma)

1. **Modelo nuevo:** `product_variants` (SKU propio, código de barras propio, precio override opcional, imagen propia opcional) + sistema de atributos (`attributes`/`attribute_values`, o algo más simple tipo `option1`/`option2` si no hace falta un sistema genérico completo). `Product` pasa a ser el "estilo padre" (nombre, categoría, imagen base).
2. **Migración de `inventory_stocks`** de `product_id` a `product_variant_id` — rompe el constraint único actual, requiere migración de datos real para todo lo que ya existe en producción.
3. **`SaleItem`/`QuoteItem`** pasan a referenciar la variante, no el producto — con cascada a `SaleService::create()`, el cálculo de COGS (`$product->cost` hoy, tendría que resolver `$variant->cost ?? $product->cost`), tickets/PDFs (mostrar "Camisa Azul — Talla M"), y el reporte 607 de NCF.
4. **POS:** selector de variante (matriz talla×color) antes de agregar al carrito — cambio de flujo, no cosmético. Escaneo de código de barras por variante.
5. **Import/export de productos** (hoy no existe ningún importador real — confirmado, `app/Exports/` no tiene `ProductsImport`) nacería ya variant-aware si se construye después de esto.

### Dependencias reales — por qué el orden importa más que en otros módulos

Mismo patrón que Impuestos (v1.2.0): **todo lo que se construya después hereda el hueco si esto no se resuelve primero.**

- **v1.4.0 (Devoluciones)** construida antes, asumiendo `product_id` — una devolución no sabría de qué variante devolver stock. Retrabajo garantizado si Variantes llega después.
- **v1.5.0 (Compras + Transferencias + Tomas Físicas)** — una orden de compra, una transferencia, un conteo físico, todos necesitan la variante exacta, no el producto padre.

**Si se confirma un cliente real de variantes antes de que v1.4.0/v1.5.0 arranquen en serio, esta versión debe adelantarse** (mismo tipo de decisión, con la misma evidencia por escrito, que adelantó Multi-tenant de v1.5.0 a v1.3.0 — ver corrección al inicio del documento). Si se confirma **después** de que esas dos ya estén construidas, hay que aceptar el retrabajo de agregarles la dimensión de variante — no es gratis en ningún orden, pero es más barato adelantarlo que parcharlo después.

### Descartado explícitamente, y por qué

- **Construirlo "por si acaso" sin cliente confirmado** — no hay evidencia de demanda real todavía (un solo reenvío de tercero, no un cliente en conversación activa). Es trabajo de arquitectura de catálogo grande (toca `Product`, `InventoryStock`, `SaleItem`, `QuoteItem`, POS, exports, NCF) para especular sobre un perfil de cliente que hoy no existe en la base.

---

## v1.7.0 — Límites por Plan + Multi-sucursal y Tesorería

**Propuesta (2026-10-02), por validar antes de construir.** Nace de un análisis externo (multi-sucursal tipo cadena, plan de $89, destino del dinero al cierre), adaptado aquí a lo que el código tiene de verdad. Se descartan las partes del análisis que no aplican.

### Principio

**Una sucursal no es una tabla nueva.** Es una agrupación con significado de lo que ya existe: almacenes (`Warehouse`), cajas (`PosTerminal`, ligadas a un almacén) y usuarios. Es la misma corrección de alcance que fijó v1.3.0: un negocio con sucursales es **un solo tenant**, nunca tenants anidados.

**El Plan vende almacenes y cajas, no "sucursales".** El dueño decide cómo repartirlos: varios almacenes en un mismo local (multi-almacén puro) o repartidos en ciudades (multi-sucursal). Para la infraestructura cuesta lo mismo; el significado lo pone el cliente.

### Qué hay hoy en el código (confirmado)

- **`warehouses`:**
  - `code`, `name`, `type` (`static` / `mobile` / `pos`), `address`, `description`, `is_active`, `softDeletes` y cuenta contable opcional.
  - No hay nada que agrupe almacenes en un "local".
- **`pos_terminals`:**
  - Tienen `warehouse_id`, así que la caja ya pertenece a un almacén.
  - Tienen `cash_account_id` (cuenta contable de caja, solo con `accounting.advanced`).
  - Un almacén sin terminales ya funciona como **depósito**: recibe, mueve y audita stock, pero no se puede abrir turno ahí porque no hay caja.
- **`users`:** sin `warehouse_id`. Todo usuario ve todos los almacenes, cajas y ventas del tenant.
- **`Plan` (landlord):**
  - Solo limita **usuarios** (`users_limit` + `Plan::canCreateMoreUsers()`, REQ-05.6: Emprendedor 1, PyME y Pro sin techo).
  - **No hay límite de almacenes ni de cajas**: un plan de $29 puede crear 50 almacenes y 50 cajas.
  - La landing (zertixpos.com) tampoco lo anuncia.
- **Dinero al cerrar el turno:**
  - `PosSession` guarda el arqueo (esperado, contado, diferencia), pero el efectivo **no tiene destino registrado**: el turno cierra y el dinero "desaparece" del sistema.
  - `PosCashMovement` existe, pero está parado a medias desde v1.4.0. No se reactiva sin decidirlo.

### Fase 1 — Límites de almacenes y cajas por Plan

**Independiente del resto:** se puede adelantar y hacer sola, antes de v1.5.0 si hace falta. Copia el patrón de `users_limit`.

| Plan | Usuarios (ya existe) | Almacenes | Cajas (terminales POS) |
|---|---|---|---|
| Emprendedor ($29) | 1 | 1 | 1 |
| PyME ($59) | Sin límite | 3 | 3 |
| Pro ($89) | Sin límite | Sin límite | Sin límite |

- **Datos (landlord):**
  - `plans.warehouses_limit` y `plans.terminals_limit`, nullable (`null` = sin techo), en una migración central (no de tenant).
  - Se siembran con `PlanSeeder`.
- **Modelo:** `Plan::canCreateMoreWarehouses()` y `canCreateMoreTerminals()`, junto a `canCreateMoreUsers()`. Cuentan solo los registros **no borrados** (la papelera no ocupa cupo).
- **Dónde se bloquea:**
  - **Crear:** `WarehouseController::store()`, y `PosTerminalController::create()` / `store()`. Mismo mensaje que usuarios: "Tu plan actual (X) permite un máximo de N…".
  - **Restaurar de la papelera:** `WarehouseTable::restore()` y `PosTerminalTable::restore()`. Restaurar también suma al conteo; sin esto, el tope se salta borrando y restaurando.
  - **En la tabla:** el botón "Crear" se deshabilita (no se oculta) con "Límite del plan alcanzado (N/N)", igual que `user-table.blade.php`.
- **Dónde se muestra:**
  - Líneas "Hasta N almacenes" y "Hasta N cajas" en el paso de plan del Wizard (`install/partials/step-plan`) y en Gestionar suscripción, como hoy "Hasta N usuarios".
  - La landing externa se actualiza aparte.
- **Negocios que ya superan el límite:** no se les borra ni desactiva nada; solo no pueden crear ni restaurar más hasta bajar o cambiar de plan.
- **Pendiente de revisar al construirlo:**
  - Si `UserTable::restore()` ya respeta `users_limit` (mismo hueco posible).
  - Cómo cuenta el almacén que crea el Wizard por defecto: con Emprendedor, ese ya ocupa su único cupo.

### Fase 2 — Identidad de sucursal y alcance por usuario

- **Identidad en el almacén:**
  - `warehouses` gana `branch_name` (nullable), el nombre del local o punto físico ("Bonao", "La Vega"). Los almacenes con el mismo `branch_name` forman **una sucursal**, sin tabla `branches`.
  - `is_main` marca la casa matriz.
  - `is_active` ya existe y no se duplica.
- **Usuario ligado a una sucursal:**
  - `users.branch_name` (o `warehouse_id`, a decidir al construir; con varios almacenes por local, el nombre de sucursal es más fiel) con alcance **opcional**:
    - Con valor, el usuario solo ve los almacenes, cajas, ventas, cobros y stock de su sucursal.
    - Vacío (dueño o admin central), lo ve todo.
  - **Cómo se filtra:** con un scope por consulta en los `baseQuery()` de las tablas Livewire y en los servicios que listan, **no** con un global scope de Eloquent a ciegas. Un global scope también filtraría reportes, jobs y el propio cierre de turno donde no corresponde, y es difícil de depurar.
  - **Permiso propio** para ver todas las sucursales (por ejemplo `branches.view_all`), en vez de depender solo de que el campo esté vacío.
- **Sucursal activa en sesión (dueño):** un selector compacto en la cabecera del backoffice para cambiar de sucursal o ver "Todas".
  - El cajero en el TPV ya opera sobre la caja y el almacén de su terminal; ahí solo se muestra el badge con la sucursal, sin selector.

### Fase 3 — Tesorería: a dónde va el efectivo al cerrar

> **Absorbida por v1.5.0 (2026-10-02).** Las cuentas de caja/banco, el kardex inmutable y la conciliación se construyen en `v1.5.0.md` Fase 3. El **destino automático del cierre de turno se descartó**: el depósito es manual desde tesorería, porque el cajero no debe elegir cuentas y muchos negocios dejan fondo en la gaveta. Lo de abajo queda como registro del planteamiento original; lo que sigue vigente para v1.7.0 es solo que las cuentas puedan ligarse a una sucursal (`branch_name`) cuando exista multi-sucursal.

- **Cuentas de fondos** (`treasury_accounts`, nombre a definir sin chocar con `accounting_accounts`):
  - Campos: nombre ("Caja fuerte Bonao", "Banco Popular"), tipo (`vault` o `bank`), saldo y `branch_name` opcional (vacío = cuenta central del dueño).
  - **Sin asientos contables**, para mantener el desacople de Contabilidad de v1.1.0.
  - Con `accounting.advanced` activo, más adelante se puede mapear cada cuenta a una cuenta contable. No en esta versión.
- **Libro de movimientos inmutable:** entrada y salida, monto, origen (`pos_close` / `manual` / `return`), referencia polimórfica y usuario. Nunca se edita ni se borra; un error se corrige con un movimiento inverso.
- **Cierre de turno con destino:**
  - Al cerrar, el cajero (o supervisor) elige la cuenta de destino compatible con su sucursal, o una central.
  - El monto **contado** (`closing_balance`, no el esperado) entra como movimiento `pos_close`; la diferencia sigue registrada en el turno, como hoy.
  - Toca `PosSessionService::close()` y la vista de cierre (Fase 9.3).
- **Reemplaza la necesidad de reactivar `PosCashMovement` para esto.** Si al construir resulta que ese módulo encaja, se evalúa en ese momento; no se asume.

### Fase 4 — Consolidado por sucursal (dueño)

- Vista `show`/dashboard con `x-ui.infolist.*`, solo para quien ve todas las sucursales.
- **Disponibilidad:** saldo de cada cuenta de fondos.
- **Rendimiento por sucursal:** ventas, costo, margen, efectivo en custodia y alertas de stock bajo, agrupado por sucursal.
- Agrupa con consultas `groupBy` sobre el almacén y la sucursal, no con un `ReturnTable` / `SaleTable` "extendido".

### Descartado explícitamente, y por qué

- **Tabla `branches`:** la sucursal es la agrupación de almacenes por `branch_name`. Una tabla aparte duplicaría claves y obligaría a mantener dos fuentes de verdad. Se reevalúa solo si aparece un dato que no cabe en el almacén (horario, RNC propio por sucursal, etc.).
- **Tenants anidados / sub-tenants:** fuera de modelo desde v1.3.0.
- **Global scope de Eloquent por usuario:** demasiado amplio; ver Fase 2.
- **Asientos contables de tesorería:** fuera de alcance (desacople de Contabilidad).
- **Todo lo del análisis original que no aplica al código:** la tienda de ropa como cliente inmediato, "redactar v1.4.0" (ya hecha) y `financial_accounts` con UUID (el sistema usa ids incrementales en todas partes).

### Dependencias reales

- **Fase 1 (límites):** ninguna. Puede hacerse ya.
- **Fases 2-4:**
  - **Transferencias entre Almacenes (v1.5.0)** es lo que mueve inventario entre sucursales; sin eso, cada sucursal sería una isla de stock.
  - **Compras (v1.5.0)** necesita saber a qué almacén o sucursal entra la mercancía.
- **Variantes (v1.6.0):** no la bloquea, pero si se construye antes, el consolidado debe sumar por variante.

---

## Radar — módulos con evidencia real de que faltan, pero sin pedido confirmado (no construir todavía)

**Distinto de v1.6.0 de arriba:** esto no son versiones comprometidas, es una lista de vigilancia — cosas que en algún momento se identificaron como necesarias (en código, en docs, o en la naturaleza del cliente actual) y quedaron sin construir. Se documentan acá para no perderlas de vista, no para empezar a construirlas.

| Candidato | Evidencia real (no especulación) | Para quién sería |
| :--- | :--- | :--- |
| **Ventas Pausadas ("Parked Sales")** | `docs/features/POS-Interfaz.md` Fase 8 — diseño completo con checklist, todo sin marcar (`[ ]`). Confirmado: no existe tabla `parked_sales` en ninguna migración | Cualquier negocio de mostrador — cliente va a buscar dinero, compara precios, sin tener que cancelar el carrito completo |
| **Modo offline + sincronización posterior** | `docs/features/POS-Interfaz.md:864` — listado en la cola futura (`feat/pos-offline-mode`) desde siempre, cero código | El vendedor ambulante (cliente real ya en cartera, ver `docs/analisis/modulos-base-satelite.md`) — conexión inestable en la calle |
| **Fidelización/puntos de cliente** | `docs/features/POS-Interfaz.md:865` — listado en la misma cola (`feat/pos-loyalty-system`), cero código | Retail con clientes recurrentes — pedido común de tiendas de ropa/electrodomésticos |
| **Listas de precio por tipo de cliente (mayorista vs. detalle)** | Confirmado que no existe — distinto del motor de descuentos (`docs/analisis/politica-descuentos.md`, ya bien construido, pero es %-off puntual, no un precio base distinto por segmento) | Distribución con clientes mayoristas y minoristas a la vez — el perfil que ya tiene ZertixPOS hoy (embasadora de agua) |
| **Rutas y Entregas** | `docs/analisis/modulos-base-satelite.md:64` — el link del sidebar **no tiene ruta real detrás** (`/rutas` no registrada en `routes/`), placeholder de la época del hielo | **El cliente que ya se tiene ahora mismo** (embasadora de agua, reparto a domicilio) — el propio doc lo señala explícitamente, es el más urgente de esta lista |
| ~~**CxP operativa** (gastos del día a día — luz, agua, alquiler)~~ | **Pasa a v1.5.0** (Fase 4, gasto directo sobre la CxP genérica) — ya no es radar | — |

---

## Notas de Implementación

- **El orden de este documento asume que `v1.1.0.md` se completa primero.** Ninguna fase de aquí empieza antes de que el registro de módulos, `Plan`, y el desacople de Contabilidad estén cerrados — son la base sobre la que se apoya todo lo demás (CxC/CxP operativas sin Contabilidad, `sales.ncf` como flag, y ahora también `installation_modules`/`Plan` como el dato que Multi-tenant reutiliza 1:1 en v1.3.0).
- **Impuestos (v1.2.0) sigue siendo el único punto real de bloqueo duro** sobre Devoluciones y Compras — nada de eso cambia con el adelanto de Multi-tenant. Multi-tenant, en cambio, ya no bloquea ni es bloqueado por ninguna versión de negocio (v1.4.0/v1.5.0) — es infraestructura ortogonal, adelantada por presión real de clientes esperando, no por prioridad de humor (ver corrección al inicio del documento, con la evidencia técnica que la sostiene).
- **La migración de `DataTable` se planeó originalmente sin versión fija (gradual, módulo por módulo), pero terminó absorbida de una sola vez en v1.3.0 Fase 0** — cambio real de plan, no un error de este documento: una vez que el Panel de Súper Admin necesitó el motor nuevo desde el día uno, migrar solo eso y dejar las 24 tablas viejas conviviendo en dos motores era peor que migrar todo junto (ver v1.3.0 §7).
- **PostgreSQL queda descartado como prerequisito de Multi-tenant, no descartado para siempre.** Si en el futuro aparece una razón concreta y específica (no "se ve más profesional") — full-text search avanzado, un tipo de dato que MySQL no cubra bien — esa conversación se da en ese momento, con esa justificación puntual, no como parte de esta decisión.
- **`docs/promts.md` es solo la libreta personal de trabajo diario** (bugs sueltos, ideas sin madurar) — cualquier cosa ahí que llegue a tener alcance y versión asignada se documenta acá, no en los dos lugares a la vez.
