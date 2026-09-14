<?php

namespace App\Livewire\Install;

use App\Enums\TaxIdentifierType;
use App\Jobs\ProvisionTenantJob;
use App\Models\Configuration\Plan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;
use Livewire\WithFileUploads;
use Stancl\Tenancy\Database\Models\Domain;

/**
 * REQ-4.6, v1.3.0 Fase 4 — envuelve el wizard de un solo tenant de v1.1.0
 * (Admin/Empresa/Plan/Finalizar, ver docs/features/v1.1.0.md Fase 8) para el
 * mundo multi-tenant: antes de esta fase, este componente asumía que ya
 * existía una base de datos de tenant lista para escribir (nunca fue cierto
 * desde que las rutas se movieron a `routes/tenant.php` en la Fase 1 —
 * corría siempre contra la conexión central, un bug latente nunca ejercitado
 * en producción porque `dev`/`test2` se completaron a mano).
 *
 * Corre en el dominio central (`routes/web.php`), sin tenant todavía.
 *
 * **El `Tenant` se crea al final, en "Finalizar" — no antes (revertido
 * 2026-09-04).** La primera versión lo creaba al terminar el paso Empresa,
 * para tener contra qué tenant sembrar las provincias del cascadeo — esa
 * razón ya no aplica (`provinceOptions()`/`municipalityOptions()` son listas
 * estáticas, no consultan ninguna base). Crear temprano dejó un problema real
 * sin resolver: "Atrás" desde Plan hacia Empresa después de ese punto no
 * podía deshacer nada (el `Tenant` con el subdominio viejo ya existía), y si
 * el usuario abandonaba el wizard ahí quedaba un tenant huérfano sin
 * `Subscription`. Con la creación al final (tal como REQ-4.6 ya especificaba
 * desde el principio), **nada se persiste hasta el último clic** — "Atrás"
 * en cualquier paso previo a Finalizar es siempre seguro, sin excepciones
 * que codificar, mismo principio que ya tenía el paso de revisión ("nada se
 * guarda hasta 'Comenzar ahora'"). Coincide con el patrón real de Odoo (la
 * cuenta se activa una sola vez, al final, sin vuelta atrás después).
 *
 * **REQ-4.1 revisado (2026-09-04): sin bandera de contexto.** El plan
 * original preveía un flag `self_service`/`assisted` para diferenciar quién
 * opera este mismo wizard (REQ-4.2/REQ-4.3) — se descartó: el Contexto B
 * (asistido) termina siendo una superficie completamente distinta, el Súper
 * Admin (Fase 5, todavía no construido) creando el tenant directo desde su
 * panel, no una variante de esta misma pantalla pública. Este componente es
 * en su totalidad el Contexto A.
 */
class InstallWizard extends Component
{
    use WithFileUploads;

    public int $step = 0;

    /**
     * `'forward'`|`'backward'` — de qué lado tiene que entrar la animación
     * del paso actual (`tailwind.config.js`, `carousel-in`/`carousel-in-reverse`;
     * install-wizard.blade.php elige la clase según esto). Sin esto, "Atrás"
     * entraba con la misma animación que "Siguiente" (desde la derecha),
     * que se siente al revés — "Atrás" tiene que entrar desde la izquierda.
     */
    public string $stepDirection = 'forward';

    /**
     * En `true` mientras `ProvisionTenantJob` corre en la cola (Redis) — la
     * vista muestra una pantalla de progreso de pantalla completa en vez del
     * paso normal (`step-provisioning.blade.php`), que sondea
     * `checkProvisioningStatus()` cada 2s (`wire:poll`) hasta que el Job
     * marca `done`/`failed` en `Cache`. `finish()` solo despacha el Job y
     * prende esta bandera — no hace ningún trabajo pesado él mismo (ver
     * docblock de `finish()` sobre por qué se sacó de la request HTTP).
     */
    public bool $provisioning = false;

    /** Clave de `Cache` que el Job usa para avisar su estado — ver `finish()`/`checkProvisioningStatus()`. */
    public ?string $provisioningToken = null;

    /** Mensaje a mostrar si `ProvisionTenantJob` falló — ver `checkProvisioningStatus()`. */
    public ?string $provisioningError = null;

    /**
     * `true` cuando `ProvisionTenantJob` terminó bien — step-provisioning
     * muestra la pantalla de éxito con un link real (`<a href>`, no un
     * redirect por script) en vez de navegar solo (ver docblock de
     * `checkProvisioningStatus()`, hallazgo 2026-09-05: Chrome/Edge muestran
     * su propio diálogo nativo "¿Quieres salir del sitio web?" en un
     * redirect por script después de interactuar con un campo de
     * contraseña — confirmado real incluso en Odoo, no es un bug nuestro
     * arreglable con JS. La salida es dejar que el usuario haga el último
     * click él mismo).
     */
    public bool $provisioningDone = false;

    /** URL de login del tenant recién creado — botón "Entrar a mi negocio" y "Copiar" en la pantalla de éxito. */
    public ?string $tenantLoginUrl = null;

    // Paso 0 — Administrador (REQ-08.2 de v1.1.0)
    public string $adminName = '';

    public string $adminEmail = '';

    public string $adminPassword = '';

    // Paso 1 — Empresa (REQ-08.3 de v1.1.0 + REQ-4.7 subdominio, ver
    // self::RESERVED_SUBDOMAINS para la lista real de reservados).
    public string $subdominio = '';

    /**
     * Auto-sugerencia del subdominio a partir del nombre de la empresa
     * (patrón Odoo: "Agua Discovery" → "agua-discovery", editable) — se
     * apaga apenas el usuario toca el campo a mano (`updatedSubdominio()`),
     * para no pisarle una edición manual con la siguiente tecla en
     * nombreEmpresa. Ver `updatedNombreEmpresa()`.
     */
    public bool $subdominioTouched = false;

    public string $nombreEmpresa = '';

    public $logo = null;

    public ?string $taxIdentifierType = null;

    public string $taxId = '';

    public string $telefono = '';

    public string $email = '';

    public string $direccion = '';

    public ?int $provinciaId = null;

    public ?int $municipioId = null;

