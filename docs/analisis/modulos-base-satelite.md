# Arquitectura Base/Satélite — Roadmap hacia un modelo estilo Odoo

**Fecha:** 2026-07-29
**Contexto del pivote:** ZertixPOS nació como ERP a medida para una distribuidora de hielo (rutas, freezers en campo — ver [sobre-ingenieria-modulos.md](sobre-ingenieria-modulos.md)). Ese proyecto se soltó; ahora se retoma con un cliente nuevo y distinto (embasadora de agua) y otro perfil totalmente distinto en cartera (vendedor ambulante). El objetivo ya no es "el ERP de la empresa de hielo" — es un producto instalable por cliente, con un núcleo mínimo que **cualquier** negocio de venta necesita, y funcionalidad adicional que se activa **solo si el cliente la necesita**.

**Restricción real de esta semana:** hay una instalación para la embasadora que se quiere probar el viernes. Este documento es el mapa de qué hacer y en qué orden — **no** implica parar ni retrasar esa entrega. La sección "Qué hacer antes del viernes" al final es explícita sobre eso: nada de lo de aquí bloquea la demo.

**Modelo de negocio de despliegue (aclarado en esta conversación):** por ahora, cada cliente es una instalación individual en su propio subdominio (no multi-tenant todavía). La idea del wizard de instalación es que, al instalar, se elijan los módulos satélite que aplican a ese cliente (vía flags), dejando guardado qué está activo. Cuando más adelante se construya el multi-tenant (`stancl/tenancy`, ver `docs/promts.md`), ese mismo registro de flags por instalación se exporta e importa como el registro de módulos activos por tenant — mismo modelo de datos, solo cambia de "una fila en la instalación" a "una fila por tenant".

---

## 1. Definiciones

**Revisión (2026-08-13):** este documento originalmente definía solo dos niveles (base / satélite). Se agrega un tercer nivel intermedio — **núcleo flexible** — porque en la práctica hay módulos que *todo* negocio necesita disponible por defecto (a diferencia de un satélite, que no viene incluido si no se pidió) pero que un negocio concreto puede decidir que no le aplica (a diferencia de un base fijo, que nunca es opt-out). Ver el detalle completo en `docs/features/v1.1.0.md`, Fase 10 (§10.4 en adelante) — ese es el documento de implementación; este sigue siendo la referencia de la taxonomía.

- **Módulo base fijo (core):** lo que el sistema necesita para funcionar como lo que es — un punto de venta con catálogo y cobro. Sin esto no hay producto. No es opt-out en ninguna instalación, no tiene flag, nunca aparece en ninguna pantalla de configuración.
- **Núcleo flexible (nuevo):** funcionalidad que *todo* negocio tiene disponible desde el día uno (encendida por defecto, sin importar el Plan — no es algo que el Plan venda) pero que el dueño puede apagar si genuinamente no le aplica a su operación (ej. un negocio 100% servicios no necesita Inventario; uno 100% contado no necesita CxC). A diferencia de un satélite, nunca empieza apagado. A diferencia de un base fijo, sí se puede apagar — y al apagarse, el módulo **se pausa en el tiempo**: deja de registrar información nueva de ese dominio (no solo se oculta la UI), pero nada de lo ya existente se borra. Ver la tabla completa en §2.1.5.
- **Módulo satélite:** agrega o profundiza funcionalidad sobre uno o más módulos base/flexibles. Casi siempre depende de un módulo base para alimentarse (usa sus datos/eventos) pero el base nunca depende del satélite. Empieza **apagado** por defecto y solo se activa si el Plan del cliente lo incluye y el dueño lo enciende explícitamente.
- Dentro de un módulo satélite puede haber **variantes** (la idea que mencionaste de "POS pero para cafetería" vs. POS actual, o "POS simplificado" para vendedor ambulante) — eso es una satelización de segundo nivel: no es un módulo nuevo, es un *modo* dentro de un módulo existente, activado por el mismo mecanismo de flags.

