<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Sales\Ncf\NcfLog;
use App\Models\Sales\Ncf\NcfSequence;
use App\Models\Sales\Ncf\NcfType;
use App\Support\Reports\ReportPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Reporte de NCF (v1.5.0 Fase 6, antes "Control de NCF"): cuántos comprobantes quedan, cuánto
 * duran al ritmo actual, qué secuencia vence o se agota, y lo emitido y anulado en el período.
 * ?ncf_type= lo limita a un tipo.
 */
class NcfReportController extends Controller
{
    /** Avisa de las secuencias que vencen en estos días. */
    const EXPIRY_WARNING_DAYS = 15;

    public function __invoke(Request $request)
    {
        $period = ReportPeriod::fromRequest($request);
        $types = NcfType::where('is_active', true)->orderBy('code')->get(['id', 'code', 'name']);
        $typeId = $types->contains('id', $request->integer('ncf_type')) ? $request->integer('ncf_type') : null;

        $sequences = NcfSequence::with('type:id,code,name')
            ->where('status', NcfSequence::STATUS_ACTIVE)
            ->when($typeId, fn ($q, $id) => $q->where('ncf_type_id', $id))
            ->orderBy('expiry_date')
            ->get();

        $logs = NcfLog::whereBetween('created_at', [$period->from, $period->to])
            ->when($typeId, fn ($q, $id) => $q->where('ncf_type_id', $id));

        $emitted = (clone $logs)->where('status', NcfLog::STATUS_USED)->count();
        $voided = (clone $logs)->where('status', NcfLog::STATUS_VOIDED)->count();
        $valid = $sequences->filter(fn ($s) => $s->expiry_date->isFuture());
        $available = (int) $valid->sum(fn ($s) => max(0, $s->to - $s->current));
        $days = max(1, (int) $period->from->diffInDays($period->to->copy()->min(now())) + 1);
        $dailyAverage = round($emitted / $days, 1);

        $byDay = (clone $logs)->where('status', NcfLog::STATUS_USED)
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')->groupBy('day')->pluck('total', 'day');

        return view('reports.ncf', [
            'period' => $period,
            'types' => $types,
            'typeId' => $typeId,
            'stats' => [
                'available' => $available,
                'daily_average' => $dailyAverage,
                'days_remaining' => $dailyAverage > 0 ? (int) floor($available / $dailyAverage) : null,
                'emitted' => $emitted,
                'voided' => $voided,
                'void_rate' => $emitted + $voided > 0 ? round($voided / ($emitted + $voided) * 100, 1) : 0,
                'next_expiry' => $valid->first()?->expiry_date,
            ],
            'byDay' => $this->days($period->from, $period->to, $byDay),
            'byType' => NcfLog::query()->where('ncf_logs.status', NcfLog::STATUS_USED)
                ->whereBetween('ncf_logs.created_at', [$period->from, $period->to])
                ->when($typeId, fn ($q, $id) => $q->where('ncf_logs.ncf_type_id', $id))
                ->join('ncf_types', 'ncf_types.id', '=', 'ncf_logs.ncf_type_id')
                ->groupBy('ncf_types.id', 'ncf_types.code', 'ncf_types.name')
                ->selectRaw('ncf_types.code, ncf_types.name, COUNT(*) as total')
                ->orderByDesc('total')->get(),
            'voidReasons' => (clone $logs)->where('status', NcfLog::STATUS_VOIDED)->whereNotNull('cancellation_reason')
                ->groupBy('cancellation_reason')->selectRaw('cancellation_reason as reason, COUNT(*) as total')
                ->orderByDesc('total')->limit(8)->get(),
            'sequences' => $sequences->map(fn ($s) => $this->sequenceRow($s)),
            'alerts' => $this->alerts($typeId),
            'recent' => NcfLog::with(['sale.client:id,name', 'type:id,code,name'])
                ->whereBetween('created_at', [$period->from, $period->to])
                ->when($typeId, fn ($q, $id) => $q->where('ncf_type_id', $id))
                ->latest()->limit(10)->get(),
        ]);
    }

    private function days(Carbon $from, Carbon $to, Collection $byDay): array
    {
        $bars = [];
        for ($d = $from->copy(); $d->lte($to); $d->addDay()) {
            $total = (int) ($byDay[$d->toDateString()] ?? 0);
            $bars[] = ['label' => $d->format('j'), 'value' => $total, 'tooltip' => $d->translatedFormat('j M').' · '.$total.' NCF'];
        }

        return $bars;
    }

    private function sequenceRow(NcfSequence $s): object
    {
        $total = max(1, $s->to - $s->from + 1);
        $used = max(0, $s->current - $s->from + 1);

        return (object) [
            'id' => $s->id,
            'label' => ($s->type->code ?? '').' · '.($s->type->name ?? '').' · serie '.$s->series,
            'remaining' => max(0, $s->to - $s->current),
            'progress' => round(min(100, $used / $total * 100), 1),
            'expiry_date' => $s->expiry_date,
            'expired' => $s->expiry_date->isPast(),
            'low' => $s->isLow(),
        ];
    }

    /** Vencidas, agotadas, por agotarse y por vencer: lo que hay que pedir a la DGII. */
    private function alerts(?int $typeId): Collection
    {
        $active = NcfSequence::with('type:id,code,name')->where('status', NcfSequence::STATUS_ACTIVE)
            ->when($typeId, fn ($q, $id) => $q->where('ncf_type_id', $id))->get();

        $alerts = collect();
        foreach ($active as $s) {
            $name = ($s->type->name ?? 'NCF').' (serie '.$s->series.')';
            $remaining = $s->to - $s->current;
            $daysLeft = (int) now()->startOfDay()->diffInDays($s->expiry_date, false);

            if ($s->expiry_date->isPast()) {
                $alerts->push(['variant' => 'error', 'text' => "{$name} venció el {$s->expiry_date->format('d/m/Y')}. Pide una nueva a la DGII."]);
            } elseif ($remaining <= 0) {
                $alerts->push(['variant' => 'error', 'text' => "{$name} no tiene más comprobantes disponibles."]);
            } elseif ($s->isLow()) {
                $alerts->push(['variant' => 'warning', 'text' => "{$name}: quedan {$remaining} comprobantes."]);
            } elseif ($daysLeft <= self::EXPIRY_WARNING_DAYS) {
                $alerts->push(['variant' => 'warning', 'text' => "{$name} vence en {$daysLeft} días ({$s->expiry_date->format('d/m/Y')})."]);
            }
        }

        return $alerts;
    }
}
