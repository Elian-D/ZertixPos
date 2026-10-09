# Componentes Infolist (`x-ui.infolist.*`)

Componentes para las vistas de detalle ("show") con el lenguaje visual del **Infolist de Filament**, sin instalar Filament (v1.4.0 Fase 3, REQ-3.1). El patrón completo — qué va en cada sección, íconos, acciones del encabezado, checklist — vive en el skill `/filament-show` (`.claude/skills/filament-show/SKILL.md`); este documento es la referencia de la API.

Primera vista construida con ellos: `resources/views/clients/show.blade.php`.

---

## Estructura de archivos

```plaintext
resources/views/components/ui/infolist/
├── section.blade.php          # Tarjeta de sección (encabezado + grilla de entradas)
├── entry.blade.php            # Par etiqueta/valor
├── repeatable.blade.php       # Contenedor de filas-tarjeta (+ empty-state)
├── repeatable-item.blade.php  # Una fila-tarjeta
├── repeater.blade.php         # Líneas editables de un formulario (contraer/expandir todo + agregar)
├── repeater-item.blade.php    # Una línea editable (tarjeta gris contraíble)
├── tabs.blade.php             # Tarjeta contenedora con barra de pestañas
└── tab.blade.php              # Panel de una pestaña
```

Son componentes anónimos (sin clase PHP).

---

## `x-ui.infolist.section`

| Prop | Tipo | Default | Descripción |
|---|---|---|---|
| `title` | `string` | — | Título en tipo oración (obligatorio) |
| `icon` | `string\|null` | `null` | Heroicon **outline** por tema real (`heroicon-o-identification`) |
| `description` | `string\|null` | `null` | Texto corto bajo el título |
| `cols` | `0\|1\|2\|3\|4` | `4` | Columnas de la grilla `<dl>`. `0` = el slot se renderiza tal cual (listas, filas-tarjeta). No usar `null`: Blade lo trata como "no pasado" y aplica el default. `3` y `4` llegan a su ancho completo recién desde `xl` (1280px); en `sm`–`lg` son 2 columnas, para que los badges no se partan con la barra lateral abierta |
| `collapsible` | `bool` | `false` | Encabezado clickeable con chevron (Alpine `x-collapse`) |
| `collapsed` | `bool` | `false` | Arranca cerrada (solo con `collapsible`) |
| `flat` | `bool` | `false` | Sin sombra: para secciones dentro de otra tarjeta (pestañas) |

Slot opcional `headerActions`: botones a la derecha del título.

```blade
<x-ui.infolist.section title="Crédito" icon="heroicon-o-credit-card">
    <x-ui.infolist.entry label="Límite de crédito" :value="$money($client->credit_limit)" />
    <x-ui.infolist.entry label="Términos de pago" :value="$client->payment_terms.' días'" />
</x-ui.infolist.section>

<x-ui.infolist.section title="Términos y notas" icon="heroicon-o-chat-bubble-left-ellipsis" :cols="0" collapsible collapsed>
    <p class="text-sm text-gray-600">{{ $quote->notes }}</p>
</x-ui.infolist.section>
```

---

## `x-ui.infolist.entry`

| Prop | Tipo | Default | Descripción |
|---|---|---|---|
| `label` | `string` | — | Etiqueta (obligatoria) |
| `value` | `mixed` | `null` | Valor en texto plano. Si hay contenido en el slot, el slot gana |
| `strong` | `bool` | `false` | Dato principal: semibold oscuro |
| `full` | `bool` | `false` | Ocupa toda la fila (`sm:col-span-full`) |
| `href` | `string\|null` | `null` | El valor es un enlace (otro show, `tel:`, `mailto:`) |

**Vacío** (`value` null/`''` y slot vacío) → `—` gris. Así un dato faltante se ve igual en todo el sistema — no escribir "N/A" ni "Sin datos" a mano.

```blade
<x-ui.infolist.entry label="Nombre" :value="$client->name" strong />
<x-ui.infolist.entry label="Email" :value="$client->email" :href="$client->email ? 'mailto:'.$client->email : null" />
<x-ui.infolist.entry label="Estado">
    <x-ui.badge :variant="$quote->status_variant" size="sm" :dot="false">{{ $quote->status_label }}</x-ui.badge>
</x-ui.infolist.entry>
<x-ui.infolist.entry label="Dirección" :value="$client->address" full />
```

