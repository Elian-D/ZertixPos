<?php

namespace Tests;

use App\Http\Middleware\Billing\EnsureSubscriptionActive;
use App\Http\Middleware\EnsureInstallationWizardCompleted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/**
 * Base de todo test que toque el negocio (usuarios, ventas, inventario…). Desde v1.3.0
 * (multi-tenant) el negocio vive en la base de cada tenant, así que aquí:
 *  - la base de testing hace de base del tenant y se migra con database/migrations/tenant,
 *    no con el esquema central;
 *  - las peticiones HTTP se saltan el middleware de tenancy (resolver el tenant por
 *    subdominio, bloquear el dominio central, el Wizard y la suscripción): sin esto las
 *    rutas de routes/tenant.php responden 404 desde localhost.
 *
 * Todos los tests con base de datos deben heredar de aquí: RefreshDatabase migra una sola
 * vez por proceso, así que un test que migre el esquema central rompe a los que corren
 * después.
 */
abstract class TenantTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            InitializeTenancyByDomain::class,
            PreventAccessFromCentralDomains::class,
            EnsureInstallationWizardCompleted::class,
            EnsureSubscriptionActive::class,
        ]);
    }

    protected function migrateFreshUsing()
    {
        return [
            '--path' => ['database/migrations/tenant'],
            '--realpath' => false,
            '--seed' => $this->shouldSeed(),
        ];
    }
}