    // Paso 2 — Tipo de Negocio (REQ-4.4, pantalla propia desde el rediseño
    // 2026-09-05 — antes vivía dentro del paso Empresa).
    /**
     * Clave de {@see self::BUSINESS_TYPES}. Decide qué checkboxes de
     * `$selectedModules` vienen pre-marcados (`updatedBusinessType()`) y qué
     * Plan se recomienda en el paso siguiente (`getRecommendedPlanProperty()`).
     * No es una instalación a la carta: el Plan elegido en el paso 3 sigue
     * siendo el techo real (ver `ProvisionTenantJob::handle()`), esto solo
     * decide cuáles de los módulos que ese Plan ya trae arrancan encendidos.
     */
    public ?string $businessType = null;

    /**
     * Módulos satélite (`config('modules')`, category `satellite`) que el
     * usuario quiere activos en su instalación — pre-cargado por
     * `updatedBusinessType()` desde `BUSINESS_TYPES[...]['suggested_modules']`,
     * pero editable a mano vía los checkboxes de step-tipo-negocio ("ajustable
     * por el usuario", REQ-4.4). La reconciliación final contra lo que el
     * Plan elegido realmente incluye pasa en `ProvisionTenantJob`, no acá.
     */
    public array $selectedModules = [];

    // Paso 3 — Plan (REQ-08.4 de v1.1.0, REQ-4.5 sin cobro)
    public ?int $planId = null;

    /**
     * REQ-4.7 — lista definitiva (reemplaza el mínimo de la primera ronda de
     * REQ-4.6). Sin infraestructura detrás todavía (Fase 6, DNS comodín,
     * sigue Pendiente) — esto es la única pieza de código de esa fase
     * (docs/features/v1.3.0.md §Fase 6): reservar en el wizard los nombres
     * que, cuando exista el wildcard `*.zertixpos.com`, van a necesitarse
     * para otra cosa o van a ser un riesgo de suplantación si un tenant los
     * toma primero. Agrupada por qué reserva cada bloque, no una lista
     * plana — así una revisión futura sabe si un nombre nuevo ya está
     * cubierto por una categoría existente o hace falta agregarlo.
     */
    private const RESERVED_SUBDOMAINS = [
        // Estándar de cualquier hosting/DNS — un tenant en cualquiera de
        // estos rompería correo, autodiscovery de clientes de email o
        // paneles de proveedor si algún día se configuran para el dominio raíz.
        'www', 'mail', 'smtp', 'pop', 'pop3', 'imap', 'ftp', 'sftp',
        'ns', 'ns1', 'ns2', 'mx', 'autodiscover', 'autoconfig', 'webmail', 'cpanel', 'whm',

        // Superficies propias de ZertixPOS, ya existentes o del roadmap
        // (Fase 5, Súper Admin) — un tenant no puede ocupar el nombre de una
        // pantalla que el propio sistema necesita servir en el dominio raíz.
        'app', 'api', 'admin', 'central', 'landlord', 'superadmin', 'panel', 'staff',

        // Tenants/subdominios reales o reservados por el negocio mismo.
        // 'demo' ya es un tenant real (REQ-3.9) — Domain::where() ya lo
        // bloquearía por duplicado, pero está acá también para dar el
        // mensaje de "reservado" en vez de "ya está en uso" si alguien lo intenta.
        'demo', 'status', 'blog', 'docs', 'help', 'soporte', 'support',

        // Infraestructura técnica genérica — por si el marketing site o los
        // assets estáticos del futuro terminan sirviéndose desde un
        // subdominio propio (CDN, bucket, etc.) en vez de una ruta del dominio raíz.
        'cdn', 'static', 'assets', 'files', 'storage', 'media',

        // Ambientes no productivos — para que ningún cliente real quede en
        // un subdominio que suene a ambiente de pruebas interno, y viceversa
        // (que un ambiente de pruebas interno futuro no colisione con un tenant).
        'test', 'testing', 'staging', 'dev', 'sandbox', 'beta',

        // Riesgo de suplantación (phishing) — nombres que un atacante
        // registraría como tenant para que luzcan como una superficie
        // oficial de autenticación/cobro de ZertixPOS, no de su negocio.
        'login', 'auth', 'sso', 'secure', 'ssl', 'vpn', 'payment', 'payments', 'billing', 'checkout',

        // La marca misma — nadie más puede ser "zertixpos.zertixpos.com" ni
        // parecer una superficie oficial del producto.
        'zertixpos', 'zertix', 'pos',
    ];

    /**
     * REQ-4.4 — curaduría de UX, no un catálogo propio de módulos: las
     * claves de `suggested_modules` son las mismas de `config('modules')`
     * (category `satellite`). Lista corta a propósito (docs/analisis/
     * modulos-base-satelite.md §6, los dos perfiles reales de cliente hoy) —
     * se amplía cuando aparezca un perfil que de verdad no encaje en ninguno,
     * no de forma especulativa.
     *
     * `available => false` (solo `farmacias` por ahora) marca un tipo de
     * roadmap sin ningún módulo satélite real detrás todavía (no existe
     * "recetas médicas"/"lotes con vencimiento", ni "comandas/propina legal"
     * en `config('modules')`) — se muestran en la UI como referencia de
     * hacia dónde va el producto, pero `availableBusinessTypes()` los
     * excluye de las opciones seleccionables Y de la validación del
     * servidor (`rulesForStep(2)`), así que no hay forma de elegirlos ni
     * mandándolos a mano fuera de la UI.
     *
     * Sin `'otro'` (rediseño 2026-09-05, pedido explícito): el paso entero
     * es omitible (ver `rulesForStep(2)`/step-tipo-negocio.blade.php, link
     * "Omitir este paso"), así que una tarjeta "Otro" que no sugiere nada
     * quedó redundante con saltar el paso directamente.
     */
    private const BUSINESS_TYPES = [
        'retail' => [
            'label' => 'Colmado / Retail',
            'description' => 'Venta rápida de mostrador y caja',
            'icon' => 'heroicon-s-building-storefront',
            'suggested_modules' => [],
        ],
        'services' => [
            'label' => 'Servicios profesionales',
            'description' => 'Negocios de servicios sin stock físico',
            'icon' => 'heroicon-s-briefcase',
            'suggested_modules' => [],
        ],
        'ambulante' => [
            'label' => 'Vendedor ambulante / Ruta sin oficina',
            'description' => 'Cobro rápido desde celular o terminal portátil',
            'icon' => 'heroicon-s-device-phone-mobile',
            'suggested_modules' => ['sales.delivery_points'],
        ],
        'distribucion' => [
            'label' => 'Distribuidora con rutas',
            'description' => 'Reparto y venta mayorista en calle',
            'icon' => 'heroicon-s-truck',
            'suggested_modules' => ['sales.delivery_points', 'clients.field_assets'],
        ],
        'restaurant' => [
            'label' => 'Restaurante / Cafetería',
            'description' => 'Comandas, mesas y propina legal',
            'icon' => 'heroicon-s-cake',
            'suggested_modules' => [],
            'available' => false,
            // Texto puramente descriptivo (roadmap) — no son claves de
            // config('modules'), no existen todavía. Solo para step-tipo-negocio.
            'planned_features' => ['Control de mesas', 'Comandas de cocina', 'Propina legal (10%)'],
        ],
        'farmacias' => [
            'label' => 'Farmacias y Salud',
            'description' => 'Recetas médicas y lotes con vencimiento',
            'icon' => 'heroicon-s-beaker',
            'suggested_modules' => [],
            'available' => false,
            'planned_features' => ['Control de lotes', 'Alertas de vencimiento', 'Registro de recetas'],
        ],
    ];