> Un slot con solo un `@if` que no se cumple cuenta como vacío (se hace `trim()`), así que `—` aparece solo — no hace falta un `@else`.

---

## `x-ui.infolist.repeatable` + `repeatable-item`

Filas-tarjeta (RepeatableEntry de Filament) para líneas de un documento o registros relacionados: cada fila repite sus propias etiquetas, en vez de una `<table>` apretada. En móvil las columnas se apilan de a 2.

`repeatable`: `empty` (bool), `emptyTitle`, `emptyDescription`, `emptyIcon` — con `empty` muestra `x-ui.empty-state` en lugar del slot.
`repeatable-item`: `cols` (`3|4|5|6|7`, default `6`). Dentro de una sección, esa sección va con `:cols="0"`.

```blade
<x-ui.infolist.repeatable :empty="$quotes->isEmpty()" emptyTitle="Sin cotizaciones">
    @foreach($quotes as $quote)
        <x-ui.infolist.repeatable-item :cols="5">
            <x-ui.infolist.entry label="Cotización" :value="'#'.$quote->id" strong :href="route('clients.quotes.show', $quote)" />
            <x-ui.infolist.entry label="Total" :value="$money($quote->grand_total)" />
        </x-ui.infolist.repeatable-item>
    @endforeach
</x-ui.infolist.repeatable>
```

---

## `x-ui.infolist.repeater` + `repeater-item`

Líneas **editables** de un formulario, como el Repeater de Filament. Para ver líneas en solo lectura en un show, usa `repeatable`, no este. Primer uso: el ajuste de inventario (`resources/views/inventory/movements/create.blade.php`). Es la base para las líneas de compras, mermas y transferencias.

**Qué hace:**
- **Tarjetas:** cada línea es una tarjeta **gris** (`bg-gray-50`), que contrasta con el fondo blanco de la sección. Arranca abierta y se contrae o expande con un clic en su encabezado.
- **Encabezado:** lleva el **título o resumen** de la línea, el botón de quitar y el chevron. El resumen se ve también con la línea contraída.
- **Acordeón:** abrir una línea, o agregar una nueva, cierra las demás del mismo repeater, para dejar espacio. "Expandir todo" sí las abre todas. Las líneas que llegan al cargar la página (un `old()` tras un error de validación) se quedan abiertas, para que se vean sus errores.
- **Contraer todo / Expandir todo:** aparece solo con más de una línea. Solo afecta a las líneas de ese repeater, así que dos repeaters en la misma página no se pisan.
- **Agregar:** el botón va **siempre debajo de la última línea**, para agregar otra sin volver a subir.

`repeater`:

| Prop | Tipo | Default | Descripción |
|---|---|---|---|
| `addAction` | `string\|null` | `null` | Expresión Alpine del botón de agregar (`'addLine()'`). Sin ella no hay botón |
| `addLabel` | `string` | `'Agregar línea'` | Texto del botón |
| `countExpr` | `string\|null` | `null` | Cantidad de líneas como expresión Alpine (`'lines.length'`), cuando salen de un `x-for` |
| `count` | `int\|null` | `null` | Cantidad de líneas cuando se renderizan con un `@foreach` de Blade |

`repeater-item`:

| Prop / slot | Tipo | Default | Descripción |
|---|---|---|---|
| `title` | `string\|null` | `null` | Título fijo (`'Nueva línea'`) |
| slot `title` | — | — | Resumen dinámico en lugar del prop: `<span x-text="summary(line)"></span>` |
| `removeAction` | `string\|null` | `null` | Expresión Alpine del botón de quitar (`'removeLine(i)'`). Sin ella no hay botón |
| `removeDisabled` | `string\|null` | `null` | Expresión Alpine que lo deshabilita (`'lines.length === 1'`) |
| `collapsed` | `bool` | `false` | Arranca contraída |
| `compact` | `bool` | `false` | Una sola fila, sin contraer: el título a la izquierda y los campos a la derecha (ver abajo) |
| slot | — | — | Los campos de la línea |

