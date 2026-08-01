<?php

namespace App\Http\Controllers\Clients;

use App\Http\Controllers\Controller;
use App\Http\Requests\Clients\StoreQuickClientRequest;
use App\Services\Client\ClientService;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;

class PosQuickCustomerController extends Controller
{
    /**
     * Almacena un cliente de forma rápida desde el POS.
     */
    public function store(StoreQuickClientRequest $request, ClientService $clientService): JsonResponse
    {
        try {
            // Utilizamos el nuevo método del service que creamos anteriormente
            // que internamente usa el QuickClientDTO y el createClient estándar.
            $client = $clientService->createQuickClient($request->validated());

            return response()->json([
                'success' => true,
                'message' => "Cliente {$client->name} registrado con éxito.",
                'client'  => [
                    'id'           => $client->id,
                    'name'         => $client->name,
                    'display_name' => $client->display_name,
                    'tax_id'       => $client->tax_id,
                    'tax_label'    => $client->tax_label, // El accesor que definimos en el modelo
                    // Mismo shape que PosWorkspace::getClients(): un cliente recién creado
                    // siempre arranca limpio (sin balance ni mora), pero deben existir estos
                    // campos porque el Workspace los lee al mostrarlo como seleccionado.
                    'available'   => (float) ($client->credit_limit - $client->balance),
                    'is_moroso'   => false,
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error("Error en creación rápida de cliente POS: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'No se pudo registrar al cliente: ' . $e->getMessage()
            ], 422);
        }
    }
}