    /**
     * Corrección real (2026-09-04): `/install` es una ruta central sin
     * restricción de dominio — Laravel la sirve sin importar el host. Si se
     * entra desde el dominio de un tenant real que YA existe, hay que
     * bloquear, no solo por prolijidad: `TenancyServiceProvider::bootLivewireUpdateRoute()`
     * (REQ-1.13) registra la ruta AJAX de Livewire con `InitializeTenancyByDomain`
     * a propósito, para que cualquier `wire:click` dentro de un tenant real
     * funcione — pero eso significa que si esta página se carga desde el
     * dominio de un tenant existente, CADA llamada de este wizard
     * (`nextStep()`/`finish()`) se ejecutaría por dentro de ESE tenant, no en
     * el dominio central. Reproducido de verdad: crear un tenant nuevo desde
     * `aguadescovery.localhost/install` (un tenant real ya existente) rompió
     * con `SQLSTATE[42S02]: Table 'tenant....plans' doesn't exist` — la
     * validación del plan corrió contra la base de ESE tenant, no landlord.
     *
     * **Reanudar tras recarga/cierre accidental durante el aprovisionamiento
     * (hallazgo 2026-09-05).** `$provisioning`/`$provisioningToken` son
     * propiedades de Livewire — sobreviven un re-render AJAX pero no una
     * recarga completa de página (`mount()` arranca de cero). Sin esto, un
     * usuario que recarga/cierra la pestaña mientras `ProvisionTenantJob`
     * corre en la cola vuelve a `/install` y ve el wizard vacío en el paso 0,
     * sin ninguna señal de que su instalación sigue (o ya terminó) del otro
     * lado. El token se guarda en la sesión de Laravel (no solo en la
     * propiedad del componente) en `finish()` — acá se lee ANTES del guard de
     * dominio de arriba, porque si hay un token pendiente hay que resolverlo
     * sin importar en qué dominio se cargó `/install`.
     */
    public function mount(): void
    {
        if ($token = session('install_wizard_token')) {
            $status = Cache::get("install-wizard:{$token}");

            if (! $status) {
                // Token vencido (TTL de 10 min, igual que el resto del mecanismo)
                // o nunca existió — no hay nada que reanudar, arranca de cero.
                session()->forget('install_wizard_token');
            } elseif ($status['status'] === 'done') {
                session()->forget('install_wizard_token');
                $this->provisioning = true;
                $this->provisioningDone = true;
                $this->tenantLoginUrl = $status['login_url'];

                return;
            } else {
                // pending o failed — reengancha a la pantalla de progreso/error
                // en vez de mostrar un wizard en blanco.
                $this->provisioningToken = $token;
                $this->provisioning = true;
                $this->provisioningError = $status['status'] === 'failed' ? $status['message'] : null;

                return;
            }
        }

        if (Domain::where('domain', request()->getHost())->exists()) {
            $this->redirect('/dashboard', navigate: false);
        }
    }

    /** Tarjetas del paso Tipo de Negocio (REQ-4.4) — todo `BUSINESS_TYPES` tal cual, incluidas las no disponibles (la vista decide cómo pintarlas). */
    public static function businessTypeCards(): array
    {
        return self::BUSINESS_TYPES;
    }

    /** Claves seleccionables de verdad — excluye los placeholders de roadmap (`available === false`). Única fuente de verdad para la validación del servidor. */
    private static function availableBusinessTypes(): array
    {
        return collect(self::BUSINESS_TYPES)
            ->filter(fn (array $type) => ($type['available'] ?? true) === true)
            ->keys()
            ->all();
    }

    /** Auto-slug (Odoo-style) — ver docblock de $subdominioTouched. */
    public function updatedNombreEmpresa(): void
    {
        if (! $this->subdominioTouched) {
            $this->subdominio = Str::slug($this->nombreEmpresa, '-');
        }
    }

    public function updatedSubdominio(): void
    {
        $this->subdominioTouched = true;
    }

    /**
     * REQ-4.4 — pre-carga `$selectedModules` con la sugerencia del tipo de
     * negocio elegido (step-tipo-negocio.blade.php). Pisa cualquier ajuste manual previo a propósito: si el
     * usuario cambia de tipo de negocio, la sugerencia anterior ya no aplica
     * (mismo criterio que un formulario normal al cambiar una selección que
     * determina otras) — distinto del caso subdominio/nombreEmpresa, que sí
     * necesita "no pisar" porque ahí el usuario edita el campo derivado
     * directamente, no la fuente de la sugerencia.
     */
    public function updatedBusinessType(): void
    {
        $this->selectedModules = self::BUSINESS_TYPES[$this->businessType]['suggested_modules'] ?? [];
    }