Regla de dependencia (para que el mapa de abajo tenga sentido): **un módulo base fijo nunca importa/consulta un módulo flexible o satélite**. Hoy esa regla se rompe en varios puntos (ver §3) — es la deuda a limpiar antes de que activar/desactivar flags sea seguro. Entre **satélite y flexible** sí puede haber una dependencia dura real (ej. Compras necesita Inventario encendido para tener sentido) — se resuelve con **bloqueo explícito, nunca cascada automática ni un resolver genérico de dependencias** (evita la explosión combinatoria de validar 2^n combinaciones); ver la tabla corta y mantenida a mano en `docs/features/v1.1.0.md` §10.7.

---

## 2. Inventario de módulos actuales, clasificado

Basado en `routes/admin/*`, `app/Models/*`, y el menú real en [app-layout.blade.php](resources/views/components/app-layout.blade.php).

### 2.1 Módulos BASE FIJOS (obligatorios en toda instalación, sin flag, no opt-out)

| Módulo | Qué hace | Por qué es base fijo |
|---|---|---|
| **Auth / Usuarios / Roles** (Spatie Permission) | Login, control de acceso, roles y permisos por acción | Ningún sistema multiusuario funciona sin esto |
| **Configuración General** (`ConfiguracionGeneral`, `TipoPago`, `Impuesto`, `TaxIdentifierType`) | Datos de la empresa, moneda, tasa de impuesto, métodos de pago | Todo el resto (ventas, POS, facturación) lee de aquí |
| **Productos** (`Product`, `Category`, `Unit`) | Catálogo vendible: nombre, precio, SKU, unidad, categoría | Sin catálogo no hay qué vender |
| **Clientes — núcleo** (`Client`, solo campos: nombre, contacto, tax_id, crédito, estado) | Identifica a quién se le vende/factura | Necesario incluso para "Consumidor Final" walk-in |
| **Ventas / POS — núcleo** (`Sale`, `SaleItem`, `SalePayment`, POS Workspace, `PosTerminal`/`PosSession` mínimos) | El producto en sí: cobrar, generar la venta | Es la razón de ser de ZertixPOS |
| **Devoluciones / Reembolsos** (`Sale::cancel()`/flujo de devolución, sin comprobante fiscal) | Anular o devolver una venta mal hecha | No es "un módulo que alguien no usa" como los de §2.1.5 — es la red de seguridad para corregir un error de cobro. Hasta el negocio más simple la necesita alguna vez; apagarla es un riesgo operativo, no una preferencia de UX, por eso se queda fija y no pasa a flexible |

**Nota sobre Geo:** `Country`/`State` hoy son genéricos (multi-país) pero el ítem "Depuración Geográfica" de `docs/promts.md` ya pide dejarlo fijo a RD. Una vez hecho eso, Geo deja de ser un "módulo" propiamente — pasa a ser configuración fija dentro de Configuración General, no algo que activar/desactivar.

### 2.1.5 Núcleo FLEXIBLE (encendido por defecto en todo Plan, apagable por el dueño)

**Revisión (2026-08-13):** estos 4 módulos vivían en §2.1 como base fijo hasta esta ronda. Se reclasifican porque, a diferencia de Auth/Productos/Ventas, sí hay negocios reales que legítimamente no los usan — y forzarlos encendidos les agrega ruido de UI (alertas de stock a quien no vende físico, campos de crédito a quien solo vende de contado) sin aportarles nada. La diferencia con un satélite es que **no dependen del Plan** — vienen encendidos siempre, sin importar qué se contrató, porque no son parte de lo que el Plan vende, son preferencia de operación.

