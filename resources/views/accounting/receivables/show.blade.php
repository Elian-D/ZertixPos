{{-- Detalle de la cuenta por cobrar — patrón Infolist (/filament-show,
     docs/ui/infolist.md). Fila 1: documento | saldo (50/50). Fila 2: abonos.
     Fila 3: devoluciones (si hubo). Recibe $receivable (client, accountingAccount,
     collections.tipoPago/creator, saleReturns cargados; returned_amount asignado)
     y $sale (venta origen o null). --}}
@use('App\Models\Accounting\Receivable')
@use('App\Models\Accounting\ClientCollection')
@use('App\Models\Configuration\TipoPago')
@php
    $currency = config('regional.currency_symbol');
    $money = fn ($v) => $currency.number_format((float) $v, 2);

    $isOpen = in_array($receivable->status, [Receivable::STATUS_UNPAID, Receivable::STATUS_PARTIAL], true);
    $isOverdue = $receivable->is_overdue;
    $original = $receivable->original_amount;
    $paid = $receivable->paid_amount;
    $returned = (float) $receivable->returned_amount;
    $balance = (float) $receivable->current_balance;
    // Progreso sobre el monto original: lo cobrado + lo devuelto ya no se debe.
    $settledPct = $original > 0 ? min(100, round(($paid + $returned) / $original * 100)) : 0;
    $daysToDue = (int) now()->startOfDay()->diffInDays($receivable->due_date->copy()->startOfDay(), false);

    $pmHex = TipoPago::getBadgeHexColors();
    $pmIcons = TipoPago::getBadgeIcons();
@endphp

