<div {{ $attributes->merge(['class' => $getBadgeClasses(), 'style' => $getCustomStyles()]) }}>
    @if($icon)
        <x-dynamic-component :component="$icon" class="{{ $getIconClasses() }}" style="{{ $getIconStyles() }}" />
    @elseif($dot)
        <span class="{{ $getDotClasses() }}" style="{{ $getDotStyles() }}"></span>
    @endif

    <span>{{ $slot }}</span>
</div>
