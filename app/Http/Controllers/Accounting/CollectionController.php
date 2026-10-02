<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Http\Requests\Accounting\Collection\StoreCollectionRequest;
use App\Models\Accounting\ClientCollection;
use App\Services\Accounting\Collection\CollectionCatalogService;
use App\Services\Accounting\Collection\CollectionPrintService;
use App\Services\Accounting\Collection\CollectionService;
use Illuminate\Http\Request;

/**
 * Categoría C (docs/analisis/politica-soft-deletes.md) — un Cobro es la
 * bitácora de dinero recibido de un cliente: nunca se borra ni se archiva.
 * `cancel()` (revierte el saldo de la CxC y su asiento contable) es toda la
 * "eliminación" que existe. Sin SoftDeletesTrait, sin destroy().
 */
class CollectionController extends Controller
{
    public function __construct(
        protected CollectionService $service,
        protected CollectionCatalogService $catalogService,
        protected CollectionPrintService $printService
    ) {}

    /**
     * Listado migrado a Livewire — ver App\Livewire\App\Finance\CollectionTable.
     */
    public function index()
    {
        return view('finance.collections.index');
    }

    /**
     * Formato de impresión del recibo — ticket térmico (default, ancho dinámico
     * según la terminal de origen) o carta/PDF, mismo patrón que InvoiceController
     * (Fase 6, REQ-6.8).
     */
    public function print(ClientCollection $payment, Request $request)
    {
        try {
            $format = $request->query('format', 'ticket');

            if ($format === 'letter') {
                if ($request->boolean('download')) {
                    $pdf = $this->printService->generateLetterPDF($payment);

                    return $pdf->download($this->printService->getFileName($payment));
                }

                $payment->load(['client', 'creator', 'tipoPago', 'receivable']);

                return view('finance.collections.pdf', compact('payment'));
            }

            // ?preview=1 → solo el ticket, sin wrapper ni impresión automática (iframe del show).
            if ($request->boolean('preview')) {
                return $this->printService->getTicketView($payment);
            }

            $view = $this->printService->getTicketView($payment)->render();

            return view('finance.collections.print', compact('payment', 'view'));
        } catch (\Exception $e) {
            return back()->with('error', 'No se pudo cargar el formato: '.$e->getMessage());
        }
    }

    /**
     * Detalle del cobro (v1.4.0 Fase 3, patrón Infolist — /filament-show), con la
     * vista previa del recibo al lado. Reemplaza el modal "view-payment" del listado.
     */
    public function show(ClientCollection $payment)
    {
        $payment->load([
            'client:id,name,commercial_name,tax_id',
            'tipoPago',
            'creator:id,name',
            'receivable',
            'posSession.terminal:id,name',
        ]);

        return view('finance.collections.show', ['payment' => $payment]);
    }

    public function create()
    {
        return view('finance.collections.create', $this->catalogService->getForForm());
    }

    public function store(StoreCollectionRequest $request)
    {
        try {
            $this->service->createCollection($request->validated());

            return redirect()
                ->route('finance.collections.index')
                ->with('success', 'Cobro registrado y contabilizado correctamente.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function cancel(ClientCollection $payment)
    {
        try {
            $this->service->cancelCollection($payment);

            return back()->with('success', 'El cobro ha sido anulado y el saldo de la factura revertido.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
