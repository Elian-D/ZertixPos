<?php

namespace App\Http\Controllers\Sales\Pos;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Models\Sales\Pos\PosSession;
use App\Services\Sales\Pos\PosSessionServices\{PosSessionService, PosSessionCatalogService, PosSessionReportService};
use App\Http\Requests\Sales\Pos\PosSessions\{OpenSessionRequest, CloseSessionRequest, UpdatePosSessionRequest};
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class PosSessionController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected PosSessionService $service,
        protected PosSessionCatalogService $catalogService,
        protected PosSessionReportService $reportService
    ) {}

    /**
     * Listado migrado a Livewire — ver App\Livewire\App\Sales\PosSessionTable.
     */
    public function index()
    {
        $this->authorize('pos sessions history');

        return view('sales.pos.sessions.index');
    }

    /**
     * Acción de Apertura (Store).
     */
    public function store(OpenSessionRequest $request)
    {
        try {
            $session = $this->service->open($request->validated());
            
            return redirect()->back()->with('success', "Turno abierto correctamente en {$session->terminal->name}");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function show(PosSession $posSession)
    {
        $this->authorize('pos sessions history');

        $posSession->load(['terminal', 'user', 'openedBy', 'closedBy']);

        // Reutilizamos el mismo servicio que arma el PDF/ticket para pintar el
        // desglose por forma de pago aquí también — una sola fuente de verdad.
        $report = $this->reportService->getReportData($posSession);

        return view('sales.pos.sessions.show', array_merge(compact('posSession'), [
            'salesDetail'          => $report['salesDetail'],
            'columns'              => $report['columns'],
            'breakdownRows'        => $report['breakdownRows'],
            'grandTotal'           => $report['grandTotal'],
            'creditTotal'          => $report['creditTotal'],
            'totalSalesWithCredit' => $report['totalSalesWithCredit'],
        ]));
    }

    /**
     * Reporte de turno imprimible: PDF carta (?format=letter, default) o ticket
     * térmico (?format=ticket). ?download=1 fuerza descarga en vez de vista inline.
     */
    public function print(PosSession $posSession, Request $request)
    {
        $this->authorize('pos sessions history');

        $data = $this->reportService->getReportData($posSession);
        $format = $request->query('format', 'letter');

        if ($format === 'ticket') {
            $view = view('sales.pos.sessions.formats.ticket', $data)->render();

            return view('sales.pos.sessions.print', array_merge($data, ['view' => $view]));
        }

        $pdf = Pdf::loadView('sales.pos.sessions.formats.full', $data)->setPaper('letter', 'portrait');
        $fileName = "Turno-{$posSession->id}.pdf";

        return $request->boolean('download')
            ? $pdf->download($fileName)
            : $pdf->stream($fileName);
    }
    /**
     * Vista dedicada de cierre (Fase 9.3, reemplaza el modal). El permiso ya se
     * exige en la ruta (middleware), este método solo arma los datos de la vista.
     */
    public function closeForm(PosSession $posSession)
    {
        if (! $posSession->isOpen()) {
            return redirect()->route('sales.pos.sessions.index')
                ->with('error', 'Este turno ya está cerrado.');
        }

        $posSession->load('terminal');

        return view('sales.pos.sessions.close', [
            'session'     => $posSession,
            'expected'    => $posSession->calculateExpected(),
            'reasons'     => PosSession::getReasons(),
            'salesDetail' => $this->reportService->getReportData($posSession)['salesDetail'],
        ]);
    }

    /**
     * Acción de Cierre (Patch).
     * Asegúrate de que el nombre coincida: {pos_session} -> $posSession
     */
    public function close(CloseSessionRequest $request, PosSession $posSession)
    {
        try {
            // Ejecuta la lógica de liquidación del servicio
            $this->service->close($posSession, $request->validated());
            
            return redirect()->route('sales.pos.sessions.index')
                ->with('success', 'Turno cerrado y arqueado correctamente.')
                // El index abre esto en pestaña nueva (ver script en sessions/index.blade.php)
                // — mismo patrón que el auto-print de venta en PosWorkspace (Fase 7.4).
                ->with('autoPrintSessionUrl', route('sales.pos.sessions.print', $posSession));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Edición administrativa (Notas).
     */
    public function update(UpdatePosSessionRequest $request, PosSession $posSession)
    {
        $this->service->update($posSession, $request->validated());

        return redirect()->back()->with('success', 'Turno actualizado correctamente.');
    }
}