    /**
     * Módulos satélite reales (`config('modules')`, category `satellite`) —
     * la fuente de verdad de qué checkboxes existen en step-empresa. No se
     * lista `base_flexible` acá: esos vienen encendidos por defecto en todo
     * Plan y el dueño los apaga después desde "Funcionalidades del Sistema",
     * no son parte de esta curaduría de instalación (ver docs/analisis/
     * modulos-base-satelite.md §2.1.5).
     */
    public function getSatelliteModulesProperty(): array
    {
        return collect(config('modules'))
            ->filter(fn (array $module) => $module['category'] === 'satellite')
            ->all();
    }

    /**
     * Módulos "núcleo flexible" (`config('modules')`, category `base_flexible`)
     * — vienen encendidos por defecto en TODA instalación, sin importar el
     * tipo de negocio (docs/analisis/modulos-base-satelite.md §2.1.5). Se
     * muestran como badge en cada tarjeta de step-tipo-negocio junto a los
     * satélite sugeridos, para no dejar una tarjeta sin ninguna
     * funcionalidad listada solo porque ese tipo no sugiere ningún satélite
     * — es información real (lo que trae cualquier instalación), no una
     * sugerencia que dependa de la tarjeta elegida.
     */
    public function getBaseFlexibleModulesProperty(): array
    {
        return collect(config('modules'))
            ->filter(fn (array $module) => $module['category'] === 'base_flexible')
            ->all();
    }

    /**
     * Feedback en vivo mientras se escribe (preview de step-empresa, vista
     * "no tocada" estilo Odoo) — la validación real corre en
     * `rulesForStep(1)`, vía `Rule::notIn(self::RESERVED_SUBDOMAINS)` y la
     * closure de duplicado; este método devuelve el mismo motivo en texto
     * para mostrarlo ANTES de que el usuario intente avanzar, con el mismo
     * estilo de error que el resto del formulario (rojo, sin ícono aparte —
     * pedido explícito: nada de triángulo/amarillo).
     */
    public function getSubdomainUnavailableReasonProperty(): ?string
    {
        if ($this->subdominio === '') {
            return null;
        }

        if (in_array($this->subdominio, self::RESERVED_SUBDOMAINS, true)) {
            return 'Ese subdominio está reservado.';
        }

        if (Domain::where('domain', $this->fullDomain())->exists()) {
            return 'Ese subdominio ya está en uso.';
        }

        return null;
    }

    /**
     * Lista estática de las 32 provincias de RD — mismos ids que
     * `database/seeders/sql/geo_data_rd.sql` (el dump inserta ids explícitos,
     * no autoincrementales, así que el id elegido acá coincide con el real
     * una vez que el tenant nuevo se siembra). No se consulta ninguna base:
     * cuando este paso se muestra, el `Tenant` (y su `provinces`) todavía no
     * existe. Decisión explícita (2026-09-04): quedan estáticas acá en vez
     * de moverse a landlord como `Plan` (REQ-3.4) — a diferencia de `Plan`,
     * tienen FKs reales en varias tablas de tenant (`configuraciones_generales`,
     * `clients`, etc.), migrar eso es un trabajo bastante más grande que no
     * se justifica solo para esta pantalla.
     */
    public function provinceOptions(): array
    {
        return [
            1 => 'Distrito Nacional', 2 => 'Azua', 3 => 'Baoruco', 4 => 'Barahona',
            5 => 'Dajabón', 6 => 'Duarte', 7 => 'Elías Piña', 8 => 'El Seibo',
            9 => 'Espaillat', 10 => 'Independencia', 11 => 'La Altagracia', 12 => 'La Romana',
            13 => 'La Vega', 14 => 'María Trinidad Sánchez', 15 => 'Monte Cristi', 16 => 'Pedernales',
            17 => 'Peravia', 18 => 'Puerto Plata', 19 => 'Hermanas Mirabal', 20 => 'Samaná',
            21 => 'San Cristóbal', 22 => 'San Juan', 23 => 'San Pedro de Macorís', 24 => 'Sanchez Ramírez',
            25 => 'Santiago', 26 => 'Santiago Rodríguez', 27 => 'Valverde', 28 => 'Monseñor Nouel',
            29 => 'Monte Plata', 30 => 'Hato Mayor', 31 => 'San José de Ocoa', 32 => 'Santo Domingo',
        ];
    }

