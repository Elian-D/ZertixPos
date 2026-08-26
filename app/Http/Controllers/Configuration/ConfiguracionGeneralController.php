<?php

namespace App\Http\Controllers\Configuration;

use App\Enums\TaxIdentifierType;
use App\Http\Controllers\Controller;
use App\Models\Configuration\ConfiguracionGeneral;
use App\Models\Geo\Municipality;
use App\Models\Geo\Province;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ConfiguracionGeneralController extends Controller
{
    public function edit()
    {
        $config = ConfiguracionGeneral::actual();
        $provinces = Province::ordered()->get();

        // Precargado completo (~158 filas) — filtrado cascada provincia->municipio
        // se hace en Alpine.js del lado del cliente, sin request AJAX (Fase 6.9).
        $municipalities = Municipality::select('id', 'name', 'province_id')->orderBy('name')->get();

        $taxTypes = collect(TaxIdentifierType::cases())
            ->map(fn (TaxIdentifierType $type) => ['value' => $type->value, 'label' => $type->label()]);

        return view('configuration.general.edit', compact(
            'config',
            'provinces',
            'municipalities',
            'taxTypes'
        ));
    }

    public function update(Request $request)
    {
        $config = ConfiguracionGeneral::actual();

        $validated = $request->validate([
            'nombre_empresa' => 'required|string|max:255',
            'logo' => 'nullable|image|max:2048',
            'tax_id' => 'nullable|string|max:50',
            'tax_identifier_type' => ['nullable', Rule::enum(TaxIdentifierType::class)],
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'direccion' => 'nullable|string',
            'provincia_id' => 'required|exists:provinces,id',
            'municipio_id' => 'nullable|exists:municipalities,id',
            'dias_gracia_mora' => 'nullable|integer|min:0',
        ]);

        // El toggle de NCF (antes acá, 'ncf_enabled') se eliminó de esta pantalla
        // (REQ-10.9) — el flag 'sales.ncf' del registro de módulos se administra
        // desde Configuración → Funcionalidades del Sistema (REQ-10.6), un solo
        // lugar para editarlo en vez de dos pantallas que podían desincronizarse.

        // Logo
        if ($request->hasFile('logo')) {

            // 1. Eliminar logo anterior si existe
            if ($config && $config->logo && Storage::disk('public')->exists($config->logo)) {
                Storage::disk('public')->delete($config->logo);
            }

            // 2. Guardar nuevo logo
            $validated['logo'] = $request->file('logo')->store('config', 'public');
        } else {
            // Si no se sube nuevo logo, conservar el actual
            if ($config) {
                $validated['logo'] = $config->logo;
            }
        }

        ConfiguracionGeneral::updateOrCreate(['id' => 1], $validated);

        return back()->with('success', 'Configuración actualizada correctamente.');
    }
}
