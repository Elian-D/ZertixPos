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
| **Cuentas por Cobrar simples** (`Receivable`, solo el registro de saldo/estado, sin `Payment`/`JournalEntry` formales) | Saber quién debe y cuánto quedó pendiente de una venta a crédito | Es información operativa mínima, no contabilidad formal — cualquier negocio que vende "fiado" la necesita |

**Nota sobre Geo:** `Country`/`State` hoy son genéricos (multi-país) pero el ítem "Depuración Geográfica" de `docs/promts.md` ya pide dejarlo fijo a RD. Una vez hecho eso, Geo deja de ser un "módulo" propiamente — pasa a ser configuración fija dentro de Configuración General, no algo que activar/desactivar.

### 2.2 Módulos SATÉLITE (opt-in por instalación)

| Módulo | Qué hace | Depende de (base) | Encaja con qué perfil | Estado actual |
|---|---|---|---|---|
| **NCF / Fiscal RD** (`Sales/Ncf/*`: `NcfType`, `NcfSequence`, `NcfLog`) | Comprobantes fiscales dominicanos, secuencias, validación RNC | Ventas | Cualquier negocio formal en RD que facture con NCF | **Ya es el ejemplo correcto**: gateado por `general_config()->usa_ncf`, aislado en su propio namespace. Es la plantilla a replicar para todo lo demás. |
| **Contabilidad formal** (`AccountingAccount`, `JournalEntry`, `JournalItem`, `Payment`, `DocumentType` correlativos) | Partida doble, plan de cuentas, asientos, pagos con recibo formal | Ventas, CxC | Solo el cliente que explícitamente pide contabilidad formal / tiene contador que la exige dentro del sistema | Analizado a fondo en [sobre-ingenieria-modulos.md §1](sobre-ingenieria-modulos.md); acoplado por código a códigos de cuenta hardcodeados — **hay que desacoplar antes de poder apagarlo con un flag de forma segura** (ver §3) |
| **Clientes — Activos en Campo** (`PointOfSale`, `Equipment`, `EquipmentType`, `BusinessType`) | Puntos de venta del cliente + equipos (freezers) prestados con serial | Clientes (núcleo) | Distribución con activos prestados en campo — el caso original de hielo. La embasadora de agua *podría* reusar esto si presta enfriadores/dispensadores, pero el vendedor ambulante no | Hoy vive dentro del núcleo de `Clients`, cargado siempre |
| **Cotizaciones** (`Quote`, `QuoteItem`) | Presupuestos formales antes de la venta | Ventas, Clientes | Negocios con venta mayorista/por encargo (la embasadora podría necesitarlo para pedidos grandes); el vendedor ambulante no | Ya está en namespace propio `Sales/Quotes` |
| **Inventario avanzado — multi-almacén, transferencias, tomas físicas, mermas** | Más de un almacén, transferencias entre almacenes con estados, conteos, pérdidas | Inventario (núcleo) | Negocios con más de un punto de almacenamiento (la embasadora, si tiene planta + camión/ruta) | Transferencias/tomas físicas/mermas están **pendientes de construir** (`docs/promts.md`, sección Logística) — construirlas ya de una vez como satélite, no como parte del núcleo |
| **Rutas y Entregas** | Planificación de rutas de reparto | Ventas, Clientes | Justo el caso de la embasadora de agua (reparto a domicilio) y el del hielo original | El link "Rutas y Entregas" en el sidebar **no tiene ruta real detrás** (`/rutas` no está registrado en `routes/`) — es un placeholder de la época del hielo. Hay que decidir: revivirlo como satélite real (probablemente lo necesite la embasadora) o quitarlo del menú mientras no exista. |
| **Compras / Proveedores** | Órdenes de compra, proveedores | Inventario, Productos | Cualquier negocio que reabastece formalmente | **No existe todavía** (pendiente en `docs/promts.md`) — construirlo ya dentro del modelo de flags desde el día 1 |
| **Devoluciones / Notas de Crédito (B04)** | Reembolsos con comprobante fiscal | Ventas, NCF | Cualquier negocio con NCF activo que necesite anular/devolver | **No existe todavía** (pendiente) — depende de NCF, debería heredar su flag |
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
    'clients.field_assets' => [
        'label' => 'Clientes: Puntos de Venta y Equipos',
        'category' => 'satellite',
        'depends_on' => ['clients.core'],
        'route_prefixes' => ['admin/clients/pos', 'admin/clients/equipments', 'admin/clients/businessTypes', 'admin/clients/equipmentTypes'],
    ],
    'accounting.advanced' => [
        'label' => 'Contabilidad formal (partida doble)',
        'category' => 'satellite',
        'depends_on' => ['sales.core'],
        'route_prefixes' => ['admin/accounting/journal_entries', 'admin/accounting/accounts', 'admin/accounting/document_types', 'admin/accounting/payments'],
    ],
    'sales.quotes' => [ /* ... */ ],
    'sales.ncf' => [ /* ... */ ], // ya existe como usa_ncf, se migra a este mismo registro
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
3. **Desacoplar Contabilidad** (roadmap ya detallado en `sobre-ingenieria-modulos.md §1`, paso 1: quitar los `AccountingAccount::where('code', ...)` hardcodeados de `SaleService`, `ReceivableService`, `PaymentService`, el listener POS y el dashboard). Es el más urgente de desacoplar porque es el que más se autoinyecta.
4. **Desacoplar Clientes→Activos en Campo**: sacar `PointOfSale`/`Equipment` del núcleo de `Client`, mover sus rutas/vistas a su propio flag `clients.field_assets`, off por defecto en instalaciones nuevas.
5. **Desacoplar Inventario→Warehouse→Contabilidad**: sacar `createAccountingAccount()` del `booted()` de `Warehouse`.
6. **Apagar por defecto** en el registro de módulos: `accounting.advanced`, `clients.field_assets`, `sales.quotes` (evaluar caso a caso por cliente), dejar todo lo demás (§2.1) siempre en `true`.
7. **Construir el wizard de instalación** que lee `config/modules.php` y escribe `installation_modules` — este es el que mencionaste, ligado 1:1 al mismo dato que luego migra a multi-tenant.
8. **Nuevos módulos de `docs/promts.md`** (Compras, Devoluciones/NC, Transferencias, Tomas Físicas) se construyen **ya** dentro de este modelo — nacen con su flag, no se integran al núcleo.
9. **Multi-tenant** (`stancl/tenancy`, PostgreSQL con esquemas): en este punto `installation_modules` se convierte en `tenant_modules` (misma forma + `tenant_id`), y cada instalación individual existente se migra pasando su fila de flags + sus datos al esquema de su tenant. Como los flags y las dependencias base/satélite ya están resueltos desde el paso 6, esta migración es de datos, no de arquitectura.

