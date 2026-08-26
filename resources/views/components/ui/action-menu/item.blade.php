{{--
    resources/views/components/ui/action-menu/item.blade.php
    ------------------------------------------------------------
    Ítem individual de x-ui.action-menu. Renderiza <button> o <a> según
    si recibe href, igual que x-ui.button.

    PROPS:
      icon    — heroicon opcional a la izquierda
      variant — 'default' | 'danger'
      href    — si se pasa, renderiza <a> en vez de <button>
--}}

@props([
    'icon'    => null,
    'variant' => 'default',
    'href'    => null,
])

@php
    $tag = $href ? 'a' : 'button';
    $colorClasses = $variant === 'danger'
        ? 'text-red-600 hover:bg-red-50'
        : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900';
@endphp

<{{ $tag }}
    @if($href) href="{{ $href }}" @else type="button" @endif
    {{ $attributes->merge([
        'class' => "w-full flex items-center gap-2.5 px-4 py-2.5 text-sm font-medium text-left transition-colors duration-150 {$colorClasses}",
    ]) }}
>
    @if($icon)
        <x-dynamic-component :component="$icon" class="w-4 h-4 flex-shrink-0" />
    @endif
    <span>{{ $slot }}</span>
</{{ $tag }}>
