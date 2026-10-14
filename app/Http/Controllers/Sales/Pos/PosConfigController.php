<?php

namespace App\Http\Controllers\Sales\Pos;

use App\Http\Controllers\Controller;
use App\Models\Sales\Pos\PosSetting;
use App\Services\Sales\Pos\PosConfig\PosConfigService;
use App\Http\Requests\Sales\Pos\PosConfig\UpdatePosConfigRequest;
use App\Models\Accounting\FinancialAccount;
use App\Models\Clients\Client;
use App\Services\Accounting\Treasury\CustomerPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PosConfigController extends Controller
{
    protected $configService;

    public function __construct(PosConfigService $configService)
    {
        $this->configService = $configService;
    }

    /**
     * Muestra el formulario de configuración
     */
    public function edit()
    {
        $settings = PosSetting::getSettings();
        $clients = Client::select('id', 'name')->orderBy('name')->get();
        // v1.5.0 REQ-3.6: cuentas de banco a las que pueden entrar los pagos que no son efectivo.
        $bankAccounts = FinancialAccount::active()->where('type', FinancialAccount::TYPE_BANK)->orderBy('name')->get(['id', 'name', 'bank_name', 'last_four_digits', 'type']);

        $missingBankAccount = app(CustomerPaymentService::class)->missingDefaultAccount();

        return view('sales.pos.settings.edit', compact('settings', 'clients', 'bankAccounts', 'missingBankAccount'));
    }

    /**
     * Procesa la actualización
     */
    public function update(UpdatePosConfigRequest $request)
    {
        try {
            // Debug: ver qué datos llegan
            Log::info('POS Config Update Request', $request->validated());

            $this->configService->update($request->validated());

            return redirect()
                ->back()
                ->with('success', 'Configuración del POS actualizada correctamente.');

        } catch (\Exception $e) {
            Log::error('POS Config Update Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()
                ->back()
                ->withErrors(['error' => 'Ocurrió un error al guardar la configuración.'])
                ->withInput();
        }
    }
}