**Variante `compact`:** es para listas largas donde cada línea lleva solo una o dos cantidades. Con cientos de productos, una tarjeta contraíble por producto haría la página eterna. Primer uso: la pantalla de conteo de la toma física (`resources/views/inventory/counts/count.blade.php`).
- Misma tarjeta gris, en una sola fila.
- Al ser una fila, no lleva encabezado, chevron ni la barra "Contraer todo / Expandir todo": no le pases `countExpr` al repeater.
- El título va en `<x-slot:title>` (nombre + SKU/código en dos líneas). Los campos van en el slot, alineados a la derecha.
- En móvil la fila se apila: título arriba, campos abajo.
- Admite `x-bind:class` para resaltar una línea (por ejemplo, la que se acaba de escanear).

```blade
<x-ui.infolist.repeater>
    <template x-for="item in filtered" :key="item.id">
        <x-ui.infolist.repeater-item compact>
            <x-slot:title><span x-text="item.name"></span></x-slot:title>
            <div class="w-28"><x-ui.forms.input name="" type="number" x-model="item.counted" /></div>
        </x-ui.infolist.repeater-item>
    </template>
</x-ui.infolist.repeater>
```

Dentro de un `<template x-for>`, el `repeater-item` es el único elemento raíz del template, como exige Alpine. Cada línea guarda su estado abierta/contraída de forma local, y las expresiones (`line`, `i`) vienen del scope del `x-for`. Los campos usan `x-bind:name="'lines[' + i + '][campo]'"` y `x-bind:id`. Los radios funcionan sin `for` porque `x-ui.forms.radio` envuelve su input.

```blade
<x-ui.infolist.repeater countExpr="lines.length" addAction="addLine()">
    <template x-for="(line, i) in lines" :key="i">
        <x-ui.infolist.repeater-item removeAction="removeLine(i)" removeDisabled="lines.length === 1">
            <x-slot:title>
                <span x-text="summary(line)"></span>
            </x-slot:title>

            {{-- campos de la línea --}}
        </x-ui.infolist.repeater-item>
    </template>
</x-ui.infolist.repeater>
```

---

## `x-ui.infolist.tabs` + `tab`

Tarjeta contenedora con barra de pestañas, para un registro que tiene listados propios (un cliente con sus cotizaciones y facturas). La primera pestaña suele ser "Resumen" con las secciones del registro (usar `flat` en ellas, ya están dentro de la tarjeta).

`tabs`: `tabs` (arreglo de `['name', 'label', 'icon', 'count' => opcional]`), `default` (pestaña inicial; default la primera).

Claves opcionales para usarlo en un formulario (v1.5.0, form de producto):
- **`show`:** expresión Alpine del scope padre que oculta la pestaña cuando es falsa (ej. `'!isService'`). Si la pestaña activa se oculta, vuelve a la primera.
- **`error`:** `true` pinta un punto rojo junto a la etiqueta. Se combina con un `default` calculado en la vista para que, con validación fallida, el form abra en la pestaña que tiene el error.
`tab`: `name` (igual al del arreglo).

La pestaña activa se refleja en el hash de la URL (`/app/clients/5#invoices`): un enlace o un recargo abre directo en esa pestaña. Alpine local, sin Livewire.

```blade
<x-ui.infolist.tabs :tabs="[
    ['name' => 'summary', 'label' => 'Resumen', 'icon' => 'heroicon-o-user-circle'],
    ['name' => 'invoices', 'label' => 'Facturas', 'icon' => 'heroicon-o-receipt-percent', 'count' => $invoicesCount],
]">
    <x-ui.infolist.tab name="summary">
        <x-ui.infolist.section title="Datos del cliente" icon="heroicon-o-identification" flat>…</x-ui.infolist.section>
    </x-ui.infolist.tab>
    <x-ui.infolist.tab name="invoices">…</x-ui.infolist.tab>
</x-ui.infolist.tabs>
```

Una pestaña que el usuario no puede ver no se agrega al arreglo ni se renderiza su panel — y el controlador tampoco la consulta.

---

## Notas

- Estados → accessors del modelo (`status_variant`/`status_label`, ej. `Quote`, `Invoice`), nunca un `match` en la vista.
- La vista no dispara consultas: todo llega del `show()` del controlador (`load()`, `limit()`, `count()`).
- Clases de grilla dinámicas están escritas completas en un mapa dentro de cada componente (`grid-cols-1 sm:grid-cols-2 …`) para que el JIT de Tailwind las genere — no construirlas por concatenación.