| Módulo | Flag | Por qué es candidato a apagarse | Qué se congela al apagar (nunca se borra nada) |
|---|---|---|---|
| **Inventario** (`Warehouse`, `InventoryStock`, `InventoryMovement`) | `inventory.tracking` | Negocio 100% servicios (barbería, consultoría) — nunca necesita saber "cuánto stock queda" | `SaleService`/`InventoryMovementService` dejan de crear `InventoryMovement`/tocar `InventoryStock` para cualquier producto (no solo se salta la *validación* de stock, se salta la *escritura*). Menú Inventario y columnas/badges de stock ocultos en Productos y POS. Rutas de `routes/admin/inventory.php` gateadas por `module:inventory.tracking`. |
| **Cuentas por Cobrar + Abonos operativos** (`Receivable`) | `sales.receivables` | Negocio 100% contado, nunca da crédito | El formulario de venta deja de ofrecer `payment_type=credit`. No se crean `Receivable` nuevos. `credit_limit`/`payment_terms` del Cliente y `dias_gracia_mora` de Configuración General pasan a `disabled` (visibles, no editables) en vez de ocultarse — son datos que pueden existir de cuando el módulo estaba activo. |
| **Cuentas por Pagar operativas** (deudas/gastos del día a día del dueño — luz, agua, alquiler, adelantos sin nómina formal) | `sales.payables` | Negocio que lleva sus gastos aparte del sistema | No se crean registros de gasto/deuda nuevos. Módulo **pendiente de construir** (ver roadmap §5, ítem 3.5) — nace ya con este flag desde el día uno, para no repetir el error de Contabilidad (construirlo fijo y desacoplarlo después). |
| **Cotizaciones** (`Quote`, `QuoteItem`) | `sales.quotes` | Negocio de venta directa que nunca cotiza antes de vender | No se crean `Quote` nuevos. Rutas de Cotizaciones gateadas. |

Confirmado contra código para los cuatro: hoy `PaymentService::createPayment()` acopla el abono de CxC a un `JournalEntry` obligatorio y a `AccountingAccount::where('code','1.1.01')` hardcodeado — ese es el bug a corregir (REQ-02.8 en `v1.1.0.md`), no el diseño deseado; el abono en sí (CxC) es independiente del asiento contable que genera (satélite, `accounting.advanced`). `QuoteService` solo depende de `Product`/`SaleService`, no de Contabilidad ni NCF, así que apagar Cotizaciones no arrastra nada más. Detalle completo de implementación (middleware, vista de toggles, reglas de dependencia con satélites) en `docs/features/v1.1.0.md` Fase 10, §10.4 en adelante — este documento se queda como la referencia de la taxonomía, no repite el detalle de construcción.

### 2.2 Módulos SATÉLITE (opt-in por instalación)

