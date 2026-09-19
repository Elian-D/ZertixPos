<div>

    <x-ui.page-header
        title="Tenants"
        description="Negocios instalados en ZertixPOS."
        :count="$tenants->total()"
        countLabel="tenants"
    >
        <x-slot:actions>
            {{--
                REQ-5.6 / REQ-4.3 (ver docs/features/v1.3.0.md §4.3, segunda
                revisión) — enlaza al wizard público (/install) en pestaña
                nueva, sin duplicar el formulario. El wizard ya no redirige
                solo al terminar (muestra un link que el usuario clickea),
                así que la pestaña de este panel nunca se ve afectada.
            --}}
            <x-ui.button
                :href="route('install.wizard')"
                target="_blank"
                rel="noopener"
                variant="primary"
                iconLeft="heroicon-s-plus">
                Nuevo Tenant
            </x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <x-data-table.base-table
        :items="$tenants"
        :columns="$this->columns()"
        :visibleColumns="$visibleColumns"
        :activeChips="$this->getActiveChips()"
        :hasFilters="$this->activeFilterCount() > 0"
    >
        <x-slot:filterSlot>
            <x-data-table.filter-container :activeCount="$this->activeFilterCount()">

                <x-data-table.filter-select
                    label="Plan" filterKey="plan_id"
                    :options="$plans"
                    placeholder="Todos los planes" />

                <x-data-table.filter-select
                    label="Estado" filterKey="status"
                    :options="$statuses"
                    placeholder="Todos" />

            </x-data-table.filter-container>
        </x-slot:filterSlot>

        @forelse($tenants as $item)
            @php
                $domain = $item->domains->first()?->domain;

                // Mismo cálculo de puerto que InstallWizard::tenantLoginUrl()
                // (el subdominio se sirve en el mismo puerto que este panel,
                // no uno distinto) — REQ-5.1, ajuste 2026-09-14.
                $port = request()->getPort();
                $portSuffix = in_array($port, [80, 443], true) || $port === null ? '' : ":{$port}";
                $domainUrl = $domain ? request()->getScheme().'://'.$domain.$portSuffix : null;

                $subscription = $item->latestSubscription;
                $statusVariant = match (true) {
                    $item->is_demo => 'primary',
                    $subscription === null => 'slate',
                    $subscription->status === 'active' => 'success',
                    $subscription->status === 'trialing' => 'info',
                    $subscription->status === 'past_due' => 'error',
                    $subscription->status === 'cancelled' => 'slate',
                    default => 'slate',
                };
                $statusLabel = $item->is_demo
                    ? 'Demo'
                    : ($statuses[$subscription?->status] ?? 'Sin suscripción');
            @endphp
            <tr class="hover:bg-slate-50 transition-colors duration-150">

                <x-data-table.cell column="business_name" :visible="$visibleColumns">
                    <span class="font-medium text-slate-900">{{ $item->business_name ?? '—' }}</span>
                </x-data-table.cell>

                <x-data-table.cell column="domain" :visible="$visibleColumns">
                    @if($domainUrl)
                        <a href="{{ $domainUrl }}" target="_blank" rel="noopener"
                            class="text-xs text-zertix-secondary hover:text-zertix-primary hover:underline transition-colors">
                            {{ $domain }}
                        </a>
                    @else
                        <span class="text-xs text-slate-400">—</span>
                    @endif
                </x-data-table.cell>

                <x-data-table.cell column="plan" :visible="$visibleColumns">
                    <span class="text-slate-600">{{ $item->plan?->name ?? '—' }}</span>
                </x-data-table.cell>

                <x-data-table.cell column="status" :visible="$visibleColumns">
                    <x-ui.badge :variant="$statusVariant" size="sm">{{ $statusLabel }}</x-ui.badge>
                </x-data-table.cell>

                <x-data-table.cell column="created_at" :visible="$visibleColumns">
                    <span class="text-xs text-slate-400">{{ $item->created_at?->format('d/m/Y') }}</span>
                </x-data-table.cell>

                <td class="px-4 py-3.5 text-right">
                    <span class="text-slate-300 text-sm">—</span>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="{{ count($visibleColumns) + 1 }}" class="px-6 py-16">
                    <x-ui.empty-state variant="simple" icon="heroicon-o-building-office-2" title="No hay tenants registrados"
                        description="Intenta ajustar los filtros de búsqueda." />
                </td>
            </tr>
        @endforelse

    </x-data-table.base-table>

</div>
