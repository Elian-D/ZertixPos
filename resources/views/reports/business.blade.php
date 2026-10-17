{{-- Estado del negocio (v1.5.0 REQ-6.2): dinero + mercancía + me deben − debo = neto, hoy.
     Recibe $show (qué partes tienen su módulo encendido), $accounts, $byWarehouse, $totals,
     $topDebtors y $topCreditors (App\Http\Controllers\Reports\BusinessStatusController). --}}
@php
    $currency = config('regional.currency_symbol');
    $money = fn ($v) => ($v < 0 ? '−' : '').$currency.number_format(abs((float) $v), 2);
    $t = $totals;
    $accountTypes = ['cash' => 'Caja', 'bank' => 'Banco'];
@endphp

<x-app-layout title="Estado del negocio">
    <div class="p-4 md:p-6 flex flex-col gap-6">
        <x-ui.page-header title="Estado del negocio" :description="'Hoy, '.now()->translatedFormat('j \d\e F').' · ¿en qué está el negocio?'" />

        @php
            $parts = array_filter(['Dinero', $show['inventory'] ? 'mercancía' : null, $show['receivables'] ? 'lo que te deben' : null]);
            $netHint = implode(' + ', $parts).($show['payables'] ? ' − lo que debes' : '');
        @endphp
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
            <x-ui.stat label="Dinero" :value="$money($t['money'])" :variant="$t['money'] < 0 ? 'error' : 'default'" hint="Cajas y Bancos" />
            @if($show['inventory'])
                <x-ui.stat label="+ Mercancía" :value="$money($t['inventory'])" hint="Existencias a costo" />
            @endif
            @if($show['receivables'])
                <x-ui.stat label="+ Me deben" :value="$money($t['receivables'])" hint="Cuentas por cobrar" />
            @endif
            @if($show['payables'])
                <x-ui.stat label="− Debo" :value="$money($t['payables'])" :variant="$t['payables'] > 0 ? 'error' : 'default'" hint="Cuentas por pagar" />
            @endif
        </div>

        <x-ui.stat label="= Neto: lo que es del negocio" :value="$money($t['net'])" :variant="$t['net'] < 0 ? 'error' : 'success'"
            :hint="$netHint" class="ring-1 ring-zertix-primary/30" />

        <x-ui.alert variant="neutral">No incluye el efectivo de los turnos abiertos: entra a Cajas y Bancos al depositarlo.</x-ui.alert>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
            <x-ui.infolist.section title="Dinero" icon="heroicon-o-banknotes" :cols="0">
                <x-ui.report.rows empty="Sin cuentas activas en Cajas y Bancos.">
                    @foreach($accounts as $account)
                        <x-ui.report.row :label="$account->name" :sub="($accountTypes[$account->type] ?? $account->type).($account->bank_name ? ' · '.$account->bank_name : '')"
                            :value="$money($account->current_balance)" :variant="$account->current_balance < 0 ? 'error' : 'default'"
                            :href="auth()->user()->can('financial_accounts.view') ? route('finance.treasury.show', $account) : null" />
                    @endforeach
                </x-ui.report.rows>
            </x-ui.infolist.section>

            @if($show['inventory'])
                <x-ui.infolist.section title="Mercancía" icon="heroicon-o-building-office-2" :cols="0" description="A costo, por almacén">
                    @can('inventory.dashboard')
                        <x-slot:headerActions>
                            <x-ui.button href="{{ route('reports.inventory') }}" size="sm" variant="secondary" appearance="ghost">Ver inventario</x-ui.button>
                        </x-slot:headerActions>
                    @endcan
                    <x-ui.report.rows empty="Sin existencias.">
                        @foreach($byWarehouse as $row)
                            <x-ui.report.row :label="$row->name" :hint="$row->skus.' productos'" :value="$money($row->cost)" />
                        @endforeach
                    </x-ui.report.rows>
                </x-ui.infolist.section>
            @endif

            @if($show['receivables'])
                <x-ui.infolist.section title="Me deben" icon="heroicon-o-arrow-down-left" :cols="0" description="Clientes con más saldo pendiente">
                    <x-ui.report.rows empty="Nadie te debe.">
                        @foreach($topDebtors as $row)
                            <x-ui.report.row :label="$row->name" :sub="$row->count.' '.($row->count == 1 ? 'factura' : 'facturas').($row->overdue > 0 ? ' · vencido '.$money($row->overdue) : '')"
                                :value="$money($row->total)" />
                        @endforeach
                    </x-ui.report.rows>
                </x-ui.infolist.section>
            @endif

            @if($show['payables'])
                <x-ui.infolist.section title="Debo" icon="heroicon-o-arrow-up-right" :cols="0" description="Proveedores con más saldo pendiente">
                    <x-ui.report.rows empty="No le debes a nadie.">
                        @foreach($topCreditors as $row)
                            <x-ui.report.row :label="$row->name" :sub="$row->count.' '.($row->count == 1 ? 'cuenta' : 'cuentas').($row->overdue > 0 ? ' · vencido '.$money($row->overdue) : '')"
                                :value="$money($row->total)" />
                        @endforeach
                    </x-ui.report.rows>
                </x-ui.infolist.section>
            @endif
        </div>
    </div>
</x-app-layout>