| Módulo | Qué hace | Depende de (base) | Encaja con qué perfil | Estado actual |
|---|---|---|---|---|
| **NCF / Fiscal RD** (`Sales/Ncf/*`: `NcfType`, `NcfSequence`, `NcfLog`) | Comprobantes fiscales dominicanos, secuencias, validación RNC | Ventas | Cualquier negocio formal en RD que facture con NCF | **Ya es el ejemplo correcto**: gateado por `general_config()->usa_ncf`, aislado en su propio namespace. Es la plantilla a replicar para todo lo demás. |
| **Contabilidad formal** (`AccountingAccount`, `JournalEntry`, `JournalItem`, `DocumentType` correlativos, y el **asiento derivado** de un abono vía `accounting_account_roles`) | Partida doble, plan de cuentas, asientos automáticos por rol de cuenta | Ventas, CxC/CxP (como dato de entrada, no como dueño de su flujo) | Solo el cliente que explícitamente pide contabilidad formal / tiene contador que la exige dentro del sistema | Analizado a fondo en [sobre-ingenieria-modulos.md §1](sobre-ingenieria-modulos.md); acoplado por código a códigos de cuenta hardcodeados en `PaymentService`/`ReceivableService` — **ya no incluye el abono en sí** (eso es CxC/CxP base, ver arriba), solo la posición contable que ese abono genera si el módulo está activo — **hay que desacoplar antes de poder apagarlo con un flag de forma segura** (ver §3) |
| **`sales.delivery_points`** — Puntos de Venta / Clientes Físicos de Ruta (`PointOfSale`, `BusinessType`) | Geolocalización del colmado/negocio, dirección física, días de visita, tipo de negocio | Clientes (núcleo) | **Casi todos.** La embasadora de agua para sus camiones, un cliente tipo "Plaza Merengue", y también el vendedor ambulante para agendar y registrar los colmados fijos de su ruta | Hoy vive mezclado con `Equipment` bajo un solo satélite de "Activos en Campo" — separado en esta revisión porque el perfil que lo usa es mucho más amplio que el que usa equipos en préstamo |
| **`clients.field_assets`** — Gestión de Equipos en Préstamo/Alquiler (`Equipment`, `EquipmentType`) | Neveras, freezers, anaqueles, exhibidores, dispensadores con número de serie, en comodato | Clientes (núcleo) | **Solo distribuidoras/envasadoras grandes** que firman contratos de comodato y prestan activos caros para amarrar al cliente (la embasadora, un cliente tipo "Plaza Merengue"). El vendedor ambulante no tiene capital ni espacio para prestar equipos — este flag va en `false` para ese perfil | Hoy vive dentro del núcleo de `Clients`, cargado siempre — separar de `sales.delivery_points` (arriba), no son el mismo caso de uso aunque compartían el mismo satélite hasta esta revisión |
| **Inventario avanzado — multi-almacén, transferencias, tomas físicas, mermas** | Más de un almacén, transferencias entre almacenes con estados, conteos, pérdidas | Inventario (núcleo) | Negocios con más de un punto de almacenamiento (la embasadora, si tiene planta + camión/ruta) | Transferencias/tomas físicas/mermas están **pendientes de construir** (`docs/promts.md`, sección Logística) — construirlas ya de una vez como satélite, no como parte del núcleo |
| **Rutas y Entregas** | Planificación de rutas de reparto | Ventas, Clientes, `sales.delivery_points` | Justo el caso de la embasadora de agua (reparto a domicilio) y el del hielo original | El link "Rutas y Entregas" en el sidebar **no tiene ruta real detrás** (`/rutas` no está registrado en `routes/`) — es un placeholder de la época del hielo. Hay que decidir: revivirlo como satélite real (probablemente lo necesite la embasadora) o quitarlo del menú mientras no exista. |
| **`purchases.vendors`** — Compras / Proveedores formales | Órdenes de compra, catálogo de proveedores, y una CxP que nace de una compra real (no de un gasto suelto) | Productos, `sales.payables` (flexible), y **dependencia dura de `inventory.tracking`** (flexible) — una orden de compra recibida existe para aumentar stock, no tiene sentido si Inventario está apagado | Cualquier negocio que reabastece con proveedores formales y quiere trazabilidad de orden de compra | **No existe todavía** (pendiente en `docs/promts.md`). La CxP *operativa* (gastos del día a día) ya es núcleo flexible (ver §2.1.5) — este satélite es solo para cuando además se quiere Proveedor + Orden de Compra formal. La dependencia dura con `inventory.tracking` se resuelve con **bloqueo explícito al guardar, nunca cascada automática** (ver `v1.1.0.md` §10.7, tabla corta mantenida a mano para evitar explosión combinatoria) |
| **`sales.credit_notes_b04`** — Nota de Crédito Fiscal (B04) | Emitir el comprobante fiscal de una devolución | `sales.ncf` | Solo negocios con NCF activo que necesiten devolver con comprobante fiscal formal | **No existe todavía.** Depende explícitamente de `sales.ncf` — sin NCF activo no se puede emitir un B04, pero **la devolución/reembolso en sí es base** (ver tabla de módulos base arriba) y funciona sin este satélite. Si `sales.ncf` está apagado, `sales.credit_notes_b04` debe forzarse apagado también (dependencia dura, no solo sugerida). |
| **POS — variantes/modos** | Ej.: POS simplificado sin sesión/terminal formal para vendedor ambulante, o un modo tipo "comanda" para cafetería | Ventas/POS (núcleo) | Cada perfil de cliente necesita un modo distinto del mismo módulo base | Idea a futuro, todavía no diseñado — es la satelización "de segundo nivel" que mencionaste (módulos dentro de módulos) |

### 2.3 Hallazgo transversal

En los tres casos gordos (Contabilidad, Clientes→Activos en Campo, Inventario→Warehouse), el patrón de fuga es el mismo que ya se documentó en `sobre-ingenieria-modulos.md`: **el módulo satélite se autoinyecta en el núcleo** en vez de que el núcleo emita un evento y el satélite escuche si está activo. Ejemplos concretos:

