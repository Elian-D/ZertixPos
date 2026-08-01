<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Sales\Quotes\Quote;
use App\Filters\Sales\Quotes\QuoteFilters;
use App\Http\Requests\Sales\Quotes\StoreQuoteRequest;
use App\Http\Requests\Sales\Quotes\UpdateQuoteRequest;
use App\Services\Sales\Quotes\QuoteCatalogService;
use App\Services\Sales\Quotes\QuotePrintService;
use App\Services\Sales\Quotes\QuoteService;
use App\Tables\SalesTables\QuoteTable;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\Log;

// App\Http\Controllers\Sales\QuoteController.php

use App\Services\Sales\SalesServices\SaleCatalogService; // Importar

class QuoteController extends Controller
{
    public function __construct(
        protected QuoteService $service,
        protected QuoteCatalogService $catalogService,
        protected SaleCatalogService $saleCatalogService, // Inyectar
        protected QuotePrintService $printService
    ) {}

    public function index(Request $request)
    {
        $visibleColumns = $request->input('columns', QuoteTable::defaultDesktop());
        $perPage = $request->input('per_page', 10);

        $quotes = (new QuoteFilters($request))
            ->apply(Quote::query()->with(['customer', 'user', 'sale', 'terminal']))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        // 1. Catálogos para los Filtros (QuoteCatalog)
        $quoteCatalogs = $this->catalogService->getForFilters();

        // 2. Catálogos para el Modal de Conversión (SaleCatalog)
        $saleCatalogs = $this->saleCatalogService->getForForm();

        if ($request->ajax()) {
            return view('sales.quotes.partials.table', [
                'items'          => $quotes,
                'visibleColumns' => $visibleColumns,
                'allColumns'     => QuoteTable::allColumns(),
                'tipo_pagos'     => $saleCatalogs['tipo_pagos'],
                'ncf_types'      => $saleCatalogs['ncf_types'],
                'warehouses'     => $saleCatalogs['warehouses'], // Agregar aquí
            ])->render();
        }

        // Combinamos todo para la vista inicial
        return view('sales.quotes.index', array_merge(
            [
                'items'          => $quotes,
                'visibleColumns' => $visibleColumns,
                'allColumns'     => QuoteTable::allColumns(),
                'defaultDesktop' => QuoteTable::defaultDesktop(),
                'defaultMobile'  => QuoteTable::defaultMobile(),
            ],
            $quoteCatalogs, // customers, users, statuses, origins
            [
                'tipo_pagos' => $saleCatalogs['tipo_pagos'],
                'ncf_types'  => $saleCatalogs['ncf_types'],
                'warehouses' => $saleCatalogs['warehouses'], // Agregar aquí también
            ]
        ));
    }

    /**
     * Mostrar formulario de creación (Aloja el Livewire QuoteBuilder).
     */
    public function create()
    {
        return view('sales.quotes.create', $this->catalogService->getForForm());
    }

    /**
     * Almacenar la cotización vía Servicio.
     */
    public function store(StoreQuoteRequest $request)
    {
        try {
            $quote = $this->service->store($request->validated());

            return redirect()
                ->route('sales.quotes.show', $quote)
                ->with('success', "Cotización #{$quote->id} generada con éxito.");
        } catch (Exception $e) {
            Log::error("Error creando cotización: " . $e->getMessage());
            return back()->withInput()->with('error', "Error: " . $e->getMessage());
        }
    }

    /**
     * Formulario de edición (Mismo componente Livewire con instancia cargada).
     */
    public function edit(Quote $quote)
    {
        // Solo permitir editar si es borrador y no ha expirado
        if ($quote->status !== Quote::STATUS_DRAFT || ($quote->expires_at && $quote->expires_at->isPast())) {
            return redirect()->route('sales.quotes.index')
                ->with('error', "Esta cotización no puede ser editada (Estado: {$quote->status}).");
        }

        return view('sales.quotes.edit', array_merge(
            ['quote' => $quote],
            $this->catalogService->getForForm()
        ));
    }

