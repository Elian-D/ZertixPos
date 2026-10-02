---
name: filament-show
description: Usar este skill cuando el usuario pida crear o rehacer una vista de detalle ("show") de un registro — frases como "hazme el show de X", "la vista de ver X", "el detalle de X", "quiero ver X en una página", "reemplaza el modal/iframe de X por una vista real", "vista show estilo Filament", o cualquier pantalla que muestre un registro existente en solo lectura con sus acciones. Preferir siempre este skill sobre armar un detalle a mano con divs sueltos, un modal gigante o un iframe como contenido principal, aunque el usuario no mencione "Filament" ni "infolist". NO se activa para formularios de crear/editar (eso es /filament-form) ni para listados/tablas (motor DataTable de ARCHITECTURE.md).
---

# Vista de detalle estilo Filament (Infolist, sin Filament)

Hermano de `/filament-form`: mismo motivo (ZertixPOS no instala Filament, pero se quiere su lenguaje visual), mismo alcance (solo la capa de vista). Replica el **Infolist** de Filament: el registro se lee en secciones con tarjeta, cada dato es un par etiqueta/valor en una grilla, los estados son badges, las líneas de detalle se repiten como filas-tarjeta, y las acciones viven arriba en el encabezado. Nada de modales gigantes ni iframes como contenido principal — un iframe (preview de impresión) es, como mucho, un complemento lateral.

**No genera controlador, rutas ni permisos** — si el módulo no tiene ruta `show`, se agrega primero siguiendo `ARCHITECTURE.md` (`show()` en el controlador, `permission:<recurso>.view`, eager loading en un solo `load([...])`).

## Anatomía de la página (de arriba a abajo)

1. **Breadcrumbs** — `x-ui.breadcrumbs` (`Módulo > Ver`).
2. **Encabezado** — `x-ui.page-header` con título "Ver {Recurso}" (o el identificador, ej. `DEV-000012`) y en `$actions` los botones de acción del registro:
   - **Volver** (al listado) siempre visible en `$actions`, primero a la izquierda: `variant="secondary" appearance="outline" iconLeft="heroicon-s-arrow-left"`. Nunca dentro del menú "···".
   - Acciones de estado con color semántico: `success` (Aprobar), `info` (Enviar), `error` (Anular/Rechazar), `primary` (Editar, siempre la última a la derecha).
   - Descargas/impresión en `appearance="outline" variant="secondary"` (PDF, Imprimir), a la izquierda del resto.
   - Más de 4 acciones → las menos usadas van a `$secondary` (menú "···"). Si no hay acciones secundarias reales, **no se declara el slot `$secondary`** — un menú "···" con solo "Volver" no aporta nada.
   - Cada acción respeta su permiso (`@can`) y el estado del registro (no mostrar "Anular" en algo anulado). Acciones destructivas pasan por `x-ui.confirm-deletion-modal`.
3. **Avisos de estado** (opcional) — una franja sobre las secciones cuando el registro está en un estado que cambia cómo se lee (anulado, vencido): quién/cuándo/por qué.
4. **Secciones** (o **pestañas**, ver abajo).

## La sección: tarjeta con encabezado + grilla de entradas

Patrón único, igual para todas las vistas — no reinventarlo por pantalla:

```blade
<section class="bg-white border border-gray-100 rounded-2xl shadow-sm">
    <div class="flex items-center gap-2 px-5 sm:px-6 py-4 border-b border-gray-100">
        <x-heroicon-o-{icono} class="w-5 h-5 text-slate-400" />
        <h3 class="text-sm font-semibold text-gray-900">{Título de la sección}</h3>
        {{-- opcional: descripción corta bajo el título, text-xs text-slate-500 --}}
    </div>
    <dl class="p-5 sm:p-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-{2|3|4} gap-x-6 gap-y-5">
        {{-- entradas --}}
    </dl>
</section>
```

Diferencias a propósito con `/filament-form`: título en **tipo oración** (no mayúsculas), ícono **outline** gris (no sólido de color), encabezado separado del contenido por el borde — es lo que da la lectura "limpia" del Infolist.

**Entrada (etiqueta/valor):**

```blade
<div>
    <dt class="text-sm font-medium text-gray-900">{Etiqueta}</dt>
    <dd class="mt-1 text-sm text-gray-600">{valor}</dd>
</div>
```

Reglas de la entrada:
- **Vacío** → `—` en gris, nunca un hueco ni "N/A" mezclado con "Sin datos".
- **Dato principal** de la sección (nombre, total) → `font-semibold text-gray-900`.
- **Estados/categorías/tipos** → `x-ui.badge size="sm"` con la variante del accessor del modelo (`status_variant`/`status_label`), nunca texto plano coloreado a mano.
- **Montos** → moneda de `config('regional.currency_symbol')`, `number_format(…, 2)`, `font-mono` solo si hay columnas de números alineados. Montos negativos/descuentos en `text-amber-600`; el total final más grande y en `text-zertix-primary-700`.
- **Fechas** → `d/m/Y` (y `h:i A` si importa la hora); vencida → `text-state-error`.
- **Referencias a otro registro** (venta origen, cliente) → enlace al show de ese registro si existe y el usuario puede verlo.
- **Entrada ancha** (notas, dirección larga) → `sm:col-span-full`.

**Columnas de la grilla:** 4 para datos cortos (identidad, contacto), 3 para mezcla, 2 para textos largos. Siempre 1 en móvil.

