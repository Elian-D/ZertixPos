<?php

declare(strict_types=1);

use App\Models\Tenant;
use Stancl\Tenancy\Database\Models\Domain;

return [
    'tenant_model' => Tenant::class,
    'id_generator' => Stancl\Tenancy\UUIDGenerator::class,

    'domain_model' => Domain::class,

    /**
     * The list of domains hosting your central app.
     *
     * Only relevant if you're using the domain or subdomain identification middleware.
     */
    'central_domains' => [
        '127.0.0.1',
        'localhost',
        'zertixpos.com',
    ],

    /**
     * Tenancy bootstrappers are executed when tenancy is initialized.
     * Their responsibility is making Laravel features tenant-aware.
     *
     * To configure their behavior, see the config keys below.
     */
    'bootstrappers' => [
        Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper::class,
        Stancl\Tenancy\Bootstrappers\CacheTenancyBootstrapper::class,
        Stancl\Tenancy\Bootstrappers\FilesystemTenancyBootstrapper::class,
        Stancl\Tenancy\Bootstrappers\QueueTenancyBootstrapper::class,
        // Activado (2026-09-06) — requiere phpredis, ya confirmado instalado
        // (REDIS_CLIENT=phpredis en .env, extensión cargada). Sin esto, las
        // sesiones (SESSION_DRIVER=redis) quedaban sin aislar por tenant: a
        // diferencia de Cache::get()/put() (CacheTenancyBootstrapper), la
        // sesión resuelve el store vía Cache::store('redis') internamente
        // (Illuminate\Session\SessionManager::createCacheHandler()), que
        // devuelve el repositorio SIN pasar por el wrapper de tags de
        // Stancl\Tenancy\CacheManager::__call() — ese wrapper solo intercepta
        // llamadas directas tipo Cache::get()/put(), no las que ya pasaron
        // por ->store(). RedisTenancyBootstrapper resuelve esto a nivel de
        // conexión (OPT_PREFIX del cliente phpredis), por debajo de cualquier
        // capa de Cache, así que sí cubre este caso. Ver `redis.prefixed_connections`
        // abajo — tiene que incluir 'default' (la conexión que usa la sesión).
        Stancl\Tenancy\Bootstrappers\RedisTenancyBootstrapper::class,
    ],

    /**
     * Database tenancy config. Used by DatabaseTenancyBootstrapper.
     */
    'database' => [
        'central_connection' => env('DB_CONNECTION', 'central'),

        /**
         * Connection used as a "template" for the dynamically created tenant database connection.
         * Note: don't name your template connection tenant. That name is reserved by package.
         */
        'template_tenant_connection' => 'tenant_template', // REQ-1.5 — antes null (fallback implícito a 'mysql')

        /**
         * Tenant database names are created like this:
         * prefix + tenant_id + suffix.
         */
        'prefix' => 'tenant',
        'suffix' => '',

        /**
         * TenantDatabaseManagers are classes that handle the creation & deletion of tenant databases.
         */
        'managers' => [
            'sqlite' => Stancl\Tenancy\TenantDatabaseManagers\SQLiteDatabaseManager::class,
            'mysql' => Stancl\Tenancy\TenantDatabaseManagers\MySQLDatabaseManager::class,
            'mariadb' => Stancl\Tenancy\TenantDatabaseManagers\MySQLDatabaseManager::class,
            'pgsql' => Stancl\Tenancy\TenantDatabaseManagers\PostgreSQLDatabaseManager::class,

        /**
         * Use this database manager for MySQL to have a DB user created for each tenant database.
         * You can customize the grants given to these users by changing the $grants property.
         */
            // 'mysql' => Stancl\Tenancy\TenantDatabaseManagers\PermissionControlledMySQLDatabaseManager::class,

        /**
         * Disable the pgsql manager above, and enable the one below if you
         * want to separate tenant DBs by schemas rather than databases.
         */
            // 'pgsql' => Stancl\Tenancy\TenantDatabaseManagers\PostgreSQLSchemaManager::class, // Separate by schema instead of database
        ],
    ],

    /**
     * Cache tenancy config. Used by CacheTenancyBootstrapper.
     *
     * This works for all Cache facade calls, cache() helper
     * calls and direct calls to injected cache stores.
     *
     * Each key in cache will have a tag applied on it. This tag is used to
     * scope the cache both when writing to it and when reading from it.
     *
     * You can clear cache selectively by specifying the tag.
     */
    'cache' => [
        'tag_base' => 'tenant', // This tag_base, followed by the tenant_id, will form a tag that will be applied on each cache call.
    ],

    /**
     * Filesystem tenancy config. Used by FilesystemTenancyBootstrapper.
     * https://tenancyforlaravel.com/docs/v3/tenancy-bootstrappers/#filesystem-tenancy-boostrapper.
     */
    'filesystem' => [
        /**
         * Each disk listed in the 'disks' array will be suffixed by the suffix_base, followed by the tenant_id.
         */
        'suffix_base' => 'tenant',
        'disks' => [
            'local',
            'public',
            // 's3',
        ],

        /**
         * Use this for local disks.
         *
         * See https://tenancyforlaravel.com/docs/v3/tenancy-bootstrappers/#filesystem-tenancy-boostrapper
         */
        'root_override' => [
            // Disks whose roots should be overridden after storage_path() is suffixed.
            'local' => '%storage_path%/app/',
            'public' => '%storage_path%/app/public/',
        ],

        /**
         * Should storage_path() be suffixed.
         *
         * Note: Disabling this will likely break local disk tenancy. Only disable this if you're using an external file storage service like S3.
         *
         * For the vast majority of applications, this feature should be enabled. But in some
         * edge cases, it can cause issues (like using Passport with Vapor - see #196), so
         * you may want to disable this if you are experiencing these edge case issues.
         */
        'suffix_storage_path' => true,

        /**
         * By default, asset() calls are made multi-tenant too. You can use global_asset() and mix()
         * for global, non-tenant-specific assets. However, you might have some issues when using
         * packages that use asset() calls inside the tenant app. To avoid such issues, you can
         * disable asset() helper tenancy and explicitly use tenant_asset() calls in places
         * where you want to use tenant-specific assets (product images, avatars, etc).
         */
        // REQ-1.14 (v1.3.0 Fase 1): en false — asset() estático (Vite, public/img,
        // favicon, logo del sidebar) es igual para todos los tenants, no debe
        // pasar por /tenancy/assets/*. Lo que SÍ es de un tenant específico
        // (logo de empresa subido vía HandleStorage) usa tenant_asset() explícito
        // en su lugar (ver configuration/general/edit.blade.php y el ticket POS).
        'asset_helper_tenancy' => false,
    ],

    /**
     * Redis tenancy config. Used by RedisTenancyBootstrapper.
     *
     * Note: You need phpredis to use Redis tenancy.
     *
     * Note: You don't need to use this if you're using Redis only for cache.
     * Redis tenancy is only relevant if you're making direct Redis calls,
     * either using the Redis facade or by injecting it as a dependency.
     */
    'redis' => [
        'prefix_base' => 'tenant', // Each key in Redis will be prepended by this prefix_base, followed by the tenant id.
        'prefixed_connections' => [ // Redis connections whose keys are prefixed, to separate one tenant's keys from another.
            // 'default' es la conexión que usa SESSION_DRIVER=redis (config/session.php,
            // 'connection' => env('SESSION_CONNECTION') — null por defecto, cae en
            // 'default'). Es el único caso real hoy: no hay usos directos de la
            // fachada Redis:: en app/, y cache/queue ya se aíslan por sus propios
            // bootstrappers (CacheTenancyBootstrapper/QueueTenancyBootstrapper),
            // sin depender de esto.
            'default',
        ],
    ],

    /**
     * Features are classes that provide additional functionality
     * not needed for tenancy to be bootstrapped. They are run
     * regardless of whether tenancy has been initialized.
     *
     * See the documentation page for each class to
     * understand which ones you want to enable.
     */
    'features' => [
        // Stancl\Tenancy\Features\UserImpersonation::class,
        // Stancl\Tenancy\Features\TelescopeTags::class,
        // Stancl\Tenancy\Features\UniversalRoutes::class,
        // Stancl\Tenancy\Features\TenantConfig::class, // https://tenancyforlaravel.com/docs/v3/features/tenant-config
        // Stancl\Tenancy\Features\CrossDomainRedirect::class, // https://tenancyforlaravel.com/docs/v3/features/cross-domain-redirect
        // Stancl\Tenancy\Features\ViteBundler::class,
    ],

    /**
     * Should tenancy routes be registered.
     *
     * Tenancy routes include tenant asset routes. By default, this route is
     * enabled. But it may be useful to disable them if you use external
     * storage (e.g. S3 / Dropbox) or have a custom asset controller.
     */
    'routes' => true,

    /**
     * Parameters used by the tenants:migrate command.
     *
     * `--schema-path` (2026-09-04, perf real) — sin esto, un tenant NUEVO
     * corre las 84 migraciones de tenant una por una: confirmado con
     * medición real (proceso limpio, no acumulado) que eso solo tarda
     * 38-40s, siendo el cuello de botella real de crear un tenant (el
     * db:seed que le sigue tarda ~1.4s). El dump (`schema/mysql-schema.sql`,
     * generado con `php artisan schema:dump --database=tenant`, 52 CREATE
     * TABLE reales — coincide con lo que se ve en phpMyAdmin, las otras ~32
     * de las 84 migraciones crean algo que una migración posterior modifica
     * o elimina) se importa de una sola vez y deja las 84 filas de
     * `migrations` ya marcadas como corridas — cualquier migración nueva que
     * se agregue DESPUÉS de este dump sigue corriendo normal, encima del
     * dump. Regenerar con: `php artisan schema:dump --database=tenant
     * --path=database/migrations/tenant/schema/mysql-schema.sql` dentro de
     * `$tenant->run()` sobre un tenant ya migrado, cada vez que se agreguen
     * migraciones nuevas y se quiera consolidar de nuevo.
     */
    'migration_parameters' => [
        '--force' => true, // This needs to be true to run migrations in production.
        '--path' => [database_path('migrations/tenant')],
        '--realpath' => true,
        '--schema-path' => database_path('migrations/tenant/schema/mysql-schema.sql'),
    ],

    /**
     * Parameters used by the tenants:seed command.
     */
    'seeder_parameters' => [
        '--class' => 'DatabaseSeeder', // root seeder class
        // '--force' => true, // This needs to be true to seed tenant databases in production
    ],
];
