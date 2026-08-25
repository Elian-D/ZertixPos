# Componentes de Formulario (`x-ui.forms.*`)

Adaptación a ZertixPOS de los componentes de formulario de Orvian (Fase 7, REQ-7.5 de `docs/features/v1.2.0.md`). Reemplazan `x-text-input`/`x-input-label`/checkbox sueltos de Laravel Breeze en todo el sistema. A diferencia del original de Orvian (estilo **Line UI**, solo borde inferior), ZertixPOS usa estilo **caja completa bordeada** (`border` + `rounded-lg`), mismo radio de 8px que `x-ui.button`/`x-ui.badge`, para consistencia visual del sistema — ver mockup `formulario/screen.png`.

---

## Tabla de Contenido

- [Estructura de Archivos](#estructura-de-archivos)
- [Diferencia real con el original de Orvian](#diferencia-real-con-el-original-de-orvian)
- [Convenciones Compartidas](#convenciones-compartidas)
- [Bug corregido: el hint/error se salía del ancho del input](#bug-corregido-el-hinterror-se-salía-del-ancho-del-input)
- [x-ui.forms.input](#x-uiformsinput)
- [x-ui.forms.select](#x-uiformsselect)
- [x-ui.forms.textarea](#x-uiformstextarea)
- [x-ui.forms.checkbox](#x-uiformscheckbox)
- [x-ui.forms.radio](#x-uiformsradio)
- [x-ui.forms.toggle](#x-uiformstoggle)
- [x-ui.forms.file-input (nuevo en ZertixPOS)](#x-uiformsfile-input-nuevo-en-zertixpos)
- [Notas Adicionales](#notas-adicionales)

---

## Estructura de Archivos

```plaintext
app/
└── View/
    └── Components/
        └── Ui/
            └── Forms/
                ├── Input.php
                ├── Select.php
                ├── Textarea.php
                ├── Checkbox.php
                ├── Radio.php
                ├── Toggle.php
                └── FileInput.php          # No existe en Orvian original
resources/
└── views/
    └── components/
        └── ui/
            └── forms/
                ├── input.blade.php
                ├── select.blade.php
                ├── textarea.blade.php
                ├── checkbox.blade.php
                ├── radio.blade.php
                ├── toggle.blade.php
                └── file-input.blade.php
```

---

## Diferencia real con el original de Orvian

**No es solo cambio de paleta.** El `Input.php` de Orvian es estilo underline (`border-0 border-b`, sin caja, tipo "floating label"). El mockup de ZertixPOS pide caja completa bordeada, fondo blanco, borde gris que pasa a verde (`zertix-primary`) en foco. Son dos lenguajes visuales distintos — se reescribió `inputClasses()`/`selectClasses()`/`textareaClasses()` en cada componente en vez de solo recolorear:

```php
// Antes (Orvian, underline)
'w-full border-0 border-b bg-transparent rounded-none px-0 py-3 ...'

// Después (ZertixPOS, caja)
'w-full rounded-lg border px-3 py-2.5 text-sm ... bg-white border-slate-200 focus:border-zertix-primary focus:ring-1 focus:ring-zertix-primary/20'
```

Mismo ajuste de `focus:border-orvian-orange` → `focus:border-zertix-primary` en los 7 componentes. Checkbox/Radio/Toggle marcados usan `zertix-primary` como color del "check"/pista activa.

**Nota de implementación repetida en `Input`/`Select`/`Textarea`:** `bg-white` nunca va en la clase base incondicional — si conviviera con `bg-state-error/5` en el branch de error, ambas compiten por el mismo `background-color` en la hoja compilada y gana la que quede después, no la del HTML (mismo bug ya encontrado y resuelto en `Badge`/`Button`, ver `docs/ui/badge.md`/`docs/ui/buttons.md`). Cada estado (normal/error/disabled) declara su propio fondo completo.

---

## Convenciones Compartidas

### Sistema de Estados

Todos los componentes con entrada de texto (`Input`, `Select`, `Textarea`) comparten tres estados visuales:

| Estado | Color del borde | Color del label | Fondo | Icono |
|---|---|---|---|---|
| **Default** | `slate-200` | `slate-600` | `bg-white` | `slate-400` |
| **Focus** | `zertix-primary` (ring 1px) | — | igual | `zertix-primary` |
| **Error** | `state-error` | `state-error` | `bg-state-error/5` | `exclamation-circle` (automático, reemplaza `iconRight`) |
| **Disabled** | `slate-100` | — | `bg-slate-50` | `opacity-50`, `cursor-not-allowed` |

La transición a Focus usa `group` + `group-focus-within:` en el wrapper — el label e ícono cambian de color sin JavaScript.

### Props de Mensaje

| Prop | Tipo | Comportamiento |
|---|---|---|
| `error` | `string\|null` | Mensaje en rojo debajo del campo; en Input/Select/FileInput reemplaza el ícono derecho por `exclamation-circle` |
| `hint` | `string\|null` | Texto gris informativo debajo del campo. Se oculta si hay `error` activo |

### Integración con Livewire

Igual que el resto de `x-ui.*`: `$attributes->merge()` pasa cualquier atributo adicional (`wire:model`, `wire:model.live`, etc.) directo al elemento nativo.

```blade
<x-ui.forms.input name="name" wire:model="name" />
<x-ui.forms.select name="plan_id" wire:model.live="plan_id" />
<x-ui.forms.checkbox name="terms" wire:model="terms" />
<x-ui.forms.toggle name="advanced" wire:model="advanced" />
```

---

## Bug corregido: el hint/error se salía del ancho del input

**Encontrado en producción (2026-08-20):** un `hint`/`error` con una cadena sin espacios (ej. un identificador largo tipo `DGIIaaaaaaaaaa...`) no se cortaba dentro del ancho del campo — se salía por encima de otros campos vecinos, exactamente lo que Filament evita de fábrica.

**Causa real:** el wrapper raíz de cada componente (`<div class="flex flex-col group">`) es a su vez un hijo flex/grid dentro del layout del formulario. Un hijo flex tiene `min-width: auto` por defecto — permite que su contenido intrínseco (una palabra sin puntos de corte) lo estire más allá del ancho del contenedor en vez de respetarlo. El `<p>` del hint tampoco tenía `break-words`, así que no había ningún punto de quiebre disponible aunque el contenedor sí midiera lo correcto.

**Fix aplicado** en `input.blade.php`, `select.blade.php`, `textarea.blade.php`, `file-input.blade.php` (y preventivamente en `checkbox.blade.php`/`radio.blade.php`/`toggle.blade.php` por el mismo riesgo con `description`):

```blade
{{-- Antes --}}
<div class="flex flex-col group">
    ...
    <p class="mt-1.5 text-xs text-slate-400">{{ $hint }}</p>

{{-- Después --}}
<div class="flex flex-col min-w-0 group">
    ...
    <p class="mt-1.5 text-xs text-slate-400 break-words">{{ $hint }}</p>
```

`min-w-0` le devuelve al wrapper la capacidad de encogerse al ancho real del contenedor padre (grid/flex del formulario), y `break-words` le da al texto un punto de quiebre aunque no tenga espacios. Los dos cambios son necesarios — solo uno de los dos no resuelve el overflow.

---

## x-ui.forms.input

Componente para entradas de texto de una sola línea. Soporta todos los tipos de `<input>` HTML.

### API

| Prop | Tipo | Default | Descripción |
|---|---|---|---|
| `label` | `string` | `''` | Texto del label. Si está vacío, no se renderiza |
| `name` | `string` | `''` | Atributo `name` |
| `id` | `string` | `→ name` | Atributo `id` |
| `type` | `string` | `text` | `text`, `email`, `password`, `number`, `tel`, `url`, `search` |
| `placeholder` | `string` | `''` | Texto placeholder |
| `iconLeft` | `string\|null` | `null` | Heroicon izquierdo (agrega `pl-10`) |
| `iconRight` | `string\|null` | `null` | Heroicon derecho. Ignorado si hay `error` |
| `error` | `string\|null` | `null` | Mensaje de error |
| `hint` | `string\|null` | `null` | Texto auxiliar (oculto si hay `error`) |
| `required` | `bool` | `false` | Agrega `*` al label y `required` al input |
| `disabled` | `bool` | `false` | Desactiva el campo |
| `readonly` | `bool` | `false` | Campo de solo lectura |

### Ejemplos

**Input básico con icono y hint:**
```blade
<x-ui.forms.input
    label="Nombre del Cliente"
    name="name"
    placeholder="Ej. Ferretería Duarte SRL"
    icon-left="heroicon-o-building-storefront"
    hint="Nombre comercial o razón social"
/>
```

**Con validación de servidor:**
```blade
<x-ui.forms.input
    label="Correo Electrónico"
    name="email"
    type="email"
    icon-left="heroicon-o-envelope"
    wire:model.live="email"
    :error="$errors->first('email')"
    required
/>
```

---

## x-ui.forms.select

Select nativo estilizado (caja + chevron), compatible con `$slot` para pasar `<option>` directamente.

### API

| Prop | Tipo | Default | Descripción |
|---|---|---|---|
| `label` | `string` | `''` | Texto del label |
| `name` | `string` | `''` | Atributo `name` |
| `id` | `string` | `→ name` | Atributo `id` |
| `placeholder` | `string` | `'Seleccionar...'` | Opción vacía por defecto. `placeholder=""` la omite |
| `iconLeft` | `string\|null` | `null` | Heroicon izquierdo opcional |
| `error` | `string\|null` | `null` | Mensaje de error |
| `hint` | `string\|null` | `null` | Texto auxiliar |
| `required` | `bool` | `false` | — |
| `disabled` | `bool` | `false` | — |

### Ejemplo

```blade
<x-ui.forms.select
    label="Almacén"
    name="warehouse_id"
    icon-left="heroicon-o-building-office-2"
    wire:model.live="warehouse_id"
    :error="$errors->first('warehouse_id')"
    required
>
    @foreach ($warehouses as $warehouse)
        <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
    @endforeach
</x-ui.forms.select>
```

---

## x-ui.forms.textarea

Área de texto con el mismo estilo de caja que `Input`.

### API

| Prop | Tipo | Default | Descripción |
|---|---|---|---|
| `label` | `string` | `''` | Texto del label |
| `name` | `string` | `''` | Atributo `name` |
| `id` | `string` | `→ name` | Atributo `id` |
| `placeholder` | `string` | `''` | Placeholder |
| `rows` | `int` | `3` | Número de filas visibles |
| `resize` | `bool` | `false` | Permite redimensionar verticalmente (`resize-y`) |
| `error` | `string\|null` | `null` | Mensaje de error |
| `hint` | `string\|null` | `null` | Texto auxiliar |
| `required` | `bool` | `false` | — |
| `disabled` | `bool` | `false` | — |
| `readonly` | `bool` | `false` | — |

### Ejemplo

```blade
<x-ui.forms.textarea
    label="Notas de la Venta"
    name="notes"
    placeholder="Ej. Cliente pidió entrega antes de las 5pm..."
    :rows="3"
    wire:model="notes"
    hint="Opcional — visible solo para el equipo interno"
/>
```

---

## x-ui.forms.checkbox

Checkbox con estilo ZertixPOS. Usa `@tailwindcss/forms` para el check nativo y `text-zertix-primary` para el color del tilde.

### API

| Prop | Tipo | Default | Descripción |
|---|---|---|---|
| `label` | `string` | `''` | Texto principal |
| `name` | `string` | `''` | Atributo `name` (usar `name="ids[]"` para arrays) |
| `id` | `string` | `→ name` | Atributo `id` |
| `value` | `string` | `'1'` | Valor cuando está marcado |
| `checked` | `bool` | `false` | Estado inicial marcado |
| `description` | `string\|null` | `null` | Texto descriptivo pequeño bajo el label |
| `error` | `string\|null` | `null` | Mensaje de error (bajo el checkbox) |
| `disabled` | `bool` | `false` | — |

### Ejemplo

```blade
<x-ui.forms.checkbox
    label="Aplica ITBIS"
    name="apply_tax"
    wire:model="apply_tax"
    description="Este producto factura con el 18% de impuesto"
/>
```

---

## x-ui.forms.radio

Radio button con estilo ZertixPOS. El punto interior verde usa `text-zertix-primary` vía `@tailwindcss/forms`.

### API

| Prop | Tipo | Default | Descripción |
|---|---|---|---|
| `label` | `string` | `''` | Texto del radio |
| `name` | `string` | `''` | Nombre del grupo (compartido entre radios relacionados) |
| `id` | `string` | `→ name_value` | Se genera como `{name}_{value}` si no se pasa |
| `value` | `string` | `''` | Valor enviado al seleccionar |
| `checked` | `bool` | `false` | Estado inicial seleccionado |
| `description` | `string\|null` | `null` | Texto descriptivo bajo el label |
| `disabled` | `bool` | `false` | — |

### Ejemplo

```blade
<div class="flex flex-col gap-3">
    <x-ui.forms.radio label="Contado" name="payment_mode" value="cash" wire:model="payment_mode" />
    <x-ui.forms.radio label="Crédito" name="payment_mode" value="credit" wire:model="payment_mode"
        description="Genera una cuenta por cobrar" />
</div>
```

---

## x-ui.forms.toggle

Interruptor visual con Alpine.js. Internamente usa un `<input type="checkbox">` oculto (`sr-only`) compatible con `wire:model`.

### API

| Prop | Tipo | Default | Descripción |
|---|---|---|---|
| `label` | `string` | `''` | Texto principal |
| `name` | `string` | `''` | Nombre del input oculto |
| `id` | `string` | `→ name` | Atributo `id` |
| `checked` | `bool` | `false` | Estado inicial activo |
| `description` | `string\|null` | `null` | Descripción pequeña bajo el label |
| `disabled` | `bool` | `false` | Desactiva el click sin cambiar el look |

### Comportamiento Visual

| Estado | Color de la pista | Bolita |
|---|---|---|
| **Off** | `slate-200` | Izquierda |
| **On** | `zertix-primary` (+ `shadow-zertix-primary/30`) | Derecha |
| **Disabled** | Sin cambio, `opacity-50` | — |

### Ejemplo

```blade
<x-ui.forms.toggle
    label="Permitir cobro de CxC en esta terminal"
    name="allow_receivable_collection"
    wire:model="allow_receivable_collection"
    :checked="$allow_receivable_collection"
/>
```

> [!NOTE]
> Con `wire:model`, la sincronización pasa por `@entangle` sobre la variable Alpine `on`. Pasa siempre `:checked="$tuPropiedad"` para que el estado inicial de Livewire inicialice correctamente el valor de Alpine al cargar.

---

## x-ui.forms.file-input (nuevo en ZertixPOS)

**No existe en el catálogo original de Orvian.** Input de archivo con caja clickeable (mismo estilo que `Input`/`Select`) en vez del `<input type="file">` nativo del navegador — el input real queda oculto (`hidden`) y se dispara por clic en la caja falsa.

### API

| Prop | Tipo | Default | Descripción |
|---|---|---|---|
| `label` | `string` | `''` | Texto del label |
| `name` | `string` | `''` | Atributo `name` |
| `id` | `string\|null` | `→ name` | Atributo `id` |
| `iconLeft` | `string\|null` | `'heroicon-o-cloud-arrow-up'` | Heroicon izquierdo (ya trae uno por defecto) |
| `error` | `string\|null` | `null` | Mensaje de error |
| `hint` | `string\|null` | `null` | Texto auxiliar |
| `required` | `bool` | `false` | — |
| `disabled` | `bool` | `false` | — |
| `accept` | `string` | `'*'` | Atributo `accept` nativo (ej. `image/*`, `.pdf`) |
| `multiple` | `bool` | `false` | Permite seleccionar más de un archivo |

### Comportamiento

- Muestra `"Seleccionar archivo..."` hasta que el usuario elige uno; entonces muestra el nombre del archivo (o `"N archivos"` si `multiple` y hay más de uno).
- Botón "×" para limpiar la selección aparece solo cuando hay un archivo elegido, y solo si no hay `error` activo (en ese caso el espacio lo ocupa el ícono de error).

### Ejemplo

```blade
<x-ui.forms.file-input
    label="Logo del Negocio"
    name="logo"
    accept="image/*"
    hint="PNG o JPG, máximo 2MB"
    :error="$errors->first('logo')"
/>
```

---

## Notas Adicionales

- **`@tailwindcss/forms`** es requerido para el estilo nativo de checkbox/radio. Verificar que esté en `plugins` de `tailwind.config.js`.
- **Alpine.js** es requerido para `x-ui.forms.toggle` y `x-ui.forms.file-input`. Los demás son puramente HTML/PHP.
- El `id` se genera automáticamente desde `name` si no se pasa. Para checkboxes en arrays (`name="ids[]"`), pasar siempre un `id` explícito y único.
- Todos los tokens de color (`zertix-primary`, `zertix-secondary`, `state-error`, etc.) ya están definidos en `tailwind.config.js` (Fase 7.1, ver `docs/features/v1.2.0.md`).
- **Reestructuración de layout de formularios (card gigante → secciones/multi-card): explícitamente fuera de alcance de esta fase, diferida a una versión futura (tentativo v1.6.0).** El mockup de demo hecho para esta fase (`formulario/DESIGN.md` + `formulario/screen.png`) se conserva como referencia para cuando se retome — no se descarta por diferirse.
