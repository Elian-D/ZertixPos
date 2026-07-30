# ZertixPOS — Módulo POS Interfaz

**RAMA PADRE:**
`feat/pos-system`

**Objetivo:** Implementar el sistema completo de Punto de Venta con soporte para cotizaciones (persistentes y no persistentes), descuentos configurables, integración con `SalesService`, interfaz reactiva completa, cierre de caja y módulo de impresión. La arquitectura se basa en configuración centralizada (`pos_settings`) que controla el comportamiento de todas las fases posteriores.

> **Estado actual:** Fase 7 (interfaz completa del Workspace POS) completada. Las fases 1 a 7 están listas; pendientes: Fase 8 (ventas pausadas), Fase 9 (cierre de caja) y Fase 10 (módulo de impresión).

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

### 9.1 — Proceso de Cierre

- [ ] **Expected total:** suma de todas las ventas en efectivo de la sesión.
- [ ] **Counted total:** dinero físico contado por el cajero (input manual).
- [ ] **Difference:** diferencia calculada automáticamente con validación visual.
- ~~Asiento contable automático: ELIMINADO.~~

### 9.2 — Reporte de Sesión

- [ ] Resumen de ventas por método de pago.
- [ ] Listado de movimientos de caja (entradas/salidas).
- [ ] Exportación a PDF para archivo físico.

---

## Fase 10 — Módulo de Impresión
**Rama:** `feat/pos-print-service`

- [ ] **Soporte 58mm** — ticket compacto sin logo, solo datos esenciales.
- [ ] **Soporte 80mm** — ticket estándar con logo, datos del cliente y totales detallados.
- [ ] **PDF estándar** — versión carta para envío por email o impresión en impresora láser.
- [ ] `PosPrintService` centraliza la lógica de selección de formato según `receipt_size` en `pos_settings`.
- [ ] `auto_print_receipt` dispara la impresión automáticamente al completar la venta.

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
- [ ] Fase 9 — Cierre de caja con asiento contable automático
- [ ] Fase 10 — Módulo de impresión 58mm/80mm/PDF

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
11. pos-session-settlement        ⬜ PENDIENTE
12. pos-print-service             ⬜ PENDIENTE
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