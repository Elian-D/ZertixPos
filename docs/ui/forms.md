# Componentes de Formulario (`x-ui.forms.*`)

Adaptación a ZertixPOS de los componentes de formulario de Orvian (Fase 7, REQ-7.5 de `docs/features/v1.2.0.md`). Reemplazan `x-text-input`/`x-input-label`/checkbox sueltos de Laravel Breeze en todo el sistema. A diferencia del original de Orvian (estilo **Line UI**, solo borde inferior), ZertixPOS usa estilo **caja completa bordeada** (`border` + `rounded-lg`), mismo radio de 8px que `x-ui.button`/`x-ui.badge`, para consistencia visual del sistema — ver mockup `formulario/screen.png`.

---

## Tabla de Contenido

- [Estructura de Archivos](#estructura-de-archivos)
- [Diferencia real con el original de Orvian](#diferencia-real-con-el-original-de-orvian)
- [Convenciones Compartidas](#convenciones-compartidas)
- [Bug corregido: el hint/error se salía del ancho del input](#bug-corregido-el-hinterror-se-salía-del-ancho-del-input)
- [Bugs corregidos (Fase 7.9): toggle de password tapado por el error + rojo persistente en foco](#bugs-corregidos-fase-79-toggle-de-password-tapado-por-el-error--rojo-persistente-en-foco)
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
| **Error** | `state-error` | `state-error` | `bg-state-error/5` | `exclamation-circle` (automático, reemplaza `iconRight` — **excepto en `Input` con `type="password"`**, ver Fase 7.9 abajo) |
| **Disabled** | `slate-100` | — | `bg-slate-50` | `opacity-50`, `cursor-not-allowed` |

La transición a Focus usa `group` + `group-focus-within:` en el wrapper — el label e ícono cambian de color sin JavaScript. **Excepción (Fase 7.9):** cuando el campo tiene `error` Y recibe foco, el borde/ring/fondo rojo se neutraliza mientras dura el foco (vía Alpine, no vía `focus:` de Tailwind) — ver la sección de bugs corregidos más abajo.

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

## Bugs corregidos (Fase 7.9): toggle de password tapado por el error + rojo persistente en foco

**Encontrado en producción (2026-09-18), reportado con captura real de `profile/partials/tab-security.blade.php`.** Dos problemas de UX en el estado de error, ambos en el mismo flujo (cambiar contraseña con la actual incorrecta):

### 1. El ícono de error tapaba el toggle de mostrar/ocultar

`input.blade.php` decidía el ícono derecho con `@if ($error) ... @elseif ($isPassword()) ...` — mutuamente excluyente. Con `type="password"` y `error` a la vez, el `exclamation-circle` ganaba y el botón del ojito no se renderizaba en absoluto: el usuario no tenía forma de revisar qué escribió mal justo cuando más lo necesitaba.

**Fix:** se invirtió la prioridad — `isPassword()` va primero, `error` segundo. El error no deja de comunicarse (borde/fondo rojo del campo + mensaje debajo siguen igual), y el propio botón del toggle ya se pinta de `text-state-error` vía `iconColorClasses()` cuando hay error, así que sigue señalando visualmente el problema sin perder la función.

### 2. El borde/ring rojo se reafirmaba (no se neutralizaba) al hacer foco

`inputClasses()`/`selectClasses()`/`textareaClasses()`/`groupWrapperClasses()` definían el branch de error con `focus:border-state-error focus:ring-state-error/20` (o `focus-within:` en el wrapper con addon) — es decir, el propio foco reforzaba el rojo en vez de aflojarlo. Efecto real: el usuario hace clic en el campo para corregir la contraseña, y el campo se pone *más* rojo (ring activo), como si le estuviera diciendo "sigue mal" mientras todavía no ha escrito nada nuevo.

**Por qué no es solo una clase de Tailwind:** el error viene de `$errors->first(...)` o de una prop de Livewire — un string que Blade ya resolvió al renderizar. Blade no sabe "el usuario tiene el campo enfocado ahora mismo"; eso solo existe en el navegador. Por eso el fix necesita JS (Alpine), no un simple cambio de clase `focus:`.

**Fix:** cada componente con esta capacidad (`Input`, `Select`, `Textarea`) agrega, **solo cuando hay `error`**, un estado Alpine `focused` (`x-data="{ focused: false }"`, o combinado con `showPassword` en `Input` vía el helper `alpineData()` de `Input.php`) y listeners `@focus`/`@blur` en el campo real. Mientras `focused` es `true`, una clase `:class` con el modificador `!` de Tailwind (`!border-zertix-primary !ring-zertix-primary/20 !bg-white !text-slate-800`, y `!text-slate-600` en el label) sobreescribe el rojo estático con el mismo look que tendría un campo sin error en foco normal. Se usa `!important` (no un simple orden de clases) porque competir por especificidad con las clases `focus:`/`focus-within:` estáticas que ya existen en la hoja compilada es frágil — el orden real depende de cómo Tailwind ordena los variants, no de cuál "parece" más específica en el HTML.

**Qué NO cambia al enfocar (a propósito):** el ícono izquierdo (si lo hay) y el mensaje de error debajo del campo se quedan en rojo — solo se neutraliza el marco/fondo del campo en sí. El usuario sigue teniendo la referencia de qué está corrigiendo, solo deja de sentir que "todavía está mal" mientras lo edita.

**Archivos tocados:** `Input.php` (`alpineData()`), `input.blade.php`, `select.blade.php`, `textarea.blade.php`. `Checkbox`/`Radio`/`Toggle`/`FileInput` no comparten este patrón de borde/ring (o no tienen `type="password"`), así que quedan fuera de este fix.

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
| `addonLeft` | `string\|null` | `null` | Texto plano no editable pegado al borde izquierdo (ej. `"https://"`) |
| `addonRight` | `string\|null` | `null` | Texto plano no editable pegado al borde derecho (ej. `".zertixpos.com"`) |

### Addon (`addonLeft`/`addonRight`, nuevo en v1.3.0)

Para un sufijo/prefijo fijo que forma parte del valor real (subdominio, código de país, unidad) pero no debe ser editable — patrón "grouped input" estándar (una sola caja compartida entre el addon y el campo, no dos cajas bordeadas pegadas, que se verían como doble borde). Con addon, el borde/radio/fondo/ring de foco los lleva el wrapper (`groupWrapperClasses()`), no el `<input>` (`inputClasses()` se vuelve "desnudo": sin borde, sin fondo propio, `focus:ring-0`) — mismo cuidado de "cada estado declara su propio fondo completo" que el resto del componente.

**Es solo texto — nunca un ícono ni un botón.** No se combina con `iconLeft`/`iconRight` en el mismo lado (uno de los dos gana visualmente); sí se puede combinar un addon de un lado con un ícono del lado opuesto.

```blade
<x-ui.forms.input
    label="Subdominio"
    name="subdominio"
    wire:model.live="subdominio"
    placeholder="tu-negocio"
    :addonRight="'.' . request()->getHost()"
    :error="$errors->first('subdominio')"
    required
/>
```

### Toggle mostrar/ocultar contraseña (REQ-7.11)

**`type="password"` trae el botón de mostrar/ocultar de fábrica — no es opt-in.** No hay ningún caso real en el sistema donde un campo de contraseña deba impedir revelarla, así que no existe un prop separado para activarlo. Cuando `type="password"`:

- El `<input>` deja de usar el atributo estático `type="password"` y pasa a un binding reactivo de Alpine: `:type="showPassword ? 'text' : 'password'"`.
- El slot del ícono derecho lo ocupa un `<button type="button">` con `heroicon-s-eye-slash`/`heroicon-s-eye` — a diferencia del `iconRight` estático (que es `pointer-events-none`), este sí recibe clicks.
- `iconRight` se **ignora** si `type="password"` — el espacio derecho es del toggle, no hay forma de combinar ambos.
- **El toggle tiene prioridad sobre el error (Fase 7.9, ver "Bugs corregidos" arriba).** Antes el `exclamation-circle` reemplazaba el toggle cuando había error — eso dejaba al usuario sin forma de revisar la contraseña que escribió mal. Ahora el toggle se queda siempre; el error se sigue viendo por el borde/fondo rojo del campo, el mensaje debajo, y el propio botón del toggle pintado de `text-state-error`.

```blade
<x-ui.forms.input
    type="password"
    label="Contraseña"
    name="password"
    icon-left="heroicon-s-lock-closed"
    :error="$errors->first('password')"
    required
/>
```

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
| `preview` | `bool` | `false` | Miniatura del archivo elegido (si es imagen) — ver abajo |
| `dropzone` | `bool` | `false` | Variante cuadrada de arrastrar/soltar (logo/foto) — ver abajo |
| `size` | `string` | `'md'` | Tamaño de la caja `dropzone` — `xs`/`sm`/`md`/`lg`/`xl`. Ignorado si `dropzone` es `false` |

### Comportamiento

- Muestra `"Seleccionar archivo..."` hasta que el usuario elige uno; entonces muestra el nombre del archivo (o `"N archivos"` si `multiple` y hay más de uno).
- Botón "×" para limpiar la selección aparece solo cuando hay un archivo elegido, y solo si no hay `error` activo (en ese caso el espacio lo ocupa el ícono de error).

### Preview de imagen (`preview`, nuevo en v1.3.0)

**Opt-in — `false` por defecto, no cambia ningún file-input existente en el sistema.** Con `preview="true"`, si el archivo elegido es una imagen (`file.type` empieza con `image/`), se genera un `URL.createObjectURL()` en el `@change` y se muestra como miniatura (`w-7 h-7 rounded object-cover`) reemplazando el `iconLeft` mientras haya un archivo elegido. El object URL se revoca (`URL.revokeObjectURL()`) tanto al limpiar (`clear()`) como al elegir un archivo nuevo, para no filtrar memoria del navegador. No sirve para `multiple` (solo previsualiza el primer archivo).

### Variante dropzone (`dropzone`, nuevo en v1.3.0)

**Opt-in — `false` por defecto, no cambia el look del file-input horizontal ya usado en el importador de datos (`x-data-table.import`).** Con `dropzone="true"`, la caja clickeable pasa de ser una fila tipo campo de texto a un **cuadrado real** (ancho y alto iguales vía `dropzoneSizeClasses()`, no un `h-*` con `w-full` que terminaba rectangular según el ancho del contenedor — bug real reportado con el logo del Wizard) con borde punteado, ícono centrado y `"Subir logo"` (o el `fileName` elegido) debajo — pensado para logo/foto de perfil, no para archivos genéricos. El `hint` se renderiza DENTRO del cuadro en vez de debajo (evita el mensaje duplicado). Combinado con `preview="true"`, la imagen elegida llena el cuadro entero (`object-cover` — se recorta para adaptarse sin deformarse, sin importar su proporción original) en vez de una miniatura chica, con un botón "×" flotante arriba a la derecha para limpiar. Misma lógica Alpine (`fileName`/`previewUrl`/`clear()`/`onChange()`) que la variante normal — solo cambia el markup de la caja.

**Tamaños (`size`)** — mismo nombre de escala que `x-ui.button`:

| Size | Dimensión |
|---|---|
| `xs` | `w-16 h-16` |
| `sm` | `w-20 h-20` |
| `md` (default) | `w-28 h-28` |
| `lg` | `w-36 h-36` |
| `xl` | `w-44 h-44` |

```blade
<x-ui.forms.file-input
    label="Logo del Negocio"
    name="logo"
    accept="image/*"
    :dropzone="true"
    :preview="true"
    size="lg"
    hint="PNG, JPG hasta 2MB"
    :error="$errors->first('logo')"
/>
```

```blade
<x-ui.forms.file-input
    label="Logo del Negocio"
    name="logo"
    accept="image/*"
    :preview="true"
    hint="PNG o JPG, máximo 2MB"
    :error="$errors->first('logo')"
/>
```

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
