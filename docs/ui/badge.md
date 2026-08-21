# Componente Badge (`x-ui.badge`)

Adaptación a ZertixPOS del `x-ui.badge` de Orvian (Fase 7, REQ-7.2 de `docs/features/v1.2.0.md`). El componente centraliza la representación visual de etiquetas de estado. Soporta variantes de color semánticas, un indicador opcional (punto **o ícono**), dos tamaños y colores hexadecimales personalizados para casos dinámicos.

---

## Tabla de Contenido

- [Estructura de Archivos](#estructura-de-archivos)
- [API del Componente](#api-del-componente)
- [Variantes de Color](#variantes-de-color)
- [Colores Hexadecimales Personalizados](#colores-hexadecimales-personalizados)
- [Tamaños](#tamaños)
- [Indicador: Punto o Ícono](#indicador-punto-o-ícono)
- [Ejemplos de Uso](#ejemplos-de-uso)
- [Notas Adicionales](#notas-adicionales)

---

## Estructura de Archivos

```plaintext
app/
└── View/
    └── Components/
        └── Ui/
            └── Badge.php                     # Lógica y generación de clases CSS
resources/
└── views/
    └── components/
        └── ui/
            └── badge.blade.php               # Plantilla del componente
```

---

## API del Componente

| Prop | Tipo | Default | Opciones |
|---|---|---|---|
| `variant` | `string` | `info` | `primary`, `success`, `warning`, `error`, `info`, `slate` |
| `hex` | `string\|null` | `null` | Cualquier código hexadecimal válido (ej. `#FF5733`, `3B82F6`) |
| `dot` | `bool` | `true` | — |
| `icon` | `string\|null` | `null` | Heroicon a mostrar en vez del punto (ver [Indicador](#indicador-punto-o-ícono)) |
| `size` | `string` | `md` | `sm`, `md` |

> **IMPORTANTE:** Los props `variant` y `hex` son **mutuamente excluyentes**. Si se proporciona `hex`, se ignora `variant`. Usar `hex` cuando se necesiten colores arbitrarios no definidos en el sistema de diseño (ej. roles/categorías personalizadas).

> **Diferencia con el Badge original de Orvian:** ahí el ícono se lograba a mano, metiendo un `<x-heroicon-*>` dentro del slot con `:dot="false"` — funcionaba, pero cada call site repetía el mismo tamaño/color de ícono por su cuenta. En ZertixPOS se agrega el prop `icon` para que el componente resuelva tamaño y color del ícono igual que ya resuelve el color del punto, sin que cada vista lo calcule aparte. El slot con ícono manual sigue funcionando (ver nota abajo), `icon` es la forma recomendada nueva.

---

## Variantes de Color

Cada variante usa el token de color equivalente de ZertixPOS, aplicado en tres partes del badge: fondo semitransparente, texto y borde.

| Variante | Token | Uso recomendado |
|---|---|---|
| `primary` | `zertix-primary` | Destacado, novedad, acción requerida |
| `success` | `state-success` | Activo, aprobado, completado |
| `warning` | `state-warning` | Pendiente, en revisión, precaución |
| `error` | `state-error` | Inactivo, rechazado, bloqueado |
| `info` | `state-info` | Informativo, en proceso, neutral |
| `slate` | `slate-500` | Deshabilitado, archivado, sin clasificar |

Todas las variantes aplican un fondo con opacidad `/10` en modo claro y `/20` en modo oscuro, con el color sólido en texto y borde `/20`. Esto garantiza legibilidad en ambos modos sin necesidad de configuración adicional.

---

## Colores Hexadecimales Personalizados

El prop `hex` permite asignar **cualquier color hexadecimal** al badge, aplicando automáticamente la misma lógica visual del sistema de diseño (fondo semitransparente, texto sólido, borde con opacidad).

### Cómo funciona

Cuando se proporciona `hex`, el componente:

1. **Ignora** el prop `variant` (no se aplican clases de Tailwind de color)
2. **Genera estilos CSS inline** con las siguientes opacidades:
   - **Fondo:** `{hex}1a` (10% de opacidad)
   - **Texto:** `{hex}` (100% sólido)
   - **Borde:** `{hex}33` (20% de opacidad)
3. **Indicador:** si `dot="true"`, el círculo usa el color hexadecimal sólido; si se pasa `icon`, el ícono hereda el mismo color vía `color: {hex}` (mismo mecanismo que el texto)

### Formato aceptado

El prop `hex` acepta códigos hexadecimales con o sin el símbolo `#`:

- `#FF5733` ✅
- `FF5733` ✅
- `#3b82f6` ✅
- `3B82F6` ✅

### Ejemplo de uso

```html
<x-ui.badge hex="#9333EA">Coordinador Regional</x-ui.badge>

@foreach($customRoles as $role)
    <x-ui.badge hex="{{ $role->color }}">
        {{ $role->name }}
    </x-ui.badge>
@endforeach
```

### Advertencias

- ⚠️ **Validación:** el componente NO valida si el hexadecimal es válido. Asegúrate de validar los valores antes de pasarlos al componente.
- ⚠️ **Accesibilidad:** algunos colores hexadecimales pueden no cumplir con ratios de contraste WCAG AA/AAA. Considera validar el contraste si los usuarios finales eligen los colores.
- ⚠️ **Performance:** usar `hex` implica estilos inline. Para badges estáticos con colores predefinidos, preferir `variant` (aprovecha Tailwind CSS y purging).

---

## Tamaños

| Size | Padding | Gap | Font size |
|---|---|---|---|
| `sm` | `px-2.5 py-0.5` | `gap-1.5` | `text-[9px]` |
| `md` | `px-4 py-1.5` | `gap-2` | `text-xs` |

El tamaño `sm` es adecuado para espacios reducidos como celdas de tabla o líneas de texto. El tamaño `md` es el valor por defecto y el recomendado para uso general.

---

## Indicador: Punto o Ícono

El badge admite dos formas de indicador visual antes del texto, mutuamente excluyentes: el **punto** (heredado de Orvian) o un **ícono** (nuevo en ZertixPOS). Ninguno es obligatorio.

| Prop | Comportamiento |
|---|---|
| `dot="true"` *(default)* | Renderiza `<span class="w-2 h-2 rounded-full">` antes del texto, coloreado según `variant`/`hex` |
| `icon="heroicon-s-check-circle"` | Renderiza el heroicon indicado antes del texto, coloreado según `variant`/`hex`, con el tamaño ajustado al `size` del badge (`w-3 h-3` en `sm`, `w-3.5 h-3.5` en `md`) |
| `dot="false"` (sin `icon`) | No se muestra ningún indicador — solo el texto del slot |

Si se pasan `icon` y `dot="true"` a la vez, `icon` tiene prioridad (el punto no se renderiza) — evita tener que acordarse de poner `:dot="false"` cada vez que se usa `icon`.

**Antes** (Badge original de Orvian, sin prop `icon`): el ícono se lograba metiéndolo a mano dentro del slot:
```blade
<x-ui.badge variant="success" :dot="false">
    <x-heroicon-s-check-circle class="w-3.5 h-3.5" /> Activo
</x-ui.badge>
```
Esto sigue funcionando (el slot acepta cualquier markup), pero cada call site define su propio tamaño/color de ícono a mano.

**Ahora** (con el prop `icon`, recomendado):
```blade
<x-ui.badge variant="success" icon="heroicon-s-check-circle">Activo</x-ui.badge>
```
El componente resuelve tamaño y color del ícono de la misma forma que ya resuelve el color del punto — consistente en todo el sistema sin repetir clases en cada vista.

El punto (o el ícono) es útil para badges que representan estados en tiempo real (activo/inactivo, conectado/desconectado). Para badges de categoría o etiqueta estática, se recomienda usar `:dot="false"` sin `icon`.

---

## Ejemplos de Uso

### Con variantes del sistema

```html
<x-ui.badge variant="success">Activo</x-ui.badge>

<x-ui.badge variant="slate" :dot="false">Inactivo</x-ui.badge>

<x-ui.badge variant="warning" size="sm">Pendiente</x-ui.badge>

<x-ui.badge variant="error">Bloqueado</x-ui.badge>

<x-ui.badge variant="info" :dot="false">Secundaria</x-ui.badge>

<x-ui.badge variant="primary" size="sm">Nuevo</x-ui.badge>
```

### Con la variante de ícono

```html
<x-ui.badge variant="success" icon="heroicon-s-check-circle">Activo</x-ui.badge>

<x-ui.badge variant="error" icon="heroicon-s-x-circle">Rechazado</x-ui.badge>

<x-ui.badge variant="warning" icon="heroicon-s-clock" size="sm">En espera</x-ui.badge>

<x-ui.badge variant="info" icon="heroicon-s-arrow-path">Sincronizando</x-ui.badge>
```

### Con colores hexadecimales personalizados

```html
<x-ui.badge hex="{{ $user->role->color }}">
    {{ $user->role->name }}
</x-ui.badge>

<x-ui.badge hex="#F59E0B" :dot="false" size="sm">Premium</x-ui.badge>
<x-ui.badge hex="#8B5CF6" :dot="false" size="sm">Exclusivo</x-ui.badge>
<x-ui.badge hex="#10B981" :dot="false" size="sm">Disponible</x-ui.badge>

<x-ui.badge hex="{{ $server->is_online ? '#22C55E' : '#EF4444' }}" icon="heroicon-s-signal">
    {{ $server->is_online ? 'Online' : 'Offline' }}
</x-ui.badge>
```

### Uso dinámico con Livewire

```html
<x-ui.badge :variant="$record->status_variant">
    {{ $record->status_label }}
</x-ui.badge>

<x-ui.badge hex="{{ $record->role->color }}">
    {{ $record->role->display_name }}
</x-ui.badge>
```

> [!TIP]
> Para mapear estados de Eloquent a variantes del badge, define un accessor en el modelo o un método en el componente Livewire que devuelva el nombre de la variante como string:
> ```php
> public function getStatusVariantAttribute(): string
> {
>     return match($this->status) {
>         'active'   => 'success',
>         'pending'  => 'warning',
>         'rejected' => 'error',
>         'inactive' => 'slate',
>         default    => 'info',
>     };
> }
> ```

**En celdas de tabla:**
```html
<x-ui.badge variant="{{ $row->status_variant }}" size="sm">
    {{ $row->status_label }}
</x-ui.badge>
```

---

## Notas Adicionales

- El componente usa `$attributes->merge()` sobre el elemento raíz (`<div>`), lo que permite añadir clases adicionales o atributos HTML directamente desde el sitio de uso sin necesidad de modificar el componente.
- El texto del badge proviene del `$slot`, lo que permite pasar contenido dinámico o traducciones directamente: `<x-ui.badge>{{ __('status.active') }}</x-ui.badge>`.
- El badge no es interactivo por diseño — no incluye estados hover ni focus. Si se necesita un badge clickeable, envuélvelo en un `<button>` o `<a>` externo.
- Todos los tokens de color (`zertix-primary`, `state-success`, `state-warning`, `state-error`, `state-info`) ya están definidos en `tailwind.config.js` (Fase 7.1) para que las clases sean generadas correctamente y no sean eliminadas en el build de producción.
- **Híbrido por diseño:** el componente mantiene compatibilidad total con el sistema de diseño mediante `variant`, mientras ofrece flexibilidad ilimitada mediante `hex` para casos de uso dinámicos como roles personalizados o integraciones con APIs externas.
