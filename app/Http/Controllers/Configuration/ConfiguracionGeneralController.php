<?php

namespace App\Http\Controllers\Configuration;

use App\Enums\TaxIdentifierType;
use App\Http\Controllers\Controller;
use App\Models\Configuration\ConfiguracionGeneral;
use App\Models\Configuration\Impuesto;
use App\Models\Configuration\InstallationModule;
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
        $impuestos = Impuesto::all();

        // Precargado completo (~158 filas) — filtrado cascada provincia->municipio
        // se hace en Alpine.js del lado del cliente, sin request AJAX (Fase 6.9).
        $municipalities = Municipality::select('id', 'name', 'province_id')->orderBy('name')->get();

        $taxTypes = collect(TaxIdentifierType::cases())
            ->map(fn (TaxIdentifierType $type) => ['value' => $type->value, 'label' => $type->label()]);

        return view('configuration.general.edit', compact(
            'config',
            'provinces',
            'municipalities',
            'taxTypes',
            'impuestos'
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

            // Flag de módulo — ver manejo aparte más abajo, no es columna de esta tabla.
            'ncf_enabled' => 'nullable|boolean',

            'impuesto_nombre' => 'required|string|max:255',
            'impuesto_tipo' => 'required|in:porcentaje,fijo',
            'impuesto_valor' => 'required|numeric|min:0',
            'impuesto_incluido' => 'nullable|boolean',
        ]);

        // El toggle de NCF ya no es una columna de configuraciones_generales — es el
        // flag 'sales.ncf' del registro de módulos (Fase 3/4). Se escribe aparte y se
        // saca de $validated antes de guardar, para no intentar escribirlo en una
        // columna que ya no existe.
        InstallationModule::updateOrCreate(
            ['module_key' => 'sales.ncf'],
            ['is_enabled' => $request->has('ncf_enabled')]
        );
        unset($validated['ncf_enabled']);

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

        $impuesto = Impuesto::updateOrCreate(
            ['id' => $config?->impuesto_id], // Si existe lo edita, si no, crea uno nuevo
            [
                'nombre' => $validated['impuesto_nombre'],
                'tipo' => $validated['impuesto_tipo'],
                'valor' => $validated['impuesto_valor'],
                'es_incluido' => $request->has('impuesto_incluido'),
            ]
        );

        $validated['impuesto_id'] = $impuesto->id;

        ConfiguracionGeneral::updateOrCreate(['id' => 1], $validated);

        return back()->with('success', 'Configuración actualizada correctamente.');
    }
}