---

## 6. Ajuste al caso de los dos clientes actuales

- **Embasadora de agua (instalación viernes):** perfil parecido al original de hielo (producción + reparto), así que probablemente **sí** necesite Rutas y Entregas más adelante, y tal vez Cotizaciones para pedidos mayoristas. No necesita Activos en Campo (equipos prestados) salvo que también preste dispensadores — a confirmar con el cliente. Contabilidad formal: probablemente no, a menos que tengan contador que la pida explícitamente — usar CxC simple + NCF si facturan formal.
- **Vendedor ambulante:** el caso más "mínimo" posible — valida que el núcleo (§2.1) realmente pueda operar solo, sin ningún satélite encendido. Es también el mejor caso de prueba para la futura "variante de POS simplificado" (§2.2, última fila): sesión/terminal/PIN de POS tal como existen hoy son demasiada ceremonia para una sola persona vendiendo desde un carrito.

## 7. Qué hacer antes del viernes (para que quede explícito)

**Nada de lo de arriba es prerrequisito de la demo.** Para el viernes, la instalación de la embasadora se hace igual que cualquier despliegue hoy: se instala el sistema completo en su subdominio y, si hace falta una interfaz limpia para la demo, se ocultan a mano en el sidebar/permisos de esa instalación los módulos que no le sirven (Activos en Campo, Contabilidad formal si no la piden) — sin tocar código de negocio ni el mecanismo de flags, que todavía no existe. El trabajo de este documento arranca **después**, con la instalación ya funcionando en producción para ese cliente.
