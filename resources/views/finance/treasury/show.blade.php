{{-- Cuenta de Cajas y Bancos — show de consulta (v1.5.0 REQ-3.5, /filament-show).
     Fila 1: datos | saldo. Fila 2: últimos movimientos (el resto, en Movimientos de
     Tesorería). Fila 3: conciliaciones. Las operaciones viven en sus propias vistas.
     Recibe $account (con las últimas reconciliations.creator), $movements (los últimos,
     withIndexRelations()), $movementsCount y $assignedToPos. --}}
@php
    $currency = config('regional.currency_symbol');
    $money = fn ($v) => $currency.number_format((float) $v, 2);
    $user = auth()->user();
    $active = $account->is_active;
    $last = $account->reconciliations->first();
@endphp

<x-app-layout :title="$account->name">
    <div class="p-4 md:p-6 flex flex-col gap-6">

        <x-ui.page-header :title="$account->name" :description="$account->bank_label ?? $account->type_label">
            <x-slot:actions>
                <x-ui.button href="{{ route('finance.treasury.index') }}" variant="secondary" appearance="outline" iconLeft="heroicon-s-arrow-left">
                    Volver
                </x-ui.button>
                @if($active && $user->can('financial_accounts.reconcile'))
                    <x-ui.button href="{{ route('finance.treasury.reconcile.form', ['account' => $account->id]) }}" variant="secondary" appearance="outline" iconLeft="heroicon-s-scale">
                        Conciliar
                    </x-ui.button>
                @endif
                @if($active && $user->can('financial_accounts.move'))
                    <x-ui.button href="{{ route('finance.treasury.movements.create', ['account' => $account->id]) }}" variant="primary" iconLeft="heroicon-s-plus">
                        Nuevo movimiento
                    </x-ui.button>
                @endif
            </x-slot:actions>

            @can('financial_accounts.manage')
                <x-slot:secondary>
                    <x-ui.button href="{{ route('finance.treasury.edit', $account) }}" variant="secondary" appearance="ghost" class="w-full justify-start" iconLeft="heroicon-s-pencil-square">
                        Editar datos
                    </x-ui.button>
                    <form method="POST" action="{{ route('finance.treasury.toggle', $account) }}">
                        @csrf @method('PATCH')
                        <x-ui.button type="submit" :variant="$active ? 'error' : 'secondary'" appearance="ghost" class="w-full justify-start" iconLeft="heroicon-s-power">
                            {{ $active ? 'Desactivar cuenta' : 'Activar cuenta' }}
                        </x-ui.button>
                    </form>
                </x-slot:secondary>
            @endcan
        </x-ui.page-header>

        @unless($active)
            <x-ui.alert variant="neutral" icon="heroicon-s-no-symbol">
                Cuenta inactiva: no recibe movimientos ni se concilia.
            </x-ui.alert>
        @endunless
        @if($account->isNegative())
            <x-ui.alert variant="warning">
                El saldo está en negativo. Concilia la cuenta para dejarla igual a la realidad.
            </x-ui.alert>
        @endif

        {{-- Fila 1: datos | saldo --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
            <x-ui.infolist.section title="Datos de la cuenta" :icon="$account->isBank() ? 'heroicon-o-building-library' : 'heroicon-o-banknotes'" :cols="2">
                <x-ui.infolist.entry label="Nombre" :value="$account->name" strong />
                <x-ui.infolist.entry label="Estado">
                    <x-ui.badge :variant="$active ? 'success' : 'slate'" size="sm" :dot="false">{{ $active ? 'Activa' : 'Inactiva' }}</x-ui.badge>
                </x-ui.infolist.entry>
                <x-ui.infolist.entry label="Tipo" :value="$account->type_label" />
                @if($account->isBank())
                    <x-ui.infolist.entry label="Banco" :value="$account->bank_label" />
                @endif
                @if($assignedToPos)
                    <x-ui.infolist.entry label="POS" value="Recibe los pagos que no son efectivo" />
                @endif
                <x-ui.infolist.entry label="Creada" :value="$account->created_at->format('d/m/Y')" />
                <x-ui.infolist.entry label="Nota" :value="$account->notes" full />
            </x-ui.infolist.section>

            <x-ui.infolist.section title="Saldo" icon="heroicon-o-currency-dollar" :cols="2">
                <x-ui.infolist.entry label="Saldo según el sistema">
                    <span @class(['text-xl font-bold tabular-nums', 'text-state-error' => $account->isNegative(), 'text-zertix-primary-700' => ! $account->isNegative()])>
                        {{ $money($account->current_balance) }}
                    </span>
                </x-ui.infolist.entry>
                <x-ui.infolist.entry label="Última conciliación">
                    @if($last)
                        {{ $last->reconciled_at->format('d/m/Y') }}
                        <span class="block text-xs text-gray-500">
                            {{ $last->hasDifference() ? 'Diferencia de '.($last->difference > 0 ? '+' : '−').$money(abs($last->difference)) : 'Sin diferencia' }}
                        </span>
                    @else
                        <span class="text-gray-400">Nunca</span>
                    @endif
                </x-ui.infolist.entry>
            </x-ui.infolist.section>
        </div>

        {{-- Fila 2: últimos movimientos --}}
        <x-ui.infolist.section title="Últimos movimientos" icon="heroicon-o-queue-list" :cols="0"
            :description="$movementsCount > $movements->count() ? 'Los '.$movements->count().' más recientes de '.$movementsCount : null">
            <x-ui.infolist.repeatable :empty="$movements->isEmpty()" emptyIcon="heroicon-o-queue-list"
                emptyTitle="Sin movimientos" emptyDescription="Esta cuenta todavía no tiene movimientos.">
                @foreach($movements as $m)
                    <x-ui.infolist.repeatable-item :cols="5" :class="$m->isReversed() ? 'opacity-60' : ''">
                        <x-ui.infolist.entry label="Fecha" :value="$m->movement_date->format('d/m/Y')" />
                        <x-ui.infolist.entry label="Origen" class="col-span-2">
                            <span class="inline-flex flex-wrap items-center gap-1.5">
                                <x-ui.badge :variant="$m->origin_variant" size="sm" :dot="false">{{ $m->origin_label }}</x-ui.badge>
                                @if($m->isReversed())
                                    <span class="text-xs font-medium text-state-error">Anulado</span>
                                @endif
                            </span>
                            <span class="block text-xs font-normal text-gray-500 truncate">{{ $m->detail ?? $m->notes }}</span>
                        </x-ui.infolist.entry>
                        <x-ui.infolist.entry label="Monto">
                            <span @class(['font-semibold tabular-nums', 'text-state-success' => $m->isIn(), 'text-state-error' => ! $m->isIn()])>
                                {{ $m->isIn() ? '+' : '−' }}{{ $money($m->amount) }}
                            </span>
                            <span class="block text-xs font-normal text-gray-500 tabular-nums">Saldo {{ $money($m->balance_after) }}</span>
                        </x-ui.infolist.entry>
                        <x-ui.infolist.entry label="" class="sm:text-right">
                            <x-ui.button variant="secondary" appearance="ghost" size="sm" iconLeft="heroicon-o-eye"
                                x-data x-on:click="$dispatch('open-modal', 'view-movement-{{ $m->id }}')">
                                Ver
                            </x-ui.button>
                        </x-ui.infolist.entry>
                    </x-ui.infolist.repeatable-item>
                @endforeach
            </x-ui.infolist.repeatable>

            @if($movementsCount)
                <p class="mt-4 text-xs text-slate-500">
                    <a href="{{ route('finance.treasury.movements.index', ['account' => $account->id]) }}" class="font-semibold text-zertix-primary-700 hover:underline">
                        Ver todos en Movimientos de Tesorería
                    </a>
                </p>
            @endif
        </x-ui.infolist.section>

        {{-- Fila 3: conciliaciones --}}
        <x-ui.infolist.section title="Conciliaciones" icon="heroicon-o-scale" :cols="0"
            :description="$account->reconciliations->count() >= 10 ? 'Las 10 más recientes' : null">
            <x-ui.infolist.repeatable :empty="$account->reconciliations->isEmpty()" emptyIcon="heroicon-o-scale"
                emptyTitle="Sin conciliaciones" emptyDescription="Concilia para comparar el saldo del sistema con lo contado o con el estado del banco.">
                @foreach($account->reconciliations as $rec)
                    <x-ui.infolist.repeatable-item :cols="5">
                        <x-ui.infolist.entry label="Fecha" :value="$rec->reconciled_at->format('d/m/Y')" strong />
                        <x-ui.infolist.entry label="Según el sistema" :value="$money($rec->system_balance)" />
                        <x-ui.infolist.entry label="Real" :value="$money($rec->real_balance)" strong />
                        <x-ui.infolist.entry label="Diferencia">
                            @if($rec->hasDifference())
                                <span @class(['font-semibold tabular-nums', 'text-state-error' => $rec->difference < 0, 'text-state-success' => $rec->difference > 0])>
                                    {{ $rec->difference > 0 ? '+' : '−' }}{{ $money(abs($rec->difference)) }}
                                </span>
                                <span class="block text-xs text-gray-500">{{ $rec->reason_label }}</span>
                            @else
                                <x-ui.badge variant="success" size="sm" :dot="false">Cuadró</x-ui.badge>
                            @endif
                        </x-ui.infolist.entry>
                        <x-ui.infolist.entry label="Registró">
                            {{ $rec->creator->name ?? '—' }}
                            @if($rec->notes)
                                <span class="block text-xs text-gray-500">{{ $rec->notes }}</span>
                            @endif
                        </x-ui.infolist.entry>
                    </x-ui.infolist.repeatable-item>
                @endforeach
            </x-ui.infolist.repeatable>
        </x-ui.infolist.section>
    </div>

    @include('finance.treasury.movements.partials.detail-modals', ['movements' => $movements])
</x-app-layout>
