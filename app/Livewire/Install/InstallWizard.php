<?php

namespace App\Livewire\Install;

use App\Enums\TaxIdentifierType;
use App\Models\Configuration\ConfiguracionGeneral;
use App\Models\Configuration\Plan;
use App\Models\Geo\Municipality;
use App\Models\Geo\Province;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;
use Livewire\WithFileUploads;

class InstallWizard extends Component
{
    use WithFileUploads;

    public int $step = 0;

    // Paso 0 — Administrador (REQ-08.2)
    public string $adminName = '';

    public string $adminEmail = '';

    public string $adminPassword = '';

    public string $adminPasswordConfirmation = '';

    // Paso 1 — Empresa (REQ-08.3). country_id/currency/timezone no existen —
    // son constantes desde Fase 6 (config/regional.php), nada que preguntar.
    public string $nombreEmpresa = '';

    public $logo = null;

    public ?string $taxIdentifierType = null;

    public string $taxId = '';

    public string $telefono = '';

    public string $email = '';

    public string $direccion = '';

    public ?int $provinciaId = null;

    public ?int $municipioId = null;

    // Paso 2 — Plan (REQ-08.4)
    public ?int $planId = null;

    public function mount(): void
    {
        // Sin esto, una instalación que solo corrió `composer run setup`
        // (migrate, sin --seed — ver composer.json) llegaría al Wizard sin
        // provincias ni planes que mostrar en los selects. Idempotente
        // (todos los seeders core usan updateOrCreate), así que no duplica
        // nada si ya se sembró antes.
        if (Province::query()->doesntExist() || Plan::query()->doesntExist()) {
            Artisan::call('db:seed', ['--class' => 'DatabaseSeeder', '--force' => true]);
        }

        $default = Province::where('name', 'Distrito Nacional')->value('id');
        $this->provinciaId = $default ?? Province::query()->value('id');
    }

    /**
     * Todos los municipios (158 filas), no filtrados por provincia — el
     * filtrado provincia→municipio se hace en Alpine.js del lado del cliente
     * (ver step-empresa.blade.php), sin request de red, tal como REQ-06.9
     * definió para este mismo patrón en Configuración General/Clientes.
     *
     * La versión anterior filtraba server-side vía wire:model.live +
     * updatedProvinciaId(), lo que abría una condición de carrera real: el
     * request "en vivo" de provinciaId y el de municipioId podían pisarse
     * entre sí, y el municipio elegido se perdía en silencio (bug reportado
     * y reproducido — el municipio nunca llegaba a guardarse).
     */
    public function getMunicipalitiesProperty()
    {
        return Municipality::select('id', 'name', 'province_id')->orderBy('name')->get();
    }

    public function getProvincesProperty()
    {
        return Province::ordered()->get();
    }

    public function getPlansProperty()
    {
        return Plan::orderBy('price')->get();
    }

    public function getSelectedProvinceProperty(): ?Province
    {
        return $this->provinciaId ? Province::find($this->provinciaId) : null;
    }

    public function getSelectedMunicipioProperty(): ?Municipality
    {
        return $this->municipioId ? Municipality::find($this->municipioId) : null;
    }

    public function getSelectedPlanProperty(): ?Plan
    {
        return $this->planId ? Plan::find($this->planId) : null;
    }

    public function nextStep(): void
    {
        $this->validate($this->rulesForStep($this->step));
        $this->step++;
    }

    public function prevStep(): void
    {
        $this->step = max(0, $this->step - 1);
    }