    /**
     * Lista estática de los 158 municipios de RD (mismos ids que el dump),
     * agrupados por `province_id` para el cascadeo Provincia→Municipio del
     * lado del cliente (Alpine, mismo patrón que ya usaba el wizard viejo —
     * antes con datos de `Municipality::all()`, ahora con este array).
     */
    public function municipalityOptions(): array
    {
        return [
            1 => ['name' => 'Santo Domingo de Guzmán', 'province_id' => 1],
            2 => ['name' => 'Azua', 'province_id' => 2],
            3 => ['name' => 'Las Charcas', 'province_id' => 2],
            4 => ['name' => 'Las Yayas de Viajama', 'province_id' => 2],
            5 => ['name' => 'Padre Las Casas', 'province_id' => 2],
            6 => ['name' => 'Peralta', 'province_id' => 2],
            7 => ['name' => 'Sabana Yegua', 'province_id' => 2],
            8 => ['name' => 'Pueblo Viejo', 'province_id' => 2],
            9 => ['name' => 'Tábara Arriba', 'province_id' => 2],
            10 => ['name' => 'Guayabal', 'province_id' => 2],
            11 => ['name' => 'Estebanía', 'province_id' => 2],
            12 => ['name' => 'Neiba', 'province_id' => 3],
            13 => ['name' => 'Galván', 'province_id' => 3],
            14 => ['name' => 'Tamayo', 'province_id' => 3],
            15 => ['name' => 'Villa Jaragua', 'province_id' => 3],
            16 => ['name' => 'Los Ríos', 'province_id' => 3],
            17 => ['name' => 'Barahona', 'province_id' => 4],
            18 => ['name' => 'Cabral', 'province_id' => 4],
            19 => ['name' => 'Enriquillo', 'province_id' => 4],
            20 => ['name' => 'Paraíso', 'province_id' => 4],
            21 => ['name' => 'Vicente Noble', 'province_id' => 4],
            22 => ['name' => 'El Peñón', 'province_id' => 4],
            23 => ['name' => 'La Ciénaga', 'province_id' => 4],
            24 => ['name' => 'Fundación', 'province_id' => 4],
            25 => ['name' => 'Las Salinas', 'province_id' => 4],
            26 => ['name' => 'Polo', 'province_id' => 4],
            27 => ['name' => 'Jaquimeyes', 'province_id' => 4],
            28 => ['name' => 'Dajabón', 'province_id' => 5],
            29 => ['name' => 'Loma de Cabrera', 'province_id' => 5],
            30 => ['name' => 'Partido', 'province_id' => 5],
            31 => ['name' => 'Restauración', 'province_id' => 5],
            32 => ['name' => 'El Pino', 'province_id' => 5],
            33 => ['name' => 'San Francisco de Macorís', 'province_id' => 6],
            34 => ['name' => 'Arenoso', 'province_id' => 6],
            35 => ['name' => 'Castillo', 'province_id' => 6],
            36 => ['name' => 'Pimentel', 'province_id' => 6],
            37 => ['name' => 'Villa Riva', 'province_id' => 6],
            38 => ['name' => 'Las Guáranas', 'province_id' => 6],
            39 => ['name' => 'Eugenio María de Hostos', 'province_id' => 6],
            40 => ['name' => 'Comendador', 'province_id' => 7],
            41 => ['name' => 'Bánica', 'province_id' => 7],
            42 => ['name' => 'El Llano', 'province_id' => 7],
            43 => ['name' => 'Hondo Valle', 'province_id' => 7],
            44 => ['name' => 'Pedro Santana', 'province_id' => 7],
            45 => ['name' => 'Juan Santiago', 'province_id' => 7],
            46 => ['name' => 'El Seibo', 'province_id' => 8],
            47 => ['name' => 'Miches', 'province_id' => 8],
            48 => ['name' => 'Moca', 'province_id' => 9],
            49 => ['name' => 'Cayetano Germosén', 'province_id' => 9],
            50 => ['name' => 'Gaspar Hernández', 'province_id' => 9],
            51 => ['name' => 'Jamao Al Norte', 'province_id' => 9],
            52 => ['name' => 'San Víctor', 'province_id' => 9],
            53 => ['name' => 'Jimaní', 'province_id' => 10],
            54 => ['name' => 'Duvergé', 'province_id' => 10],
            55 => ['name' => 'La Descubierta', 'province_id' => 10],
            56 => ['name' => 'Postrer Río', 'province_id' => 10],
            57 => ['name' => 'Cristóbal', 'province_id' => 10],
            58 => ['name' => 'Mella', 'province_id' => 10],
            59 => ['name' => 'Higüey', 'province_id' => 11],
            60 => ['name' => 'San Rafael del Yuma', 'province_id' => 11],
            61 => ['name' => 'La Romana', 'province_id' => 12],
            62 => ['name' => 'Guaymate', 'province_id' => 12],
            63 => ['name' => 'Villa Hermosa', 'province_id' => 12],
            64 => ['name' => 'La Vega', 'province_id' => 13],
            65 => ['name' => 'Constanza', 'province_id' => 13],
            66 => ['name' => 'Jarabacoa', 'province_id' => 13],
            67 => ['name' => 'Jima Abajo', 'province_id' => 13],
            68 => ['name' => 'Nagua', 'province_id' => 14],
            69 => ['name' => 'Cabrera', 'province_id' => 14],
            70 => ['name' => 'El Factor', 'province_id' => 14],
            71 => ['name' => 'Río San Juan', 'province_id' => 14],
            72 => ['name' => 'Monte Cristi', 'province_id' => 15],
            73 => ['name' => 'Castañuelas', 'province_id' => 15],
            74 => ['name' => 'Guayubín', 'province_id' => 15],
            75 => ['name' => 'Las Matas de Santa Cruz', 'province_id' => 15],
            76 => ['name' => 'Pepillo Salcedo', 'province_id' => 15],
            77 => ['name' => 'Villa Vásquez', 'province_id' => 15],
            78 => ['name' => 'Pedernales', 'province_id' => 16],
            79 => ['name' => 'Oviedo', 'province_id' => 16],
            80 => ['name' => 'Baní', 'province_id' => 17],
            81 => ['name' => 'Nizao', 'province_id' => 17],
            82 => ['name' => 'Matanzas', 'province_id' => 17],
            83 => ['name' => 'Puerto Plata', 'province_id' => 18],
            84 => ['name' => 'Altamira', 'province_id' => 18],
            85 => ['name' => 'Guananico', 'province_id' => 18],
            86 => ['name' => 'Imbert', 'province_id' => 18],
            87 => ['name' => 'Los Hidalgos', 'province_id' => 18],
            88 => ['name' => 'Luperón', 'province_id' => 18],
            89 => ['name' => 'Sosúa', 'province_id' => 18],
            90 => ['name' => 'Villa Isabela', 'province_id' => 18],
            91 => ['name' => 'Villa Montellano', 'province_id' => 18],
            92 => ['name' => 'Salcedo', 'province_id' => 19],
            93 => ['name' => 'Tenares', 'province_id' => 19],
            94 => ['name' => 'Villa Tapia', 'province_id' => 19],
            95 => ['name' => 'Samaná', 'province_id' => 20],
            96 => ['name' => 'Sánchez', 'province_id' => 20],
            97 => ['name' => 'Las Terrenas', 'province_id' => 20],
            98 => ['name' => 'San Cristóbal', 'province_id' => 21],
            99 => ['name' => 'Sabana Grande de Palenque', 'province_id' => 21],
            100 => ['name' => 'Bajos de Haina', 'province_id' => 21],
            101 => ['name' => 'Cambita Garabitos', 'province_id' => 21],
            102 => ['name' => 'Villa Altagracia', 'province_id' => 21],
            103 => ['name' => 'Yaguate', 'province_id' => 21],
            104 => ['name' => 'San Gregorio de Nigua', 'province_id' => 21],
            105 => ['name' => 'Los Cacaos', 'province_id' => 21],
            106 => ['name' => 'San Juan', 'province_id' => 22],
            107 => ['name' => 'Bohechío', 'province_id' => 22],
            108 => ['name' => 'El Cercado', 'province_id' => 22],
            109 => ['name' => 'Juan de Herrera', 'province_id' => 22],
            110 => ['name' => 'Las Matas de Farfán', 'province_id' => 22],
            111 => ['name' => 'Vallejuelo', 'province_id' => 22],
            112 => ['name' => 'San Pedro de Macorís', 'province_id' => 23],
            113 => ['name' => 'Los Llanos', 'province_id' => 23],
            114 => ['name' => 'Ramón Santana', 'province_id' => 23],
            115 => ['name' => 'Consuelo', 'province_id' => 23],
            116 => ['name' => 'Quisqueya', 'province_id' => 23],
            117 => ['name' => 'Guayacanes', 'province_id' => 23],
            118 => ['name' => 'Cotuí', 'province_id' => 24],
            119 => ['name' => 'Cevicos', 'province_id' => 24],
            120 => ['name' => 'Fantino', 'province_id' => 24],
            121 => ['name' => 'Villa La Mata', 'province_id' => 24],
            122 => ['name' => 'Santiago', 'province_id' => 25],
            123 => ['name' => 'Bisonó', 'province_id' => 25],
            124 => ['name' => 'Jánico', 'province_id' => 25],
            125 => ['name' => 'Licey al Medio', 'province_id' => 25],
            126 => ['name' => 'San José de Las Matas', 'province_id' => 25],
            127 => ['name' => 'Tamboril', 'province_id' => 25],
            128 => ['name' => 'Villa González', 'province_id' => 25],
            129 => ['name' => 'Puñal', 'province_id' => 25],
            130 => ['name' => 'Sabana Iglesia', 'province_id' => 25],
            131 => ['name' => 'Baitoa', 'province_id' => 25],
            132 => ['name' => 'San Ignacio de Sabaneta', 'province_id' => 26],
            133 => ['name' => 'Villa Los Almácigos', 'province_id' => 26],
            134 => ['name' => 'Monción', 'province_id' => 26],
            135 => ['name' => 'Mao', 'province_id' => 27],
            136 => ['name' => 'Esperanza', 'province_id' => 27],
            137 => ['name' => 'Laguna Salada', 'province_id' => 27],
            138 => ['name' => 'Bonao', 'province_id' => 28],
            139 => ['name' => 'Maimón', 'province_id' => 28],
            140 => ['name' => 'Piedra Blanca', 'province_id' => 28],
            141 => ['name' => 'Monte Plata', 'province_id' => 29],
            142 => ['name' => 'Bayaguana', 'province_id' => 29],
            143 => ['name' => 'Sabana Grande de Boyá', 'province_id' => 29],
            144 => ['name' => 'Yamasá', 'province_id' => 29],
            145 => ['name' => 'Peralvillo', 'province_id' => 29],
            146 => ['name' => 'Hato Mayor', 'province_id' => 30],
            147 => ['name' => 'Sabana de La Mar', 'province_id' => 30],
            148 => ['name' => 'El Valle', 'province_id' => 30],
            149 => ['name' => 'San José de Ocoa', 'province_id' => 31],
            150 => ['name' => 'Sabana Larga', 'province_id' => 31],
            151 => ['name' => 'Rancho Arriba', 'province_id' => 31],
            152 => ['name' => 'Santo Domingo Este', 'province_id' => 32],
            153 => ['name' => 'Santo Domingo Oeste', 'province_id' => 32],
            154 => ['name' => 'Santo Domingo Norte', 'province_id' => 32],
            155 => ['name' => 'Boca Chica', 'province_id' => 32],
            156 => ['name' => 'San Antonio de Guerra', 'province_id' => 32],
            157 => ['name' => 'Los Alcarrizos', 'province_id' => 32],
            158 => ['name' => 'Pedro Brand', 'province_id' => 32],
        ];
    }

