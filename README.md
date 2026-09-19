<p align="center">
  <picture>
    <source media="(prefers-color-scheme: dark)" srcset="public/img/logos/imagotipo-dark.svg">
    <img src="public/img/logos/imagotipo.svg" alt="ZertixPOS" width="520">
  </picture>
</p>

# ZertixPOS

Sistema ERP con punto de venta integrado para negocios de distribución y retail, con enfoque en el mercado dominicano (cumplimiento fiscal NCF). Gestiona ventas, inventario, contabilidad, clientes y terminales POS desde una sola plataforma — como SaaS **multi-tenant**, cada negocio en su propia base de datos y subdominio.

**Sitio:** [zertixpos.com](https://zertixpos.com)

---

## Multi-tenancy (SaaS)

Desde v1.3.0 el sistema es multi-tenant vía [`stancl/tenancy`](https://tenancyforlaravel.com/) v3 — cada negocio (`Tenant`) tiene su propia base de datos MySQL, aislada por completo del resto, resuelta por subdominio (`<subdominio>.zertixpos.com`).

### Dominio central vs. dominio de tenant

| | Central (`zertixpos.com`, `localhost`) | Tenant (`<subdominio>.zertixpos.com`) |
|---|---|---|
| **Rutas** | `routes/web.php`, `routes/admin.php` | `routes/tenant.php` + `routes/app/*.php` |
| **Guard** | `landlord` (`App\Models\Landlord\Admin`) | `web` (`App\Models\User`) |
| **Conexión DB** | `landlord` (fija, nunca cambia) | `tenant` (cambia por request, según el subdominio) |
| **Qué vive ahí** | Wizard de aprovisionamiento (`/install`), Panel de Súper Admin, webhook de PayPal, PDF de facturas de suscripción firmadas | Todo el negocio real: dashboard, POS, ventas, inventario, contabilidad, perfil |

`config/tenancy.php` define `central_domains` (`127.0.0.1`, `localhost`, `zertixpos.com`); cualquier otro subdominio se resuelve como tenant vía `InitializeTenancyByDomain`. Los guards están separados a propósito (`config/auth.php`) — el guard `landlord` nunca comparte sesión con ningún tenant, y `web` (la tabla `users`) **solo existe dentro de cada base de tenant**, no en la central.

### Aprovisionamiento y facturación

- **Wizard de instalación** (`/install`, `App\Livewire\Install\InstallWizard`) — crea `Tenant`+`Domain`+`Subscription` en trial de 15 días y corre las migraciones/seeders del tenant nuevo. Dos contextos: autoservicio público, o asistido desde el botón "Nuevo Tenant" del Súper Admin.
- **Suscripción real con fechas** (`Subscription`/`SubscriptionInvoice`, tablas landlord) — el middleware de corte (`EnsureSubscriptionActive`, en `routes/tenant.php`) bloquea por `current_period_ends_at`, no por el `status` de un webhook. Pasarela PayPal vía `srmklive/paypal` (`PaymentGatewayContract`/`PayPalGateway`).
- **Tenant `demo`** — poblado con `php artisan zertix:seed-demo`, exento de facturación, con edición de perfil/contraseña del admin bloqueada server-side (no solo oculta en la UI).
- **Panel de Súper Admin** (`routes/admin.php`, guard `landlord`) — listado de tenants, plan por tenant (solo lectura), botón "Nuevo Tenant" que abre el wizard.

### Comandos de tenancy

```bash
php artisan tenants:list                    # Listar tenants
php artisan tenants:migrate                 # Migrar todos los tenants (o --tenants=<id>)
php artisan tenants:migrate-fresh           # Drop + re-migrar (¡destructivo!)
php artisan tenants:seed                    # Sembrar tenant(s)
php artisan tenants:run <comando>           # Correr un comando artisan para tenant(s)
php artisan zertix:seed-demo                # Poblar el tenant demo con datos realistas
```

Un tenant **nuevo** importa `database/migrations/tenant/schema/mysql-schema.sql` en vez de correr las ~84 migraciones de tenant una por una (bajó el aprovisionamiento de ~38s a ~12s) — hay que regenerar ese dump manualmente después de agregar una migración nueva bajo `database/migrations/tenant/` (ver `ARCHITECTURE.md` §"Migraciones de tenant y el schema dump"). Un tenant que ya existe lo ignora por completo.

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
| **Configuración** | Centro de Configuración unificado — empresa, catálogos del sistema, usuarios y roles, módulos |
| **Suscripción y Facturación** | Planes, ciclo de facturación con PayPal, trial, renovación/cambio de plan |
| **Súper Admin** | Panel landlord — listado y aprovisionamiento de tenants |

---

## Stack

**Backend:** Laravel 12 · PHP 8.2+ · MySQL (tenant/landlord) / SQLite (dev de un solo tenant)
**Frontend:** Livewire 4 · Alpine.js 3 · Tailwind CSS 3 · Vite 7
**Librerías clave:**
- `stancl/tenancy` — multi-tenancy por base de datos, resuelta por subdominio
- **Redis** — caché, sesión y colas. No es opcional: `CacheTenancyBootstrapper` (el mecanismo que aísla el caché entre tenants) lo requiere en serio, no solo como mejora de performance — sin Redis el caché se cruzaría entre negocios
- `spatie/laravel-permission` — RBAC granular por módulo, aislado por tenant (caché de permisos por conexión)
- `srmklive/paypal` — pasarela de pago para suscripciones
- `maatwebsite/excel` — Importación y exportación Excel
- `barryvdh/laravel-dompdf` — Generación de PDFs (facturas, cotizaciones, facturas de suscripción)
- `livewire/livewire` — Componentes reactivos (POS, cotizador, motor de tablas `DataTable`)

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
Dentro de Sail, correr `php artisan`/`composer` siempre con `--user=sail` vía `docker exec` (nunca `docker exec` a secas) — si no, los archivos compilados (`storage/framework/views/*.php`) quedan owned por `root` y rompen la siguiente request servida por el usuario `sail`.

---

## Base de datos

**Dos bases separadas:** la **central** (landlord — tabla `tenants`, `domains`, `admins`, `subscriptions`, permisos del Súper Admin) y una **base por tenant** (todo el negocio real — `users`, `products`, `sales`, etc.), creada y destruida junto con cada `Tenant`.

```bash
php artisan migrate:fresh --seed        # Central (landlord) — reiniciar y sembrar
php artisan tenants:migrate-fresh       # Todos los tenants — reiniciar (¡destructivo!)
php artisan tenants:seed                # Sembrar datos en tenant(s)
```

SQLite solo tiene sentido para desarrollo de un único tenant sin tenancy activa; el flujo real (central + N tenants) requiere MySQL vía Docker Compose/Sail. Los seeders organizados por dominio crean: permisos, roles, usuario admin, productos de ejemplo, almacenes, catálogo contable, configuración general y tipos de pago (por tenant), más el rol Super Admin y su primer usuario (central).

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

El proyecto sigue el patrón **Skinny Controllers** con capas bien definidas. El motor de listados está en migración activa (v1.3.0 Fase 0): los módulos de Clientes, Ventas e Inventario ya corren sobre el motor Livewire nuevo; Finanzas y Sistema siguen en el motor AJAX viejo hasta su propia sub-fase. Ver `ARCHITECTURE.md` para el patrón vigente completo (columnas/filtros/papelera/toasts) antes de tocar cualquier tabla.

```
Request → FormRequest (auth + validación)
        → Filters / filterMap() (pipeline de query building)
        → Controller (orquestador, casi sin index() en módulos migrados)
        → Service (lógica de negocio + DB::transaction)
        → Model (scopeWithIndexRelations para eager loading)
```

**Patrones clave:**
- `scopeWithIndexRelations()` en todos los modelos — previene N+1 en tablas y exports
- Servicios separados: `CatalogService` (selects/dropdowns) vs Service de negocio (escrituras)
- Papelera como tab del propio índice (módulos migrados) o `SoftDeletesTrait` con vista aparte (módulos no migrados) — solo en modelos Categoría A, ver `docs/analisis/politica-soft-deletes.md`
- Pipeline de filtros — closures en `filterMap()` (Livewire) o clases en `app/Filters/` (AJAX viejo)
- `FormRequest::authorize()` — permisos Spatie validados antes del controller; en un método Livewire que reemplazó una ruta, el permiso se replica a mano (`abort_unless`)
- Rate limiting en PIN del POS — máximo 5 intentos/minuto por terminal
- Caché de permisos de Spatie aislado por tenant — cada base de tenant tiene su propio `PermissionRegistrar`

---

## Características destacadas

### Punto de Venta
- Multi-terminal con configuración individual (formato, impresora, almacén, descuentos)
- Sesiones de caja con balance de apertura/cierre
- PIN de acceso con bloqueo automático por inactividad
- PIN obligatorio para cierre de caja (arqueo ciego)
- Teclado numérico para reactivación de terminal bloqueado
- Movimientos de caja con cuenta contable
- Integración con cotizaciones y cuentas por cobrar (aplicar pagos desde el TPV)
- Validación de RNC/Cédula en tiempo real (API DGII) con autollenado

### Ventas y Facturación
- Órdenes con múltiples líneas y métodos de pago
- Facturas con numeración interna o NCF fiscal
- Cotizador en tiempo real con Livewire (cálculo instantáneo de totales)
- Conversión cotización → venta
- Impresión en múltiples formatos (ticket, carta, ruta)

### Contabilidad
- Doble entrada automática en cada venta
- Plan de cuentas jerárquico (activo, pasivo, equity, ingresos, gastos)
- Cuentas por cobrar con aging vía Observer
- Creación automática de cuentas al configurar terminales y almacenes

### Exportación e importación
- Excel en todos los módulos principales
- Importación masiva de clientes con plantilla
- Export de NCF en Excel y TXT
- Todos los exports usan `scopeWithIndexRelations()` para consistencia

---

## Permisos

Convención de nombres: `recurso.accion` (ej. `products.view`, `sales.create`), traducidos y agrupados por dominio de módulo en `roles/permissions.blade.php`.
Aplicados en `FormRequest::authorize()` para rutas reales — rechazados antes de llegar al controller. En un método Livewire que reemplazó una ruta (ej. `restore()`, `toggleActivo()`), el permiso se verifica a mano con `abort_unless(auth()->user()->can('...'), 403)` dentro del propio método.
El caché de permisos de Spatie está aislado por conexión — cada tenant tiene el suyo, nunca se filtra entre negocios.

---

## Variables de entorno clave

```env
APP_URL=http://localhost:8082         # dominio central
DB_CONNECTION=mysql                   # landlord por defecto en Sail; sqlite solo en dev sin tenancy
QUEUE_CONNECTION=redis
CACHE_STORE=redis
SESSION_DRIVER=redis
REDIS_CLIENT=phpredis
REDIS_HOST=redis
REDIS_PORT=6379
```

Redis es requisito real desde v1.3.0 (REQ-1.12), no una opción de performance — `CacheTenancyBootstrapper` lo necesita para que el caché no se cruce entre tenants. `config/tenancy.php` define `central_domains` (subdominios que NO se resuelven como tenant) y la conexión plantilla que cada base de tenant clona.

---

## Flujo de trabajo Git

Ramas de feature (`feat/<nombre>`, o durante una versión con múltiples sub-fases, `feature/v<versión>-<sub-fase>`) → `develop`/`release/*` → `main` vía pull requests.
