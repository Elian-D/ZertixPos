# ZertixPOS — Módulo POS Interfaz

**RAMA PADRE:**
`feat/pos-system`

**Objetivo:** Implementar el sistema completo de Punto de Venta con soporte para cotizaciones (persistentes y no persistentes), descuentos configurables, integración con `SalesService`, interfaz reactiva completa, cierre de caja y módulo de impresión. La arquitectura se basa en configuración centralizada (`pos_settings`) que controla el comportamiento de todas las fases posteriores.

> **Estado actual:** Fases 1 a 7 y 9 (cierre de caja) completas. Pendientes: Fase 8 (ventas pausadas), Fase 10 (módulo de impresión) y Fase 11 (correcciones pre-release: `is_active`, Independencia de Cajas, Producto/Servicio).

---

## Arquitectura del Módulo

### Conceptos Clave

**Configuración como prerequisito:**
El POS no arranca sin una fila válida en `pos_settings`. Esta tabla define qué funcionalidades están habilitadas (descuentos, clientes rápidos, impresión automática), evitando condicionales dispersos en el código.

**Sesión como unidad de trabajo:**
Cada jornada de venta vive dentro de una `PosSession`. Sin sesión abierta, el terminal no puede procesar ventas. Esto permite auditoría completa: qué se vendió, quién lo vendió, cuándo empezó y terminó, y con cuánto dinero.

**Cotizaciones como paso previo a venta:**
El sistema soporta dos tipos: cotizaciones efímeras (PDF/ticket sin persistencia) para consultas rápidas de precio, y cotizaciones persistentes (`quotes`) que actúan como preventa y pueden convertirse a venta en un solo clic mediante `SalesService`.

**Integración con contexto POS:**
Toda venta originada desde el POS lleva `pos_terminal_id`, `pos_session_id` y `sale_origin = 'pos'`, lo que permite reportes segmentados y auditoría precisa frente a ventas del backoffice.

---

## Fase 1 — Configuración Base del POS
**Rama:** `feat/pos-config`

### 1.1 — Migración y Modelo: `pos_settings`

- [x] **Migración `create_pos_settings_table`:**
    - `allow_item_discount` (bool)
    - `allow_global_discount` (bool)
    - `max_discount_percentage` (decimal)
    - `allow_quick_customer_creation` (bool)
    - `default_walkin_customer_id` (FK nullable)
    - `allow_quote_without_save` (bool)
    - `auto_print_receipt` (bool)
    - `receipt_size` (enum: 58mm | 80mm)

- [x] **Modelo `App\Models\Sales\Pos\PosSetting`** con relaciones, casts y scopes.

- [x] **Validación de arranque:** El POS verifica la existencia de esta configuración antes de renderizar cualquier vista. Si no existe, redirige al administrador a la pantalla de configuración inicial.

`ESTADO: LISTA`

---

## Fase 2 — Registro Rápido de Cliente
**Rama:** `feat/pos-quick-customers`

### 2.1 — Formulario Modal Ultraligero

- [x] **Modal dentro del POS** con campos mínimos:
    - `name` (required)
    - `document_number` (nullable)
    - `document_type` (`cedula` | `rnc` | `none`)
    - `phone` (nullable)
    - `address` (nullable)

- [x] **Reglas de negocio:**
    - Sin créditos, sin categorías, sin límites, sin campos extra.
    - Crea un registro real en la tabla `clients` usando el mismo modelo `Client`.
    - La creación está sujeta a `allow_quick_customer_creation` en `pos_settings`.

- [x] **DTO `QuickClientDTO`** para mapear el payload del modal al servicio.

**En cola futura:**
```
feat/client-dgii-lookup
```
Conectará este modal a la API de la DGII para autocompletar datos por RNC/cédula.

`ESTADO: LISTA`

---

## Fase 3 — Infraestructura de Terminales y Sesiones
**Rama:** `feat/pos-terminals` / `feat/pos-sessions` / `feat/pos-cash-movements`

### 3.1 — Seguridad de Terminales
**Rama:** `feat/pos-terminal-security` / `feat/pos-access-control` / `feat/pos-session-modals`

- [x] **Campos de seguridad en `pos_terminals`:**
    ```php
    $table->string('access_pin')->nullable();
    $table->boolean('requires_pin')->default(true);
    ```

- [x] **Almacenamiento seguro:** El PIN se guarda hasheado con `bcrypt`. Nunca en texto plano.

- [x] **Control de acceso:** Validación del PIN al iniciar sesión en el terminal. `PosTerminalLockController` gestiona la verificación (ver **Fase 7.7 (Revisión)** para el rediseño del bloqueo).

### 3.2 — Sesiones POS
**Rama:** `feat/pos-sessions`

- [x] Modelo `PosSession` con estados, relaciones y scopes.
- [x] `PosSessionService` con métodos `open`, `close` y validaciones de integridad.
- [x] `PosSessionCatalogService` para alimentar filtros y listados.

### 3.3 — Movimientos de Caja
**Rama:** `feat/pos-cash-movements`

- [x] Modelo `PosCashMovement` con tipos de movimiento (entrada/salida/apertura/cierre).
- [x] `PosCashMovementService` con registro y validación.
- [x] `PosCashMovementCatalogService` para historial y reportes.

`ESTADO: LISTA`

---

## Fase 4 — Motor de Cotizaciones (Arquitectura Pro)
**Rama:** `feat/pos-quotes`

> **Fase actual en desarrollo.**

El sistema soporta dos tipos de cotizaciones con comportamientos completamente distintos.

### Tipo 1: Quote Rápida (No Persistente)

No se guarda en base de datos. Genera un PDF o ticket 80mm al instante y se descarta. Ideal para responder "¿cuánto sale esto?" sin comprometer ningún recurso.

### Tipo 2: Quote Persistente (Preventa)

Se guarda en las tablas `quotes` y `quote_items`. Puede convertirse a venta con un botón. Cuando se convierte: `status = converted`, `sale_id = X`.

---

### 4.1 — Infraestructura y Prevención de Conflictos (JS)

- [x] Instalación de Livewire.
- [x] Configuración de Assets (`@livewireStyles` y `@livewireScriptConfig`).
- [x] Control de Instancia Alpine: Configurado vía `window.Alpine` en `app.js` para evitar duplicidad.
- [x] Registro de Permisos: `QuotePermissionsSeeder` creado y ejecutado.

### 4.2 — Base de Datos (Estructura Multiorigen)

- [x] **Migración `quotes`:**
    - Relaciones: `customer_id`, `user_id`, `pos_terminal_id` (nullable), `pos_session_id` (nullable), `sale_id` (nullable).
    - Control: `origin` (`backoffice` | `pos`), `status` (`draft`, `approved`, `converted`, `expired`, `cancelled`).
    - Finanzas: `subtotal`, `discount_total`, `total`.
    - Vencimiento: `expires_at` y `notes`.
- [x] **Migración `quote_items` (Snapshot):**
    - Campos: `quote_id`, `product_id`, `quantity`, `price` (precio del momento), `discount_amount`, `discount_percentage`, `subtotal`.
- [x] **Modelos:** `Quote` y `QuoteItem` creados con constantes y estilos.

### 4.3 — Lógica de Negocio: QuoteService & Comandos

- [x] **Método `store(array $data)`:**
    - Recálculo Real: El servicio busca los precios en la DB y suma los totales (Seguridad).
    - Transacción: Envuelve la creación en `DB::transaction()`.
- [x] **Método `convertToSale(Quote $quote)`:**
    - Validación de Estado: Verifica que no esté expirada ni ya convertida.
    - Inyección a `SalesService`: Mapea los ítems al formato requerido.
    - Cierre de Ciclo: Actualiza estado y vincula `sale_id`.
- [x] **Comando `ExpireQuotes`:**
    - Comando de consola para marcar como `expired` las cotizaciones vencidas.
    - **Comando Programado:** Registrado en `routes/console.php`.
    ```bash
    php artisan schedule:run  # Ejecuta ExpireQuotes según configuración
    ```

### 4.4 — Interfaz Reactiva (Livewire + Alpine)

- [x] **Componente `Pos\QuoteBuilder`:**
    - Estado: Array `$items` para el carrito local.
    - Reactividad: Uso de `wire:key` para evitar conflictos con el DOM.
    - Sincronización: Método `recalculateTotals()` para feedback visual rápido.
- [x] **Componente `Pos\QuoteSearch`:**
    - Implementación de `debounce(300ms)` en la búsqueda de productos.

### 4.5 — Backend & Lógica de Filtros (Pipelines)

- [x] **Filtros Individuales** (`App/Filters/Sales/Quotes/`):
    - `QuoteCustomerFilter`, `QuoteStatusFilter`, `QuoteDateFilter`, `QuoteUserFilter`, `QuoteSearchFilter`, `QuoteOriginFilter`.
- [x] **Orquestador:** `QuoteFilters` registrando los pipelines para la tabla AJAX.
- [x] **Catálogos:** `QuoteCatalogService` para alimentar selectores de filtros.

### 4.6 — HTTP, Rutas y Tablas

- [x] **FormRequests:** `StoreQuoteRequest` y `UpdateQuoteRequest` (Inmutabilidad si ya es venta).
- [x] **Rutas:** Endpoints CRUD y acciones de estado (`approve`, `cancel`, `convert`, `print`).
- [x] **Controlador:** `QuoteController` con integración de servicios.
- [x] **Definición de Tabla:** `QuoteTable` para gestión de columnas dinámicas.

### 4.7 — Frontend Administrativo

- [x] **JS de Filtros:** Configurado con mapeo de chips y formato de fecha.
- [x] **Vista Index:** Estructura completa con `window.filterSources` y Toolbar.
- [x] **Partials de Tabla:** Renderizado de columnas y badges de estado.
- [x] **Vista Create/Edit:** Integración del builder y manejo de eventos de éxito.
- [x] **Vista Show:** Implementación de visualización detallada.
- [x] **Correcciones de Impuestos:** Ajuste en el cálculo de impuestos sobre descuentos en PDF/Ticket.
- [x] **Mejora visual:** Unificación de formato de visualización entre Facturas y Cotizaciones.

### 4.8 — Impresión y Documentos

- [x] **`QuotePrintService`:** Generación de PDF (Carta) y Ticket (80mm).
- [x] **Formatos:** Vistas Blade sin lógica fiscal (NCF) para cumplimiento legal.

