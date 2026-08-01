<?php

namespace App\Http\Controllers\Configuration;

use App\Http\Controllers\Controller;
use App\Models\Configuration\ConfiguracionGeneral;
use App\Models\Configuration\Impuesto;
use App\Models\Configuration\TaxIdentifierType;
use App\Models\Geo\Country;
use App\Models\Geo\State;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ConfiguracionGeneralController extends Controller
{



    public function edit()
    {
        $config = ConfiguracionGeneral::actual();
        $countries = Country::ordered()->get();
        $impuestos = Impuesto::all();

        // Obtener estados del país configurado
        $states = $config?->country_id
            ? State::byCountry($config->country_id)->orderBy('name')->get()
            : collect();
        
        //Obtener identificadores fiscales del país configurado
        $taxTypes = $config?->country_id
            ? TaxIdentifierType::byCountry($config->country_id)->get()
            : collect();

        return view('configuration.general.edit', compact(
            'config',
            'countries',
            'states',
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
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'direccion' => 'nullable|string',
            'ciudad' => 'nullable|string|max:255',
            'country_id' => 'required|exists:countries,id',

            // Nuevo campo
            'usa_ncf' => 'nullable|boolean',


            'impuesto_nombre' => 'required|string|max:255',
            'impuesto_tipo'   => 'required|in:porcentaje,fijo',
            'impuesto_valor'  => 'required|numeric|min:0',
            'impuesto_incluido' => 'nullable|boolean',

            'state_id' => 'nullable|exists:states,id',
            'tax_identifier_type_id' => 'nullable|exists:tax_identifier_types,id',
        ]);

        $country = Country::findOrFail($validated['country_id']);
        $state = $validated['state_id']
            ? State::find($validated['state_id'])
            : null;
            
        // Manejo del checkbox usa_ncf (si no llega en el request, es false)
        $validated['usa_ncf'] = $request->has('usa_ncf');

        // Moneda automática desde país
        $validated['currency'] = $country->currency;
        $validated['currency_name'] = $country->currency_name;
        $validated['currency_symbol'] = $country->currency_symbol;

        // Timezone (prioridad estado > país)
        $validated['timezone'] = $state?->timezone
            ?? json_decode($country->timezones, true)[0]['zoneName']
            ?? config('app.timezone');

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
                'tipo'   => $validated['impuesto_tipo'],
                'valor'  => $validated['impuesto_valor'],
                'es_incluido' => $request->has('impuesto_incluido')
            ]
        );
    
        $validated['impuesto_id'] = $impuesto->id;

        ConfiguracionGeneral::updateOrCreate(['id' => 1], $validated);

        return back()->with('success', 'Configuración actualizada correctamente.');
    }
}
