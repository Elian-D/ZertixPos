# Reportes e inicio — `x-ui.report.*`

Desde v1.5.0 Fase 6, todo reporte y el inicio usan la misma forma, en el estilo de un dashboard de Filament. No hay librería de gráficas: las barras son Tailwind.

```
page-header (título + período)
x-ui.report.filters        ← Desde / Hasta / almacén… (se envía solo al cambiar)
fila de x-ui.stat           ← 3 o 4 totales
grid de x-ui.infolist.section :cols="0"
    x-ui.report.bar-chart  ← o una lista:
    x-ui.report.rows > x-ui.report.row
```

Las cifras salen siempre de `App\Services\Reports\BusinessMetrics`, nunca de una consulta en la vista. Así el inicio y el reporte no dicen números distintos. El período se lee con `App\Support\Reports\ReportPeriod::fromRequest($request)` (`?from=&to=`; por defecto, el mes en curso).

## x-ui.report.filters

Tarjeta con un `<form method="GET">` que se envía solo al cambiar un campo.

| Prop | Default | |
|---|---|---|
| `action` | URL actual | A dónde se envía. |
| `cols` | 3 | Columnas en desktop (2, 3 o 4). |

```blade
<x-ui.report.filters :cols="2">
    <x-ui.forms.input type="date" label="Desde" name="from" :value="$period->from->toDateString()" />
    <x-ui.forms.input type="date" label="Hasta" name="to" :value="$period->to->toDateString()" />
</x-ui.report.filters>
```

## x-ui.stat

La misma tarjeta de total de `docs/ui/infolist.md`. Con `href`, la tarjeta entera es un enlace (en el inicio, cada total lleva a su reporte). Un monto de más de 13 caracteres baja de `text-2xl` a `text-xl` en vez de cortarse.

## x-ui.report.rows y x-ui.report.row

Lista de filas separadas por una línea: etiqueta a la izquierda y valor a la derecha.

| Prop de `row` | |
|---|---|
| `label` | Texto principal. |
| `sub` | Línea gris debajo. |
| `value` | Valor ya formateado (el slot lo reemplaza). |
| `hint` | Texto gris antes del valor: `(2)`, `×3`, `6 / 10`. |
| `href` | La etiqueta es un enlace. |
| `strong` | Valor en negrita (default `true`). |
| `variant` | `default`, `error`, `success` o `warning`: color del valor. |
| slot `badge` | Badge entre la etiqueta y el valor (Agotado, Vencida). |

`rows` acepta `empty="Texto"`: lo muestra en gris si no hay filas.

```blade
<x-ui.report.rows empty="Todo está sobre el mínimo.">
    @foreach($lowStock as $stock)
        <x-ui.report.row :label="$stock->product->name" :sub="$stock->warehouse->name" hint="0 / 6">
            <x-slot:badge><x-ui.badge variant="error" size="sm" :dot="false">Agotado</x-ui.badge></x-slot:badge>
        </x-ui.report.row>
    @endforeach
</x-ui.report.rows>
```

Para una fila con barra de proporción (métodos de pago, valor por almacén, uso de secuencias NCF), escribe un `<li class="py-2.5">` propio dentro de `rows` con `x-ui.report.progress` debajo.

## x-ui.report.bar-chart

Barras verticales. Al pasar el mouse muestran su `tooltip`.

| Prop | Default | |
|---|---|---|
| `bars` | `[]` | `[['label' => '15', 'value' => 1200.5, 'tooltip' => '15 oct · RD$1,200.50']]` |
| `height` | 140 | Alto en px. |
| `labelEvery` | automático | Etiqueta cada N barras (~15 etiquetas). |
| `highlight` | — | Índice de la barra resaltada (ej. hoy). |
| `empty` | "Sin datos en el período." | Si todo está en 0. |

Arma los días con `BusinessMetrics::salesByDay()`, que ya trae los días sin ventas en 0.

## x-ui.report.progress

Barra horizontal de proporción. `value` de 0 a 100 y `variant`: `primary`, `success`, `warning` o `error`.

## Reglas

- **Un bloque, su permiso y su módulo.** Si el usuario no tiene el permiso o el módulo está apagado, el bloque no se calcula ni se muestra (ver la tabla de REQ-6.5 en `docs/features/v1.5.0.md`).
- **Montos de dinero** con `config('regional.currency_symbol')` y 2 decimales; los negativos con `−`.
- **Sin "Dashboard"** en títulos ni menús: el inicio es "Inicio" y los demás son reportes.
- **Nada de librerías de gráficas desde un CDN.** Si un reporte necesita algo que las barras no cubren, se decide antes.