<x-app-layout :title="'CxC '.$receivable->number">
    <div class="p-4 md:p-6 flex flex-col gap-6">

        <x-ui.page-header :title="'Cuenta por cobrar '.$receivable->number" description="Ver cuenta por cobrar">
            <x-slot:actions>
                <x-ui.button href="{{ route('finance.receivables.index') }}" variant="secondary" appearance="outline" iconLeft="heroicon-s-arrow-left">
                    Volver
                </x-ui.button>
                @if($isOpen)
                    @can('collections.create')
                        {{-- :href, no href="{{ }}": x-ui.button vuelve a escapar el valor y el
                             "&" de la query llegaba como "&amp;" (receivable_id se perdía). --}}
                        <x-ui.button :href="route('finance.collections.create', ['client_id' => $receivable->client_id, 'receivable_id' => $receivable->id])"
                            variant="success" iconLeft="heroicon-s-banknotes">
                            Registrar cobro
                        </x-ui.button>
                    @endcan
                @endif
            </x-slot:actions>
        </x-ui.page-header>

        {{-- Aviso según la situación de la cuenta --}}
        @if($isOpen && $isOverdue)
            <div class="rounded-xl border border-state-error/20 bg-state-error/5 px-5 py-3 flex items-center gap-2 text-sm text-state-error">
                <x-heroicon-s-exclamation-triangle class="w-5 h-5 shrink-0" />
                Vencida hace <strong>{{ abs($daysToDue) }} {{ abs($daysToDue) === 1 ? 'día' : 'días' }}</strong> — saldo pendiente {{ $money($balance) }}.
            </div>
        @elseif($receivable->status === Receivable::STATUS_PAID)
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-3 flex items-center gap-2 text-sm text-emerald-800">
                <x-heroicon-s-check-circle class="w-5 h-5 shrink-0" />
                Cuenta saldada.
            </div>
        @elseif($receivable->status === Receivable::STATUS_CANCELLED)
            <div class="rounded-xl border border-gray-200 bg-gray-50 px-5 py-3 flex items-center gap-2 text-sm text-gray-600">
                <x-heroicon-s-no-symbol class="w-5 h-5 shrink-0" />
                {{ $returned > 0 && $paid == 0 ? 'Anulada: la mercancía se devolvió completa.' : 'Anulada: la venta de origen se anuló.' }}
            </div>
        @endif

        {{-- Fila 1: documento | saldo --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">

            <x-ui.infolist.section title="Documento" icon="heroicon-o-document-text" :cols="2">
                <x-ui.infolist.entry label="Número" :value="$receivable->number" strong />
                <x-ui.infolist.entry label="Estado">
                    <span class="inline-flex flex-wrap gap-1.5">
                        <x-ui.badge :variant="$receivable->status_variant" size="sm" :dot="false">{{ $receivable->status_label }}</x-ui.badge>
                        @if($isOpen && $isOverdue)
                            <x-ui.badge variant="error" size="sm" icon="heroicon-s-clock">Vencida</x-ui.badge>
                        @endif
                    </span>
                </x-ui.infolist.entry>
                <x-ui.infolist.entry label="Cliente" :value="$receivable->client?->display_name" strong
                    :href="$receivable->client && auth()->user()->can('clients.view') ? route('clients.show', $receivable->client) : null" />
                <x-ui.infolist.entry label="Venta origen" :value="$sale?->number"
                    :href="$sale && auth()->user()->can('sales.view') ? route('sales.show', $sale) : null" />
                <x-ui.infolist.entry label="Emisión" :value="$receivable->emission_date?->format('d/m/Y')" />
                <x-ui.infolist.entry label="Vencimiento">
                    <span @class(['font-medium text-state-error' => $isOpen && $isOverdue])>{{ $receivable->due_date->format('d/m/Y') }}</span>
                    @if($isOpen)
                        <span @class(['block text-xs', 'text-state-error' => $isOverdue, 'text-gray-400' => ! $isOverdue])>
                            @if($daysToDue > 0)
                                en {{ $daysToDue }} {{ $daysToDue === 1 ? 'día' : 'días' }}
                            @elseif($daysToDue === 0)
                                vence hoy
                            @else
                                hace {{ abs($daysToDue) }} {{ abs($daysToDue) === 1 ? 'día' : 'días' }}
                            @endif
                        </span>
                    @endif
                </x-ui.infolist.entry>
                <x-ui.infolist.entry label="Teléfono del cliente" :value="$receivable->client?->phone"
                    :href="$receivable->client?->phone ? 'tel:'.$receivable->client->phone : null" />
                @if(module_enabled('accounting.advanced'))
                    <x-ui.infolist.entry label="Cuenta contable"
                        :value="$receivable->accountingAccount ? $receivable->accountingAccount->code.' — '.$receivable->accountingAccount->name : null" />
                @endif
                <x-ui.infolist.entry label="Descripción" :value="$receivable->description" full />
            </x-ui.infolist.section>

            <x-ui.infolist.section title="Saldo de la cuenta" icon="heroicon-o-scale" :cols="2">
                <x-ui.infolist.entry label="Saldo pendiente" full>
                    <span @class([
                        'text-3xl font-bold',
                        'text-state-error' => $isOpen && $isOverdue,
                        'text-amber-600' => $isOpen && ! $isOverdue,
                        'text-emerald-600' => ! $isOpen,
                    ])>{{ $money($balance) }}</span>

                    <span class="mt-3 block">
                        <span class="block h-2 w-full overflow-hidden rounded-full bg-slate-100">
                            <span class="block h-full rounded-full bg-emerald-500" style="width: {{ $settledPct }}%"></span>
                        </span>
                        <span class="mt-1 block text-xs text-gray-500">{{ $settledPct }}% saldado</span>
                    </span>
                </x-ui.infolist.entry>
                <x-ui.infolist.entry label="Monto original" :value="$money($original)" />
                <x-ui.infolist.entry label="Abonado">
                    <span class="text-emerald-600">{{ $paid > 0 ? '-' : '' }}{{ $money($paid) }}</span>
                </x-ui.infolist.entry>
                @if($returned > 0)
                    <x-ui.infolist.entry label="Devuelto">
                        <span class="text-amber-600">-{{ $money($returned) }}</span>
                    </x-ui.infolist.entry>
                @endif
                <x-ui.infolist.entry label="Abonos registrados" :value="(string) $receivable->collections->where('status', ClientCollection::STATUS_ACTIVE)->count()" />
            </x-ui.infolist.section>
        </div>

        {{-- Fila 2: historial de abonos --}}
        <x-ui.infolist.section title="Historial de abonos" icon="heroicon-o-banknotes" :cols="0"
            :description="$receivable->collections->count().' '.($receivable->collections->count() === 1 ? 'cobro' : 'cobros')">
            <x-ui.infolist.repeatable :empty="$receivable->collections->isEmpty()" emptyIcon="heroicon-o-banknotes"
                emptyTitle="Sin abonos" emptyDescription="Todavía no se ha cobrado nada de esta cuenta.">
                @foreach($receivable->collections as $collection)
                    @php
                        $slug = $collection->tipoPago?->slug;
                        $isActive = $collection->status === ClientCollection::STATUS_ACTIVE;
                    @endphp
                    <x-ui.infolist.repeatable-item :cols="7" :class="$isActive ? '' : 'opacity-60'">
                        <x-ui.infolist.entry label="Recibo" :value="$collection->receipt_number" strong
                            :href="auth()->user()->can('collections.view') ? route('finance.collections.show', $collection) : null" />
                        <x-ui.infolist.entry label="Fecha" :value="$collection->payment_date?->format('d/m/Y')" />
                        <x-ui.infolist.entry label="Método">
                            @if($collection->tipoPago)
                                <x-ui.badge size="sm"
                                    :hex="$pmHex[$slug] ?? TipoPago::getDefaultBadgeHex()"
                                    :icon="$pmIcons[$slug] ?? TipoPago::getDefaultBadgeIcon()">
                                    {{ $collection->tipoPago->nombre }}
                                </x-ui.badge>
                            @endif
                        </x-ui.infolist.entry>
                        <x-ui.infolist.entry label="Referencia" :value="$collection->reference" />
                        <x-ui.infolist.entry label="Registrado por" :value="$collection->creator?->name" />
                        <x-ui.infolist.entry label="Estado">
                            <x-ui.badge :variant="$isActive ? 'success' : 'error'" size="sm" :dot="false">
                                {{ ClientCollection::getStatuses()[$collection->status] ?? $collection->status }}
                            </x-ui.badge>
                        </x-ui.infolist.entry>
                        <x-ui.infolist.entry label="Monto" class="sm:text-right">
                            <span @class(['font-semibold', 'text-emerald-600' => $isActive, 'text-gray-400 line-through' => ! $isActive])>{{ $money($collection->amount) }}</span>
                        </x-ui.infolist.entry>
                        @if($collection->note)
                            <x-ui.infolist.entry label="Nota" :value="$collection->note" class="col-span-2 sm:col-span-full" />
                        @endif
                    </x-ui.infolist.repeatable-item>
                @endforeach
            </x-ui.infolist.repeatable>
        </x-ui.infolist.section>

        {{-- Fila 3: devoluciones que bajaron la deuda (solo si hubo) --}}
        @if($receivable->saleReturns->isNotEmpty())
            <x-ui.infolist.section title="Devoluciones aplicadas" icon="heroicon-o-arrow-uturn-left" :cols="0"
                description="Productos devueltos que redujeron la deuda — no son abonos.">
                <x-ui.infolist.repeatable>
                    @foreach($receivable->saleReturns as $return)
                        <x-ui.infolist.repeatable-item :cols="5" :class="$return->isVoided() ? 'opacity-60' : ''">
                            <x-ui.infolist.entry label="Devolución" :value="$return->number" strong
                                :href="auth()->user()->canany(['returns.create', 'returns.void']) ? route('sales.returns.show', $return) : null" />
                            <x-ui.infolist.entry label="Fecha" :value="$return->created_at->format('d/m/Y')" />
                            <x-ui.infolist.entry label="Realizada por" :value="$return->user?->name" />
                            <x-ui.infolist.entry label="Estado">
                                <x-ui.badge :variant="$return->isVoided() ? 'error' : 'success'" size="sm" :dot="false">
                                    {{ \App\Models\Sales\Returns\SaleReturn::getStatuses()[$return->status] ?? $return->status }}
                                </x-ui.badge>
                            </x-ui.infolist.entry>
                            <x-ui.infolist.entry label="Monto" class="sm:text-right">
                                <span class="font-semibold text-amber-600">-{{ $money($return->refund_value) }}</span>
                            </x-ui.infolist.entry>
                        </x-ui.infolist.repeatable-item>
                    @endforeach
                </x-ui.infolist.repeatable>
            </x-ui.infolist.section>
        @endif
    </div>
</x-app-layout>