- `Warehouse::booted()` crea una cuenta contable automáticamente — Inventario (base) llamando a Contabilidad (satélite).
- `SaleService::generateSaleAccountingEntry()` se ejecuta siempre, sin chequear si el modo contable avanzado está activo — Ventas (base) llamando a Contabilidad (satélite) incondicionalmente.
- `Client` carga relaciones a `PointOfSale`/`Equipment` en su núcleo — Clientes (base) cargando Activos en Campo (satélite) por defecto.

Esto es exactamente lo que el mecanismo de flags de §4 tiene que resolver — no alcanza con esconder el menú, hay que cortar la dependencia real en el código.

---

## 3. Por qué "solo esconder el menú" no alcanza

Ya viste con NCF y con el bug de `apply_tax` en el formulario clásico que un flag mal propagado deja huecos. Con Contabilidad el riesgo es peor: si simplemente se oculta el menú de "Plan de Cuentas"/"Asientos" pero `SaleService` sigue llamando a `AccountingAccount::where('code','4.1')->firstOrFail()` por debajo, una instalación "modo simple" **sigue dependiendo de que existan esas cuentas contables sembradas**, y una venta revienta si no están. El flag tiene que cortar la llamada, no solo la vista. Por eso el roadmap de abajo empieza por desacoplar antes de poder apagar nada con seguridad — ya está detallado como paso 1 del roadmap de Contabilidad en `sobre-ingenieria-modulos.md`.

---

## 4. Mecanismo técnico (flags + middleware)

**Estado real (2026-08-13):** todo lo de esta sección ya dejó de ser una propuesta — `config/modules.php`, `InstallationModule`, `module_enabled()` y el middleware `EnsureModuleEnabled`/`module:<key>` ya existen y ya están aplicados a `accounting.advanced`, `sales.delivery_points` y `clients.field_assets` (`routes/admin/accounting.php`, `routes/admin/clients.php`). Lo que falta, documentado en `docs/features/v1.1.0.md` Fase 10, es extender el mismo middleware a los 4 módulos que pasan a núcleo flexible en §2.1.5 (`inventory.tracking`, `sales.receivables`, `sales.payables`, `sales.quotes`), que hoy no tienen ningún gate de ruta porque siempre fueron base fijo.

Diseñado para que hoy sea "una instalación, una fila de flags" y mañana sea "una fila de flags por tenant" sin rediseñar nada — es el mismo shape de dato.

### 4.1 Registro estático de módulos (`config/modules.php`)

Un archivo de config (no tabla) que declara **todos** los módulos que el código sabe servir, independientemente de si están activos:

```php
return [
    'sales.delivery_points' => [
        'label' => 'Puntos de Venta / Clientes de Ruta',
        'category' => 'satellite',
        'depends_on' => ['clients.core'],
        'route_prefixes' => ['admin/clients/pos', 'admin/clients/businessTypes'],
    ],
    'clients.field_assets' => [
        'label' => 'Equipos en Préstamo/Comodato',
        'category' => 'satellite',
        'depends_on' => ['clients.core'],
        'route_prefixes' => ['admin/clients/equipments', 'admin/clients/equipmentTypes'],
    ],
    'accounting.advanced' => [
        'label' => 'Contabilidad formal (partida doble)',
        'category' => 'satellite',
        'depends_on' => ['sales.core'],
        // OJO: no incluye receivables/payables — CxC y CxP operativas son núcleo flexible, se gatean aparte (categoría 'base_flexible', ver abajo)
        'route_prefixes' => ['admin/accounting/journal_entries', 'admin/accounting/accounts', 'admin/accounting/payments'],
    ],
    'purchases.vendors' => [
        'label' => 'Compras / Proveedores formales',
        'category' => 'satellite',
        'depends_on' => ['inventory.tracking'], // dependencia dura satélite→flexible: BLOQUEO explícito al guardar, nunca cascada (ver §2.1.5 y v1.1.0.md §10.7)
        'route_prefixes' => ['admin/purchases'],
    ],
    'sales.ncf' => [ /* ... */ ], // ya existe, migrado desde el viejo usa_ncf
    'sales.credit_notes_b04' => [
        'label' => 'Nota de Crédito Fiscal (B04)',
        'category' => 'satellite',
        'depends_on' => ['sales.ncf'], // dependencia dura satélite→satélite: sí cascadea automático (pocos casos, bajo riesgo)
        'route_prefixes' => ['admin/sales/credit-notes'],
    ],

    // Núcleo flexible (revisión 2026-08-13, ver §2.1.5) — encendidos por defecto en TODO plan,
    // nunca gateados por Plan::assignTo() (se filtra solo category==='satellite'), el dueño los apaga
    // desde "Funcionalidades del Sistema" si no le aplican a su operación.
    'inventory.tracking' => [
        'label' => 'Control de Inventario',
        'category' => 'base_flexible',
        'route_prefixes' => ['admin/inventory'],
    ],
    'sales.receivables' => [
        'label' => 'Cuentas por Cobrar',
        'category' => 'base_flexible',
        'route_prefixes' => ['admin/accounting/receivables'],
    ],
    'sales.payables' => [
        'label' => 'Cuentas por Pagar',
        'category' => 'base_flexible',
        'route_prefixes' => ['admin/accounting/payables'], // aún no existe, ver roadmap §5 ítem 3.5
    ],
    'sales.quotes' => [
        'label' => 'Cotizaciones',
        'category' => 'base_flexible',
        'route_prefixes' => ['admin/sales/quotes'],
    ],
];
```