### 4.9 — Próximos Pasos: Integración POS

- [ ] Botón "Cotizar" en la interfaz de venta del POS (uso de `QuoteService`).
- [ ] Modal de "Cargar Cotización" en el POS para conversión rápida a venta.

---

## Fase 5 — Motor de Descuentos Configurable
**Rama:** `feat/pos-discounts`

Sistema híbrido que soporta descuentos por ítem y descuentos globales, ambos validados contra la configuración en `pos_settings`.

### 5.1 — Reglas de Negocio

- [x] Si `allow_item_discount = false` → UI de descuento por ítem bloqueada visualmente.
- [x] Si `allow_global_discount = false` → campo de descuento global oculto.
- [x] Si el porcentaje supera `max_discount_percentage` → error de validación antes de procesar.

### 5.2 — Persistencia

- [x] **Campos en `sale_items`:**
    ```php
    $table->decimal('discount_amount', 10, 2)->default(0);
    $table->decimal('discount_percentage', 5, 2)->default(0);
    ```
    Los descuentos nunca se infieren del total. Se guardan explícitamente para auditoría y reversión.

### 5.3 — Fuente de la verdad y Validaciones Integrales

- [x] Modificar `ticket.blade.php` y `full.blade.php` para que utilicen el `total_amount` y `discount_total` reales de `sales` en vez de heredarlo de la cotización original, garantizando la consistencia en el cálculo final de los impuestos (ITBIS) y el neto a cobrar.
- [x] Adecuar el `QuoteService` para que en el método `convertToSale` mapee los valores correspondientes al descuento global e individual por ítem de forma explícita hacia el registro de la venta (`sales` y `sale_items`).
- [x] Corregir `QuoteBuilder` para que calcule correctamente el descuento global y realice la validación de inventario (*stock disponible*) en tiempo real.
- [x] Ajustar la interfaz reactiva en `quote-builder.blade.php` con el fin de reflejar visualmente el impacto del descuento total sobre el subtotal neto consolidado.
- [x] Incorporar los campos de entrada para descuentos (porcentaje y monto fijo) en la vista de creación *fallback* tradicional de ventas de mercancía.
- [x] Actualizar la validación de integridad matemática y lógica financiera en el `StoreSaleRequest` para alinearlo con el envío del monto bruto de Alpine.js:

```php
// --- VALIDACIÓN DE INTEGRIDAD MATEMÁTICA EN STORESALEREQUEST ---
$subtotalBruto = 0;
$descuentoTotalCalculado = 0;

foreach ($this->items as $index => $item) {
    $itemBruto = ($item['quantity'] * $item['price']);
    $itemDescuento = $item['discount_amount'] ?? 0;
    
    $subtotalBruto += $itemBruto;
    $descuentoTotalCalculado += $itemDescuento;

    // Validación de stock
    $stock = InventoryStock::where('warehouse_id', $this->warehouse_id)
        ->where('product_id', $item['product_id'])
        ->first();

    if (!$stock || $stock->quantity < $item['quantity']) {
        $available = $stock ? $stock->quantity : 0;
        $validator->errors()->add("items.{$index}.quantity", "Stock insuficiente. Disponible: {$available}.");
    }
}

// 1. Validamos que el total_amount enviado corresponda al BRUTO real (suma de precio * cantidad)
if (abs($subtotalBruto - $this->total_amount) > 0.01) {
    $validator->errors()->add('total_amount', 'El monto bruto no coincide con la suma de los productos.');
}

// 2. Validamos que el descuento global enviado no sea manipulado
if (abs($descuentoTotalCalculado - ($this->discount_total ?? 0)) > 0.01) {
    $validator->errors()->add('discount_total', 'El descuento reportado no coincide con la suma de descuentos aplicados.');
}

// 3. Calculamos el Neto real a cobrar (Bruto - Descuento + ITBIS)
$subtotalNeto = $subtotalBruto - $descuentoTotalCalculado;
$totalFinalNeto = $subtotalNeto;

if ($this->boolean('apply_tax')) {
    $taxRate = general_config()->impuesto->valor ?? 0;
    $taxAmount = $subtotalNeto * ($taxRate / 100);
    $totalFinalNeto = $subtotalNeto + $taxAmount;
}

```

* [x] Modificar las validaciones de las pasarelas de pago (`cash` y `credit`) dentro del Request para comparar las transacciones de caja contra el valor neto calculado final en lugar del monto bruto original:

```php
// --- VALIDACIÓN DE EFECTIVO ---
if ($this->payment_type === Sale::PAYMENT_CASH) {
    $recibido = (float) $this->cash_received;
    $totalCobrar = (float) $totalFinalNeto;

    if ($recibido < $totalCobrar) {
        $validator->errors()->add('cash_received', 'El efectivo recibido es menor al total neto a pagar.');
    }
}

// --- LÓGICA DE CRÉDITO Y CUENTAS POR COBRAR ---
if ($this->payment_type === Sale::PAYMENT_CREDIT && $client) {
    if ($client->id == 1 || $client->name === 'Consumidor Final') {
        $validator->errors()->add('payment_type', 'El Consumidor Final no puede procesar ventas a crédito.');
    }

    $categoryCode = $client->estadoCliente->category->code ?? null;
    if (in_array($categoryCode, ['BLOQUEO_TOTAL', 'FINANCIERO_RESTRICTO'])) {
        $validator->errors()->add('client_id', "Crédito denegado: El cliente tiene un estado de {$client->estadoCliente->nombre}.");
    }

    $nuevoSaldoProyectado = $client->balance + $totalFinalNeto;
    if ($nuevoSaldoProyectado > $client->credit_limit) {
        $disponible = number_format($client->credit_limit - $client->balance, 2);
        $validator->errors()->add('total_amount', "Límite de crédito superado. Disponible: \${$disponible}.");
    }
}

```

* [x] Agregar los bloques informativos de auditoría de descuentos y desglose de totales modificados en el modal de detalles de venta (`sales.partials.modals`).

---

## Fase 6 — Integración POS con SalesService
**Rama:** `feat/pos-sales-integration`

### 6.1 — Contexto POS

- [x] **DTO `PosContext`** extendido con:
    ```php
    pos_terminal_id: int
    pos_session_id: int
    sale_origin: 'pos' | 'backoffice'
    is_walkin_customer: bool
    ```

- [x] **`SalesService::create()`** acepta el `PosContext` como parámetro opcional. Si está presente, persiste los campos de contexto en la venta.

- [x] Las ventas sin contexto POS mantienen `sale_origin = 'backoffice'` por defecto para compatibilidad hacia atrás.

---

# FASE 7 COMPLETA REAL

---

**Rama:** `feat/pos-interface`

# 7.0 — POS Entry Flow

* [x] Ruta `sales.pos.index`
* [x] Componente `PosTerminalLobby`
* [x] Selector visual de terminales
* [x] Estado visual de terminal
* [x] Modal PIN Livewire
* [x] Modal apertura de caja
* [x] Reanudación de sesión activa
* [x] Validación de permisos por terminal
* [x] Expiración automática de sesión (ventana deslizante de 30 min en `CheckTerminalAccess`)
* [x] Heartbeat de actividad (`POST sales.pos.heartbeat`, ping cada 2 min desde el Workspace)

---

# 7.1 — POS Workspace

* [x] Ruta `sales.pos.workspace`
* [x] Componente padre `PosWorkspace` (Livewire, mismo patrón que `PosTerminalLobby`)
* [x] Persistencia centralizada del estado (Alpine `posWorkspace()` alimentado por el catálogo/cliente/config precargados desde el componente Livewire)
* [x] Sincronización Alpine + Livewire (Livewire como shell/datos iniciales + eventos `pos-client-created`; carrito y checkout 100% Alpine para evitar round-trips por tecla)
* [x] Sistema de eventos internos (`window` custom events: `pos-client-created`; Breeze `open-modal`/`close-modal`)

---

# 7.2 — Product Engine

* [x] Buscador/grid de productos embebido en el Workspace (decisión de arquitectura: catálogo del almacén de la terminal precargado como JSON en vez de un componente Livewire `PosProductSearch` con debounce por tecla — cero round-trips de red por búsqueda, más resiliente en la terminal física)
* [x] debounce inteligente (filtrado reactivo Alpine, sin llamadas a servidor)
* [x] scanner barcode (lectura por lector USB/Bluetooth tipo teclado: `Enter` sobre coincidencia exacta de SKU)
* [x] búsqueda SKU
* [x] búsqueda nombre
* [x] categorías rápidas
* [x] grid optimizado (limitado a 60 resultados renderizados; sin librería de virtualización adicional)

---

# 7.3 — Cart Engine

* [x] Carrito embebido en `posWorkspace()` (decisión de arquitectura: mismo motivo que 7.2, no se crea un componente Livewire `PosCart` separado)
* [x] actualización reactiva
* [x] cantidades inline
* [x] descuentos por item
* [x] descuentos globales (nuevo — no existía en ningún UI previo; se distribuye proporcionalmente entre `discount_amount` de los ítems para no requerir cambios en el backend)
* [x] validación stock realtime (visual, contra el stock precargado; el backend revalida siempre vía `StoreSaleRequest`)
* [x] cálculo ITBIS realtime (automático cuando `general_config()->usa_ncf` está activo)

---

# 7.4 — Checkout Engine

* [x] `PosCheckoutController` (HTTP + `StoreSaleRequest`, no Livewire directo — reutiliza toda la validación de integridad existente en vez de duplicarla)
* [x] multi-payment (pago dividido / varios métodos en una sola venta — `sale_payments` lo soporta pero el Workspace solo envía un método por venta)
* [x] efectivo (numpad + cálculo de cambio)
* [x] tarjeta / transferencia (selección de `tipo_pago`; recibido = total, sin cambio)
* [x] cálculo vuelto
* [x] integración `SalesService` (vía `PosContext::fromSession`, contexto resuelto en servidor)
* [x] impresión automática (abre el ticket de `InvoiceController::print` en pestaña nueva si `pos_config('auto_print_receipt')`)
* [x] limpieza transaccional del carrito (POST real + redirect recarga el Workspace con estado limpio, sin lógica manual de reset)

---

# 7.5 — Customer Engine

