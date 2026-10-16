<?php

namespace App\Support\Reports;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Throwable;

/**
 * Período Desde/Hasta de un reporte (v1.5.0 Fase 6), leído de ?from=&to=. Por defecto, el
 * mes en curso hasta hoy. Si llegan al revés, se ordenan.
 */
final class ReportPeriod
{
    public function __construct(public readonly Carbon $from, public readonly Carbon $to) {}

    public static function fromRequest(Request $request): self
    {
        $from = self::parse($request->query('from')) ?? now()->startOfMonth();
        $to = self::parse($request->query('to')) ?? now();

        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        return new self($from->copy()->startOfDay(), $to->copy()->endOfDay());
    }

    /** "1 – 15 oct 2026" para la descripción del reporte. */
    public function label(): string
    {
        return $this->from->isSameDay($this->to)
            ? $this->to->translatedFormat('j M Y')
            : $this->from->translatedFormat('j M').' – '.$this->to->translatedFormat('j M Y');
    }

    private static function parse(mixed $value): ?Carbon
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (Throwable) {
            return null;
        }
    }
}
