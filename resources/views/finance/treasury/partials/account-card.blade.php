{{-- Tarjeta de una cuenta en Cajas y Bancos (v1.5.0 REQ-3.5). Recibe $account (con
     lastReconciliation), $assigned (ids de cuentas que reciben pagos del POS) y $money. --}}
@php
    $user = auth()->user();
    $active = $account->is_active;
    $rec = $account->lastReconciliation;
    $recState = ! $rec ? 'never' : ($rec->hasDifference() ? ($rec->difference < 0 ? 'short' : 'over') : 'ok');
@endphp

<div @class(['flex flex-col bg-white rounded-2xl border border-gray-100 shadow-sm min-w-0', 'opacity-70' => ! $active])>
    <div class="p-5 flex-1">
        <div class="flex items-start gap-3">
            <span @class([
                'w-10 h-10 rounded-xl flex items-center justify-center shrink-0',
                'bg-zertix-primary/10 text-zertix-primary' => ! $account->isBank(),
                'bg-state-info/10 text-state-info' => $account->isBank(),
            ])>
                <x-dynamic-component :component="$account->type_icon" class="w-5 h-5" />
            </span>

            <div class="min-w-0 flex-1">
                <div class="flex items-center gap-2 flex-wrap">
                    <a href="{{ route('finance.treasury.show', $account) }}" class="font-semibold text-gray-900 hover:underline truncate">{{ $account->name }}</a>
                    @if($account->isNegative())
                        <x-ui.badge variant="warning" size="sm" :dot="false">Saldo negativo</x-ui.badge>
                    @endif
                </div>
                <p class="text-xs text-slate-500 truncate">{{ $account->bank_label ?? ($account->notes ?: $account->type_label) }}</p>
            </div>

            <x-ui.action-menu>
                <x-ui.action-menu.item href="{{ route('finance.treasury.show', $account) }}" icon="heroicon-o-eye">Ver</x-ui.action-menu.item>
                @can('financial_accounts.manage')
                    <x-ui.action-menu.item href="{{ route('finance.treasury.edit', $account) }}" icon="heroicon-o-pencil-square">Editar</x-ui.action-menu.item>
                    <x-ui.action-menu.item icon="heroicon-o-power" :variant="$active ? 'danger' : 'default'"
                        x-data x-on:click="document.getElementById('toggle-account-{{ $account->id }}').submit()">
                        {{ $active ? 'Desactivar' : 'Activar' }}
                    </x-ui.action-menu.item>
                @endcan
            </x-ui.action-menu>
        </div>

        <p class="mt-5 text-xs text-slate-500">Saldo</p>
        <p @class(['text-2xl font-bold tabular-nums', 'text-state-error' => $account->isNegative(), 'text-slate-900' => ! $account->isNegative()])>
            {{ $money($account->current_balance) }}
        </p>

        @if(in_array($account->id, $assigned, true))
            <x-ui.badge variant="info" size="sm" :dot="false" icon="heroicon-s-credit-card" class="mt-3">Recibe pagos del POS</x-ui.badge>
        @endif
    </div>

    {{-- Pie: última conciliación y accesos rápidos --}}
    <div class="px-5 py-3 border-t border-gray-100 bg-gray-50/60 rounded-b-2xl flex flex-wrap items-center gap-x-4 gap-y-2">
        <span class="flex items-start gap-1.5 text-xs text-slate-600 min-w-0 flex-1 basis-40">
            <span @class([
                'mt-1 w-2 h-2 rounded-full shrink-0',
                'bg-state-success' => $recState === 'ok',
                'bg-state-error' => $recState === 'short',
                'bg-state-warning' => $recState === 'over',
                'bg-slate-300' => $recState === 'never',
            ])></span>
            <span class="min-w-0">
                @if($rec)
                    Conciliada {{ $rec->reconciled_at->format('d/m/Y') }} ·
                    {{ $rec->hasDifference() ? ($rec->difference > 0 ? '+' : '−').$money(abs($rec->difference)) : 'Cuadró' }}
                @else
                    Nunca conciliada
                @endif
            </span>
        </span>

        @if($active)
            @can('financial_accounts.move')
                <a href="{{ route('finance.treasury.movements.create', ['account' => $account->id]) }}"
                    class="text-xs font-semibold text-zertix-primary-700 hover:underline whitespace-nowrap">+ Movimiento</a>
            @endcan
            @can('financial_accounts.reconcile')
                <a href="{{ route('finance.treasury.reconcile.form', ['account' => $account->id]) }}"
                    class="inline-flex items-center gap-1 text-xs font-semibold text-slate-600 hover:text-slate-900 whitespace-nowrap">
                    <x-heroicon-o-scale class="w-4 h-4" /> Conciliar
                </a>
            @endcan
        @endif
    </div>

    @can('financial_accounts.manage')
        <form id="toggle-account-{{ $account->id }}" method="POST" action="{{ route('finance.treasury.toggle', $account) }}" class="hidden">
            @csrf @method('PATCH')
        </form>
    @endcan
</div>
