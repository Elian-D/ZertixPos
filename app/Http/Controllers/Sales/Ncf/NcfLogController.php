<?php

namespace App\Http\Controllers\Sales\Ncf;

use App\Http\Controllers\Controller;
use App\Models\Sales\Ncf\NcfLog;
use App\Filters\Sales\Ncf\NcfLogFilters;
use App\Services\Sales\Ncf\NcfReportService;
use Illuminate\Http\Request;

/**
 * Categoría C (docs/analisis/politica-soft-deletes.md) — bitácora fiscal de
 * comprobantes emitidos, exigida por la DGII. Sin SoftDeletes, sin destroy.
 */
class NcfLogController extends Controller
{
    public function __construct(
        protected NcfReportService $reportService
    ) {}

    /**
     * Listado migrado a Livewire — ver App\Livewire\App\Finance\NcfLogTable.
     */
    public function index()
    {
        return view('sales.ncf.logs.index');
    }

    /**
     * Generación de archivo TXT formato DGII (Reporte 607) — se queda como
     * ruta real: formulario GET independiente del estado de la tabla
     * (periodo fiscal explícito, no los filtros de búsqueda/paginación).
     */
    public function exportTxt(Request $request)
    {
        // 1. Limpiamos el periodo antes de validar si viene con guion (ej: 2024-02 -> 202402)
        if ($request->has('periodo')) {
            $cleanPeriodo = str_replace('-', '', $request->input('periodo'));
            $request->merge(['periodo' => $cleanPeriodo]);
        }

        // 2. Ahora validamos el string limpio (6 dígitos: YYYYMM)
        $request->validate([
            'periodo' => 'required|digits:6'
        ], [
            'periodo.digits' => 'El periodo debe tener el formato AAAAMM (ejemplo: 202402).'
        ]);

        $logs = (new NcfLogFilters($request))
                ->apply(NcfLog::query()->with(['sale.client'])) // Eager loading de client
                ->where('status', NcfLog::STATUS_USED)
                ->get();

        if ($logs->isEmpty()) {
            return back()->with('error', 'No hay registros de NCF usados para el periodo seleccionado.');
        }

        $content = $this->reportService->generate607Txt($logs, $request->periodo);

        $fileName = "DGII_607_{$request->periodo}_" . now()->format('His') . ".txt";

        return response($content)
            ->header('Content-Type', 'text/plain')
            ->header('Content-Disposition', "attachment; filename={$fileName}");
    }
}
