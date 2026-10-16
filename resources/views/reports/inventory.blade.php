{{-- Reporte de Inventario (v1.5.0 REQ-6.3): valor de la mercancía a costo y a precio, por
     almacén y por categoría; bajo el mínimo y sin movimiento. Recibe $warehouses, $warehouseId,
     $value, $byWarehouse, $byCategory, $lowStock, $lowStockCount, $trackedCount, $deadStock y
     $deadDays (App\Http\Controllers\Reports\InventoryReportController). --}}
@php
    $currency = config('regional.currency_symbol');
    $money = fn ($v) => ($v < 0 ? '−' : '').$currency.number_format(abs((float) $v), 2);
    $qty = fn ($q) => rtrim(rtrim(number_format((float) $q, 2), '0'), '.');
    $margin = $value['price'] - $value['cost'];
    $maxWarehouse = max((float) $byWarehouse->max('cost'), 0.01);
@endphp

<x-app-layout title="Reporte de Inventario">
    <div class="p-4 md:p-6 flex flex-col gap-6">
        <x-ui.page-header title="Reporte de Inventario" description="Foto de hoy: existencias positivas de productos (los servicios no tienen stock)." />

        <x-ui.report.filters :cols="2">
            <x-ui.forms.select label="Almacén" name="warehouse" placeholder="Todos los almacenes">
                @foreach($warehouses as $warehouse)
                    <option value="{{ $warehouse->id }}" @selected($warehouseId === $warehouse->id)>{{ $warehouse->name }}</option>
                @endforeach
            </x-ui.forms.select>
        </x-ui.report.filters>

        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
            <x-ui.stat label="Valor a costo" :value="$money($value['cost'])" :hint="$value['skus'].' productos · '.$qty($value['units']).' unidades'" />
            <x-ui.stat label="Valor a precio de venta" :value="$money($value['price'])" hint="Lo que vale si se vende todo" />
            <x-ui.stat label="Margen potencial" :value="$money($margin)" :variant="$margin < 0 ? 'error' : 'success'"
                :hint="$value['price'] > 0 ? number_format($margin / $value['price'] * 100, 1).'% del valor a precio' : null" />
            <x-ui.stat label="Bajo el mínimo" :value="$lowStockCount.' / '.$trackedCount" :variant="$lowStockCount ? 'error' : 'default'"
                hint="Existencias con mínimo definido" />
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
            <x-ui.infolist.section title="Valor por almacén" icon="heroicon-o-building-office-2" :cols="0">
                <x-ui.report.rows empty="Sin existencias.">
                    @foreach($byWarehouse as $row)
                        <li class="py-2.5">
                            <div class="flex items-center gap-3">
                                <span class="flex-1 min-w-0">
                                    <a href="{{ route('reports.inventory', ['warehouse' => $row->warehouse_id]) }}" class="block text-sm text-gray-800 hover:underline truncate">{{ $row->name }}</a>
                                    <span class="block text-xs text-slate-500">{{ $row->skus }} productos · {{ $qty($row->units) }} u. · a precio {{ $money($row->price) }}</span>
                                </span>
                                <span class="text-sm font-semibold tabular-nums text-gray-900">{{ $money($row->cost) }}</span>
                            </div>
                            <x-ui.report.progress class="mt-1.5" :value="(float) $row->cost / $maxWarehouse * 100" />
                        </li>
                    @endforeach
                </x-ui.report.rows>
            </x-ui.infolist.section>

            <x-ui.infolist.section title="Valor por categoría" icon="heroicon-o-squares-2x2" :cols="0" description="A costo">
                <x-ui.report.rows empty="Sin existencias.">
                    @foreach($byCategory as $row)
                        <x-ui.report.row :label="$row->name" :hint="'('.$row->skus.')'" :value="$money($row->cost)" />
                    @endforeach
                </x-ui.report.rows>
            </x-ui.infolist.section>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
            <x-ui.infolist.section title="Bajo el mínimo" icon="heroicon-o-exclamation-triangle" :cols="0"
                :description="$lowStockCount > $lowStock->count() ? 'Los '.$lowStock->count().' más críticos de '.$lowStockCount : null">
                @if(module_enabled('purchases') && auth()->user()->can('purchase_orders.create') && $lowStock->isNotEmpty())
                    <x-slot:headerActions>
                        <x-ui.button href="{{ route('purchases.orders.create') }}" size="sm" variant="secondary" appearance="outline" iconLeft="heroicon-s-plus">
                            Orden de compra
                        </x-ui.button>
                    </x-slot:headerActions>
                @endif
                <x-ui.report.rows empty="Todo está sobre el mínimo.">
                    @foreach($lowStock as $stock)
                        <x-ui.report.row :label="$stock->product->name ?? '—'" :sub="$warehouseId ? null : ($stock->warehouse->name ?? null)"
                            :hint="$qty($stock->quantity).' / '.$qty($stock->min_stock)">
                            <x-slot:badge>
                                <x-ui.badge :variant="(float) $stock->quantity <= 0 ? 'error' : 'warning'" size="sm" :dot="false">
                                    {{ (float) $stock->quantity <= 0 ? 'Agotado' : 'Bajo' }}
                                </x-ui.badge>
                            </x-slot:badge>
                        </x-ui.report.row>
                    @endforeach
                </x-ui.report.rows>
            </x-ui.infolist.section>

            <x-ui.infolist.section title="Sin movimiento" icon="heroicon-o-clock" :cols="0"
                :description="'Nada entró ni salió en '.$deadDays.' días: posible stock muerto'">
                <x-ui.report.rows :empty="'Todo se movió en los últimos '.$deadDays.' días.'">
                    @foreach($deadStock as $row)
                        <x-ui.report.row :label="$row->name" :hint="$qty($row->units).' u.'" :value="$money($row->cost)" />
                    @endforeach
                </x-ui.report.rows>
            </x-ui.infolist.section>
        </div>
    </div>
</x-app-layout>
