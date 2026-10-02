{{-- Detalle de la terminal POS — patrón Infolist con pestañas (/filament-show,
     docs/ui/infolist.md). Recibe $terminal, $openSession, $lastClosed,
     $todaySalesCount/$todaySalesTotal, $tabLimit y por pestaña $sessions/$sessionsCount,
     $sales/$salesCount ($sessions/$sales son null si el usuario no puede verlas). --}}
@use('App\Models\Sales\Sale')
@php
    $currency = config('regional.currency_symbol');
    $money = fn ($v) => $currency.number_format((float) $v, 2);
    $canSeeSession = auth()->user()->can('pos_sessions.history');
    $pinMissing = $terminal->requires_pin && empty($terminal->access_pin);
    $diffClass = fn ($d) => (float) $d < 0 ? 'text-state-error' : ((float) $d > 0 ? 'text-emerald-600' : 'text-gray-600');

    $tabs = [['name' => 'summary', 'label' => 'Resumen', 'icon' => 'heroicon-o-computer-desktop']];
    if ($sessions !== null) {
        $tabs[] = ['name' => 'sessions', 'label' => 'Turnos', 'icon' => 'heroicon-o-clock', 'count' => $sessionsCount];
    }
    if ($sales !== null) {
        $tabs[] = ['name' => 'sales', 'label' => 'Ventas', 'icon' => 'heroicon-o-shopping-cart', 'count' => $salesCount];
    }
@endphp

