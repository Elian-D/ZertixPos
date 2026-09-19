<?php

namespace App\Jobs;

use App\Models\Configuration\ConfiguracionGeneral;
use App\Models\Configuration\InstallationModule;
use App\Models\Configuration\Plan;
use App\Models\Landlord\Subscription;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\UploadedFile;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * REQ-4.6/REQ-4.8, v1.3.0 Fase 4 — corre fuera del request HTTP a propósito.
 * Crear el `Tenant` (evento `TenantCreated` → `CreateDatabase`+`MigrateDatabase`,
 * ~12s medidos con el schema dump ya aplicado) + sembrar + admin + config +
 * plan + `Subscription` tardaba lo mismo DENTRO de la request de Livewire que
 * disparaba `InstallWizard::finish()` — sin límite propio, pero expuesto al
 * timeout del servidor/proxy en producción (Nginx, un balanceador, etc.). Si
 * ese límite corta la request a mitad de camino, el proceso PHP muere con
 * ella y el `Tenant` queda a medio crear: base física creada, algo de seed
 * corrido, sin `Subscription`, sin admin — un tenant fantasma real en la
 * base de datos. Sacarlo a una cola (Redis, ya usado en el proyecto para
 * cache/sesión) elimina el problema de raíz: no hay ninguna request HTTP de
 * la que depender, el trabajo corre hasta terminar sin importar cuánto tarde.
 *
 * El estado se comunica de vuelta al componente Livewire vía `Cache`, no vía
 * el retorno del Job (no existe tal cosa en un worker separado) — la clave
 * es el mismo `$token` que `InstallWizard::finish()` generó antes de
 * despachar este Job, y que la pantalla de progreso sondea con `wire:poll`
 * (ver `step-provisioning.blade.php`).
 */
class ProvisionTenantJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    /**
     * @param array<string, mixed> $empresaData Todo lo del paso Empresa, ya validado — nombre/tax/telefono/email/direccion/provinciaId/municipioId
     * @param string|null $logoTemporaryUploadPath Ruta del archivo temporal que deja WithFileUploads (`TemporaryUploadedFile::getRealPath()`), no el objeto en sí — no es serializable de forma segura hacia el worker
     */
    public function __construct(
        private readonly string $token,
        private readonly string $fullDomain,
        private readonly string $loginUrl,
        private readonly string $adminName,
        private readonly string $adminEmail,
        private readonly string $adminPassword,
        private readonly array $empresaData,
        private readonly ?string $logoTemporaryUploadPath,
        private readonly ?string $logoOriginalName,
        private readonly int $planId,
        /** @var string[] Claves de `config('modules')` (REQ-4.4) — ver docblock de `assignSelectedModules()`. */
        private readonly array $selectedModules = [],
    ) {
    }

    public function handle(): void
    {
        try {
            // Bug real (2026-09-14, reportado por el usuario): estas 3 columnas
            // quedaban vacías en todo tenant creado por el wizard — Tenant::create()
            // se llamaba sin argumentos. `business_name` (paso Empresa) y
            // `billing_contact_*` (paso Administrador — el admin creado más abajo
            // ES el contacto de facturación, ver comentario de la migración
            // 2026_09_01_100000_add_billing_fields_to_tenants_table.php: copia
            // landlord para que el Súper Admin no dependa de una consulta
            // cross-DB). `payment_gateway`/`gateway_customer_id`/
            // `gateway_subscription_id` de `tenants` siguen null acá a propósito
            // (no hay gateway real todavía en un trial sin tarjeta) — y de hecho
            // ninguna parte del código los escribe todavía en ningún punto
            // (`PayPalGateway` guarda el `gateway_subscription_id` real en
            // `subscriptions`, no en `tenants`); no es parte de este fix, quedan
            // como estaban.
            $tenant = Tenant::create([
                'business_name' => $this->empresaData['nombreEmpresa'],
                'billing_contact_name' => $this->adminName,
                'billing_contact_email' => $this->adminEmail,
            ]);
            $tenant->domains()->create(['domain' => $this->fullDomain]);

            $tenant->run(function () {
                Artisan::call('db:seed', ['--class' => 'DatabaseSeeder', '--force' => true]);

                $admin = User::create([
                    'name' => $this->adminName,
                    'email' => $this->adminEmail,
                    'password' => $this->adminPassword, // ya viene hasheado — ver InstallWizard::finish()
                    'email_verified_at' => now(),
                ]);
                $admin->assignRole('admin');

                ConfiguracionGeneral::first()->update([
                    'nombre_empresa' => $this->empresaData['nombreEmpresa'],
                    'logo' => $this->storeLogo(),
                    'tax_id' => $this->empresaData['taxId'] ?: null,
                    'tax_identifier_type' => $this->empresaData['taxIdentifierType'],
                    'telefono' => $this->empresaData['telefono'] ?: null,
                    'email' => $this->empresaData['email'] ?: null,
                    'direccion' => $this->empresaData['direccion'],
                    'provincia_id' => $this->empresaData['provinciaId'],
                    'municipio_id' => $this->empresaData['municipioId'],
                ]);

                $plan = Plan::findOrFail($this->planId);
                $plan->assignTo();
                $this->assignSelectedModules($plan);
            });

            // Landlord, fuera del run() de arriba a propósito — Subscription
            // está fija a la conexión 'landlord', pero Tenant::find() acá
            // necesita resolver otra vez fuera de cualquier tenant activo
            // (el modelo que devolvió run() no es seguro de reusar afuera).
            $tenant = Tenant::find($tenant->getTenantKey());
            Subscription::startTrial($tenant, $this->planId);

            Cache::put("install-wizard:{$this->token}", [
                'status' => 'done',
                'login_url' => $this->loginUrl,
            ], now()->addMinutes(10));
        } catch (Throwable $e) {
            Log::error('ProvisionTenantJob: fallo aprovisionando un tenant nuevo.', [
                'token' => $this->token,
                'full_domain' => $this->fullDomain,
                'error' => $e->getMessage(),
            ]);

            Cache::put("install-wizard:{$this->token}", [
                'status' => 'failed',
                'message' => 'No pudimos terminar de crear tu cuenta. Contactá a soporte.',
            ], now()->addMinutes(10));
        }
    }

    /**
     * REQ-4.4 — `Plan::assignTo()` (arriba) ya encendió TODOS los módulos
     * satélite que el Plan elegido incluye, sin importar el tipo de negocio
     * (es la conducta correcta para cuando el dueño cambia de Plan después,
     * desde "Funcionalidades del Sistema": ver su propio docblock). Acá se
     * afina esa base para el estado INICIAL de esta instalación puntual: de
     * los módulos que el Plan ya trae, solo quedan encendidos los que el
     * usuario marcó en el Wizard (`InstallWizard::$selectedModules`,
     * pre-cargado por tipo de negocio, ajustable a mano) — el resto se apaga
     * explícitamente. Nunca prende algo que el Plan no incluya: la
     * intersección contra `$plan->moduleKeys()` es la reconciliación que
     * REQ-4.4 pide ("la reconciliación final siempre queda acotada por el
     * Plan elegido").
     */
    private function assignSelectedModules(Plan $plan): void
    {
        $includedByPlan = $plan->moduleKeys();

        collect(config('modules'))
            ->filter(fn (array $module) => $module['category'] === 'satellite')
            ->keys()
            ->each(function (string $key) use ($includedByPlan) {
                InstallationModule::updateOrCreate(
                    ['module_key' => $key],
                    ['is_enabled' => in_array($key, $includedByPlan, true) && in_array($key, $this->selectedModules, true)]
                );
            });
    }

    /**
     * El archivo temporal de Livewire (`WithFileUploads`) vive en el disco
     * `local` central bajo `livewire-tmp/` — sigue existiendo cuando el Job
     * corre (Livewire no lo borra hasta que algo lo consume o expira), así
     * que se lee de ahí y se guarda en el disco `public` real, ya dentro del
     * `run()` del tenant (mismo `store('config', 'public')` que hacía el
     * `TemporaryUploadedFile` antes, pero manual porque el objeto real no
     * sobrevive la serialización hacia el worker).
     */
    private function storeLogo(): ?string
    {
        if (! $this->logoTemporaryUploadPath || ! file_exists($this->logoTemporaryUploadPath)) {
            return null;
        }

        $uploaded = new UploadedFile(
            $this->logoTemporaryUploadPath,
            $this->logoOriginalName ?? 'logo',
            null,
            null,
            true,
        );

        return $uploaded->store('config', 'public');
    }
}
