# Arquitectura Base/Satélite — Roadmap hacia un modelo estilo Odoo

**Fecha:** 2026-07-29
**Contexto del pivote:** ZertixPOS nació como ERP a medida para una distribuidora de hielo (rutas, freezers en campo — ver [sobre-ingenieria-modulos.md](sobre-ingenieria-modulos.md)). Ese proyecto se soltó; ahora se retoma con un cliente nuevo y distinto (embasadora de agua) y otro perfil totalmente distinto en cartera (vendedor ambulante). El objetivo ya no es "el ERP de la empresa de hielo" — es un producto instalable por cliente, con un núcleo mínimo que **cualquier** negocio de venta necesita, y funcionalidad adicional que se activa **solo si el cliente la necesita**.

**Restricción real de esta semana:** hay una instalación para la embasadora que se quiere probar el viernes. Este documento es el mapa de qué hacer y en qué orden — **no** implica parar ni retrasar esa entrega. La sección "Qué hacer antes del viernes" al final es explícita sobre eso: nada de lo de aquí bloquea la demo.

**Modelo de negocio de despliegue (aclarado en esta conversación):** por ahora, cada cliente es una instalación individual en su propio subdominio (no multi-tenant todavía). La idea del wizard de instalación es que, al instalar, se elijan los módulos satélite que aplican a ese cliente (vía flags), dejando guardado qué está activo. Cuando más adelante se construya el multi-tenant (`stancl/tenancy`, ver `docs/promts.md`), ese mismo registro de flags por instalación se exporta e importa como el registro de módulos activos por tenant — mismo modelo de datos, solo cambia de "una fila en la instalación" a "una fila por tenant".

---

## 1. Definiciones

- **Módulo base (core):** lo que el sistema necesita para funcionar como lo que es — un punto de venta con catálogo, cobro e inventario mínimo. Sin esto no hay producto. No es opt-out en ninguna instalación.
- **Módulo satélite:** agrega o profundiza funcionalidad sobre uno o más módulos base. Casi siempre depende de un módulo base para alimentarse (usa sus datos/eventos) pero el base nunca depende del satélite. Se activa por instalación/cliente, vía flag.
- Dentro de un módulo satélite puede haber **variantes** (la idea que mencionaste de "POS pero para cafetería" vs. POS actual, o "POS simplificado" para vendedor ambulante) — eso es una satelización de segundo nivel: no es un módulo nuevo, es un *modo* dentro de un módulo existente, activado por el mismo mecanismo de flags.

Regla de dependencia (para que el mapa de abajo tenga sentido): **un módulo base nunca importa/consulta un módulo satélite**. Hoy esa regla se rompe en varios puntos (ver §3) — es la deuda a limpiar antes de que activar/desactivar flags sea seguro.

---

## 2. Inventario de módulos actuales, clasificado

Basado en `routes/admin/*`, `app/Models/*`, y el menú real en [app-layout.blade.php](resources/views/components/app-layout.blade.php).

### 2.1 Módulos BASE (obligatorios en toda instalación)

