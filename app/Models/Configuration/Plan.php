<?php

namespace App\Models\Configuration;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Plan extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'price', 'currency', 'features', 'users_limit'];

    protected $casts = [
        'features' => 'array',
    ];

    /**
     * null = sin techo (PyME/Pro/Corporativo). Emprendedor es 1 — pensado para
     * un único dueño/operador (REQ-05.6).
     */
    public function canCreateMoreUsers(): bool
    {
        if (is_null($this->users_limit)) {
            return true;
        }

        return User::count() < $this->users_limit;
    }

    public function moduleKeys(): array
    {
        return DB::table('plan_module')->where('plan_id', $this->id)->pluck('module_key')->all();
    }

    public static function syncModules(int $planId, array $moduleKeys): void
    {
        DB::table('plan_module')->where('plan_id', $planId)->delete();

        DB::table('plan_module')->insert(
            collect($moduleKeys)->map(fn (string $key) => ['plan_id' => $planId, 'module_key' => $key])->all()
        );
    }

    /**
     * Copia explícita plan_module → installation_modules — no un join en vivo.
     * Si el plan cambia después (syncModules()), las instalaciones ya asignadas
     * no se ven afectadas hasta que alguien vuelva a llamar assignTo().
     */
    public function assignTo(): void
    {
        $moduleKeys = $this->moduleKeys();

        collect(config('modules'))->keys()->each(function (string $key) use ($moduleKeys) {
            InstallationModule::updateOrCreate(
                ['module_key' => $key],
                ['is_enabled' => in_array($key, $moduleKeys, true)]
            );
        });

        ConfiguracionGeneral::first()?->update(['plan_id' => $this->id]);
    }
}
