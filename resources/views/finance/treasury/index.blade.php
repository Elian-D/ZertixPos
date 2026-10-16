{{-- Cajas y Bancos (v1.5.0 REQ-3.5): tarjetas en vez de tabla, porque un negocio tiene
     pocas cuentas. Totales arriba, una grilla por tipo y las inactivas plegadas.
     Recibe $cash, $banks e $inactive (con lastReconciliation), $totals, $assigned (ids que
     reciben pagos del POS) y $missingBankAccount. --}}
@php
    $currency = config('regional.currency_symbol');
    $money = fn ($v) => $currency.number_format((float) $v, 2);
    $count = fn (int $n, string $one, string $many) => $n.' '.($n === 1 ? $one : $many);
@endphp

<x-app-layout title="Cajas y Bancos">
    <div class="p-4 md:p-6 flex flex-col gap-6">

        <x-ui.page-header title="Cajas y Bancos" description="Dinero en tus cajas y cuentas de banco.">
            <x-slot:actions>
                <x-ui.button href="{{ route('finance.treasury.movements.index') }}" variant="secondary" appearance="outline" iconLeft="heroicon-s-clock">
                    Movimientos
                </x-ui.button>
                @can('financial_accounts.manage')
                    <x-ui.button href="{{ route('finance.treasury.create') }}" variant="primary" iconLeft="heroicon-s-plus">
                        Nueva cuenta
                    </x-ui.button>
                @endcan
            </x-slot:actions>
        </x-ui.page-header>

        @if($missingBankAccount)
            <x-ui.alert variant="neutral" icon="heroicon-s-credit-card">
                Las ventas con tarjeta, transferencia o depósito no entran a ninguna cuenta.
                @if($banks->isEmpty())
                    Crea una cuenta de banco y elígela en la
                @else
                    Elige una cuenta de banco por defecto en la
                @endif
                <a href="{{ route('sales.pos.settings.edit') }}" class="font-semibold underline">configuración del POS</a>.
            </x-ui.alert>
        @endif

        {{-- Totales (solo cuentas activas) --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <x-ui.stat label="Total disponible" :value="$money($totals['all'])" hint="Solo cuentas activas" hintIcon="heroicon-o-check-circle"
                :variant="$totals['all'] < 0 ? 'error' : 'default'" />
            <x-ui.stat label="En cajas" :value="$money($totals['cash'])" :hint="$count($cash->count(), 'caja', 'cajas')" />
            <x-ui.stat label="En bancos" :value="$money($totals['bank'])" :hint="$count($banks->count(), 'cuenta de banco', 'cuentas de banco')" />
        </div>

        @if($cash->isEmpty() && $banks->isEmpty())
            <x-ui.empty-state variant="simple" icon="heroicon-o-building-library" title="No hay cuentas registradas"
                description="Crea la caja fuerte o la cuenta del banco con el saldo que tienen hoy." />
        @endif

        @foreach([['Cajas', $cash], ['Bancos', $banks]] as [$title, $group])
            @if($group->isNotEmpty())
                <section>
                    <h2 class="mb-3 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ $title }} ({{ $group->count() }})</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                        @foreach($group as $account)
                            @include('finance.treasury.partials.account-card', ['account' => $account])
                        @endforeach
                    </div>
                </section>
            @endif
        @endforeach

        @if($inactive->isNotEmpty())
            <section x-data="{ open: false }">
                <button type="button" class="flex items-center gap-1.5 text-sm font-medium text-slate-600 hover:text-slate-900" x-on:click="open = !open">
                    <x-heroicon-o-chevron-right class="w-4 h-4 transition-transform" ::class="open && 'rotate-90'" />
                    Cuentas inactivas ({{ $inactive->count() }})
                </button>
                <div x-show="open" x-collapse x-cloak class="mt-3 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                    @foreach($inactive as $account)
                        @include('finance.treasury.partials.account-card', ['account' => $account])
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</x-app-layout>