| Módulo | Qué hace | Por qué es base |
|---|---|---|
| **Auth / Usuarios / Roles** (Spatie Permission) | Login, control de acceso, roles y permisos por acción | Ningún sistema multiusuario funciona sin esto |
| **Configuración General** (`ConfiguracionGeneral`, `TipoPago`, `Impuesto`, `TaxIdentifierType`) | Datos de la empresa, moneda, tasa de impuesto, métodos de pago | Todo el resto (ventas, POS, facturación) lee de aquí |
| **Productos** (`Product`, `Category`, `Unit`) | Catálogo vendible: nombre, precio, SKU, unidad, categoría | Sin catálogo no hay qué vender |
| **Clientes — núcleo** (`Client`, solo campos: nombre, contacto, tax_id, crédito, estado) | Identifica a quién se le vende/factura | Necesario incluso para "Consumidor Final" walk-in y para crédito básico |
| **Inventario — núcleo** (`Warehouse` con 1 almacén por defecto, `InventoryStock`, `InventoryMovement`) | Existencia y salida de producto por venta | Cualquier negocio que vende físico necesita saber si hay stock, aunque sea 1 solo almacén |
| **Ventas / POS — núcleo** (`Sale`, `SaleItem`, `SalePayment`, POS Workspace, `PosTerminal`/`PosSession` mínimos) | El producto en sí: cobrar, generar la venta, descontar inventario | Es la razón de ser de ZertixPOS |
| **Cuentas por Cobrar + Abonos operativos** (`Receivable`, y el abono en sí: monto/fecha/método/referencia contra `current_balance`, sin `JournalEntry` obligatorio) | Saber quién debe, cuánto, y poder registrar que pagó — total o parcial | Es información y flujo operativo mínimo, no contabilidad formal. Confirmado contra código: hoy `PaymentService::createPayment()` fuerza un `JournalEntry` y una cuenta contable hardcodeada (`1.1.01`) para poder abonar — eso es el bug a corregir (ver REQ-02.8 en `v1.1.0.md`), no el diseño correcto. Una CxC sin forma de abonarla no sirve, tal como no sirve acumular sin nunca poder saldar |
| **Cuentas por Pagar operativas** (deudas/gastos del día a día del dueño — luz, agua, alquiler, adelantos a empleados sin nómina formal — y su salida de caja) | Saber a quién le debe el negocio y poder saldarlo, simétrico a CxC | Mismo criterio que CxC: control de flujo de caja básico. No depende de Proveedor formal ni de orden de compra — no existe todavía, se construye ya como base, no como parte de Compras |
| **Cotizaciones** (`Quote`, `QuoteItem` — venta en estado borrador) | Presupuesto antes de la venta, convertible a `Sale` (`QuoteService::convertToSale()`) | Confirmado contra código: `QuoteService` solo depende de `Product` y `SaleService`, no importa Contabilidad ni NCF. Es fricción innecesaria tratarlo como opt-in — cualquier negocio que cotiza antes de vender (mayorista, pedidos grandes, hasta un colmado cotizando un picoteo) lo necesita disponible siempre. Las variantes premium (plantillas con logo, envío automático por WhatsApp, recordatorios de vencimiento) no están construidas todavía — cuando existan, se gatean como *features* dentro del módulo (mecanismo distinto, más fino que el 404-por-ruta), no como el módulo completo apagado/encendido |
| **Devoluciones / Reembolsos** (`Sale::cancel()`/flujo de devolución, sin comprobante fiscal) | Anular o devolver una venta, revertir stock y CxC | Cualquier negocio necesita poder deshacer una venta mal hecha, tenga o no NCF activo |

> **Aclaración explícita (revisada en esta ronda):** Cuentas por Cobrar — **incluyendo el abono operativo, no solo el saldo** — es **base**. Se confirmó contra el código real que hoy `PaymentService::createPayment()` acopla el abono a un `JournalEntry` obligatorio y a `AccountingAccount::where('code','1.1.01')` hardcodeado; si `accounting.advanced` se apagara tal como está hoy el código, CxC quedaría de solo lectura — eso es exactamente el bug a resolver, no el diseño deseado. El asiento contable *derivado* de un abono sigue siendo satélite (depende de `accounting.advanced` y del mapeo `accounting_account_roles`), pero el abono en sí no. Misma lógica para su contraparte: **Cuentas por Pagar operativas** (gastos/deudas del día a día del dueño) también son **base** — no dependen de Proveedor ni de orden de compra. Solo cuando el negocio quiere **Compras/Proveedores formales** (órdenes de compra, catálogo de proveedores, una CxP que nace de una compra real en vez de un gasto suelto) entra el satélite `purchases.vendors` (ver tabla de satélites).

**Nota sobre Geo:** `Country`/`State` hoy son genéricos (multi-país) pero el ítem "Depuración Geográfica" de `docs/promts.md` ya pide dejarlo fijo a RD. Una vez hecho eso, Geo deja de ser un "módulo" propiamente — pasa a ser configuración fija dentro de Configuración General, no algo que activar/desactivar.

### 2.2 Módulos SATÉLITE (opt-in por instalación)

