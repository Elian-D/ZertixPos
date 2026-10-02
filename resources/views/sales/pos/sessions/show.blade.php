{{-- Detalle del turno — patrón Infolist (/filament-show, docs/ui/infolist.md).
     Fila 1: datos del turno | arqueo de caja (50/50). Fila 2: resumen por forma de
     pago. Fila 3: ventas del turno. Los datos del reporte (salesDetail, columns,
     breakdownRows, creditTotal…) salen de PosSessionReportService — misma fuente
     que el PDF/ticket del turno. --}}
@use('App\Models\Sales\Pos\PosSession')
@php
    $currency = config('regional.currency_symbol');
    $money = fn ($v) => $currency.number_format((float) $v, 2);
    $isOpen = $posSession->isOpen();
    // Cerrado: la cifra grabada al cierre. Abierto: calculada en vivo.
    $expected = $isOpen ? $posSession->calculateExpected() : $posSession->expected_balance;
    $diff = (float) $posSession->difference;
    $methodCols = min(count($columns) + 2, 7);
    $canSeeSales = auth()->user()->can('sales.view');
@endphp

<x-app-layout :title="'Turno '.$posSession->number">
    <div class="p-4 md:p-6 flex flex-col gap-6">

        <x-ui.page-header :title="'Turno '.$posSession->number" :description="'Ver turno · '.($posSession->terminal->name ?? 'Terminal eliminada')">
            <x-slot:actions>
                <x-ui.button href="{{ route('sales.pos.sessions.index') }}" variant="secondary" appearance="outline" iconLeft="heroicon-s-arrow-left">
                    Volver
                </x-ui.button>
                <x-ui.button href="{{ route('sales.pos.sessions.print', $posSession) }}" target="_blank"
                    variant="secondary" appearance="outline" iconLeft="heroicon-s-printer">
                    Imprimir reporte
                </x-ui.button>
                <x-ui.button href="{{ route('sales.pos.sessions.print', ['pos_session' => $posSession, 'format' => 'ticket']) }}" target="_blank"
                    variant="secondary" appearance="outline" iconLeft="heroicon-s-receipt-percent">
                    Ticket
                </x-ui.button>
                @if($isOpen)
                    @can('pos_sessions.manage')
                        <x-ui.button href="{{ route('sales.pos.sessions.close-form', $posSession) }}" variant="primary" iconLeft="heroicon-s-lock-closed">
                            Cerrar turno
                        </x-ui.button>
                    @endcan
                @endif
            </x-slot:actions>
        </x-ui.page-header>

        @if($isOpen)
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-3 flex items-center gap-2 text-sm text-emerald-800">
                <x-heroicon-s-lock-open class="w-5 h-5 shrink-0" />
                Turno en curso desde el {{ $posSession->opened_at->format('d/m/Y h:i A') }} — el arqueo final estará disponible al cerrarlo.
            </div>
        @endif

        {{-- Fila 1: datos | arqueo --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">

            <x-ui.infolist.section title="Datos del turno" icon="heroicon-o-clock" :cols="2">
                <x-ui.infolist.entry label="Turno" :value="$posSession->number" strong />
                <x-ui.infolist.entry label="Estado">
                    <x-ui.badge :variant="$isOpen ? 'success' : 'slate'" size="sm"
                        :icon="$isOpen ? 'heroicon-s-lock-open' : 'heroicon-s-lock-closed'">
                        {{ PosSession::getStatuses()[$posSession->status] ?? $posSession->status }}
                    </x-ui.badge>
                </x-ui.infolist.entry>
                <x-ui.infolist.entry label="Terminal" :value="$posSession->terminal?->name" strong
                    :href="$posSession->terminal && auth()->user()->can('pos_terminals.view') ? route('sales.pos.terminals.show', $posSession->terminal) : null" />
                <x-ui.infolist.entry label="Duración">
                    {{ $posSession->opened_at->diffForHumans($posSession->closed_at ?? now(), ['syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE, 'parts' => 2]) }}
                    @if($isOpen)
                        <span class="text-xs text-emerald-600">(en curso)</span>
                    @endif
                </x-ui.infolist.entry>
                <x-ui.infolist.entry label="Apertura">
                    {{ $posSession->opened_at->format('d/m/Y h:i A') }}
                    <span class="block text-xs text-gray-400">{{ $posSession->openedBy->name ?? $posSession->user->name ?? '' }}</span>
                </x-ui.infolist.entry>
                <x-ui.infolist.entry label="Cierre">
                    @if($posSession->closed_at)
                        {{ $posSession->closed_at->format('d/m/Y h:i A') }}
                        <span class="block text-xs text-gray-400">{{ $posSession->closedBy->name ?? '' }}</span>
                    @endif
                </x-ui.infolist.entry>
                <x-ui.infolist.entry label="Notas del turno" :value="$posSession->notes" full />
            </x-ui.infolist.section>

            <x-ui.infolist.section title="Arqueo de caja" icon="heroicon-o-calculator" :cols="2">
                <x-ui.infolist.entry label="(+) Fondo inicial" :value="$money($posSession->opening_balance)" />
                <x-ui.infolist.entry label="(+) Ventas en efectivo">
                    <span class="text-emerald-700">{{ $money($posSession->cash_sales ?? 0) }}</span>
                </x-ui.infolist.entry>
                @if(($posSession->cash_collections ?? 0) > 0)
                    <x-ui.infolist.entry label="(+) Cobros CxC en efectivo">
                        <span class="text-emerald-700">{{ $money($posSession->cash_collections) }}</span>
                    </x-ui.infolist.entry>
                @endif
                <x-ui.infolist.entry label="(=) Esperado en caja">
                    <span class="text-xl font-bold text-zertix-primary-700">{{ $money($expected) }}</span>
                </x-ui.infolist.entry>

                <x-ui.infolist.entry label="Contado al cierre">
                    @if(! $isOpen)
                        <span class="text-xl font-bold text-gray-900">{{ $money($posSession->closing_balance) }}</span>
                    @endif
                </x-ui.infolist.entry>
                <x-ui.infolist.entry label="Resultado">
                    @if(! $isOpen)
                        <x-ui.badge :variant="$diff == 0 ? 'success' : ($diff > 0 ? 'info' : 'error')" size="sm"
                            :icon="$diff == 0 ? 'heroicon-s-check-circle' : 'heroicon-s-exclamation-triangle'">
                            {{ $diff == 0 ? 'Caja cuadrada' : ($diff > 0 ? 'Sobrante' : 'Faltante').' de '.$money(abs($diff)) }}
                        </x-ui.badge>
                    @else
                        <span class="text-gray-400">Pendiente de cierre</span>
                    @endif
                </x-ui.infolist.entry>

                @if($posSession->difference_reason)
                    <x-ui.infolist.entry label="Motivo del descuadre" full>
                        <span class="font-medium text-amber-700">{{ PosSession::getReasons()[$posSession->difference_reason] ?? $posSession->difference_reason }}</span>
                        @if($posSession->difference_notes)
                            <span class="block text-gray-600">{{ $posSession->difference_notes }}</span>
                        @endif
                    </x-ui.infolist.entry>
                @endif
            </x-ui.infolist.section>
        </div>

        {{-- Fila 2: resumen por forma de pago --}}
        <x-ui.infolist.section title="Resumen por forma de pago" icon="heroicon-o-banknotes" :cols="0">
            <x-ui.infolist.repeatable :empty="empty($columns)" emptyIcon="heroicon-o-banknotes"
                emptyTitle="Sin cobros todavía" emptyDescription="Aún no hay ventas de contado ni cobros en este turno.">
                @foreach($breakdownRows as $row)
                    <x-ui.infolist.repeatable-item :cols="$methodCols">
                        <x-ui.infolist.entry label="Concepto" :value="$row['concepto']" strong />
                        @foreach($columns as $col)
                            <x-ui.infolist.entry :label="$col" :value="$money($row['methods'][$col] ?? 0)" />
                        @endforeach
                        <x-ui.infolist.entry label="Total" class="sm:text-right">
                            <span class="font-semibold text-zertix-primary-700">{{ $money($row['total']) }}</span>
                        </x-ui.infolist.entry>
                    </x-ui.infolist.repeatable-item>
                @endforeach
            </x-ui.infolist.repeatable>

            @if($creditTotal > 0)
                <p class="mt-4 text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
                    Ventas totales del turno: <strong>{{ $money($totalSalesWithCredit) }}</strong> — de las cuales
                    <strong>{{ $money($creditTotal) }}</strong> fueron a crédito (CxC) y no forman parte del arqueo.
                </p>
            @endif
        </x-ui.infolist.section>

        {{-- Fila 3: ventas del turno --}}
        <x-ui.infolist.section title="Ventas del turno" icon="heroicon-o-shopping-cart" :cols="0"
            :description="count($salesDetail).' '.(count($salesDetail) === 1 ? 'venta' : 'ventas')">
            <x-ui.infolist.repeatable :empty="empty($salesDetail)" emptyIcon="heroicon-o-shopping-cart"
                emptyTitle="Sin ventas" emptyDescription="Aún no hay ventas registradas en este turno.">
                @foreach($salesDetail as $row)
                    <x-ui.infolist.repeatable-item :cols="7">
                        <x-ui.infolist.entry label="Número" :value="$row['numero']" strong
                            :href="$canSeeSales && ! empty($row['id']) ? route('sales.show', $row['id']) : null" />
                        <x-ui.infolist.entry label="Hora" :value="$row['hora']" />
                        <x-ui.infolist.entry label="Cliente" :value="$row['cliente']" class="col-span-2" />
                        <x-ui.infolist.entry label="Cant.">
                            <x-ui.badge variant="slate" size="sm" :dot="false">{{ rtrim(rtrim(number_format($row['cantidad'], 2), '0'), '.') }}</x-ui.badge>
                        </x-ui.infolist.entry>
                        <x-ui.infolist.entry label="Método" :value="$row['metodo']" />
                        <x-ui.infolist.entry label="Total" class="sm:text-right">
                            <span class="font-semibold text-zertix-primary-700">{{ $money($row['total']) }}</span>
                        </x-ui.infolist.entry>
                    </x-ui.infolist.repeatable-item>
                @endforeach
            </x-ui.infolist.repeatable>
        </x-ui.infolist.section>
    </div>

    {{-- Movimiento manual de caja: oculto, ver Fase 9.1 en docs/features/POS-Interfaz.md --}}
</x-app-layout>