    public function getPlansProperty()
    {
        return Plan::orderBy('price')->get();
    }

    public function getSelectedPlanProperty(): ?Plan
    {
        return $this->planId ? Plan::find($this->planId) : null;
    }

    /**
     * REQ-4.4/4.5 — el Plan más barato de los que ya incluyen todos los
     * módulos sugeridos por el tipo de negocio elegido. Deriva de
     * `Plan::moduleKeys()` en vez de mapear tipo de negocio → slug de Plan a
     * mano: si mañana cambia qué Plan trae qué módulo (`PlanSeeder`), esta
     * recomendación se actualiza sola, sin tocar `BUSINESS_TYPES`. Sin tipo
     * de negocio elegido, o si ninguno lo sugiere, recomienda el más barato
     * (comportamiento previo: el plan del medio como "más popular").
     */
    public function getRecommendedPlanProperty(): ?Plan
    {
        return $this->recommendedPlanFor($this->businessType);
    }

    /**
     * Mismo cálculo que `getRecommendedPlanProperty()` pero para un tipo de
     * negocio arbitrario, no solo el elegido — usado por step-tipo-negocio
     * para mostrar "Sugerido: Plan X" en cada tarjeta antes de seleccionarla
     * (mockup "Instalación Paso 3"), no solo después.
     */
    public function recommendedPlanFor(?string $typeKey): ?Plan
    {
        $suggested = self::BUSINESS_TYPES[$typeKey]['suggested_modules'] ?? [];

        if ($suggested === []) {
            return $this->plans->first();
        }

        return $this->plans->first(
            fn (Plan $plan) => array_diff($suggested, $plan->moduleKeys()) === []
        ) ?? $this->plans->first();
    }

    /** Label del tipo de negocio elegido — usado por step-plan para el badge "RECOMENDADO PARA TI" (REQ-4.4/4.5). */
    public function getBusinessTypeLabelProperty(): ?string
    {
        return self::BUSINESS_TYPES[$this->businessType]['label'] ?? null;
    }