| Módulo | Qué hace | Depende de (base) | Encaja con qué perfil | Estado actual |
|---|---|---|---|---|
| **NCF / Fiscal RD** (`Sales/Ncf/*`: `NcfType`, `NcfSequence`, `NcfLog`) | Comprobantes fiscales dominicanos, secuencias, validación RNC | Ventas | Cualquier negocio formal en RD que facture con NCF | **Ya es el ejemplo correcto**: gateado por `general_config()->usa_ncf`, aislado en su propio namespace. Es la plantilla a replicar para todo lo demás. |
| **Contabilidad formal** (`AccountingAccount`, `JournalEntry`, `JournalItem`, `DocumentType` correlativos, y el **asiento derivado** de un abono vía `accounting_account_roles`) | Partida doble, plan de cuentas, asientos automáticos por rol de cuenta | Ventas, CxC/CxP (como dato de entrada, no como dueño de su flujo) | Solo el cliente que explícitamente pide contabilidad formal / tiene contador que la exige dentro del sistema | Analizado a fondo en [sobre-ingenieria-modulos.md §1](sobre-ingenieria-modulos.md); acoplado por código a códigos de cuenta hardcodeados en `PaymentService`/`ReceivableService` — **ya no incluye el abono en sí** (eso es CxC/CxP base, ver arriba), solo la posición contable que ese abono genera si el módulo está activo — **hay que desacoplar antes de poder apagarlo con un flag de forma segura** (ver §3) |
| **`sales.delivery_points`** — Puntos de Venta / Clientes Físicos de Ruta (`PointOfSale`, `BusinessType`) | Geolocalización del colmado/negocio, dirección física, días de visita, tipo de negocio | Clientes (núcleo) | **Casi todos.** La embasadora de agua para sus camiones, un cliente tipo "Plaza Merengue", y también el vendedor ambulante para agendar y registrar los colmados fijos de su ruta | Hoy vive mezclado con `Equipment` bajo un solo satélite de "Activos en Campo" — separado en esta revisión porque el perfil que lo usa es mucho más amplio que el que usa equipos en préstamo |
| **`clients.field_assets`** — Gestión de Equipos en Préstamo/Alquiler (`Equipment`, `EquipmentType`) | Neveras, freezers, anaqueles, exhibidores, dispensadores con número de serie, en comodato | Clientes (núcleo) | **Solo distribuidoras/envasadoras grandes** que firman contratos de comodato y prestan activos caros para amarrar al cliente (la embasadora, un cliente tipo "Plaza Merengue"). El vendedor ambulante no tiene capital ni espacio para prestar equipos — este flag va en `false` para ese perfil | Hoy vive dentro del núcleo de `Clients`, cargado siempre — separar de `sales.delivery_points` (arriba), no son el mismo caso de uso aunque compartían el mismo satélite hasta esta revisión |
| **Inventario avanzado — multi-almacén, transferencias, tomas físicas, mermas** | Más de un almacén, transferencias entre almacenes con estados, conteos, pérdidas | Inventario (núcleo) | Negocios con más de un punto de almacenamiento (la embasadora, si tiene planta + camión/ruta) | Transferencias/tomas físicas/mermas están **pendientes de construir** (`docs/promts.md`, sección Logística) — construirlas ya de una vez como satélite, no como parte del núcleo |
| **Rutas y Entregas** | Planificación de rutas de reparto | Ventas, Clientes, `sales.delivery_points` | Justo el caso de la embasadora de agua (reparto a domicilio) y el del hielo original | El link "Rutas y Entregas" en el sidebar **no tiene ruta real detrás** (`/rutas` no está registrado en `routes/`) — es un placeholder de la época del hielo. Hay que decidir: revivirlo como satélite real (probablemente lo necesite la embasadora) o quitarlo del menú mientras no exista. |
| **`purchases.vendors`** — Compras / Proveedores formales | Órdenes de compra, catálogo de proveedores, y una CxP que nace de una compra real (no de un gasto suelto) | Inventario, Productos, CxP operativa (base) | Cualquier negocio que reabastece con proveedores formales y quiere trazabilidad de orden de compra | **No existe todavía** (pendiente en `docs/promts.md`). La CxP *operativa* (gastos del día a día) ya es base (ver arriba) — este satélite es solo para cuando además se quiere Proveedor + Orden de Compra formal. Depende de si el cliente realmente lo necesita — construirlo ya dentro del modelo de flags si se aborda. |
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

## 4. Mecanismo técnico propuesto (flags + middleware)

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
        // OJO: no incluye receivables/payables — CxC y CxP operativas son base, se gatean aparte (o no se gatean)
        'route_prefixes' => ['admin/accounting/journal_entries', 'admin/accounting/accounts', 'admin/accounting/payments'],
    ],
    'purchases.vendors' => [
        'label' => 'Compras / Proveedores formales',
        'category' => 'satellite',
        'depends_on' => ['inventory.core'],
        'route_prefixes' => ['admin/purchases'],
    ],
    'sales.ncf' => [ /* ... */ ], // ya existe como usa_ncf, se migra a este mismo registro
    'sales.credit_notes_b04' => [
        'label' => 'Nota de Crédito Fiscal (B04)',
        'category' => 'satellite',
        'depends_on' => ['sales.ncf'], // dependencia dura: apagado si sales.ncf está apagado
        'route_prefixes' => ['admin/sales/credit-notes'],
    ],
    // OJO: 'sales.quotes' NO va aquí — Cotizaciones es base, siempre activo, sin middleware `module:`
    // ...
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