* [x] selector cliente
* [x] quick customer modal (reutiliza el modal existente; se corrigió un `Swal.fire` roto — SweetAlert2 nunca estuvo cargado — por un banner Alpine inline)
* [x] consumidor final automático (`pos_config('default_walkin_customer_id')` con fallback al cliente con `tax_id` genérico)
* [x] bloqueo crédito inválido (cliente moroso / walk-in no puede pagar a crédito, validado en cliente y servidor)
* [x] búsqueda rápida clientes (filtro Alpine sobre clientes operativos precargados)

---

# 7.6 — Numpad System

* [x] `<x-pos.numpad>` (componente Blade reutilizable, sin `x-data` propio — evalúa contra el scope Alpine del padre; no se creó como componente Livewire `PosNumpad` para mantenerlo sin round-trips)
* [x] control focus
* [x] input targeting (props `digit`/`clear`/`backspace` parametrizables)
* [x] reutilizable (sin uso activo por ahora — ver nota en el propio componente; se dejó listo para un futuro modo AIO 100% táctil)
* [x] soporte touch completo

---

# 7.7 — Session Security

> **Rediseñada.** La pantalla de lock dedicada (`lock.blade.php` + `PosAccessController`) se eliminó. Ver "Fase 7.7 (Revisión) — Eliminación del Lockscreen" más abajo para el diseño actual y el porqué.

* [x] auto-lock inactivity (timer Alpine de 10 min sin actividad; solo corre si `terminal->requiresPinVerification()` es true — sin PIN configurado no hay nada que proteger)
* [x] PIN revalidation (`CheckTerminalAccess` + `PosTerminalLobby::verifyPin()`, Livewire)
* [x] refresh sesión actividad (ventana deslizante de 30 min + heartbeat cada 2 min vía `PosTerminalLockController@heartbeat`)
* [x] reconexión segura (middleware responde 403 JSON `require_pin` para llamadas AJAX cuando expira la sesión de terminal)

---

## Fase 7.7 (Revisión) — Eliminación del Lockscreen

**Problema encontrado:** el botón "Bloquear" del Workspace llevaba a `lock.blade.php`, una pantalla de PIN dedicada. Al desbloquear ahí, `PosAccessController@verify` marcaba `session()->put("terminal_verified.{id}", ...)` y redirigía al **Lobby** — que, sin saber que la sesión ya estaba verificada, volvía a abrir su propio modal de PIN. Resultado: el cajero tecleaba el mismo PIN dos veces para una sola acción de desbloqueo. Además:

- El botón "Bloquear" aparecía **siempre**, incluso en terminales sin `access_pin` configurado (nada que bloquear).
- "Bloquear" era un simple `<a href>` de **navegación**, no una acción que invalidara nada en el servidor. Como `CheckTerminalAccess` solo redirige a pedir PIN cuando la marca de sesión `terminal_verified.{id}` **no existe o expiró**, y esa marca nunca se borraba al "bloquear", entrar de nuevo por URL directa a `/sales/pos/workspace/{terminal}` dejaba pasar igual — el bloqueo era puramente cosmético.

**Diagnóstico:** no hacía falta una pantalla ni un middleware nuevo. El middleware `CheckTerminalAccess` ya hacía exactamente lo que se necesitaba (bloquear acceso directo por ruta si no hay verificación vigente) — el bug era que nada disparaba una invalidación real de esa marca de sesión.

**Solución:**

- [x] **Eliminados:** `resources/views/sales/pos/lock.blade.php` y `app/Http/Controllers/Sales/Pos/PosAccessController.php` completos.
- [x] **Nuevo `PosTerminalLockController`** (reemplaza a `PosAccessController`) con responsabilidad reducida:
    - `lock(PosTerminal $pos_terminal)`: hace `session()->forget("terminal_verified.{id}")` y **redirige al Lobby** (`sales.pos.index`) con un flash informativo. El Lobby es ahora el **único punto de entrada al PIN** — no hay una segunda pantalla de bloqueo.
    - `verify(Request $request)`: se conserva (throttle `pos-pin` sin cambios) porque los modales de apertura/cierre de sesión del **backoffice** (`sales/pos/sessions/partials/modal-open.blade.php` y `modal-close.blade.php`) también verifican el PIN de una terminal de forma independiente del Lobby.
    - `heartbeat(Request $request)`: sin cambios de lógica, solo reubicado.
- [x] **`CheckTerminalAccess`** ahora redirige a `sales.pos.index` (Lobby) en vez de a la ruta de lock eliminada. Como ya invalidamos `terminal_verified.{id}` de verdad al bloquear, el middleware **sí** bloquea el acceso directo por URL — este era el bug real reportado ("bloqueé la caja 3 y por ruta me deja"), no algo que requiriera un middleware nuevo ni un overlay.
- [x] **`PosTerminalLobby::selectTerminal()`** ahora salta el modal de PIN si `session()->has("terminal_verified.{id}")` ya está vigente (ej. si el middleware te devolvió al Lobby por otra razón sin que la sesión haya expirado realmente) — evita el doble PIN sin reintroducir el problema original, porque `lock()` sí borra esa marca explícitamente antes de redirigir.
- [x] **Botón "Bloquear"** en el Workspace: oculto por completo si `$terminal->requiresPinVerification()` es `false` (nada que bloquear). Sigue apuntando a `sales.pos.lock`, ahora resuelto por `PosTerminalLockController`.
- [x] **Auto-lock por inactividad**: el timer de 10 min en el Workspace (`startInactivityWatch()`) ya no arranca si la terminal no requiere PIN.
- [x] **Ruta `sales.pos.lock`** conservada por nombre (mismo `name()`), pero ahora apunta a `PosTerminalLockController@lock` con el comportamiento nuevo (invalidar + ir al Lobby, sin vista propia).

---

# 7.8 — Capa Móvil (POS táctiles portátiles)

**Motivo:** el Workspace se usará en dispositivos POS móviles con impresora integrada (ej. Sunmi V2), pantallas de ~5-6". El layout de dos columnas fijas (catálogo + panel de checkout siempre visible) no cabe ahí. En vez de una vista separada, se construyó una capa responsiva **dentro del mismo `pos-workspace.blade.php`**: mismo estado de Alpine, dos presentaciones (`hidden lg:flex` para desktop, `lg:hidden` para el layout móvil), sin duplicar lógica de negocio.

- [x] **Categorías como bottom sheet** (no chips en fila): un botón abre una hoja inferior con la lista completa; ahorra el ancho horizontal para la grilla de productos.
- [x] **Grid de productos** a 2 columnas, mismo `filteredProducts`/`addItem()` que desktop.
- [x] **FAB de carrito** fijo abajo-derecha con badge de cantidad de ítems.
- [x] **Carrito como slide-over con 2 pestañas** (en vez de meter todo en un solo panel angosto):
    - **Productos**: listado completo del carrito (+/-, eliminar) + mini-total fijo abajo.
    - **Cobrar**: selector de cliente (bottom sheet propio), radios de NCF, descuento global, desglose de totales, botón que abre el **mismo modal de pago** que desktop.
- [x] **Selector de cliente con buscador** (bottom sheet + `filteredClients` por nombre/RNC). Antes el `<select>` de escritorio tampoco tenía buscador — se agregó el getter `filteredClients`/`clientSearch` de forma reutilizable, pero el `<select>` de desktop se dejó intacto (fuera de alcance de este cambio; es candidato a convertirse en combobox si se pide más adelante).
- [x] **Modal de pago único y compartido** (`pos-checkout-modal`): se movió fuera del `<form>` del panel de escritorio para que tanto el botón "Cobrar" de desktop como el "Confirmar Cobro" de la pestaña móvil lo abran por igual. El `<form id="pos-checkout-form">` con los inputs ocultos se queda donde estaba (dentro del panel desktop, invisible en móvil); el botón de submit del modal lo referencia vía atributo HTML `form="pos-checkout-form"` en vez de depender de anidamiento en el DOM — funciona aunque el form esté dentro de un contenedor `hidden` en la resolución activa.
- [x] **`[x-cloak]`** agregado en `app.css` (no existía en ningún lado del proyecto; ya se usaba la directiva sin efecto en varias vistas).

---

## Fase 7.9 — Refactor de Métodos de Pago

**Motivo:** el selector de "Método de Pago" mostraba opciones sin orden de uso real, y dos filas que nunca debieron ser seleccionables por el cajero:

- **"Crédito"** es un slug interno protegido (`TipoPago::CREDITO`, ver `isSystemProtected()`), usado solo por `sales/create.blade.php` para dejar rastro contable cuando `payment_type === 'credit'`. No representa un método de pago real — el crédito comercial ya es su propio flujo (`payment_type`), separado del selector de tipo de pago.
- **"Nota de Crédito Aplicada"** nunca tuvo uso en el código (grep confirmado); era ruido puro en el selector.

**Cambios:**