<x-app-layout :title="$terminal->name">
    <div class="p-4 md:p-6 flex flex-col gap-6">

        <x-ui.page-header :title="$terminal->name" description="Ver terminal POS">
            <x-slot:actions>
                <x-ui.button href="{{ route('sales.pos.terminals.index') }}" variant="secondary" appearance="outline" iconLeft="heroicon-s-arrow-left">
                    Volver
                </x-ui.button>
                @can('pos_terminals.delete')
                    @unless($openSession)
                        <x-ui.button variant="error" appearance="outline" iconLeft="heroicon-s-trash"
                            x-data @click="$dispatch('open-modal', 'confirm-deletion-terminal-{{ $terminal->id }}')">
                            Eliminar
                        </x-ui.button>
                    @endunless
                @endcan
                @can('pos_terminals.edit')
                    <x-ui.button href="{{ route('sales.pos.terminals.edit', $terminal) }}" variant="primary" iconLeft="heroicon-s-pencil-square">
                        Editar
                    </x-ui.button>
                @endcan
            </x-slot:actions>
        </x-ui.page-header>

        {{-- Estado operativo de un vistazo --}}
        @if($openSession)
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-3 flex flex-wrap items-center gap-2 text-sm text-emerald-800">
                <x-heroicon-s-lock-open class="w-5 h-5 shrink-0" />
                Turno <strong>{{ $openSession->number }}</strong> abierto por <strong>{{ $openSession->openedBy->name ?? 'N/A' }}</strong>
                desde el {{ $openSession->opened_at->format('d/m/Y h:i A') }}.
                @if($canSeeSession)
                    <a href="{{ route('sales.pos.sessions.show', $openSession) }}" class="font-semibold underline">Ver turno</a>
                @endif
            </div>
        @elseif(! $terminal->is_active)
            <div class="rounded-xl border border-gray-200 bg-gray-50 px-5 py-3 flex items-center gap-2 text-sm text-gray-600">
                <x-heroicon-s-no-symbol class="w-5 h-5 shrink-0" />
                Terminal desactivada — no se pueden abrir turnos en ella.
            </div>
        @endif

        <x-ui.infolist.tabs :tabs="$tabs">

            {{-- Resumen --}}
            <x-ui.infolist.tab name="summary">
                <x-ui.infolist.section title="Actividad" icon="heroicon-o-chart-bar" flat>
                    <x-ui.infolist.entry label="Estado">
                        <x-ui.badge :variant="$terminal->is_active ? 'success' : 'slate'" size="sm" :dot="false">
                            {{ $terminal->is_active ? 'Activa' : 'Inactiva' }}
                        </x-ui.badge>
                    </x-ui.infolist.entry>
                    <x-ui.infolist.entry label="Turno actual">
                        @if($openSession)
                            <x-ui.badge variant="success" size="sm" icon="heroicon-s-lock-open">Abierto {{ $openSession->number }}</x-ui.badge>
                        @else
                            <x-ui.badge variant="slate" size="sm" icon="heroicon-s-lock-closed">Sin turno abierto</x-ui.badge>
                        @endif
                    </x-ui.infolist.entry>
                    <x-ui.infolist.entry label="Ventas de hoy">
                        <span class="font-semibold text-gray-900">{{ $money($todaySalesTotal) }}</span>
                        <span class="text-gray-500">· {{ $todaySalesCount }} {{ $todaySalesCount === 1 ? 'venta' : 'ventas' }}</span>
                    </x-ui.infolist.entry>
                    <x-ui.infolist.entry label="Último cierre">
                        @if($lastClosed)
                            {{ $lastClosed->closed_at?->format('d/m/Y h:i A') }}
                            <span class="block text-xs {{ $diffClass($lastClosed->difference) }}">
                                Diferencia {{ $money($lastClosed->difference) }}
                            </span>
                        @endif
                    </x-ui.infolist.entry>
                </x-ui.infolist.section>

                <x-ui.infolist.section title="Configuración" icon="heroicon-o-cog-6-tooth" flat>
                    <x-ui.infolist.entry label="Nombre" :value="$terminal->name" strong />
                    <x-ui.infolist.entry label="Tipo">
                        <x-ui.badge variant="info" size="sm" :dot="false"
                            :icon="$terminal->is_mobile ? 'heroicon-s-device-phone-mobile' : 'heroicon-s-computer-desktop'">
                            {{ $terminal->is_mobile ? 'Móvil' : 'Fija' }}
                        </x-ui.badge>
                    </x-ui.infolist.entry>
                    <x-ui.infolist.entry label="Almacén de despacho" :value="$terminal->warehouse?->name" />
                    <x-ui.infolist.entry label="Formato de impresión">
                        {{ $terminal->getSetting('printer_format') }}
                        @unless($terminal->printer_format)
                            <span class="text-xs text-gray-400">(ajuste global)</span>
                        @endunless
                    </x-ui.infolist.entry>
                    <x-ui.infolist.entry label="Cliente por defecto"
                        :value="$terminal->defaultClient?->display_name ?? 'Consumidor Final (global)'" />
                    @if(module_enabled('sales.ncf'))
                        <x-ui.infolist.entry label="NCF por defecto" :value="$terminal->defaultNcfType?->name ?? 'Según el cliente'" />
                    @endif
                    @if(module_enabled('accounting.advanced'))
                        <x-ui.infolist.entry label="Cuenta de caja"
                            :value="$terminal->cashAccount ? $terminal->cashAccount->code.' — '.$terminal->cashAccount->name : null" />
                    @endif
                    <x-ui.infolist.entry label="Creada" :value="$terminal->created_at->format('d/m/Y')" />
                </x-ui.infolist.section>

                <x-ui.infolist.section title="Seguridad y permisos de caja" icon="heroicon-o-shield-check" flat>
                    <x-ui.infolist.entry label="PIN de acceso">
                        @if($pinMissing)
                            <x-ui.badge variant="warning" size="sm" icon="heroicon-s-exclamation-triangle">Activo sin PIN configurado</x-ui.badge>
                        @else
                            <x-ui.badge :variant="$terminal->requires_pin ? 'success' : 'slate'" size="sm" :dot="false">
                                {{ $terminal->requires_pin ? 'Requerido' : 'No requerido' }}
                            </x-ui.badge>
                        @endif
                    </x-ui.infolist.entry>
                    <x-ui.infolist.entry label="Cobro de deudas en TPV">
                        <x-ui.badge :variant="$terminal->allow_receivable_collection ? 'success' : 'slate'" size="sm" :dot="false">
                            {{ $terminal->allow_receivable_collection ? 'Permitido' : 'No permitido' }}
                        </x-ui.badge>
                    </x-ui.infolist.entry>
                    <x-ui.infolist.entry label="Descuento por producto">
                        @if($terminal->allow_item_discount)
                            <x-ui.badge variant="info" size="sm" :dot="false">Hasta {{ rtrim(rtrim(number_format($terminal->max_item_discount_percentage, 2), '0'), '.') }}%</x-ui.badge>
                        @else
                            <x-ui.badge variant="slate" size="sm" :dot="false">No permitido</x-ui.badge>
                        @endif
                    </x-ui.infolist.entry>
                    <x-ui.infolist.entry label="Descuento global">
                        @if($terminal->allow_global_discount)
                            <x-ui.badge variant="info" size="sm" :dot="false">Hasta {{ rtrim(rtrim(number_format($terminal->max_global_discount_percentage, 2), '0'), '.') }}%</x-ui.badge>
                        @else
                            <x-ui.badge variant="slate" size="sm" :dot="false">No permitido</x-ui.badge>
                        @endif
                    </x-ui.infolist.entry>
                </x-ui.infolist.section>
            </x-ui.infolist.tab>

            {{-- Turnos --}}
            @if($sessions !== null)
                <x-ui.infolist.tab name="sessions">
                    <x-ui.infolist.repeatable :empty="$sessions->isEmpty()" emptyIcon="heroicon-o-clock"
                        emptyTitle="Sin turnos" emptyDescription="Esta terminal todavía no ha abierto ningún turno.">
                        @foreach($sessions as $session)
                            <x-ui.infolist.repeatable-item :cols="6">
                                <x-ui.infolist.entry label="Turno" :value="$session->number" strong
                                    :href="$canSeeSession ? route('sales.pos.sessions.show', $session) : null" />
                                <x-ui.infolist.entry label="Estado">
                                    <x-ui.badge :variant="$session->isOpen() ? 'success' : 'slate'" size="sm" :dot="false">
                                        {{ $session->isOpen() ? 'Abierto' : 'Cerrado' }}
                                    </x-ui.badge>
                                </x-ui.infolist.entry>
                                <x-ui.infolist.entry label="Apertura">
                                    {{ $session->opened_at?->format('d/m/Y h:i A') }}
                                    @if($session->openedBy)
                                        <span class="block text-xs text-gray-400">{{ $session->openedBy->name }}</span>
                                    @endif
                                </x-ui.infolist.entry>
                                <x-ui.infolist.entry label="Cierre">
                                    @if($session->closed_at)
                                        {{ $session->closed_at->format('d/m/Y h:i A') }}
                                        @if($session->closedBy)
                                            <span class="block text-xs text-gray-400">{{ $session->closedBy->name }}</span>
                                        @endif
                                    @endif
                                </x-ui.infolist.entry>
                                <x-ui.infolist.entry label="Fondo inicial" :value="$money($session->opening_balance)" />
                                <x-ui.infolist.entry label="Diferencia" class="sm:text-right">
                                    @if($session->isClosed())
                                        <span class="font-semibold {{ $diffClass($session->difference) }}">{{ $money($session->difference) }}</span>
                                    @endif
                                </x-ui.infolist.entry>
                            </x-ui.infolist.repeatable-item>
                        @endforeach
                    </x-ui.infolist.repeatable>

                    @if($sessionsCount > $tabLimit)
                        <p class="text-xs text-slate-500">
                            Mostrando los {{ $tabLimit }} más recientes de {{ $sessionsCount }}.
                            <a href="{{ route('sales.pos.sessions.index') }}" class="font-semibold text-zertix-primary-700 hover:underline">Ver todos</a>
                        </p>
                    @endif
                </x-ui.infolist.tab>
            @endif

            {{-- Ventas --}}
            @if($sales !== null)
                <x-ui.infolist.tab name="sales">
                    <x-ui.infolist.repeatable :empty="$sales->isEmpty()" emptyIcon="heroicon-o-shopping-cart"
                        emptyTitle="Sin ventas" emptyDescription="Esta terminal todavía no ha registrado ventas.">
                        @foreach($sales as $sale)
                            @php $isCredit = $sale->payment_type === Sale::PAYMENT_CREDIT; @endphp
                            <x-ui.infolist.repeatable-item :cols="6">
                                <x-ui.infolist.entry label="Número" :value="$sale->number" strong :href="route('sales.show', $sale)" />
                                <x-ui.infolist.entry label="Fecha" :value="$sale->sale_date->format('d/m/Y h:i A')" />
                                <x-ui.infolist.entry label="Cliente" :value="$sale->client?->display_name" />
                                <x-ui.infolist.entry label="Tipo">
                                    <x-ui.badge :variant="$isCredit ? 'warning' : 'info'" size="sm" :dot="false">
                                        {{ $isCredit ? 'Crédito' : 'Contado' }}
                                    </x-ui.badge>
                                </x-ui.infolist.entry>
                                <x-ui.infolist.entry label="Estado">
                                    <x-ui.badge :variant="$sale->status === Sale::STATUS_CANCELED ? 'error' : 'success'" size="sm" :dot="false">
                                        {{ Sale::getStatuses()[$sale->status] ?? $sale->status }}
                                    </x-ui.badge>
                                </x-ui.infolist.entry>
                                <x-ui.infolist.entry label="Total" class="sm:text-right">
                                    <span class="font-semibold text-zertix-primary-700">{{ $money($sale->grand_total) }}</span>
                                </x-ui.infolist.entry>
                            </x-ui.infolist.repeatable-item>
                        @endforeach
                    </x-ui.infolist.repeatable>

                    @if($salesCount > $tabLimit)
                        <p class="text-xs text-slate-500">
                            Mostrando las {{ $tabLimit }} más recientes de {{ $salesCount }}.
                            <a href="{{ route('sales.index') }}" class="font-semibold text-zertix-primary-700 hover:underline">Ver todas</a>
                        </p>
                    @endif
                </x-ui.infolist.tab>
            @endif
        </x-ui.infolist.tabs>
    </div>

    @can('pos_terminals.delete')
        @unless($openSession)
            <x-ui.confirm-deletion-modal
                :id="'terminal-'.$terminal->id"
                title="¿Eliminar terminal?"
                :itemName="$terminal->name"
                type="la terminal"
                :route="route('sales.pos.terminals.destroy', $terminal)">
                <strong>Aviso:</strong> Esta operación se puede deshacer desde la papelera.
            </x-ui.confirm-deletion-modal>
        @endunless
    @endcan
</x-app-layout>