Esto es lo que hace posible, a futuro, un **wizard de instalación**: lee este archivo, muestra checkboxes por módulo satélite, y guarda la selección.

### 4.2 Persistencia de qué está activo

Una tabla nueva `installation_modules` (`module_key`, `is_enabled`) — no una columna más en `ConfiguracionGeneral` (que ya está sobrecargada y es singleton). Ventaja de tabla dedicada: es literalmente la tabla que se exporta/importa entera cuando llegue el multi-tenant (se le agrega `tenant_id` y ya).

Helper análogo a `general_config()`:

```php
function module_enabled(string $key): bool
{
    static $cache = null;
    $cache ??= InstallationModule::pluck('is_enabled', 'module_key');
    return (bool) ($cache[$key] ?? false);
}
```

### 4.3 Middleware de bloqueo por ruta

`app/Http/Middleware/EnsureModuleEnabled.php`, alias `module:<key>` (mismo patrón que `permission:`), aplicado a los grupos de rutas en `routes/admin/*` (por ejemplo, envolver todo `routes/admin/accounting/*` con `->middleware('module:accounting.advanced')`). Si el módulo está apagado, **404**, no 403 — no hay que revelar que la funcionalidad existe pero está apagada, y evita que alguien intente adivinar permisos.

Esto responde directamente a lo que pediste: "middleware para evitar que entren por rutas". Los controladores no cambian; el middleware corta antes de que el request llegue.

### 4.4 Bloqueo de vista (menú)

En `app-layout.blade.php`, cada `@can(...)` de un módulo satélite se envuelve además en `@if(module_enabled('...'))`. Igual patrón que ya existe hoy con `@if($config->usa_ncf)` en la sección NCF del sidebar (línea 192) — no es un patrón nuevo, es generalizar el que ya se usó ahí.

### 4.5 Por qué no tocar el `glob()` de carga de rutas

`RouteServiceProvider` hoy carga **todas** las rutas de `routes/admin/*.php` con un `glob()` incondicional. La tentación es condicionar el `require` al flag — **no conviene**: si un módulo satélite está apagado pero su ruta nombrada no existe, cualquier `route('accounting.dashboard')` suelto en una vista (helpers, breadcrumbs, links compartidos) revienta con `RouteNotFoundException` en vez de fallar limpio. Es más seguro registrar siempre las rutas y bloquear el *acceso* con el middleware — el approach de 4.3.

---

## 5. Roadmap general (orden de ejecución)

Este es el hilo conductor que conecta con el roadmap específico de Contabilidad ya escrito en `sobre-ingenieria-modulos.md` — ese es un caso particular de este mecanismo general.