- [x] **`TipoPagoSeeder`**: reordenado por jerarquía real de caja — Efectivo, Tarjeta, Transferencia primero (uso diario); Depósito y Cheque después (requieren conciliación bancaria manual). **Cheque queda desactivado por defecto** (`estado = false`; el admin lo activa desde Configuración si lo necesita). **"Crédito"** se mantiene en la tabla (protegido, no se puede borrar) pero **siempre inactivo** — nunca aparece en un selector. **"Nota de Crédito Aplicada"** se desactiva explícitamente (no se borra físicamente: podría estar referenciada por ventas históricas).
- [x] **`TipoPago::PRIORITY_ORDER` + `TipoPago::sortByPriority()`**: helper centralizado que ordena cualquier colección de tipos de pago activos según la jerarquía; usado en `PosWorkspace.php` y `SaleCatalogService` (backoffice + cotizaciones), así ambos flujos muestran el mismo orden sin duplicar la lista.
- [x] **Referencia obligatoria para métodos no-efectivo**: tarjeta, transferencia, depósito o cheque ahora piden un campo "Referencia" (últimos dígitos, # de autorización, # de cheque — texto libre, sin formato estricto) antes de poder confirmar el cobro. Validado en `StoreSaleRequest` (solo si `tipo_pago` resuelto no es `efectivo`) y persistido en `sale_payments.reference` (columna que ya existía, sin usarse en el flujo de pago único). Efectivo no la pide: el dinero físico entra a la gaveta sin evidencia adicional que registrar.

---

## Fase 7.10 — Refactor de Vistas: Desktop/Móvil y Parciales Compartidos

**Motivo:** una auditoría (pedida explícitamente para revisar si `pos-workspace.blade.php` mezclaba dos interfaces completas) confirmó que sí: el archivo (1379 líneas) contenía el layout desktop (`hidden lg:flex`) y el layout móvil (`lg:hidden`, Fase 7.8) como dos árboles DOM paralelos, con el selector de NCF, el resumen de totales, la fila de carrito y el buscador de cliente **pegados dos veces casi idénticos** en vez de compartidos. Esto no era solo un problema de tamaño de archivo — ya había causado bugs reales en esta misma rama: el input de descuento por ítem faltante en el carrito móvil, y el botón "eliminar" sin fondo rojo en uno de los dos sitios. Cualquier cambio a esas piezas había que aplicarlo dos veces, y era fácil olvidar una.

**Diagnóstico verificado (no solo intuición):** se confirmó con grep exacto sobre el archivo, no solo lectura visual — el JS (`posWorkspace()`) estaba bien: un solo estado Alpine compartido por ambas vistas, sin duplicar lógica de negocio. El problema era 100% de HTML/marcado. El único `<form id="pos-checkout-form">` real vivía en el bloque desktop; el botón "Confirmar Cobro" del móvil lo submitea a distancia vía el atributo HTML `form="pos-checkout-form"` (confirmado, no solo sospechado) — los radios NCF y el input de RNC del bloque móvil no son decorativos, sí mutan el mismo estado real (`formData`, `ncfChoice`), solo que no llevan sus propios `<input type="hidden">`.

**Solución — división en subvistas, todas bajo el mismo `x-data` raíz** (`@include` es una inclusión de servidor / concatenación de string antes de llegar al navegador, así que no rompe el estado de Alpine compartido con tal de que las subvistas queden anidadas dentro del elemento con `x-data="posWorkspace(...)"`):

```
resources/views/livewire/sales/pos/pages/
├── pos-workspace.blade.php          (orquestador: x-data raíz, header, <script>, @includes)
└── pos-workspace/
    ├── desktop.blade.php            (layout ≥lg, antes líneas 74-364)
    ├── mobile.blade.php             (layout <lg, antes líneas 366-746)
    ├── client-modal.blade.php       (selector de cliente desktop, antes 748-783)
    ├── checkout-modal.blade.php     (modal de pago, ya compartido antes — solo reubicado)
    ├── success-modal.blade.php      (modal "Venta Hecha", ya compartido antes — solo reubicado)
    └── partials/
        ├── cart-item.blade.php      (fila de carrito; prop `touch` cambia cantidad
        │                             editable con <input> vs. solo lectura con <span>
        │                             + botones más grandes — diferencia real de UX
        │                             táctil, no un descuido de copy-paste)
        ├── ncf-selector.blade.php   (radios NCF + captura/verificación de RNC; props
        │                             `showLabel` y `rncInputBg` para las diferencias
        │                             de contexto que sí son intencionales)
        ├── totals-summary.blade.php (prop `detailed`: false=compacto con Total nomás
        │                             (desktop, el desglose completo ya se repite en el
        │                             modal de pago), true=con Descuento/ITBIS inline
        │                             (móvil, es la única vista del desglose antes de
        │                             tocar "Confirmar Cobro"))
        ├── client-search-input.blade.php  (ícono + input de búsqueda; prop `autofocus`)
        └── client-results-list.blade.php  (resultados + estado vacío; prop `closeAction`
                                             para cerrar el picker correcto tras seleccionar)
```

- [x] **Consistencia recuperada de paso:** al unificar `ncf-selector.blade.php`, la tarjeta de confirmación de RNC verificado ahora siempre muestra la línea `RNC ... · estado` (antes el bloque móvil la omitía — otro síntoma de la misma duplicación, no un cambio de diseño deliberado).
- [x] **Verificado en navegador** (no solo compilación): carrito con ítem agregado, descuento por ítem visible en ambas variantes, botón eliminar con fondo rojo en ambas, radios NCF completos (incluido Crédito Fiscal) con un cliente real seleccionado en la pestaña "Cobrar" móvil, y el picker de cliente de escritorio filtrando y seleccionando correctamente.
- [x] **Nada de lógica de negocio se tocó** — mismos métodos (`selectNcf`, `recalculateTotals`, `onClientChange`, etc.), mismo `<form id="pos-checkout-form">`, mismas rutas y validaciones de backend.

---

## Fase 8 — Parked Sales (Ventas Pausadas)
**Rama:** `feat/pos-parked-sales`

- [ ] Guardar snapshot JSON del carrito actual en una tabla `parked_sales`.
- [ ] Botón "Pausar" en la interfaz que limpia el carrito sin perder la venta.
- [ ] Lista de ventas pausadas accesible desde el POS con un clic.
- [ ] Casos de uso: cliente va a buscar dinero, cliente está comparando precios.

---

## Fase 9 — Cierre de Caja y Liquidación
**Rama:** `feat/pos-session-settlement`

> **Nota de vocabulario:** lo que el código y este documento llaman "Sesión" (`PosSession`) es, para el cajero, un **turno de caja**. Por ahora este cambio queda **solo a nivel de etiquetas visibles** en las vistas (breadcrumbs, headers, badges) — no se renombra la clase `PosSession`, ni rutas, ni variables. Eso queda pendiente como refactor aparte si se decide más adelante.

### 9.0 — Prerrequisito: Autoría real de la sesión (abrió vs. cerró)

**Motivo:** `pos_sessions` hoy solo tiene una columna `user_id`, tratada como "el dueño" de la sesión. Esto causa dos problemas confirmados en código:

- **Bug de permisos (confirmado y corregido):**
    - [x] **Permisos de Sesión:** Corregida la restricción en sesiones de TPV para permitir que múltiples usuarios autorizados realicen ventas, no solo el creador de la sesión.
    - Confirmado en tres puntos, no solo uno: `PosTerminalLobby.php` (bloqueaba re-entrar a la terminal Y decidía si resumía o intentaba abrir otra sesión comparando `user_id === Auth::id()`), `PosWorkspace.php::mount()` (el Workspace ni siquiera montaba la sesión si no coincidía el `user_id`) y `PosCheckoutController.php::store()` (el checkout de la venta en sí exigía la misma coincidencia — un segundo cajero autorizado podía llegar al Workspace por otra vía y aun así no podía cobrar). Los tres reemplazados por `Auth::user()->can('pos sessions manage')`.
- **Decisión relacionada (ver 9.3):** al eliminar la verificación de PIN para cerrar el turno, la única forma de saber *quién* realmente hizo el arqueo de cierre (que puede ser una persona distinta de quien abrió el turno en un cambio de cajero) es dejar de depender de un solo `user_id` y registrar ambos eventos por separado.

- [x] **Migración:** agregados `opened_by_user_id` y `closed_by_user_id` (FK nullable a `users`, `nullOnDelete`) a `pos_sessions` ([2026_07_30_120000_add_opened_closed_by_to_pos_sessions_table.php](database/migrations/2026_07_30_120000_add_opened_closed_by_to_pos_sessions_table.php)). Se corrió en Sail y se hizo el backfill de las 13 sesiones existentes (12 cerradas) — `opened_by_user_id` copiado de `user_id`, `closed_by_user_id` de las cerradas asignado al usuario admin (no hay forma de saber retroactivamente quién cerró cada una). Verificado por consulta directa: 0 filas quedaron en null. `user_id` se mantiene en el modelo/tabla sin cambios por compatibilidad con filtros y reportes existentes (`PosSessionUserFilter`, `PosSessionTable`) — sigue escribiéndose en paralelo a `opened_by_user_id` en `PosSessionService::open()` y en `PosTerminalLobby::openSession()` (había lógica de creación de sesión duplicada en ambos sitios, ahora consistente en los dos).
- [x] Revisado el código: `PosSessionTable.php` y las vistas de sesiones siguen usando `user_id`/relación `user` correctamente para mostrar **quién abrió** el turno (sigue siendo válido para eso); no había otro sitio usando `$session->user_id` para decidir autorización aparte de los tres ya corregidos. Se agregaron las relaciones `openedBy()` y `closedBy()` al modelo `PosSession` para cuando el reporte de 9.2 necesite mostrar ambos.
- [x] Resuelto el bug de permisos como parte de este mismo trabajo: la validación ahora es "¿tiene permiso `pos sessions manage`?", no "¿es el mismo usuario que abrió la sesión?" — en los tres puntos de entrada (Lobby, Workspace, Checkout).


- [x] **Columnas y filtros de autoría en el historial de turnos:** siguiendo el patrón de `ARCHITECTURE.md` (Tabla → Filtros pipeline → Vista), se agregaron `opened_by_user_id` ("Abierto Por") y `closed_by_user_id` ("Cerrado Por") a `PosSessionTable::allColumns()` y a los defaults desktop/mobile (reemplazando la columna `user_id` ambigua en los defaults; `user_id`/"Cajero(a)" se deja disponible en el selector de columnas por compatibilidad, ya no como filtro visible). Nuevos filtros `PosSessionOpenedByFilter` y `PosSessionClosedByFilter` (`app/Filters/Sales/Pos/SessionFilters/`), registrados en `PosSessionFilters`. Vista `sessions/partials/table.blade.php` con dos columnas nuevas usando `$session->openedBy`/`$session->closedBy` (eager-loaded en `PosSessionController::index()`); `filters.blade.php` con dos `<x-data-table.filter-select>` nuevos; chips en `pos-sessions.js` (`opened_by_user_id`, `closed_by_user_id`). Ambos filtros reutilizan el mismo catálogo `users` existente (`PosSessionCatalogService::getForFilters()`), sin query adicional.
    - **Nota:** cambio en `resources/js/pages/pos-sessions.js` — requiere `npm run build` (o `npm run dev`) para reflejarse en `public/build`; no se corrió en esta pasada por instrucción explícita de no verificar en navegador.

- [x] **Corrección del primer intento (rediseñado tras feedback):** la primera versión de este fix seguía teniendo la lógica vieja disfrazada — la tarjeta de terminal distinguía visualmente "mi sesión" vs. "ocupada por otro" (gris/deshabilitada, `cursor-not-allowed`) y la vista mostraba un banner de "no tienes permiso", mezclando autorización con presentación. Correcto es: **el permiso se exige en la ruta, la UI no necesita saber nada de permisos.**
    - **Rutas** (`routes/admin/sales/pos.php`): `sales.pos.index` (Lobby), `sales.pos.workspace` y `sales.pos.workspace.checkout.store` ahora llevan `permission:pos sessions manage` en el middleware. Sin el permiso, Laravel corta con **403 nativo** antes de renderizar nada — no hay mensaje custom que mantener en la vista.
    - **`PosTerminalLobby.php`:** se quitó `canOperate` de `render()` y toda referencia a permisos en la vista. Los métodos Livewire (`selectTerminal`, `openSession`, `proceedToWorkspaceOrOpening`) conservan `abort_unless(..., 403)` como defensa en profundidad silenciosa — necesaria porque las llamadas AJAX de Livewire (`/livewire/update`) no pasan por el middleware de la ruta de página, solo por los grupos globales.
    - **Vista (`pos-terminal-lobby.blade.php`):** eliminados `$isOwnSession`/`$isBusy` y el banner ámbar de permiso. Ahora solo hay un estado por terminal: **con turno activo** (informativo: "Abierto por {nombre}") o **disponible** — ambos igual de clickeables, porque llegar a esta página ya implica tener el permiso. Quitado el `wire:click` condicional.
    - **Colores corregidos:** "Disponible" ya no usa gris (que en esta UI se lee como ocupado/deshabilitado) — ahora usa tonos sky/celeste. "Turno activo" mantiene ámbar (comunica "en uso", no "bloqueado" — sigue siendo clickeable).
    - Se mantiene el enlace "Volver al panel" en el header (`route('dashboard')`).

- [x] **403 al probar el checkout con `usuario@local.com` ya con `pos sessions manage` — causa real encontrada (corrige la hipótesis anterior de cache):** se reprodujo el flujo completo en navegador con este usuario. Dos causas distintas, en dos intentos:
    1. **Caché de vistas Blade obsoleta** (`storage/framework/views/`): el compilado seguía con la versión vieja del blade del Lobby (`$canOperate`, ya eliminado del código fuente). Típico en Sail sobre WSL/Windows — el filesystem compartido a veces no dispara la recompilación automática por mtime. Fix puntual: `sail artisan view:clear`. Esto explicó el **primer** 403 (con error real en el log), no el permiso.
    2. **La causa de fondo, la que persistía tras limpiar cache — `create sales` es un permiso distinto de `pos sessions manage`.** Confirmado leyendo el debugbar de la petición fallida: `AuthorizationException: This action is unauthorized.` lanzada desde `StoreSaleRequest::authorize()` ([StoreSaleRequest.php:17](app/Http/Requests/Sales/StoreSaleRequest.php:17)):
        ```php
        public function authorize(): bool
        {
            return $this->user()->can('create sales');
        }
        ```
        `PosCheckoutController::store()` **reutiliza este mismo `StoreSaleRequest`** — el mismo que usa la creación de ventas del backoffice — porque el checkout del POS es, a fin de cuentas, crear un registro `Sale` (ver Fase 7.4: "reutiliza toda la validación de integridad existente en vez de duplicarla"). Esa reutilización es la decisión de arquitectura correcta (una sola fuente de verdad para qué hace válida una venta), pero tiene una consecuencia de permisos no obvia: **`pos sessions manage` solo controla abrir/cerrar/operar el turno — no autoriza a crear la venta en sí.** Para vender en el POS hacen falta **ambos** permisos: `pos sessions manage` (entrar a la terminal/turno) y `create sales` (que el checkout complete). `usuario@local.com` tenía el primero pero no el segundo — de ahí el 403 real, ya resuelto al asignárselo.
    - Verificado en navegador tras corregir ambas causas: `Usuario Normal` completó una venta real en Caja 3 sin error.

### Sugerencias sobre este hallazgo

- **Falta un rol "Cajero" de fábrica.** Hoy `RoleSeeder` solo crea `admin` (todos los permisos) y `Usuario Genérico` (solo `view dashboard`) — no existe ningún rol intermedio pensado para "persona que vende en el POS". Cualquier cajero real necesita que un admin le arme el combo de permisos a mano, y como se vio aquí, es fácil olvidar uno y terminar con un 403 que no explica por qué (el mensaje genérico de Laravel no dice *qué* permiso falta). Sugerido: agregar un rol `Cajero POS` al seeder con el mínimo viable para vender: `pos sessions manage` + `create sales` (y `pos cash movements create` si/cuando se reactive esa función, ver 9.1). No es una decisión que deba tomar yo solo — es la primera vez que el proyecto necesita un rol de "vendedor" real, así que lo dejo como sugerencia a validar contigo antes de crearlo.
- **La reutilización de `StoreSaleRequest` entre backoffice y POS es correcta, pero merece un comentario explícito.** Nada en el código de `PosCheckoutController` ni en `StoreSaleRequest::authorize()` advierte que ese permiso es compartido con el módulo de Ventas general — quien lea solo el código del POS no tiene forma de saber que necesita revisar `create sales` en Ventas, no en POS. Sugerido: un comentario corto en `authorize()` señalando que este check aplica tanto a ventas de backoffice como a checkout POS.
- **El 403 de Laravel no dice qué permiso faltó.** Para depurar esto tuve que leer el debugbar de la petición fallida — en producción (sin debugbar) un cajero solo vería "Acceso Denegado" sin pista de si es el turno, la venta, o el permiso de una tercera cosa. No es urgente para mañana, pero a futuro convendría que `StoreSaleRequest::authorize()` (o un listener del evento `AuthorizationException`) deje algo más específico en el log de errores para que un admin no tenga que reproducir el bug para diagnosticarlo.

- [x] **Eliminada la expiración de 30 min de `CheckTerminalAccess`** (pedido explícito — "no tiene sentido y no aporta nada"): la verificación del PIN de terminal ya no expira por inactividad, dura toda la sesión de navegador hasta que se bloquee explícitamente (`PosTerminalLockController::lock()`). Mismo razonamiento que ya se documentó para el PIN de cierre de turno (9.3): es un secreto compartido por *terminal*, no por *cajero* — forzar reingresarlo cada cierto tiempo no confirma que la persona correcta siga frente a la caja, solo interrumpe a quien sí está autorizado. `PosTerminalLockController::heartbeat()` (ping cada 2 min desde el Workspace) queda **vestigial** — ya no hay ventana que refrescar, el `session()->put(...)` que hace es un no-op funcional. No se quitó la ruta ni el ping del Workspace por estar fuera del pedido explícito; queda anotado como candidato a eliminar si no se le encuentra otro uso.

### 9.1 — Proceso de Cierre

- [x] **Expected total:** suma de todas las ventas en efectivo de la sesión. Resuelto como efecto de BUG 3 (abajo) — `PosSessionService::calculateExpected()` usa `$session->cash_sales`, que ahora suma correctamente solo la porción en efectivo vía `sale_payments`.
- [x] **Counted total:** dinero físico contado por el cajero (input manual). Ya existía y nunca estuvo roto — `closing_balance` en el modal de cierre.
- [x] **Difference:** diferencia calculada automáticamente con validación visual. Resuelto como efecto de BUG 1 (abajo) — `expected_balance`/`difference` ahora se persisten de verdad, y el badge "Caja Cuadrada/Faltante/Sobrante" ya lee esos valores reales.
- ~~Asiento contable automático: ELIMINADO.~~

#### Bugs confirmados en el proceso de cierre (probado en navegador, sesión real)

Se reprodujo el flujo completo: turno abierto con fondo $2,000, una venta en efectivo de $18,000 dentro de la sesión, cierre declarando $15,000 contados (faltante real esperado: $5,000). Resultado real: la sesión cerró mostrando **"Monto Esperado en Caja: $0.00"** y **"CAJA CUADRADA de $0.00"** — el faltante de $5,000 desaparece por completo. Dos causas raíz distintas, ambas confirmadas leyendo código:

- [x] **BUG 1 (crítico) — `expected_balance` y `difference` nunca se guardan:** `PosSession::$fillable` ([PosSession.php:17-26](app/Models/Sales/Pos/PosSession.php:17)) solo incluye `closing_balance`, no `expected_balance` ni `difference`. `PosSessionService::close()` hace `$session->update([...'expected_balance' => $expected, 'difference' => $difference...])`, y Eloquent **descarta esos dos campos en silencio** por protección de mass-assignment (no lanza error). Resultado: toda sesión cerrada hasta ahora ha guardado `expected_balance = 0` y `difference = 0` sin importar el arqueo real — el indicador de "Caja Cuadrada" ha estado **siempre falso-positivo**. Fix: agregar ambos campos a `$fillable`.

- [x] **BUG 2 (menor) — `cash_sales` no existe como accessor:** `sessions/show.blade.php:92` usa `$posSession->cash_sales ?? 0` para la fila "(+) Ventas en Efectivo", pero el modelo no define `getCashSalesAttribute()` — siempre es `null`, así que la vista de sesión **abierta** muestra $0.00 en ventas sin importar cuánto se haya vendido. El modal de cierre no sufre este bug en el *total* (usa `$session->calculateExpected()`, que sí consulta bien), pero tampoco muestra la línea "Ventas en Efectivo" en su desglose — el cajero ve un total que salta sin que ninguna línea visible lo explique. Fix: agregar el accessor real al modelo ([PosSession.php](app/Models/Sales/Pos/PosSession.php)) y usarlo también en el desglose del modal de cierre ([modal-close.blade.php](resources/views/sales/pos/sessions/partials/modal-close.blade.php)).

- [x] **BUG 3 (crítico, encontrado al implementar el fix de arriba) — el arqueo contaba ventas que nunca fueron en efectivo:** el accessor `cash_sales` original de BUG 2 sumaba `sales.total_amount` filtrando por `Sale->payment_type = 'cash'`. Ese campo significa "contado" vs. "crédito" (si ya se cobró o quedó a deber) — **no** es el método de pago físico. El método real vive en `sale_payments.tipo_pago_id` (`SaleService::processPayments()` siempre crea una o más filas ahí, hasta en pago único hay un fallback explícito). Esto rompía el arqueo de dos formas: (1) una venta de contado pagada 100% por tarjeta/transferencia también tenía `payment_type = cash` y se contaba de más como efectivo físico, y (2) con pago dividido/mixto (ya activo en el Workspace, `enableSplitPayment()`/`payments[]`) se contaba el total completo de la venta en vez de solo la porción en efectivo. Fix: `getCashSalesAttribute()` ahora suma `SalePayment.amount` filtrando por `tipoPago->slug === TipoPago::EFECTIVO`, con el porqué documentado en un comentario en el propio método. `PosSessionService::calculateExpected()` se simplificó para usar `$session->cash_sales` en vez de duplicar la query, una sola fuente de verdad. Verificado en navegador con una venta mixta real ($12,000 = $7,000 efectivo + $5,000 tarjeta): la sesión abierta mostró correctamente "Ventas en Efectivo: $7,000.00" y "Esperado en Caja: $8,000.00" (fondo + solo la porción cash), excluyendo la parte de tarjeta.

- [x] **Ocultar Movimientos de Caja (decisión, no eliminar):** el módulo de movimientos (`PosCashMovement`, `PosCashMovementController`, rutas `cash-movements.*`, link del sidebar, botón "Movimiento Manual" en el detalle de sesión) se **oculta** para esta entrega, sin borrar código ni datos. Motivo real: hoy `accounting_account_id` es una FK **obligatoria** en `pos_cash_movements` (acoplamiento directo a Contabilidad, ver `docs/analisis/sobre-ingenieria-modulos.md` §1), y el listener que debía generar el asiento contable de estos movimientos nunca está registrado — o sea, se exige una cuenta contable para un asiento que ni siquiera se crea. Además, en la práctica real casi nunca se saca efectivo de la caja de ventas para pagar algo (eso se hace desde caja chica u otra caja); el único caso legítimo de salida frecuente es el "retiro de excedente" hacia una caja fuerte por seguridad, no un gasto operativo. Ocultar ahora es la opción de menor riesgo para la entrega de mañana; la reintroducción **simplificada** (sin cuenta contable obligatoria, con razones curadas en vez de texto libre) queda para después del lanzamiento, como parte del mismo roadmap de desacople de Contabilidad ya documentado.
    - **Rutas** ([routes/admin/sales/pos.php](routes/admin/sales/pos.php)): grupo `cash-movements.*` completo comentado (`Route::prefix('cash-movements')...`), y el `use PosCashMovementController` también comentado — nada del controlador se carga.
    - **Sidebar** ([app-layout.blade.php](resources/views/components/app-layout.blade.php)): el `<x-sidebar.subitem>` de "Movimientos de Caja" comentado.
    - **Vista de sesión** ([sessions/show.blade.php](resources/views/sales/pos/sessions/show.blade.php)): botón "Movimiento Manual" (header) y el `@include` del modal de registro comentados. **No se tocó** la card "Movimientos Manuales" (el historial/tabla) — queda mostrando "No hay movimientos registrados" siempre, porque ese ajuste ya está asignado explícitamente a 9.2 (línea de abajo), junto con las filas "Entradas Manuales"/"Salidas Gastos" del resumen y las del modal de cierre. No lo adelanté para no mezclar el alcance de dos tareas ya documentadas por separado.
    - El cierre de caja **no depende** de estas rutas: `PosSessionService::calculateExpected()` ya sigue funcionando igual si nunca se crean movimientos (`in`/`out` quedan en 0). Verificado con `php -l` en las rutas; caché de vistas limpiada (`sail artisan view:clear`) por la lección de la sesión anterior con Sail sobre WSL. No probado en navegador esta vez.

### 9.2 — Reporte de Sesión

- [x] **Resumen de ventas por método de pago.** Nuevo `PosSessionReportService::getReportData()` ([PosSessionReportService.php](app/Services/Sales/Pos/PosSessionServices/PosSessionReportService.php)) — una sola fuente de verdad reutilizada tanto por la vista de detalle en pantalla como por el PDF/ticket (ver abajo). Devuelve:
    - `salesDetail`: cada venta completada del turno con hora, número, cliente, **cajero que la procesó** (`Sale->user`, no el que abrió/cerró el turno — esto es justo lo que habilita el fix de multi-usuario de 9.0: dentro de un mismo turno compartido, cada venta sigue sabiendo quién la cobró realmente), cantidad de ítems y total.
    - `breakdownRows`/`columns`/`columnTotals`/`grandTotal`: desglose Concepto × Forma de Pago. Hoy solo hay un concepto real (`Ventas POS`), pero la estructura es una **lista de filas a propósito** — cuando exista Cobros/CxC o Pagos por Servicio (bases ya existen parcialmente, aún no terminadas), se agregan como filas nuevas sin cambiar la forma de la tabla ni las vistas. Las columnas (formas de pago) son dinámicas: solo aparecen los métodos que realmente se usaron en ese turno, vía `sale_payments.tipo_pago_id`, no una lista fija de "todas las que tiene el sistema".
    - `PosSessionController::show()` ahora también pasa este desglose a la vista; en `sessions/show.blade.php` reemplaza el placeholder "Próximamente: Desglose por otros métodos de pago" por la tabla real.
- [x] **Movimientos de caja ocultos ajustados en las vistas** (lo que quedó pendiente de 9.1): quitadas las filas "Entradas Manuales"/"Salidas Gastos" del resumen de arqueo en `sessions/show.blade.php` y en `sessions/partials/modal-close.blade.php`; quitado el bloque/card "Movimientos Manuales" (el `@include('...table-mini'...)`) de `sessions/show.blade.php`, reemplazado por el resumen de forma de pago de arriba. `PosSessionController::show()` limpiado: ya no carga `cashMovements`/`cashIn`/`cashOut` ni las cuentas contables del catálogo (solo se usaban para el modal de movimientos, ya oculto).
- [x] **Exportación a PDF/Ticket.** Ruta nueva `GET sales.pos.sessions.print` (`{pos_session}/print`, acepta `?format=letter|ticket` y `?download=1`), acción `PosSessionController::print()`, mismo patrón que `InvoicePrintService`/`InvoiceController::print()` ya usado en Facturas/Cotizaciones (Fase 4.8):
    - **PDF Carta** ([formats/full.blade.php](resources/views/sales/pos/sessions/formats/full.blade.php)): encabezado con empresa + datos del turno (terminal, abrió, cerró, periodo), **Tabla 1 "Detalle de Ventas"** (Hora, #, Cliente, Cajero, Cantidad, Método de Pago, Total), **Tabla 2 "Resumen de Caja por Forma de Pago"** (Concepto × formas de pago dinámicas + columna Total, con nota al pie explicando que está lista para más conceptos a futuro), y el arqueo (Fondo, Ventas Efectivo, Esperado, Contado, Diferencia con badge).
    - **Ticket 80mm** ([formats/ticket.blade.php](resources/views/sales/pos/sessions/formats/ticket.blade.php)): misma información condensada al estilo monoespaciado de los tickets de factura ya existentes — venta por línea (hora/cajero/total, sin desglose de ítems para no gastar rollo de papel), resumen de forma de pago, y arqueo. Se construyó como **alternativa** disponible (botón "Ticket" junto a "Imprimir Reporte"), no como el formato principal — el caso de uso real es imprimir el PDF carta para archivo físico; el ticket queda listo por si se necesita en una terminal con impresora térmica y sin acceso fácil a impresión de escritorio.
    - Wrapper [`sessions/print.blade.php`](resources/views/sales/pos/sessions/print.blade.php) para el ticket (mismo patrón que `sales.invoices.print`: botones Imprimir/Cerrar + auto-`window.print()`). El PDF Carta se sirve directo con DomPDF (`stream()` inline o `download()` con `?download=1`).
    - Verificado: `php -l` limpio en los 3 archivos PHP tocados, ruta confirmada con `route:list --name=sessions` (`sales.pos.sessions.print` registrada), caché de vistas limpiada. **No probado en navegador** — pendiente si se quiere verificar el render real del PDF/ticket.
- [x] **Bug encontrado en el reporte (probado en navegador, sesión real con venta mixta + venta a crédito): el crédito desaparecía como "N/A" sin explicación, y el listado de ventas mentía sobre pagos divididos.** Dos causas raíz, ya corregidas:
    - **`PosSessionReportService::getReportData()`** leía `sale->payments` para etiquetar el método — pero una venta a **crédito** nunca tiene filas en `sale_payments` (`SaleService::processPayments()` corta directo a Cuentas por Cobrar, sin registrar método físico), así que salía "N/A" y el monto simplemente no aparecía en ningún total. Fix: chequeo explícito de `payment_type === Sale::PAYMENT_CREDIT` antes de mirar `payments`, etiqueta "Crédito", y se agregan `creditTotal`/`totalSalesWithCredit` al retorno del servicio — **separados** de `columns`/`columnTotals`/`grandTotal` (esos siguen siendo solo dinero real cobrado, el cuadre de caja no se tocó). Los tres formatos (PDF, ticket, vista en pantalla) ahora muestran una caja destacada — no una línea de texto suelta, a pedido explícito porque "es dinero que va a entrar" — con "Ventas totales: $X — de las cuales $Y a Crédito (CxC), no exigible en caja hoy".
    - **`resources/views/sales/partials/table.blade.php`** (listado general de ventas, no el POS): la columna "Método de Pago" leía `$sale->tipoPago->nombre` — el campo de cabecera `sales.tipo_pago_id`, el "principal" que quedó de antes de que existiera pago dividido. Para una venta mixta mostraba solo ese método suelto, nunca "Mixto". Fix: misma lógica ya usada en el reporte de sesión (cuenta `sale->payments`, "Mixto" si hay más de uno, "Crédito" si `payment_type` lo es).
- [x] **Badges de método de pago centralizados en `TipoPago`, mismo patrón que `Sale::getPaymentTypeStyles()`/`getPaymentTypeIcons()`** (esos son para Contado/Crédito — *tipo de venta*; esto es para Efectivo/Tarjeta/Transferencia/etc. — *método de pago*, no deben confundirse, son dos badges distintos que ya conviven en la misma fila de la tabla de ventas). Nuevo en [TipoPago.php](app/Models/Configuration/TipoPago.php): `getBadgeStyles()`/`getBadgeIcons()` (claves por slug, igual convención que `PRIORITY_ORDER`) + `getDefaultBadgeStyle()`/`getDefaultBadgeIcon()` como *fallback* gris para métodos nuevos no listados. Incluye diseño para `CREDITO` y para la constante nueva `MIXTO` — ninguna de las dos es una fila real de `TipoPago` en BD, pero ambas aparecen como "método" en las vistas (venta a crédito, venta con pago dividido), así que necesitaban su propio badge en vez de cortar la vista con `?? 'N/A'`. Aplicado ya en `sales/partials/table.blade.php` (la columna "Método de Pago Detallado" ahora renderiza un badge con ícono, igual que la columna de Tipo de Venta de al lado). Pendiente: no se aplicó todavía a `sessions/show.blade.php` ni a los formatos PDF/ticket del POS (esos hoy muestran el método como texto plano) — se puede extender ahí si se pide.

### 9.3 — Vista dedicada de cierre + justificación obligatoria de faltante/sobrante

**Se fusionan 9.3 y 9.4 en una sola fase** (decisión explícita): la justificación del descuadre no es una feature aparte, es contenido del mismo formulario de cierre — no tiene sentido documentarlas ni construirlas por separado cuando ambas viven en la misma pantalla y el mismo submit.

**Motivo 1 (rediseño de contenedor):** `modal-close.blade.php` es un `<x-modal>` embebido en `sessions/index.blade.php` (uno por cada sesión abierta, vía `@foreach`). Funciona con los datos de prueba actuales, pero un modal no escala: cuando una sesión acumule muchas ventas del día, el resumen de arqueo y el desglose de 9.2 por método de pago necesitan más espacio del que un modal puede dar sin volverse una caja pequeña con scroll interno incómodo.

**Motivo 2 (justificación obligatoria):** si `difference != 0` al cerrar, el sistema solo mostraba el monto (Sobrante/Faltante) en un badge — el campo `notes` existía pero era opcional y no estaba atado a la diferencia. En la práctica real, un descuadre en caja **siempre** debe quedar explicado (aunque sea "no sé qué pasó"), porque es el primer dato que se revisa cuando hay un patrón de faltantes recurrentes con un cajero o turno. No debe bloquear el cierre (eso solo empujaría al cajero a inventar un monto "cuadrado" para poder salir), pero sí debe exigir que quede una razón registrada.

Orden de implementación (jerárquico, de abajo hacia arriba — dato antes que pantalla):

1. **Migración** → 2. **Modelo** → 3. **Ruta con permiso `pos sessions manage` + Controlador** → 4. **FormRequest (validación condicional)** → 5. **Vista dedicada** (reemplaza el modal).

- [x] **1. Migración:** `difference_reason` (string nullable) y `difference_notes` (text nullable) agregados a `pos_sessions`.
- [x] **2. Modelo (`PosSession.php`):** ambos campos en `$fillable`; constantes `REASON_*` + `getReasons(): array` con la lista curada (ver abajo, revisada tras feedback) para que el select no vuele suelto por las vistas.
- [x] **3. Ruta y permiso:** `GET sales.pos.sessions.close-form` (`{pos_session}/close`) con middleware `permission:pos sessions manage` **en la ruta** (no solo en el `authorize()` del FormRequest) — mismo patrón 403-nativo ya establecido en 9.0 para Lobby/Workspace/Checkout. `PosSessionController::closeForm()` reutiliza `calculateExpected()`, sin lógica nueva de negocio (el controlador solo pinta la vista).
- [x] **4. `CloseSessionRequest`:** `difference_reason` (`nullable`, `Rule::in` sobre las claves de `getReasons()`) y `difference_notes` (`nullable`, `max:500`). Validación condicional vía `withValidator()` (mismo patrón que `StoreQuoteRequest`): calcula `$expected` desde la sesión de la ruta, compara contra `closing_balance` enviado, y si `abs(diferencia) >= 0.01` exige `difference_reason`; si el motivo es `otro`, exige además `difference_notes`. Si la diferencia es 0, no exige nada — igual que antes.
- [x] **5. Vista dedicada** ([sessions/close.blade.php](resources/views/sales/pos/sessions/close.blade.php)): reemplaza `modal-close.blade.php` (eliminado, no comentado — a diferencia de movimientos de caja, esto no es una feature a reactivar después, es un reemplazo total). Cambios de contenido respecto al modal viejo:
    - **PIN eliminado de verdad:** el modal viejo seguía teniendo la "FASE 1: BLOQUEO DE SEGURIDAD" completa en el HTML a pesar de que la decisión de quitarlo ya estaba documentada — nunca se había ejecutado. La vista nueva arranca directo en el arqueo.
    - **Select de motivo** (`x-show` cuando `difference != 0`) con las opciones de `PosSession::getReasons()`; si el valor es `otro`, aparece un textarea requerido para `difference_notes`. El botón de cierre queda deshabilitado hasta que: `real` tiene valor, y (si `difference == 0`) nada más, o (si `difference != 0`) hay motivo seleccionado y, si es "Otro", hay texto.
    - Se conserva el campo `notes` existente como "Observaciones Generales", separado del motivo del descuadre — mismo criterio documentado en la decisión original de no mezclarlos.
    - `sessions/partials/table.blade.php`: el botón de cierre pasó de `$dispatch('open-modal', ...)` a un `<a>` directo a la nueva ruta. `sessions/index.blade.php`: quitado el `@include('...modal-close')`.

**Ajustes tras feedback (mismo día):**

- [x] **Sin blur:** la vista original heredó del modal un overlay `backdrop-blur` sobre el resumen de arqueo hasta que el cajero escribía el monto contado — quitado, el resumen se ve claro siempre.
- [x] **Columna de ventas del turno:** layout pasó a 2 columnas (`lg:grid-cols-3`, formulario `col-span-2` + panel `col-span-1 sticky` con el listado hora/método/total de `salesDetail`, mismo dato que ya arma `PosSessionReportService`). Deja al cajero cotejar el efectivo contra las ventas sin salir de la pantalla. **Fuera de alcance a propósito:** un desglose por denominación (contar billetes/monedas individualmente y sumar automático hacia `closing_balance`) quedó anotado como mejora futura, no construida — reduciría el margen de error humano del conteo pero es una feature aparte.
- [x] **Motivos curados revisados:** se quitó `Diferencia no identificada` (a criterio del usuario — un motivo que no explica nada contradice el propósito de exigir explicación) y se amplió la lista a partir de causas reales de descuadre en POS, no solo las 3 originales:
    - Error al dar el vuelto
    - Error al contar el efectivo (el cajero se equivoca contando, no necesariamente hay plata de más/menos real)
    - Venta cobrada sin registrar en el sistema (típica causa de faltante — se cobra pero no se pasa por el POS)
    - Error de cobro (monto incorrecto)
    - Billete falso retirado
    - Gasto o retiro de caja no registrado (se conecta con la decisión de 9.1 de ocultar movimientos de caja — si alguien saca dinero por fuera del sistema, esta es la forma de que quede rastro igual, aunque sea a posteriori)
    - Vuelto no reclamado por el cliente (causa típica de sobrante, no solo de faltante — la lista no asume que todo descuadre es "falta dinero")
    - Otro
- [x] **Filtro (no columna) de motivo de descuadre:** siguiendo el patrón `ARCHITECTURE.md` (Filtro pipeline → Catálogo → Vista → Chip JS): `PosSessionDifferenceReasonFilter` nuevo, registrado en `PosSessionFilters`; `PosSessionCatalogService::getForFilters()` expone `difference_reasons` (`PosSession::getReasons()`); `filters.blade.php` con un `<x-data-table.filter-select>` nuevo; `pos-sessions.js` con el chip correspondiente. **No se agregó como columna** — la diferencia (monto) ya es columna desde 9.0/9.1, el motivo es solo filtro para poder responder "¿cuántos faltantes fueron por vuelto mal dado este mes?" sin necesitar verlo en cada fila de la tabla.
    - **Nota:** cambio en `pos-sessions.js` requiere `npm run build`/`npm run dev` para reflejarse; no corrido en esta pasada.


- [x] Comentar el verify-pin re RateLimit del open modal de sesiones, ahora se puede intentar abrir la sesion cuantas veces quieras sin el limite de tiempo. Quitado de `app/Providers/AppServiceProvider.php`

- [x] Eliminar el registro de los asientos contables de los movimientos de caja. Arhicovs eliminados:  `app/Listeners/Sales/Pos/CreateAccountingEntryForMovement.php` y `app/Events/Sales/Pos/CashMovementRegistered.php` además de eliminar el uso del servicio de asientos contables del `app/Services/Sales/Pos/PosSessionServices/PosSessionService.php`
---

## Fase 10 — Módulo de Impresión
**Rama:** `feat/pos-print-service`

- [x] **Soporte 58mm** — ticket compacto sin logo, solo datos esenciales.
- [x] **Soporte 80mm** — ticket estándar con logo, datos del cliente y totales detallados.
- [x] **PDF estándar** — versión carta para envío por email o impresión en impresora láser fuera del TPV, directamente en el backoffice.
- [x] `PosPrintService` centraliza la lógica de selección de formato según `receipt_size` en `pos_settings`.
- [x] `auto_print_receipt` dispara la impresión automáticamente al completar la venta.

---

## Fase 11 — Correcciones Pre-Release (`is_active`, Independencia de Cajas, Producto/Servicio)

**Rama:** `feat/pos-pre-release-fixes`

**Motivo:** tres hallazgos confirmados en código durante la revisión previa al release del lunes, empaquetados junto a la Fase 10 porque tocan el mismo terreno (terminal, checkout, catálogo) y son necesarios antes de arrancar el desacople grande de Contabilidad/módulos satélite documentado en `docs/analisis/modulos-base-satelite.md`. Detalle completo de cada hallazgo (evidencia, líneas exactas) en `docs/promts.md`; aquí solo el checklist de ejecución.

### 11.1 — `is_active` de `PosTerminal` no se respeta fuera del Lobby

Confirmado: una terminal desactivada se puede seguir usando para abrir sesión y vender — `is_active` hoy solo filtra el listado del Lobby, no es una regla de negocio real.

- [ ] `OpenSessionRequest::rules()`: `terminal_id` exige `Rule::exists('pos_terminals', 'id')->where('is_active', true)` (hoy es un `exists` plano).
- [ ] `CheckTerminalAccess::handle()`: si `!$terminal->is_active`, redirigir al Lobby (o 403 JSON si `expectsJson()`) — cubre Workspace y Checkout de una vez, ambas rutas ya pasan por este middleware.
- [ ] `PosTerminalController::update()`: si el request pone `is_active=false` y la terminal tiene una sesión abierta, rechazar con el mismo guard/mensaje que ya usa `destroy()` — no anular ventas/sesión en curso por un cambio de flag.

### 11.2 — Independencia de Cajas: descuentos 100% por terminal (se elimina la config global)

**Decisión de alcance (revisada):** no es un override con fallback al global — es reemplazo total. "Heredar del global" no resuelve nada, es la misma rigidez con un paso extra. `allow_item_discount`/`allow_global_discount`/`max_discount_percentage` **dejan de existir en `pos_settings`** y pasan a ser campos **obligatorios de cada `PosTerminal`**, sin fallback. Decisión ya tomada: la venta manual de backoffice (`sales/create.blade.php`) y `QuoteBuilder` (no están atados a una terminal) **quedan sin límite de descuento** — no hay a qué heredar una vez que el global desaparece, y el backoffice es de uso exclusivo de admins de confianza.

- [ ] Migración `pos_terminals`: agregar los 3 campos `NOT NULL` con default (`true`/`true`/`10.00`, mismos valores que tenía el global hoy) — el `default` de columna resuelve el backfill de terminales existentes sin script aparte.
- [ ] Migración `pos_settings`: `dropColumn` de los 3 campos. El resto de la tabla (cliente rápido, auto-print, `receipt_size`) no se toca.
- [ ] `PosSetting.php`: quitar los 3 campos de `$fillable`/casts/`createDefault()`.
- [ ] `PosTerminal.php`: 3 campos nuevos en `$fillable`/casts — se leen directo (`$terminal->allow_item_discount`), no pasan por `getSetting()` (ese patrón es justo el fallback que se está descartando).
- [ ] `SaleService::validateDiscounts()`: recibe `?PosContext $context`. Con contexto (venta POS), valida contra los 3 campos del terminal directo. **Sin contexto (venta de backoffice), no valida ningún tope** — se salta el bloque completo.
- [ ] `PosConfigService::validateDiscount()`: eliminar (quedaría leyendo columnas inexistentes; sin caller fuera de sí mismo).
- [ ] `PosConfigService::update()` + `UpdatePosConfigRequest`: quitar referencias a los 3 campos.
- [ ] `sales/pos/settings/edit.blade.php`: quitar la sección de descuentos completa (checkboxes + campo + variables Alpine que ya no aplican a esta pantalla).
- [ ] `PosWorkspace::render()` + `pos-workspace.blade.php`: leen `$this->terminal->allow_item_discount`/`allow_global_discount`/`max_discount_percentage` directo.
- [ ] **`QuoteBuilder.php` y `sales/create.blade.php` — cambio obligatorio, no opcional:** ambos leen hoy `pos_config()?->allow_item_discount`/`max_discount_percentage`. Tras dropear las columnas, esa lectura da `null`, y en `sales/create.blade.php` (`{{ !pos_config()?->allow_item_discount ? 'disabled' : '' }}`) eso invierte el resultado acordado — `null` deshabilita el campo en vez de dejarlo libre. Reemplazar por valores fijos en código (descuento siempre habilitado, tope de UI en 100, sin validación server-side), coherente con "backoffice sin límite".
- [ ] Formularios de terminal (`Store`/`UpdatePosTerminalRequest` + vistas): los 3 campos pasan a **obligatorios** (`required`), no más lenguaje de "heredado" — se configuran al crear la terminal, con el default de la migración precargado.

### 11.3 — `is_stockable` (Producto vs. Servicio): corte real del flujo + refactor de alta

Confirmado: el bug real es el opuesto al esperado — un "servicio" no vende infinito, se **bloquea** ("Stock insuficiente. Disponible: 0") en cuanto su stock fantasma (creado en 0 por `firstOrCreate`) intentaría ir a negativo, porque ningún punto del flujo de venta sabe que `is_stockable` existe salvo el botón del Workspace (cosmético). Además cada venta de un "servicio" genera igual `InventoryMovement` y asiento de Costo de Ventas.

**Backend:**
- [ ] `StoreSaleRequest::withValidator()`: precargar `is_stockable` de los productos del payload y saltar el chequeo de `InventoryStock` cuando es `false`.
- [ ] `SaleService::create()`: no llamar a `inventoryService->register()` para ítems con `is_stockable=false` — sin `InventoryMovement`, sin `InventoryStock`, sin asiento de Costo de Ventas.

**Alta/edición de Producto/Servicio (refactor de formulario):**
- [ ] Seeder: agregar categoría `Servicios` a `CategorySeeder`.
- [ ] `products/create.blade.php` y `edit.blade.php`: agregar `x-data` (hoy son formularios planos) con toggle **Producto / Servicio** primero, antes del campo Nombre (mismo patrón visual que el toggle Contado/Crédito de `sales/create.blade.php`).
- [ ] Reordenar: Toggle → Nombre → Categoría → Unidad → resto igual.
- [ ] Al elegir Servicio: categoría se preselecciona a `Servicios` (editable), unidad se fija a `Unidad` y se deshabilita (fuera de alcance por ahora: unidades propias tipo "horas").
- [ ] Costo: helper text dinámico según el toggle (compra a proveedor vs. mano de obra/tercerizado/destajo).
- [ ] Quitar el checkbox suelto "¿Gestionar Stock?" — el toggle nuevo es la única fuente de `is_stockable`.

**Decisión de alcance (revisada, no construir):** se descartó una tabla pivote de visibilidad por almacén/terminal (`product_warehouse`) para restringir qué servicios/productos aparecen en cada terminal. Ocultar catálogo por terminal es contraproducente: un empleado nuevo que no conoce todo el catálogo necesita verlo completo para poder ofrecerlo. Un producto o servicio se vende desde cualquier terminal/almacén sin restricción de catálogo.

**Renombrado de UI (solo texto visible, cero cambios de rutas/clases/BD):**
- [ ] Sidebar: "Productos" → "Productos/Servicios".
- [ ] `products/index.blade.php`: título y botón "Nuevo Producto" → "...Producto/Servicio".
- [ ] `products/create.blade.php`/`edit.blade.php`: títulos y botones de guardar.
- [ ] `ProductTable::allColumns()`: revisar el label de `is_stockable` ("Gestionar Stock") acorde al nuevo toggle — no bloqueante.

---

## En Cola Futura

```
feat/client-dgii-lookup     — Consulta automática de datos por RNC/cédula a la DGII
feat/pos-offline-mode       — Operación sin conexión con sincronización posterior
feat/pos-loyalty-system     — Sistema de puntos y fidelización de clientes
```

---

## Checklist de Completitud

### Fases Completadas
- [x] Fase 1 — `pos_settings`: tabla, modelo y validación de arranque
- [x] Fase 2 — Modal de cliente rápido con `QuickClientDTO`
- [x] Fase 3 — Terminales con PIN hasheado, sesiones y movimientos de caja
- [x] Fase 4 — Motor de cotizaciones completo (persistentes y no persistentes)

### Fase 4 en Detalle
- [x] Livewire instalado y Alpine sin conflictos de instancia
- [x] Migración `quotes` con multiorigen (backoffice/pos)
- [x] Migración `quote_items` con snapshot de precios
- [x] `QuoteService::store()` con recálculo real en DB
- [x] `QuoteService::convertToSale()` con validación de estado
- [x] Comando `ExpireQuotes` registrado en scheduler
- [x] Filtros pipeline: Customer, Status, Date, User, Search, Origin
- [x] FormRequests con inmutabilidad para cotizaciones convertidas
- [x] `QuoteTable` con columnas dinámicas
- [x] Vista Index con chips, toolbar y tabla AJAX
- [x] Vista Create/Edit con builder y eventos de éxito
- [x] Vista Show con detalle completo
- [x] `QuotePrintService`: PDF carta y Ticket 80mm
- [x] Cálculo de impuestos corregido sobre descuentos
- [ ] Botón "Cotizar" en interfaz POS
- [ ] Modal "Cargar Cotización" en POS

### Pendiente
- [x] Fase 5 — Motor de descuentos con validación configurable
- [x] Fase 6 — Integración `PosContext` en `SalesService`
- [x] Fase 7 — Interfaz completa POS (Workspace, carrito, checkout, cliente, numpad, seguridad de sesión). Multi-payment (pago dividido) queda pendiente como único punto abierto.
- [ ] Fase 8 — Ventas pausadas (snapshot JSON)
- [x] Fase 9 — Cierre de caja y liquidación (sin asiento contable automático — eliminado a propósito, ver 9.1)
- [ ] Fase 10 — Módulo de impresión 58mm/80mm/PDF
- [ ] Fase 11 — Correcciones pre-release: `is_active`, Independencia de Cajas, Producto/Servicio

---

## Orden Real de Implementación

```
1.  pos-config                    ✅ LISTA
2.  pos-quick-customers           ✅ LISTA
3.  feat/pos-terminal-security    ✅ LISTA
4.  feat/pos-access-control       ✅ LISTA
5.  feat/pos-session-security-ui  ✅ LISTA
6.  pos-quotes                    ✅ LISTA
7.  pos-discounts                 ✅ LISTA
8.  pos-sales-integration         ✅ LISTA
9.  pos-interface                 ✅ LISTA
10. pos-parked-sales              ⬜ PENDIENTE
11. pos-session-settlement        ✅ LISTA
12. pos-print-service             ⬜ PENDIENTE
13. pos-pre-release-fixes         ⬜ PENDIENTE
```

---

## Notas de Implementación

**Dependencia crítica entre fases:**
`pos-sales-integration` (Fase 6) debe completarse antes de `pos-interface` (Fase 7), porque la interfaz llama directamente al `SalesService` con el `PosContext`. Construir la interfaz sin ese contrato definido genera deuda técnica costosa.

**Descuentos y precisión decimal:**
Los campos `discount_amount` y `discount_percentage` en `sale_items` usan `decimal(10,2)` y `decimal(5,2)` respectivamente. Nunca calcular el descuento a posteriori desde el total — siempre persistir ambos valores explícitamente.

**Teclado numérico como requisito no negociable:**
La interfaz POS debe funcionar completamente sin teclado físico. Esto cubre el caso de tablets táctiles en mostrador, que es el hardware más común en el segmento objetivo de ZertixPOS.

**Parked Sales como JSON, no como `Sale`:**
Las ventas pausadas no crean una `Sale` en la base de datos. Son snapshots del estado del carrito. Esto evita NCFs fantasmas y asientos contables incompletos.