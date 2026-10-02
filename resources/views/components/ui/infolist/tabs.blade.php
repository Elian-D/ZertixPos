{{--
    x-ui.infolist.tabs — tarjeta contenedora con barra de pestañas (registro con
    listados propios, ej. un cliente con sus cotizaciones y facturas). Cada panel
    es un x-ui.infolist.tab con el mismo `name`.

    PROPS:
      tabs    — [['name' => 'summary', 'label' => 'Resumen', 'icon' => 'heroicon-o-user-circle', 'count' => null], ...]
                `count` opcional: se muestra como badge junto a la etiqueta.
      default — pestaña inicial (default: la primera).

    La pestaña activa se refleja en el hash de la URL (#quotes), así un enlace o un
    recargo abre directo en esa pestaña. Alpine local, sin Livewire.
--}}
@props(['tabs' => [], 'default' => null])

@php
    $names = array_column($tabs, 'name');
    $default ??= $names[0] ?? null;
@endphp

<div {{ $attributes->class(['bg-white border border-gray-100 rounded-2xl shadow-sm']) }}
    x-data="{
        tab: @js($default),
        names: @js($names),
        init() {
            const fromHash = window.location.hash.replace('#', '');
            if (this.names.includes(fromHash)) this.tab = fromHash;
        },
        select(name) {
            this.tab = name;
            history.replaceState(null, '', '#' + name);
        },
    }">

    <div class="flex gap-1 overflow-x-auto px-3 pt-3 pb-2 border-b border-gray-100" role="tablist">
        @foreach($tabs as $t)
            <button type="button" role="tab"
                @click="select(@js($t['name']))"
                :aria-selected="tab === @js($t['name'])"
                :class="tab === @js($t['name'])
                    ? 'bg-zertix-primary-50 text-zertix-primary-700'
                    : 'text-slate-500 hover:text-slate-700 hover:bg-slate-50'"
                class="inline-flex items-center gap-2 whitespace-nowrap rounded-lg px-3 py-2 text-sm font-medium transition-colors">
                @if(! empty($t['icon']))
                    <x-dynamic-component :component="$t['icon']" class="w-4 h-4" />
                @endif
                {{ $t['label'] }}
                @if(isset($t['count']))
                    <span class="rounded-full bg-slate-100 px-1.5 py-0.5 text-[10px] font-bold text-slate-600">{{ $t['count'] }}</span>
                @endif
            </button>
        @endforeach
    </div>

    <div class="p-4 sm:p-5">
        {{ $slot }}
    </div>
</div>
