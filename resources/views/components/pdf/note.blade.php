{{--
    x-pdf.note — caja de nota o aviso.

    PROPS:
      label   — título corto en mayúsculas (opcional).
      variant — warn (notas, ámbar) | alert (pendiente/atención, naranja)
                | danger (anulado) | info (neutro). Default: warn.
--}}
@props(['label' => null, 'variant' => 'warn'])

<div class="note note-{{ $variant }}">
    @if($label)
        <span class="label" style="color: inherit;">{{ $label }}</span>
    @endif
    {{ $slot }}
</div>
