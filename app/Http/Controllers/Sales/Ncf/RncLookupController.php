<?php

namespace App\Http\Controllers\Sales\Ncf;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RncLookupController extends Controller
{
    /**
     * Proxy server-side hacia la API de consulta de RNC/Cédula (DGII vía Megaplus).
     * El navegador nunca llama directo al proveedor externo: ver nota de arquitectura
     * en la respuesta al usuario (CORS + control de errores + desacoplo de proveedor).
     */
    public function lookup(Request $request)
    {
        $request->validate([
            'rnc' => ['required', 'string', 'regex:/^[0-9]{9,11}$/'],
        ], [
            'rnc.regex' => 'El RNC/Cédula debe tener 9 u 11 dígitos numéricos.',
        ]);

        $rnc = $request->string('rnc');

        try {
            $response = Http::timeout(6)
                ->get('https://rnc.megaplus.com.do/api/consulta', ['rnc' => $rnc]);
        } catch (\Throwable $e) {
            Log::warning('RNC lookup: fallo de conexión con el proveedor externo', [
                'rnc' => (string) $rnc,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => true,
                'mensaje' => 'No se pudo contactar el servicio de validación de RNC. Intenta de nuevo.',
            ], 503);
        }

        if (! $response->successful()) {
            return response()->json([
                'error' => true,
                'mensaje' => 'El servicio de validación de RNC no respondió correctamente.',
            ], 502);
        }

        return response()->json($response->json());
    }
}
