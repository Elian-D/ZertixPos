# Componente Button (`x-ui.button`)

Adaptación a ZertixPOS del `x-ui.button` de Orvian (Fase 7, REQ-7.3 de `docs/features/v1.2.0.md`). Es el elemento de acción central del sistema. Centraliza toda la lógica visual y es **polimórfico**: detecta automáticamente si debe renderizarse como `<button>` o como `<a>` según el contexto. Soporta variantes de color del sistema, colores hexadecimales con cálculo de contraste automático, un modo de ícono exclusivo altamente accesible, y — nuevo en ZertixPOS — un **guard de doble-submit integrado** para cualquier `<x-ui.button type="submit">`.

---

## Tabla de Contenido

- [Estructura de Archivos](#estructura-de-archivos)
- [API del Componente](#api-del-componente)
- [Variantes y Colores Hexadecimales](#variantes-y-colores-hexadecimales)
- [Tipos de Estilo](#tipos-de-estilo)
- [Polimorfismo (Tag Dinámico)](#polimorfismo-tag-dinámico)
- [Tamaños](#tamaños)
- [Uso de Iconos y Accesibilidad](#uso-de-iconos-y-accesibilidad)
- [Guard de Doble-Submit (nuevo en ZertixPOS)](#guard-de-doble-submit-nuevo-en-zertixpos)
- [Estados de Carga (Livewire)](#estados-de-carga-livewire)
- [Props Adicionales](#props-adicionales)
- [Ejemplos de Uso](#ejemplos-de-uso)
- [Notas Adicionales](#notas-adicionales)

---

## Estructura de Archivos

```plaintext
app/
└── View/
    └── Components/
        └── Ui/
            ├── Button.php                    # Lógica, contraste YIQ y polimorfismo
            └── Loading.php                   # Spinner usado por el guard de doble-submit
resources/
└── views/
    └── components/
        └── ui/
            ├── button.blade.php              # Plantilla del componente
            └── loading.blade.php             # Plantilla del spinner
```

---

## API del Componente

| Prop | Tipo | Default | Opciones / Descripción |
|---|---|---|---|
| `variant` | `string` | `primary` | `primary`, `secondary`, `success`, `warning`, `info`, `error`, `link` |
| `appearance` | `string` | `solid` | `solid`, `outline`, `ghost` |
| `size` | `string` | `md` | `sm`, `md`, `lg`, `xl` |
| `iconLeft` | `string\|null` | `null` | Nombre de Heroicon (ej: `heroicon-s-plus`) |
| `iconRight` | `string\|null` | `null` | Nombre de Heroicon |
| `icon` | `string\|null` | `null` | Heroicon para modo icono exclusivo |
| `hex` | `string\|null` | `null` | Color hexadecimal arbitrario (ej: `#7C3AED`) |
| `href` | `string\|null` | `null` | Si está presente, renderiza un `<a>` en lugar de `<button>` |
| `disabled` | `bool` | `false` | Deshabilita el elemento |
| `disabledWhen` | `string\|null` | `null` | Expresión Alpine cruda (ej. `"isSubmitDisabled"`) que se OR-ea con el guard de doble-submit — solo aplica a `type="submit"`. Ver [Guard de Doble-Submit](#guard-de-doble-submit-nuevo-en-zertixpos) |
| `fullWidth` | `bool` | `false` | Agrega `w-full` |
| `hoverEffect` | `bool` | `false` | Activa micro-interacción de elevación en hover/active |

> El componente original de Orvian tenía un prop `loadingText` para reemplazar el texto del slot durante el envío (ej. "Guardando..."). **No se portó** — ver [Guard de Doble-Submit](#guard-de-doble-submit-nuevo-en-zertixpos): en ZertixPOS el guard reemplaza el ícono por un spinner sin tocar el texto, así que no hace falta.
>
> **Bug corregido respecto al original de Orvian:** el prop de apariencia visual se llamaba `type` (`solid`/`outline`/`ghost`) — el mismo nombre que el atributo HTML nativo `type="submit"`/`type="button"`. Laravel extrae cualquier atributo que coincida con un parámetro del constructor de la clase del componente directo a esa propiedad, así que `<x-ui.button type="submit">` nunca llegaba a `$attributes`: el HTML siempre renderizaba `type="button"`, **el formulario nunca se enviaba por ese botón**, y el guard de doble-submit tampoco podía detectar el submit. Se renombró el prop a `appearance` para eliminar la colisión — confirmado con `Blade::render()` que ahora `type="submit"` sí llega al `<button>` renderizado.

---

## Variantes y Colores Hexadecimales

El componente puede usar los tokens del sistema mediante el prop `variant`, o colores dinámicos mediante el prop `hex`.

### Variantes del Sistema (`variant`)

| Variante | Token | Uso recomendado |
|---|---|---|
| `primary` | `zertix-primary` | Acción principal, CTA |
| `secondary` | `zertix-secondary` | Acción secundaria o neutral |
| `success` | `state-success` | Confirmación, guardado |
| `warning` | `state-warning` | Advertencia, precaución |
| `info` | `state-info` | Información contextual |
| `error` | `state-error` | Eliminación, acción destructiva |
| `link` | `zertix-secondary` | Acción inline sin peso visual |

### Soporte Hexadecimal (`hex`)

Para colores arbitrarios (métodos de pago, categorías, etc. — mismo mecanismo que `x-ui.badge`, ver `docs/ui/badge.md`). El componente calcula automáticamente la luminancia perceptual (YIQ del W3C) del color de fondo para asegurar la legibilidad del texto.

- **YIQ ≥ 128 (Claro):** el texto será oscuro (`#1e293b`).
- **YIQ < 128 (Oscuro):** el texto será blanco (`#ffffff`).

```blade
{{-- Fondo violeta oscuro (YIQ bajo) → Texto blanco --}}
<x-ui.button hex="#7C3AED">Director/a</x-ui.button>

{{-- Fondo blanco verdoso (YIQ alto) → Texto oscuro --}}
<x-ui.button hex="#F0FDF4">Activo</x-ui.button>
```

---

## Tipos de Estilo

El prop `appearance` controla la apariencia visual del botón independientemente del color.

- **`solid` (Default):** fondo relleno. Mayor peso visual. Usa `hover:opacity-90` para consistencia.
- **`outline`:** borde visible con fondo semitransparente. Parten de una opacidad base `/10` que se intensifica en hover `/20`.
- **`ghost`:** sin fondo ni borde base. El fondo aparece solo en hover con baja opacidad. Ideal para barras de herramientas, filas de tabla y acciones secundarias.

> [!NOTE]
> La variante `link` ignora parcialmente los estilos. En `solid` se renderiza como texto plano; en `outline` agrega un borde inferior (subrayado). El prop `size` no afecta su padding.

---

## Polimorfismo (Tag Dinámico)

Al pasar el prop `href`, el componente renderiza automáticamente una etiqueta `<a>` manteniendo exactamente el mismo aspecto visual y comportamiento.

```blade
{{-- <button> (default, acciona un método) --}}
<x-ui.button variant="primary" wire:click="save">Guardar</x-ui.button>

{{-- <a> (con href, navegación) --}}
<x-ui.button href="{{ route('app.dashboard') }}" variant="secondary">
    Volver
</x-ui.button>
```

---

## Tamaños

| Size | Padding (con texto) | Dimensión (icono exclusivo) | Font size | Icono |
|---|---|---|---|---|
| `sm` | `px-4 py-2` | `w-8 h-8` | `text-xs` | `w-4 h-4` |
| `md` | `px-6 py-3` | `w-11 h-11` | `text-sm` | `w-5 h-5` |
| `lg` | `px-8 py-4` | `w-14 h-14` | `text-base` | `w-5 h-5` |
| `xl` | `px-10 py-5` | `w-16 h-16` | `text-lg` | `w-7 h-7` |

**Ajuste respecto al original de Orvian:** radio de esquina `rounded-xl` (~12px) → `rounded-lg` (8px) en `getButtonClasses()`, para igualar el estándar del mockup de ZertixPOS.

**Ajuste de paddings (iniciado por el usuario, completado acá):** el padding de texto (`sm`/`md`/`lg`/`xl`) se redujo respecto al original de Orvian — `lg` tenía un bug real en el ajuste inicial (`py-6`, más alto que `xl` con `py-5`, sin escalar con el `px-5`), corregido a `py-2.5`. El modo solo-ícono (`icon`) también se redujo — `w-11 h-11`/etc. dejaban demasiado aire alrededor de un ícono de `w-5 h-5`; ahora `sm`/`md`/`lg`/`xl` son `w-7`/`w-9`/`w-11`/`w-14`.

**Ring de foco: `focus-visible`, no `focus`.** El ring (`ring-2 ring-offset-2`, color por variante) es la señal de foco para navegación por teclado — necesaria por accesibilidad (WCAG 2.4.7), no es decorativa y no se debe quitar. Pero disparado con `focus:` aparecía también al hacer clic con mouse, lo cual se siente ruidoso sin aportar nada (el usuario ya ve el resultado del clic). Cambiado a `focus-visible:` — el ring solo aparece con navegación por teclado (Tab), nunca con clic de mouse, mismo patrón que usan la mayoría de design systems modernos.

---

## Uso de Iconos y Accesibilidad

El componente distingue dos modos según si el `$slot` tiene contenido:

**1. Botón con texto e icono:** usa `iconLeft` o `iconRight`. El icono acompaña al texto.

**2. Modo "Solo Icono" (Exclusivo):** usa `icon` (o deja el slot vacío con `iconLeft`/`iconRight`). El componente asume dimensiones cuadradas perfectas automáticamente.

> [!IMPORTANT]
> **Accesibilidad (a11y):** cuando el componente detecta el modo "solo icono", intentará inferir un `aria-label` básico del nombre del icono como fallback (ej: "trash" para `heroicon-s-trash`). Se recomienda pasar un `aria-label` explícito para lectores de pantalla.

```blade
{{-- aria-label inferido: "trash" --}}
<x-ui.button variant="error" icon="heroicon-s-trash" size="sm" />

{{-- aria-label explícito (recomendado) --}}
<x-ui.button variant="error" icon="heroicon-s-trash" size="sm" aria-label="Eliminar usuario" />
```

---

## Guard de Doble-Submit (nuevo en ZertixPOS)

**No existía en el `x-ui.button` original de Orvian** — se construye en esta fase, dentro del componente que reemplaza a `primary-button.blade.php`, absorbiendo lo que originalmente iba a ser un requerimiento aparte (REQ-2.2 en el diseño viejo del roadmap). Cualquier `<x-ui.button type="submit">` del sistema lo hereda automáticamente, sin que cada vista tenga que declarar nada.

### Cómo funciona

1. Al hacer clic en un botón `type="submit"`, Alpine valida el `<form>` más cercano con `reportValidity()` (validación nativa del navegador — si hay campos `required` vacíos o inválidos, el guard no se activa y el navegador muestra su mensaje nativo).
2. Si la validación pasa, el botón entra en estado `sending`: se deshabilita (`:disabled`) y su ícono (o el ícono único en modo solo-ícono) se reemplaza por `<x-ui.loading>`.
3. **El texto del slot NO cambia.** A diferencia del patrón original de Orvian (que reemplazaba el texto por "Guardando..."), acá se eligió el estilo Filament — spinner al lado, texto intacto — para evitar que el botón cambie de ancho (lo cual desplaza elementos vecinos, ej. el botón "Cancelar" al lado) y para no requerir un string de copy nuevo en cada call site.
4. Si el botón no tiene ningún ícono (`iconLeft`/`icon` no declarados), el spinner simplemente no tiene dónde aparecer en modo texto — en ese caso considerá agregar un `iconLeft` al botón si querés feedback visual, o usar el mecanismo de Livewire (`wire:loading`, ver abajo) si el submit es vía `wire:click` en vez de un `<form>` HTML nativo.

### Alcance

El guard solo se activa cuando:
- `tag()` resuelve a `<button>` (no aplica a botones con `href`, que son enlaces de navegación, no submits).
- El atributo HTML `type` es `submit` (default de HTML es `button`, así que hay que pasar `type="submit"` explícitamente).

No se activa en botones `wire:click` — esos siguen el patrón de `wire:loading` documentado abajo, que es opt-in por diseño (ver por qué en esa sección).

### Ejemplo

```blade
<form action="{{ route('products.store') }}" method="POST">
    @csrf
    {{-- ... campos del formulario ... --}}

    <x-ui.button type="submit" variant="primary" iconLeft="heroicon-s-check">
        Guardar Producto
    </x-ui.button>
</form>
```
Mientras el request está en curso, el ícono de check se reemplaza por un spinner y el botón queda deshabilitado — "Guardar Producto" nunca cambia de texto ni el botón cambia de ancho. Un segundo clic durante el envío no hace nada (el botón está `disabled`).

---

## Estados de Carga (Livewire)

> [!IMPORTANT]
> El componente **no** aplica ningún `wire:loading` por defecto a ningún botón — aplicarlo automáticamente a todos los botones ante cualquier request Livewire en curso (no solo el que originó la acción) produce parpadeos de "botones no relacionados" iluminándose sin motivo.

El feedback de carga en botones `wire:click` es **opt-in explícito**: cada botón que lo necesite debe declarar su propio `wire:loading` con `wire:target` apuntando a la acción exacta.

```blade
<x-ui.button
    variant="primary"
    wire:click="save"
    wire:loading.class.add="opacity-60 pointer-events-none"
    wire:loading.attr="disabled"
    wire:target="save">
    <span wire:loading.remove wire:target="save">Guardar cambios</span>
    <span wire:loading wire:target="save" class="flex items-center gap-2">
        <x-ui.loading size="sm" /> Guardando...
    </span>
</x-ui.button>
```

Esto es intencionalmente distinto del guard de doble-submit (que sí es automático) — los botones Livewire no tienen un `<form>` HTML nativo que validar con `reportValidity()`, así que el mecanismo automático no aplica ahí; se mantiene el patrón manual de Orvian para ese caso.

---

## Props Adicionales

- **`disabled`:** agrega el atributo HTML `disabled`, aplica `opacity-60 cursor-not-allowed` y desactiva el `hoverEffect`.
- **`fullWidth`:** agrega `w-full` (ignorado en modo solo icono).
- **`hoverEffect`:** activa una micro-interacción de elevación (`hover:-translate-y-0.5 hover:shadow-lg hover:shadow-zertix-primary/20`). Recomendado para CTAs principales. *(Ajuste respecto a Orvian: el original usaba `hover:scale-[1.03] active:scale-[0.98]` — se cambió a elevación + sombra para igualar el mockup de ZertixPOS.)*

---

## Ejemplos de Uso

**Ghost para barras de herramientas:**
```blade
<x-ui.button appearance="ghost" variant="secondary" iconLeft="heroicon-o-pencil-square">
    Editar
</x-ui.button>
```

**CTA con micro-interacción:**
```blade
<x-ui.button variant="primary" :hoverEffect="true" iconLeft="heroicon-s-plus">
    Nuevo Registro
</x-ui.button>
```

**Botón de icono exclusivo Outline:**
```blade
<x-ui.button variant="secondary" appearance="outline" size="lg" icon="heroicon-s-cog-6-tooth" aria-label="Ajustes" />
```

**Enlace tipo botón (Polimorfismo):**
```blade
<x-ui.button href="/productos/crear" variant="primary" iconLeft="heroicon-s-plus">
    Crear Producto
</x-ui.button>
```

**Submit con guard de doble-envío (automático):**
```blade
<x-ui.button type="submit" variant="primary" iconLeft="heroicon-s-check">
    Guardar
</x-ui.button>
```

---

## Notas Adicionales

- El atributo `type="button"` HTML se renderiza por defecto cuando no es un enlace. Para formularios, pasa explícitamente `type="submit"`.
- La variante `link` no debe combinarse con `fullWidth` ni `hoverEffect`.
- Los tokens del sistema de diseño (`zertix-primary`, `zertix-secondary`, `state-*`) ya están definidos en `tailwind.config.js` (Fase 7.1).
- Las clases dinámicas que arma `getButtonClasses()` viven en un archivo `.php` (`app/View/Components/Ui/Button.php`), no en un `.blade.php` — `tailwind.config.js` (`content:`) necesita incluir `./app/View/Components/**/*.php` para que el JIT las genere (ya corregido en Fase 7.2, ver `docs/features/v1.2.0.md` §7.2).
- `x-ui.loading` (usado por el guard) es agnóstico de color — hereda `border-current`, así que se adapta solo al texto del botón que lo contiene, sin necesitar ajuste de color propio.