    /**
     * REQ-4.4/4.5 — labels de los módulos sugeridos por el tipo de negocio
     * elegido que ESTE plan puntual no incluye. Elección de diseño explícita
     * (2026-09-05, tras comparar con el "diagnóstico" de Alegra que oculta
     * el resto de los planes): acá no se bloquea nada — el usuario sigue
     * pudiendo elegir cualquier plan — pero si elige uno que no cubre lo que
     * su tipo de negocio sugiere, step-plan se lo dice explícitamente en vez
     * de apagar esos módulos en silencio (lo que hacía antes de este
     * cambio). Vacío si no hay tipo de negocio elegido o si el plan ya
     * cubre todo lo sugerido.
     */
    public function missingModulesFor(Plan $plan): array
    {
        $suggested = self::BUSINESS_TYPES[$this->businessType]['suggested_modules'] ?? [];

        if ($suggested === []) {
            return [];
        }

        $missingKeys = array_diff($suggested, $plan->moduleKeys());

        return collect($missingKeys)
            ->map(fn (string $key) => $this->satelliteModules[$key]['label'] ?? $key)
            ->all();
    }

    public function nextStep(): void
    {
        $this->validate($this->rulesForStep($this->step));

        $this->stepDirection = 'forward';
        $this->step++;
    }

    /**
     * "Atrás" es siempre seguro en cualquier paso previo a Finalizar — nada
     * se persiste todavía (ver docblock de la clase). Sin casos especiales.
     */
    public function prevStep(): void
    {
        $this->stepDirection = 'backward';
        $this->step = max(0, $this->step - 1);
    }

    /**
     * "Editar" en el paso Finalizar (rediseño 2026-09-05, mockup "Paso 5") —
     * salta directo a un paso anterior en vez de volver de a uno. Mismo
     * principio que prevStep(): siempre seguro, nada se persiste antes de
     * "Comenzar ahora". Sin validar el paso destino a propósito — si el
     * usuario ya llegó a Finalizar, todos los pasos previos ya pasaron su
     * propia validación al menos una vez.
     */
    public function goToStep(int $step): void
    {
        $this->stepDirection = $step >= $this->step ? 'forward' : 'backward';
        $this->step = max(0, min($step, 4));
    }

    /**
     * Título/subtítulo/ancho por paso (rediseño 2026-09-05, mockups Stitch
     * "Instalación Paso N") — vive en el componente y no en cada partial
     * porque install-wizard.blade.php los renderiza AFUERA de la card
     * (mejor jerarquía visual que title+subtitle metidos dentro de la
     * misma caja que el formulario, como estaba antes). El paso Plan (3) no
     * tiene subtítulo fijo acá — install-wizard.blade.php arma el suyo
     * dinámico con `$this->businessTypeLabel`/`$this->recommendedPlan`.
     */
    private const STEP_META = [
        0 => ['title' => 'Crea tu cuenta maestra', 'subtitle' => 'Esta cuenta tendrá acceso total para configurar y administrar tu punto de venta.', 'maxWidth' => '500px'],
        1 => ['title' => 'Datos de tu Empresa', 'subtitle' => 'Identidad corporativa, subdominio de acceso y datos fiscales requeridos para facturación.', 'maxWidth' => '680px'],
        2 => ['title' => '¿Cuál es el modelo de tu negocio?', 'subtitle' => 'Activamos las funciones ideales para tu día a día y te sugerimos el plan óptimo para tu operación.', 'maxWidth' => '940px'],
        3 => ['title' => 'Selecciona el plan para tu negocio', 'subtitle' => null, 'maxWidth' => '1024px'],
        4 => ['title' => 'Revisión final de tu instalación', 'subtitle' => 'Verificá la información de tu cuenta, tu empresa y el modelo configurado antes de comenzar a operar.', 'maxWidth' => '840px'],
    ];

    public function getStepMetaProperty(): array
    {
        return self::STEP_META[$this->step] ?? self::STEP_META[0];
    }

    private function fullDomain(): string
    {
        return "{$this->subdominio}." . request()->getHost();
    }

    /**
     * Confirma la revisión, despacha `ProvisionTenantJob` a la cola (Redis)
     * y prende la pantalla de progreso — este método YA NO crea nada él
     * mismo. Corría todo síncrono dentro de esta misma request (~12-15s
     * medidos) hasta que se movió a una cola: sin límite de tiempo propio,
     * un request HTTP corriendo esos mismos 12-15s queda expuesto al timeout
     * del servidor/proxy en producción, y si algo corta la request a mitad
     * de camino el `Tenant` queda huérfano (base creada, seed a medias, sin
     * `Subscription`). El archivo del logo se extrae ACÁ (path + nombre
     * real) porque el objeto `TemporaryUploadedFile` de Livewire no viaja de
     * forma confiable a través de la serialización hacia el worker — el Job
     * solo recibe strings.
     */
    public function finish(): void
    {
        $this->validate($this->rulesForStep(3));

        $this->provisioningError = null;
        $this->provisioningToken = (string) Str::uuid();
        $loginUrl = $this->tenantLoginUrl();

        Cache::put("install-wizard:{$this->provisioningToken}", ['status' => 'pending'], now()->addMinutes(10));

        // Sesión, no solo la propiedad del componente — ver docblock de mount()
        // sobre por qué (reanudar tras recarga/cierre de pestaña).
        session(['install_wizard_token' => $this->provisioningToken]);

        ProvisionTenantJob::dispatch(
            token: $this->provisioningToken,
            fullDomain: $this->fullDomain(),
            loginUrl: $loginUrl,
            adminName: $this->adminName,
            adminEmail: $this->adminEmail,
            adminPassword: Hash::make($this->adminPassword),
            empresaData: [
                'nombreEmpresa' => $this->nombreEmpresa,
                'taxId' => $this->taxId,
                'taxIdentifierType' => $this->taxIdentifierType,
                'telefono' => $this->telefono,
                'email' => $this->email,
                'direccion' => $this->direccion,
                'provinciaId' => $this->provinciaId,
                'municipioId' => $this->municipioId,
            ],
            logoTemporaryUploadPath: $this->logo?->getRealPath(),
            logoOriginalName: $this->logo?->getClientOriginalName(),
            planId: $this->planId,
            selectedModules: $this->selectedModules,
        );

        $this->provisioning = true;
    }