    /**
     * Actualizar vía Servicio.
     */
    public function update(UpdateQuoteRequest $request, Quote $quote)
    {
        try {
            // El servicio store puede adaptarse o crear un update()
            $this->service->update($quote, $request->validated());

            return redirect()
                ->route('sales.quotes.show', $quote)
                ->with('success', "Cotización #{$quote->id} actualizada correctamente.");
        } catch (Exception $e) {
            Log::error("Error actualizando cotización {$quote->id}: " . $e->getMessage());
            return back()->with('error', "No se pudo actualizar: " . $e->getMessage());
        }
    }

    /**
     * Detalle de la cotización y opciones de conversión.
     */
    public function show(Quote $quote)
    {
        $quote->load(['items.product', 'customer', 'user', 'sale']);
        
        // Obtener catálogos para el modal de conversión
        $saleCatalogs = $this->saleCatalogService->getForForm();
        
        return view('sales.quotes.show', array_merge(
            compact('quote'),
            [
                'tipo_pagos' => $saleCatalogs['tipo_pagos'],
                'ncf_types'  => $saleCatalogs['ncf_types'],
                'warehouses' => $saleCatalogs['warehouses'],
            ]
        ));
    }

    /**
     * Acción de Aprobación: Bloquea la edición y valida para el cliente.
     */
    public function approve(Quote $quote)
    {
        try {
            if ($quote->status !== Quote::STATUS_DRAFT) {
                throw new Exception("Solo se pueden aprobar cotizaciones en estado borrador.");
            }

            $quote->update(['status' => Quote::STATUS_APPROVED]);
            
            return back()->with('success', "Cotización #{$quote->id} marcada como Aprobada.");
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Vista previa en iframe para mostrar el formato de la cotización.
     */
    public function preview(Quote $quote)
    {
        $quote->load(['items.product', 'customer', 'user']);
        
        $format = request('format', 'letter');
        
        $viewMap = [
            'letter' => 'pdf',
            'ticket' => 'ticket',
        ];

        $viewName = $viewMap[$format] ?? 'pdf';

        return view("sales.quotes.formats.{$viewName}", [
            'quote' => $quote
        ]);
    }

    /**
     * Convertir cotización en una venta real.
     */

    public function convert(Request $request, Quote $quote)
    {
        $rules = [
            'payment_type' => 'required|in:cash,credit',
            'tipo_pago_id' => 'required_if:payment_type,cash|exists:tipo_pagos,id',
            'ncf_type_id'  => 'nullable|exists:ncf_types,id',
            'warehouse_id' => 'required|exists:warehouses,id',
        ];

        $validated = $request->validate($rules);

        try {
            $sale = $this->service->convertToSale($quote, $validated);

            return redirect()
                ->route('sales.invoices.show', $sale->invoice->id) // Redirigir a la factura recién creada
                ->with('success', "Cotización convertida exitosamente. Venta #{$sale->number}");

        } catch (Exception $e) {
            return back()->with('error', "Error en conversión: " . $e->getMessage());
        }
    }

    /**
     * Cancelación administrativa con motivo.
     */
    public function cancel(Request $request, Quote $quote)
    {
        $validated = $request->validate([
            'reason' => 'required|string|min:5|max:255'
        ]);

        try {
            if ($quote->status === Quote::STATUS_CONVERTED) {
                throw new Exception("No se puede cancelar una cotización que ya es una venta.");
            }

            $quote->update([
                'status' => Quote::STATUS_CANCELLED,
                'notes' => $quote->notes . "\n[Cancelación]: " . $validated['reason']
            ]);

            return back()->with('info', "La cotización ha sido cancelada.");
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Impresión delegada al PrintService.
     */
    public function print(Quote $quote, $format = 'letter')
    {
        $quote->load(['items.product', 'customer', 'user']);

        if ($format === 'ticket') {
            return view('sales.quotes.print', [
                'quote' => $quote,
                'view'  => $this->printService->getTicketView($quote)
            ]);
        }

        return $this->printService->generateLetterPDF($quote)
            ->stream("Cotizacion-{$quote->id}.pdf");
    }
}