{{-- x-pdf.section — título de sección con línea + su contenido (tabla, texto...). --}}
@props(['title'])

<div class="section-title">{{ $title }}</div>
{{ $slot }}
