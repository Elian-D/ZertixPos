# ZertixPOS — Módulo POS Interfaz

**RAMA PADRE:**
`feat/pos-system`

**Objetivo:** Implementar el sistema completo de Punto de Venta con soporte para cotizaciones (persistentes y no persistentes), descuentos configurables, integración con `SalesService`, interfaz reactiva completa, cierre de caja y módulo de impresión. La arquitectura se basa en configuración centralizada (`pos_settings`) que controla el comportamiento de todas las fases posteriores.

> **Estado actual:** Fase 4 en curso (`feat/pos-quotes`). Las fases 1, 2 y 3 están completadas.

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

- [x] **Control de acceso:** Validación del PIN al iniciar sesión en el terminal. `PosAccessController` gestiona la verificación.

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

## Fase 7 — Interfaz Completa POS
**Rama:** `feat/pos-interface`

### 7.1 — Layout Desktop

```
--------------------------------------------------
| Catálogo / búsqueda (70%) | Carrito + Cobro (30%) |
--------------------------------------------------
```

**Panel Izquierdo (70%):**
- Búsqueda rápida con debounce
- Filtros por categoría (tabs o chips)
- Grid de productos con foto, nombre y precio
- Soporte para scan de código de barras

**Panel Derecho (30%):**
- Cliente seleccionado (con botón de cambio rápido)
- Items del carrito con cantidad editable
- Campo de descuento global
- Subtotal / ITBIS / Total
- Métodos de pago
- Teclado numérico grande (obligatorio, sin dependencia del teclado físico)
- Botón "Cobrar"

### 7.2 — Componentes Livewire

- [ ] `PosCart` — gestión del carrito, cálculo de totales, validación de descuentos
- [ ] `PosProductSearch` — búsqueda con debounce y filtro por categoría
- [ ] `PosCheckout` — selección de método de pago, cálculo de vuelto, integración con `SalesService`
- [ ] `PosNumpad` — teclado numérico reactivo reutilizable

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
- [ ] **Asiento contable automático:** generado por `JournalEntryService` al cerrar.

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
- [ ] Fase 6 — Integración `PosContext` en `SalesService`
- [ ] Fase 7 — Interfaz completa POS (layout + componentes Livewire)
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
6.  pos-quotes                    🔄 EN CURSO (pasos finales de integración POS)
7.  pos-discounts                 ⬜ PENDIENTE
8.  pos-sales-integration         ⬜ PENDIENTE
9.  pos-interface                 ⬜ PENDIENTE
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