1. **(Ya, sin bloquear el viernes)** No agregar más módulos nuevos sin registrarlos mentalmente como base o satélite — aunque el flag técnico no exista todavía, decide desde ya en qué categoría cae cada cosa nueva de `docs/promts.md` (Compras, Devoluciones, Transferencias → todas satélite).
2. **Semana post-viernes:** construir el mecanismo técnico de §4 (config de módulos, tabla `installation_modules`, helper, middleware) — pero con **todos los flags en `true` por defecto**. Cero cambio de comportamiento, solo se instala el riel. Se prueba que nada se rompe.
3. **Desacoplar Contabilidad** (roadmap ya detallado en `sobre-ingenieria-modulos.md §1`, paso 1: quitar los `AccountingAccount::where('code', ...)` hardcodeados de `SaleService`, `ReceivableService`, `PaymentService`, el listener POS y el dashboard). Incluye separar `PaymentService::createPayment()` en dos pasos: el abono operativo (siempre corre, actualiza `Receivable`/CxP) y el asiento contable derivado (solo si `accounting.advanced` está activo). Es el más urgente de desacoplar porque es el que más se autoinyecta.
3.5. **Construir CxP operativa** como módulo base, simétrico a CxC — deudas/gastos del dueño con su salida de caja, sin Proveedor ni orden de compra.
4. **Desacoplar Clientes→Activos en Campo**: sacar `PointOfSale`/`BusinessType` (→ `sales.delivery_points`) y `Equipment`/`EquipmentType` (→ `clients.field_assets`) del núcleo de `Client` — dos flags separados, no uno solo, porque el perfil que usa cada uno es distinto (casi todos necesitan puntos de venta de ruta; solo las distribuidoras grandes prestan equipos). Off por defecto en instalaciones nuevas.
5. **Desacoplar Inventario→Warehouse→Contabilidad**: sacar `createAccountingAccount()` del `booted()` de `Warehouse`.
6. **Apagar por defecto** en el registro de módulos: `accounting.advanced`, `clients.field_assets`, `sales.quotes` (evaluar caso a caso por cliente), dejar todo lo demás (§2.1) siempre en `true`.
7. **Construir el wizard de instalación** que lee `config/modules.php` y escribe `installation_modules` — este es el que mencionaste, ligado 1:1 al mismo dato que luego migra a multi-tenant.
8. **Nuevos módulos de `docs/promts.md`** (Compras, Devoluciones/NC, Transferencias, Tomas Físicas) se construyen **ya** dentro de este modelo — nacen con su flag, no se integran al núcleo.
9. **Multi-tenant** (`stancl/tenancy`, PostgreSQL con esquemas): en este punto `installation_modules` se convierte en `tenant_modules` (misma forma + `tenant_id`), y cada instalación individual existente se migra pasando su fila de flags + sus datos al esquema de su tenant. Como los flags y las dependencias base/satélite ya están resueltos desde el paso 6, esta migración es de datos, no de arquitectura.

---

## 6. Ajuste al caso de los dos clientes actuales

- **Embasadora de agua (instalación viernes):** perfil parecido al original de hielo (producción + reparto), así que probablemente **sí** necesite Rutas y Entregas más adelante. Cotizaciones para pedidos mayoristas ya está disponible por defecto (es base, no hay que activar nada). No necesita Activos en Campo (equipos prestados) salvo que también preste dispensadores — a confirmar con el cliente. Contabilidad formal: probablemente no, a menos que tengan contador que la pida explícitamente — usar CxC/CxP operativas + NCF si facturan formal.
- **Vendedor ambulante:** el caso más "mínimo" posible — valida que el núcleo (§2.1) realmente pueda operar solo, sin ningún satélite encendido. Es también el mejor caso de prueba para la futura "variante de POS simplificado" (§2.2, última fila): sesión/terminal/PIN de POS tal como existen hoy son demasiada ceremonia para una sola persona vendiendo desde un carrito.

## 7. Qué hacer antes del viernes (para que quede explícito)

**Nada de lo de arriba es prerrequisito de la demo.** Para el viernes, la instalación de la embasadora se hace igual que cualquier despliegue hoy: se instala el sistema completo en su subdominio y, si hace falta una interfaz limpia para la demo, se ocultan a mano en el sidebar/permisos de esa instalación los módulos que no le sirven (Activos en Campo, Contabilidad formal si no la piden) — sin tocar código de negocio ni el mecanismo de flags, que todavía no existe. El trabajo de este documento arranca **después**, con la instalación ya funcionando en producción para ese cliente.
