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

## `x-ui.infolist.tabs` + `tab`

Tarjeta contenedora con barra de pestañas, para un registro que tiene listados propios (un cliente con sus cotizaciones y facturas). La primera pestaña suele ser "Resumen" con las secciones del registro (usar `flat` en ellas, ya están dentro de la tarjeta).

`tabs`: `tabs` (arreglo de `['name', 'label', 'icon', 'count' => opcional]`), `default` (pestaña inicial; default la primera).
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