    /**
     * Sondeado por `wire:poll.2s` desde `step-provisioning.blade.php` — el
     * Job corre en un proceso de worker aparte, no hay ninguna respuesta
     * HTTP de la que colgarse para saber cuándo terminó, así que se pregunta
     * el estado cada 2 segundos vía `Cache` (misma clave que el Job usa para
     * avisar `done`/`failed`, ver `ProvisionTenantJob::handle()`).
     */
    public function checkProvisioningStatus(): void
    {
        if ($this->provisioningDone) {
            // Ya se resolvió — wire:poll sigue llamando cada 2s porque no hay
            // forma simple de pausarlo desde acá sin JS extra; esto lo hace
            // barato (sin Cache::get de más) en vez de complicar el poll.
            return;
        }

        $status = Cache::get("install-wizard:{$this->provisioningToken}");

        if (! $status || $status['status'] === 'pending') {
            return;
        }

        if ($status['status'] === 'failed') {
            $this->provisioning = false;
            $this->provisioningError = $status['message'];

            return;
        }

        session()->forget('install_wizard_token');

        // Ya NO se navega sola (histórico de intentos abajo) — deja la
        // decisión en manos del usuario, con un link real.
        //
        // Intento 1: `$this->redirect(...)` (script `window.location.href =`)
        // con el guard de beforeunload quitado a tiempo vía este mismo evento.
        // Funcionó en el navegador de prueba, pero el usuario lo reprodujo en
        // Chrome real y en Edge: el diálogo nativo "¿Quieres salir del sitio
        // web?" seguía saliendo.
        //
        // Intento 2: reemplazar el redirect por un click de `<a>` armado a
        // mano en JS (la teoría: Chrome tiene un heurístico propio,
        // independiente de cualquier `beforeunload`, para redirects por
        // script después de interactuar con un campo de contraseña). Rompió
        // la redirección por completo sin confirmar la teoría.
        //
        // Confirmación real (2026-09-05, encontrada por el usuario probando
        // el signup real de Odoo): el propio Odoo pisa el mismo problema —
        // un intento de crear una base de datos y entrar automáticamente
        // terminó en su propia pantalla de error ("Ocurrió un problema
        // durante la creación de tu base de datos"). Es una limitación real
        // de los navegadores modernos con redirects automáticos post-signup,
        // no algo arreglable a fuerza de JS. Se abandona el auto-redirect:
        // `$provisioningDone`/`$tenantLoginUrl` hacen que step-provisioning
        // muestre una pantalla de éxito con un `<a href>` real ("Entrar a mi
        // negocio") — un click real de usuario, no un script, así que ningún
        // heurístico de navegador lo bloquea — más un botón "Copiar" por si
        // prefiere pegar la URL en otra pestaña. Este evento solo queda para
        // desregistrar el guard de beforeunload, ya no para navegar.
        $this->dispatch('provisioning-done');
        $this->provisioningDone = true;
        $this->tenantLoginUrl = $status['login_url'];
    }

    private function tenantLoginUrl(): string
    {
        $port = request()->getPort();
        $portSuffix = in_array($port, [80, 443], true) || $port === null ? '' : ":{$port}";

        return request()->getScheme().'://'.$this->fullDomain().$portSuffix.'/login';
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function rulesForStep(int $step): array
    {
        return match ($step) {
            0 => [
                // Sin Rule::unique(User::class): el admin es siempre el primer
                // usuario de un Tenant que todavía no existe — no hay contra
                // qué comparar, y User es un modelo por-tenant (REQ-1.7). Sin
                // confirmación de contraseña (quitada en el rediseño 2026-09-05,
                // pedido explícito del usuario — no aporta nada real acá).
                'adminName' => ['required', 'string', 'max:255'],
                'adminEmail' => ['required', 'email', 'max:255'],
                'adminPassword' => ['required', 'string', Password::defaults()],
            ],
            1 => [
                'subdominio' => [
                    'required', 'string', 'min:3', 'max:63',
                    'regex:/^[a-z0-9]([a-z0-9-]*[a-z0-9])?$/',
                    Rule::notIn(self::RESERVED_SUBDOMAINS),
                    // Rule::unique no sirve para esto — hay que comparar
                    // contra el dominio COMPLETO (subdominio + host), no
                    // contra el valor crudo del campo.
                    function (string $attribute, mixed $value, \Closure $fail): void {
                        if (Domain::where('domain', $this->fullDomain())->exists()) {
                            $fail('Ese subdominio ya está en uso.');
                        }
                    },
                ],
                'nombreEmpresa' => ['required', 'string', 'max:255'],
                'logo' => ['nullable', 'image', 'max:2048'],
                'taxIdentifierType' => ['required', Rule::enum(TaxIdentifierType::class)],
                'taxId' => ['required', 'string', 'max:50'],
                // Teléfono/correo del negocio (REQ-4.6) quedaron fuera del wizard en el
                // rediseño 2026-09-05 (pedido explícito) — son datos de operación del
                // día a día, no de arranque; se completan después desde "Configuración
                // General" dentro del tenant ya instalado. `telefono`/`email` siguen
                // existiendo como columnas nullable en `configuraciones_generales`.
                'direccion' => ['required', 'string', 'max:500'],
                'provinciaId' => ['required', Rule::in(array_keys($this->provinceOptions()))],
                'municipioId' => ['nullable', Rule::in(array_keys($this->municipalityOptions()))],
            ],
            2 => [
                // `nullable`, no `required` (rediseño 2026-09-05, pedido
                // explícito): el paso completo es omitible — elegir un tipo
                // de negocio es curaduría de UX opcional, no hay nada que de
                // verdad se rompa sin él (el Plan del paso siguiente sigue
                // siendo el techo real de todos modos). Rule::in solo contra
                // las claves disponibles de verdad — un placeholder de
                // roadmap (ej. `farmacias`) nunca pasa esta validación, ni
                // siquiera mandado a mano fuera de la UI.
                'businessType' => ['nullable', Rule::in(self::availableBusinessTypes())],
                'selectedModules' => ['array'],
                'selectedModules.*' => [Rule::in(array_keys($this->satelliteModules))],
            ],
            3 => [
                'planId' => ['required', Rule::exists('plans', 'id')],
            ],
            default => [],
        };
    }

    public function render()
    {
        return view('livewire.install.install-wizard')
            ->layout('layouts.install');
    }
}
