{{-- Detalle del cliente — patrón Infolist (/filament-show, docs/ui/infolist.md).
     Recibe $client (provincia/municipio/accountingAccount cargados), $isMoroso,
     $tabLimit, y por pestaña: $quotes/$quotesCount, $invoices/$invoicesCount
     ($quotes/$invoices son null si el usuario no puede ver esa pestaña). --}}
@php
    $currency = config('regional.currency_symbol');
    $hasCredit = $client->credit_limit > 0;
    $exceeded = $hasCredit && $client->balance > $client->credit_limit;
    $money = fn ($v) => $currency.number_format((float) $v, 2);

    $tabs = [['name' => 'summary', 'label' => 'Resumen', 'icon' => 'heroicon-o-user-circle']];
    if ($quotes !== null) {
        $tabs[] = ['name' => 'quotes', 'label' => 'Cotizaciones', 'icon' => 'heroicon-o-document-text', 'count' => $quotesCount];
    }
    if ($invoices !== null) {
        $tabs[] = ['name' => 'invoices', 'label' => 'Facturas', 'icon' => 'heroicon-o-receipt-percent', 'count' => $invoicesCount];
    }
@endphp

<x-app-layout :title="$client->display_name">
    <div class="p-4 md:p-6 flex flex-col gap-6">

        <x-ui.page-header :title="$client->display_name" description="Ver cliente">
            <x-slot:actions>
                <x-ui.button href="{{ route('clients.index') }}" variant="secondary" appearance="outline" iconLeft="heroicon-s-arrow-left">
                    Volver
                </x-ui.button>
                @unless($client->isConsumidorFinal())
                    @can('clients.delete')
                        <x-ui.button variant="error" appearance="outline" iconLeft="heroicon-s-trash"
                            x-data @click="$dispatch('open-modal', 'confirm-deletion-{{ $client->id }}')">
                            Eliminar
                        </x-ui.button>
                    @endcan
                    @can('clients.edit')
                        <x-ui.button href="{{ route('clients.edit', $client) }}" variant="primary" iconLeft="heroicon-s-pencil-square">
                            Editar
                        </x-ui.button>
                    @endcan
                @endunless
            </x-slot:actions>
        </x-ui.page-header>

        @if($exceeded)
            <div class="rounded-xl border border-state-error/20 bg-state-error/5 px-5 py-3 flex items-center gap-2 text-sm text-state-error">
                <x-heroicon-s-exclamation-triangle class="w-5 h-5 shrink-0" />
                Superó su límite de crédito por <strong>{{ $money($client->balance - $client->credit_limit) }}</strong>.
            </div>
        @endif

        <x-ui.infolist.tabs :tabs="$tabs">

            {{-- Resumen --}}
            <x-ui.infolist.tab name="summary">
                <x-ui.infolist.section title="Datos del cliente" icon="heroicon-o-identification" flat>
                    <x-ui.infolist.entry label="Nombre / Razón social" :value="$client->name" strong />
                    <x-ui.infolist.entry label="Nombre comercial" :value="$client->commercial_name" />
                    <x-ui.infolist.entry :label="$client->tax_label" :value="$client->tax_id" />
                    <x-ui.infolist.entry label="Tipo">
                        <x-ui.badge variant="info" size="sm" :dot="false"
                            :icon="$client->type === 'company' ? 'heroicon-s-building-office' : 'heroicon-s-user'">
                            {{ $client->type === 'company' ? 'Empresa' : 'Individual' }}
                        </x-ui.badge>
                    </x-ui.infolist.entry>
                    <x-ui.infolist.entry label="Estado">
                        <span class="inline-flex flex-wrap gap-1.5">
                            <x-ui.badge :variant="$client->is_active ? 'success' : 'slate'" size="sm" :dot="false">
                                {{ $client->is_active ? 'Activo' : 'Inactivo' }}
                            </x-ui.badge>
                            @if($isMoroso)
                                <x-ui.badge variant="error" size="sm" icon="heroicon-s-exclamation-triangle">Moroso</x-ui.badge>
                            @endif
                        </span>
                    </x-ui.infolist.entry>
                    <x-ui.infolist.entry label="Cliente desde" :value="$client->created_at->format('d/m/Y')" />
                    <x-ui.infolist.entry label="Última actualización" :value="$client->updated_at->format('d/m/Y h:i A')" />
                </x-ui.infolist.section>

                <x-ui.infolist.section title="Contacto y ubicación" icon="heroicon-o-phone" flat>
                    <x-ui.infolist.entry label="Teléfono" :value="$client->phone" :href="$client->phone ? 'tel:'.$client->phone : null" />
                    <x-ui.infolist.entry label="Email" :value="$client->email" :href="$client->email ? 'mailto:'.$client->email : null" />
                    <x-ui.infolist.entry label="Provincia" :value="$client->provincia?->name" />
                    <x-ui.infolist.entry label="Municipio" :value="$client->municipio?->name" />
                    <x-ui.infolist.entry label="Dirección" :value="$client->address" full />
                </x-ui.infolist.section>

                <x-ui.infolist.section title="Crédito" icon="heroicon-o-credit-card" flat>
                    <x-ui.infolist.entry label="Límite de crédito">
                        @if($hasCredit)
                            {{ $money($client->credit_limit) }}
                        @else
                            <x-ui.badge variant="slate" size="sm" :dot="false">Solo contado</x-ui.badge>
                        @endif
                    </x-ui.infolist.entry>
                    <x-ui.infolist.entry label="Saldo actual">
                        <span @class([
                            'font-semibold',
                            'text-state-error' => $exceeded,
                            'text-amber-600' => ! $exceeded && $client->balance > 0,
                            'text-gray-900' => $client->balance <= 0,
                        ])>{{ $money($client->balance) }}</span>
                    </x-ui.infolist.entry>
                    <x-ui.infolist.entry label="Crédito disponible"
                        :value="$hasCredit ? $money(max(0, $client->credit_limit - $client->balance)) : null" />
                    <x-ui.infolist.entry label="Términos de pago"
                        :value="$hasCredit && $client->payment_terms ? $client->payment_terms.' días' : null" />
                </x-ui.infolist.section>

                @if(module_enabled('accounting.advanced'))
                    <x-ui.infolist.section title="Contabilidad" icon="heroicon-o-building-library" :cols="2" flat>
                        <x-ui.infolist.entry label="Cuenta contable"
                            :value="$client->accountingAccount ? $client->accountingAccount->code.' — '.$client->accountingAccount->name : 'Cuenta general de Cuentas por Cobrar'" />
                    </x-ui.infolist.section>
                @endif
            </x-ui.infolist.tab>

            {{-- Cotizaciones --}}
            @if($quotes !== null)
                <x-ui.infolist.tab name="quotes">
                    <x-ui.infolist.repeatable :empty="$quotes->isEmpty()" emptyIcon="heroicon-o-document-text"
                        emptyTitle="Sin cotizaciones" emptyDescription="Este cliente todavía no tiene cotizaciones.">
                        @foreach($quotes as $quote)
                            <x-ui.infolist.repeatable-item :cols="5">
                                <x-ui.infolist.entry label="Cotización" :value="$quote->number" strong :href="route('clients.quotes.show', $quote)" />
                                <x-ui.infolist.entry label="Estado">
                                    <x-ui.badge :variant="$quote->status_variant" size="sm" :dot="false">{{ $quote->status_label }}</x-ui.badge>
                                </x-ui.infolist.entry>
                                <x-ui.infolist.entry label="Fecha" :value="$quote->created_at->format('d/m/Y')" />
                                <x-ui.infolist.entry label="Válida hasta">
                                    @if($quote->expires_at)
                                        <span @class(['text-state-error' => $quote->expires_at->isPast() && in_array($quote->status, ['draft', 'approved'])])>
                                            {{ $quote->expires_at->format('d/m/Y') }}
                                        </span>
                                    @endif
                                </x-ui.infolist.entry>
                                <x-ui.infolist.entry label="Total">
                                    <span class="font-semibold text-zertix-primary-700">{{ $money($quote->grand_total) }}</span>
                                </x-ui.infolist.entry>
                            </x-ui.infolist.repeatable-item>
                        @endforeach
                    </x-ui.infolist.repeatable>

                    @if($quotesCount > $tabLimit)
                        <p class="text-xs text-slate-500">
                            Mostrando las {{ $tabLimit }} más recientes de {{ $quotesCount }}.
                            <a href="{{ route('clients.quotes.index') }}" class="font-semibold text-zertix-primary-700 hover:underline">Ver todas</a>
                        </p>
                    @endif
                </x-ui.infolist.tab>
            @endif

            {{-- Facturas --}}
            @if($invoices !== null)
                <x-ui.infolist.tab name="invoices">
                    <x-ui.infolist.repeatable :empty="$invoices->isEmpty()" emptyIcon="heroicon-o-receipt-percent"
                        emptyTitle="Sin facturas" emptyDescription="Este cliente todavía no tiene facturas.">
                        @foreach($invoices as $invoice)
                            @php
                                $sale = $invoice->sale;
                                $isCredit = $sale?->payment_type === \App\Models\Sales\Sale::PAYMENT_CREDIT;
                            @endphp
                            <x-ui.infolist.repeatable-item :cols="6">
                                <x-ui.infolist.entry label="Número" :href="route('finance.invoices.show', $invoice)" strong>
                                    {{ $invoice->invoice_number }}
                                </x-ui.infolist.entry>
                                <x-ui.infolist.entry label="Fecha" :value="$sale?->sale_date?->format('d/m/Y')" />
                                <x-ui.infolist.entry label="Tipo">
                                    <x-ui.badge :variant="$isCredit ? 'warning' : 'info'" size="sm" :dot="false">
                                        {{ $isCredit ? 'Crédito' : 'Contado' }}
                                    </x-ui.badge>
                                </x-ui.infolist.entry>
                                <x-ui.infolist.entry label="Estado">
                                    <x-ui.badge :variant="$invoice->status_variant" size="sm" :dot="false">{{ $invoice->status_label }}</x-ui.badge>
                                </x-ui.infolist.entry>
                                <x-ui.infolist.entry label="Total" :value="$sale ? $money($sale->grand_total) : null" />
                                <x-ui.infolist.entry label="Saldo">
                                    @if($isCredit && $sale->receivable)
                                        <span @class([
                                            'font-semibold',
                                            'text-amber-600' => $sale->receivable->current_balance > 0,
                                            'text-emerald-600' => $sale->receivable->current_balance <= 0,
                                        ])>{{ $money($sale->receivable->current_balance) }}</span>
                                    @endif
                                </x-ui.infolist.entry>
                            </x-ui.infolist.repeatable-item>
                        @endforeach
                    </x-ui.infolist.repeatable>

                    @if($invoicesCount > $tabLimit)
                        <p class="text-xs text-slate-500">
                            Mostrando las {{ $tabLimit }} más recientes de {{ $invoicesCount }}.
                            <a href="{{ route('finance.invoices.index') }}" class="font-semibold text-zertix-primary-700 hover:underline">Ver todas</a>
                        </p>
                    @endif
                </x-ui.infolist.tab>
            @endif
        </x-ui.infolist.tabs>
    </div>

    @unless($client->isConsumidorFinal())
        @can('clients.delete')
            <x-ui.confirm-deletion-modal
                :id="$client->id"
                title="¿Eliminar Cliente?"
                :itemName="$client->name"
                type="el cliente"
                :route="route('clients.destroy', $client)">
                <strong>Aviso:</strong> Esta operación se puede deshacer desde la papelera.
            </x-ui.confirm-deletion-modal>
        @endcan
    @endunless
</x-app-layout>
