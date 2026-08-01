# ZertixPOS

Sistema ERP con punto de venta integrado para negocios de distribución y retail. Gestiona ventas, inventario, contabilidad, clientes y terminales POS desde una sola plataforma.

**Sitio:** [zertixpos.com](https://zertixpos.com)

---

## Módulos

| Módulo | Descripción |
|--------|-------------|
| **Ventas** | Órdenes, facturas, cotizaciones, pagos múltiples |
| **Punto de Venta** | Terminales con sesiones de caja, PIN, recibos |
| **Inventario** | Almacenes, movimientos, stock en tiempo real |
| **Contabilidad** | Contabilidad de doble entrada, cuentas por cobrar, pagos |
| **Clientes** | CRM con límites de crédito, equipos, estados personalizados |
| **Productos** | Catálogo con categorías, unidades, acciones masivas |
| **NCF** | Cumplimiento fiscal dominicano (tipos, secuencias, log) |
| **Configuración** | Moneda, impuestos, tipos de pago, usuarios y roles |

---

## Stack

**Backend:** Laravel 12 · PHP 8.2+ · MySQL / SQLite  
**Frontend:** Livewire 4 · Alpine.js 3 · Tailwind CSS 3 · Vite 7  
**Librerías clave:**
- `spatie/laravel-permission` — RBAC granular por módulo
- `maatwebsite/excel` — Importación y exportación Excel
- `barryvdh/laravel-dompdf` — Generación de PDFs (facturas, cotizaciones)
- `livewire/livewire` — Componentes reactivos (POS, cotizador)

---

## Instalación

### Primera vez
```bash
composer run setup
```
Instala dependencias, crea `.env`, genera clave, corre migraciones y compila assets.

### Desarrollo
```bash
composer run dev
```
Levanta en paralelo: servidor HTTP, queue listener, logs en tiempo real y Vite dev server.

### Servicios individuales
```bash
php artisan serve                    # HTTP en :8000
php artisan queue:listen --tries=1   # Procesador de jobs
php artisan pail --timeout=0         # Logs en tiempo real
npm run dev                          # Vite (Tailwind + JS)
```

### Docker (Laravel Sail)
```bash
./vendor/bin/sail up
./vendor/bin/sail shell
```

---

## Base de datos

SQLite por defecto en desarrollo. MySQL en producción vía Docker Compose.

```bash
php artisan migrate:fresh --seed     # Reiniciar y sembrar datos
```

Los seeders organizados por dominio crean: permisos, roles, usuario admin, productos de ejemplo, almacenes, catálogo contable, configuración general y tipos de pago.

---

## Tests

```bash
composer run test
php artisan test tests/Feature/YourTest.php --filter=methodName
```

Usa SQLite en memoria (configurado en `phpunit.xml`).

---

## Calidad de código

```bash
./vendor/bin/pint          # Formatear
./vendor/bin/pint --test   # Verificar sin modificar
```

---

## Arquitectura

El proyecto sigue el patrón **Skinny Controllers** con capas bien definidas:

```
Request → FormRequest (auth + validación)
        → Filters (pipeline de query building)
        → Controller (orquestador)
        → Service (lógica de negocio + DB::transaction)
        → Model (scopeWithIndexRelations para eager loading)
```

**Patrones clave:**
- `scopeWithIndexRelations()` en todos los modelos — previene N+1 en tablas y exports
- Servicios separados: `CatalogService` (selects/dropdowns) vs `BusinessService` (escrituras)
- `SoftDeletesTrait` — papelera, restaurar y borrado definitivo en todos los módulos
- Pipeline de filtros — cada filtro es una clase independiente en `app/Filters/`
- `FormRequest::authorize()` — permisos Spatie validados antes del controller
- Rate limiting en PIN del POS — máximo 5 intentos/minuto por terminal

---

## Características destacadas

### Punto de Venta
- Multi-terminal con configuración individual (formato, impresora, almacén)
- Sesiones de caja con balance de apertura/cierre
- PIN de acceso con bloqueo automático por inactividad
- PIN obligatorio para cierre de caja (arqueo ciego)
- Teclado numérico para reactivación de terminal bloqueado
- Movimientos de caja con cuenta contable
- Integración con cotizaciones

### Ventas y Facturación
- Órdenes con múltiples líneas y métodos de pago
- Facturas con numeración interna o NCF fiscal
- Cotizador en tiempo real con Livewire (cálculo instantáneo de totales)
- Conversión cotización → venta
- Impresión en múltiples formatos (ticket, carta, ruta)

### Contabilidad
- Doble entrada automática en cada venta
- Plan de cuentas jerárquico (activo, pasivo, equity, ingresos, gastos)
- Cuentas por cobrar con aging via Observer
- Creación automática de cuentas al configurar terminales y almacenes

### Exportación e importación
- Excel en todos los módulos principales
- Importación masiva de clientes con plantilla
- Export de NCF en Excel y TXT
- Todos los exports usan `scopeWithIndexRelations()` para consistencia

---

## Permisos

Convención de nombres: `view module`, `create module`, `edit module`, `delete module`, `restore module`.  
Permisos especiales: `pos config view`, `print invoices`, `export sales`, entre otros.  
Aplicados en `FormRequest::authorize()` — rechazados antes de llegar al controller.

---

## Variables de entorno clave

```env
DB_CONNECTION=sqlite          # o mysql para producción
APP_DEBUG=true                # solo desarrollo
QUEUE_CONNECTION=database
CACHE_STORE=database
SESSION_DRIVER=database
```

---

## Flujo de trabajo Git

Ramas de feature (`feat/<nombre>`) → `develop` → `main` vía pull requests.
