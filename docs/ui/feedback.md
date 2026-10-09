# Avisos y confirmaciones (`x-ui.alert`, `x-ui.confirm-modal`)

Componentes que se agregaron en v1.5.0 con la toma física, para no escribir a mano un `<div>` de aviso ni un modal de confirmación en cada vista.

| Componente | Para qué | No usar para |
|---|---|---|
| `x-ui.toasts` | Resultado de una acción, que desaparece solo (`session('success')`, `notify()`) | Algo que tiene que seguir visible |
| `x-ui.alert` | Un aviso que **se queda** en la página: documento anulado o cancelado, el supuesto de un conteo, una advertencia antes de actuar | Confirmar una acción |
| `x-ui.confirm-modal` | Confirmar una acción que **no es eliminar**: aplicar, cancelar un borrador, aprobar, enviar | Eliminar o anular un registro: para eso sigue `x-ui.confirm-deletion-modal` |

---

## `x-ui.alert`

| Prop | Tipo | Default | Descripción |
|---|---|---|---|
| `variant` | `string` | `info` | `neutral` (gris), `info`, `success`, `warning` o `error` |

**Cuándo usar `neutral`:** para avisos informativos que no son un error ni una advertencia, como un borrador o un consejo de uso. Si el aviso va en amarillo o azul, el usuario cree que está haciendo algo mal. Ejemplos: "Cuenta cada producto antes de venderlo" y "Borrador: el stock cambia al aplicar". Los textos van cortos, en una o dos líneas; si hace falta un desglose, se pone una lista corta (`<dl>`), no un párrafo.
| `icon` | `string\|false\|null` | uno por variante | Heroicon del aviso; `false` lo quita |
| `title` | `string\|null` | `null` | Texto en negrita antes del mensaje |

El mensaje va en el slot.

```blade
<x-ui.alert variant="warning" title="Cuenta justo después de crear la toma.">
    La diferencia se calcula contra la existencia que tenía el sistema al crearla.
</x-ui.alert>

<x-ui.alert variant="error">
    Cancelada por <strong>{{ $count->canceler->name }}</strong> el {{ $count->canceled_at->format('d/m/Y') }}.
</x-ui.alert>
```

---

## `x-ui.confirm-modal`

Se abre con `$dispatch('open-modal', '<name>')` y envía un `<form method="POST">` a `route`.

| Prop | Tipo | Default | Descripción |
|---|---|---|---|
| `name` | `string` | — | Nombre del modal (obligatorio) |
| `title` | `string` | — | La pregunta (`'¿Aplicar toma física?'`) |
| `description` | `string\|null` | `null` | Qué pasa al confirmar, en texto plano |
| `route` | `string` | — | Acción del formulario |
| `method` | `string` | `POST` | `POST`, `PUT`, `PATCH` o `DELETE` |
| `confirmLabel` | `string` | `Confirmar` | Texto del botón |
| `variant` | `string` | `primary` | `primary`, `warning` o `error`: color del ícono y del botón |
| `icon` | `string\|null` | uno por variante | Heroicon del encabezado |
| `show` | `bool` | `false` | Abre el modal al cargar la página. Sirve cuando el slot trae un campo, para que su error de validación se vea (`:show="$errors->has('void_reason')"`) |

El slot es opcional: un detalle extra bajo la descripción, normalmente un `x-ui.alert` con lo que va a cambiar.

```blade
<x-ui.button variant="primary" x-data x-on:click="$dispatch('open-modal', 'apply-count')">Aplicar</x-ui.button>

<x-ui.confirm-modal name="apply-count" title="¿Aplicar toma física?" :route="route('inventory.counts.apply', $count)"
    confirmLabel="Aplicar toma"
    description="Cada producto contado con diferencia ajusta el stock actual del almacén.">
    <x-ui.alert variant="warning">2 con faltante · diferencia neta -RD$235.00.</x-ui.alert>
</x-ui.confirm-modal>
```