**Ícono por tema real de la sección:** `identification`/`user` (datos del registro o persona), `phone` (contacto), `credit-card`/`banknotes` (crédito, pagos), `list-bullet`/`queue-list` (líneas), `calculator` (totales), `document-text` (encabezado de documento), `chat-bubble-left-ellipsis` (notas), `lock-closed` (datos restringidos), `arrow-uturn-left` (devoluciones), `clock` (historial).

## Líneas de detalle: entrada repetible (RepeatableEntry)

Las líneas de un documento (productos de una venta/cotización/devolución) **no** van en una `<table>` apretada: cada línea es una fila-tarjeta con sus propias etiquetas, como el RepeatableEntry de Filament. Escala bien en móvil (las columnas se apilan) y se lee igual que el resto de la vista:

```blade
<div class="p-5 sm:p-6 space-y-3">
    @foreach($items as $item)
        <div class="rounded-xl border border-gray-100 shadow-sm px-4 py-3 grid grid-cols-2 sm:grid-cols-6 gap-4">
            <div class="col-span-2">
                <dt class="text-sm font-medium text-gray-900">Descripción</dt>
                <dd class="mt-1 text-sm font-semibold text-gray-900">{{ $item->product->name }}</dd>
            </div>
            {{-- Cant. (badge gris), P. unitario, Dto., Impuestos (badge), Subtotal (primary, derecha) --}}
        </div>
    @endforeach
</div>
```

- Cantidad → `x-ui.badge variant="slate" :dot="false"`; impuesto → badge `info` ("ITBIS 18%", "Sin impuesto").
- Subtotal de la línea → `font-semibold text-zertix-primary-700`.
- Sin líneas → `x-ui.empty-state variant="simple"`.

## Totales

Sección propia al final de las líneas, grilla de 4: Subtotal · Descuento (ámbar) · Impuestos · **Total** (más grande, `text-lg font-bold text-zertix-primary-700`). Los totales se leen de los accessors del modelo (`grand_total`, etc.), nunca recalculados en la vista.

## Secciones colapsables y restringidas

- **Colapsable** (términos, notas largas, auditoría): encabezado clickeable con chevron, Alpine local `x-data="{ open: false }"`, cerrada por defecto si no es lo primero que se busca.
- **Restringida** (costos, márgenes): envuelta en `@can`, con `lock-closed` y descripción "Visible solo para …". Si el usuario no tiene permiso, la sección no se renderiza — no se muestra vacía.

## Pestañas (registros con relaciones propias)

Cuando el registro es el centro de varios listados (un cliente con sus cotizaciones, facturas, cobros): una tarjeta con barra de pestañas arriba (ícono + texto, activa con fondo `bg-zertix-primary-50 text-zertix-primary-700`) y el contenido de cada pestaña debajo. La primera pestaña es "Resumen" con las secciones del registro; las demás son listados cortos del mismo formato fila-tarjeta, con enlace al show de cada elemento. Alpine local para cambiar de pestaña; solo si un listado es pesado, se carga con Livewire `lazy`.

## Columna lateral (opcional)

Solo cuando hay algo que acompaña la lectura sin ser el contenido: vista previa del documento impreso (`iframe` a la ruta de impresión en modo `preview`), resumen de estado, línea de tiempo. Layout `grid-cols-1 lg:grid-cols-3 gap-6`, contenido en `lg:col-span-2`, lateral `lg:sticky lg:top-6`. En móvil va debajo. Referencia real: `resources/views/sales/returns/show.blade.php`.

## Cómo se arma

- **Vista:** `resources/views/<módulo>/show.blade.php` dentro de `x-app-layout`, contenedor `p-4 md:p-6 flex flex-col gap-6`.
- **Datos:** todo lo que la vista lee se carga en el `show()` con un solo `load([...])` — la vista nunca dispara consultas (ni `->count()`/`->exists()` por línea: eso es N+1).
- **Lógica de presentación** (labels, variantes de badge, montos derivados) vive en accessors del modelo, no en `@php` largos en la vista.
- **Acciones:** ruta real con su permiso o método Livewire con `abort_unless(auth()->user()->can(...), 403)` (ver CLAUDE.md). Feedback con toast.
- Componentes reales siempre: `x-ui.page-header`, `x-ui.breadcrumbs`, `x-ui.button`, `x-ui.badge`, `x-ui.empty-state`, `x-ui.confirm-deletion-modal`, `x-ui.action-menu` — nada de `<button>`/badges con Tailwind suelto (`docs/ui/*.md`).
- **Usar los componentes `x-ui.infolist.*`** (`section`, `entry`, `repeatable`, `repeatable-item`, `tabs`, `tab`) — API completa en `docs/ui/infolist.md`, ejemplo real en `resources/views/clients/show.blade.php`. Los snippets de markup de arriba muestran lo que esos componentes renderizan; no copiar ese markup a mano en una vista nueva.

## Checklist antes de dar la vista por terminada

- [ ] ¿Cada sección tiene ícono outline por tema real y título en tipo oración?
- [ ] ¿Toda entrada vacía muestra `—` y todo estado es un `x-ui.badge`?
- [ ] ¿Las líneas usan filas-tarjeta (RepeatableEntry), no una tabla apretada?
- [ ] ¿Las acciones del encabezado respetan permiso y estado del registro, y las destructivas piden confirmación?
- [ ] ¿La vista no dispara consultas propias (todo viene del `load()` del controlador)?
- [ ] ¿Se ve bien en móvil (grillas a 1 columna, filas-tarjeta apiladas, lateral debajo)?
- [ ] ¿Se probó el render real (server-side, buscando strings clave) antes de darla por terminada?
