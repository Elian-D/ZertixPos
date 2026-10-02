{{--
    x-ui.infolist.tab — panel de una pestaña de x-ui.infolist.tabs.

    PROPS:
      name — debe coincidir con un `name` del arreglo `tabs` del contenedor.
--}}
@props(['name'])

<div x-show="tab === @js($name)" x-cloak role="tabpanel" {{ $attributes->class(['space-y-5']) }}>
    {{ $slot }}
</div>