    /**
     * Único punto donde se escribe algo — todo lo anterior (pasos 0 a 2, y el
     * paso 3 de revisión) es puramente en memoria del propio Livewire, nada
     * toca la base de datos todavía. Antes `complete()` (al final del paso de
     * Plan) ya creaba el usuario y sembraba el catálogo, así que "Volver" desde
     * la revisión no tenía sentido (ya estaba todo guardado) y el usuario no
     * podía corregir nada sin reiniciar. Ahora el paso 3 es una revisión real:
     * "← Volver" solo mueve $step, "Comenzar ahora" es lo único que persiste.
     */
    public function finish(): void
    {
        $this->validate($this->rulesForStep(2));

        DB::transaction(function () {
            // Corre PRIMERO: todos los seeders `core` son idempotentes
            // (updateOrCreate/firstOrCreate, o un guard explícito en los que hacían
            // insert crudo — ver GeoDataSeeder/AccountingAccountSeeder), pero
            // ConfiguracionGeneralSeeder en particular vuelve a dejar
            // `nombre_empresa`/`provincia_id` en sus valores por defecto vacíos.
            // Si este db:seed corriera después de escribir los datos reales de la
            // empresa, los volvería a pisar. "Consumidor Final" lo crea
            // ClientSeeder — sin UserSeeder en core desde REQ-07.13, así que esto
            // no vuelve a crear admin@local.com/usuario@local.com.
            Artisan::call('db:seed', ['--class' => 'DatabaseSeeder', '--force' => true]);

            $admin = User::create([
                'name' => $this->adminName,
                'email' => $this->adminEmail,
                'password' => $this->adminPassword, // cast 'hashed' en el modelo — ver App\Models\User
                'email_verified_at' => now(), // instalador confía en su propio correo, sin flujo de verificación
            ]);
            $admin->assignRole('admin');

            $logoPath = $this->logo ? $this->logo->store('config', 'public') : null;

            ConfiguracionGeneral::first()->update([
                'nombre_empresa' => $this->nombreEmpresa,
                'logo' => $logoPath,
                'tax_id' => $this->taxId ?: null,
                'tax_identifier_type' => $this->taxIdentifierType,
                'telefono' => $this->telefono,
                'email' => $this->email ?: null,
                'direccion' => $this->direccion,
                'provincia_id' => $this->provinciaId,
                'municipio_id' => $this->municipioId,
            ]);

            Plan::findOrFail($this->planId)->assignTo();

            // Login corre al final del mismo request que ya va a redirigir, no
            // antes — login() regenera la sesión (Laravel migra el ID por
            // seguridad), lo que invalida el token CSRF que el navegador tiene
            // en memoria. Si quedara una llamada Livewire después de esto en la
            // misma carga de página, usaría el token viejo y el servidor
            // respondería 419 (bug real, reproducido probando el flujo).
            Auth::login($admin);
        });

        session()->flash('success', 'Instalación completada. Ya podés empezar a usar el sistema.');

        // Fase 9 (Wizard de Completar Instalación) todavía no existe — redirige al
        // dashboard por ahora. Cuando se construya, este target cambia, y su ruta
        // NO debe llamarse 'install.*' (colisionaría con el guard de arriba, que
        // redirige lejos de cualquier install.* en cuanto isInstalled() es true).
        $this->redirect(route('dashboard'), navigate: false);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function rulesForStep(int $step): array
    {
        return match ($step) {
            0 => [
                'adminName' => ['required', 'string', 'max:255'],
                'adminEmail' => ['required', 'email', 'max:255', Rule::unique(User::class, 'email')],
                'adminPassword' => ['required', 'string', Password::defaults()],
                'adminPasswordConfirmation' => ['required', 'same:adminPassword'],
            ],
            1 => [
                'nombreEmpresa' => ['required', 'string', 'max:255'],
                'logo' => ['nullable', 'image', 'max:2048'],
                'taxIdentifierType' => ['required', Rule::enum(TaxIdentifierType::class)],
                'taxId' => ['required', 'string', 'max:50'],
                'telefono' => ['required', 'string', 'max:20'],
                'email' => ['nullable', 'email', 'max:255'],
                'direccion' => ['required', 'string', 'max:500'],
                'provinciaId' => ['required', Rule::exists('provinces', 'id')],
                'municipioId' => ['nullable', Rule::exists('municipalities', 'id')],
            ],
            2 => [
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
