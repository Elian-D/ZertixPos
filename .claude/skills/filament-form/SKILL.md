---
name: filament-form
description: Usar este skill cuando el usuario pida crear o rehacer un formulario/vista de crear-editar para un módulo del sistema — frases como "hazme el formulario de X", "la vista de crear/editar X", "el form de X", "necesito un CRUD visual para X", "arma el create/edit de X", "quiero el formulario en estilo Filament", o cualquier pedido de una pantalla de alta/edición de un modelo. Preferir siempre este skill sobre escribir campos sueltos en create.blade.php/edit.blade.php a mano, incluso si el usuario no menciona "Filament" ni "secciones" explícitamente — cualquier formulario nuevo de más de 2-3 campos se beneficia de este patrón. NO se activa para formularios de un solo campo (ej. un modal de confirmación) ni para pantallas de listado/tabla (esas usan el motor DataTable de ARCHITECTURE.md, no este skill).
---

# Formulario estilo Filament (sin Filament)

Este skill existe porque ZertixPOS no instala Filament como paquete (sería una segunda tecnología de UI compitiendo con Blade+Livewire+Orvian que ya está establecida, mismo criterio que ya se usó para descartar Filament en el panel de Súper Admin) — pero el usuario sí quiere la sensación de un formulario Filament: secciones con card, ícono y título, en vez de un campo tras otro sin agrupar dentro de una tarjeta gigante. Este skill captura ese lenguaje visual con los componentes reales del proyecto.

**Alcance acotado a propósito: esto es solo la capa de vista.** No genera controlador, `FormRequest`, rutas ni migración — si el módulo todavía no existe, primero hay que construir esas piezas (ver `ARCHITECTURE.md`), este skill entra recién cuando toca la vista de crear/editar.

## Paso 1 — Leer el modelo real antes de inventar campos

Nunca asumas nombres de columna ni reglas de validación. Antes de escribir un solo campo:

1. Lee la migración del modelo (`database/migrations/tenant/*_create_<tabla>_table.php` o la más reciente que la modifique) y el `$fillable` del `Model`.
2. Si existen `Store<Modelo>Request`/`Update<Modelo>Request`, léelos — ahí están las reglas reales (`required`, `unique`, `exists`, etc.) y a veces revelan campos que el modelo tiene pero que no deberían ser editables desde este form.
3. Si el modelo tiene relaciones que el form necesita poblar (selects de catálogo), revisa si ya existe un `CatalogService` para ese módulo (`getForForm()`) en vez de armar el query a mano en la vista.

## Paso 2 — Agrupar los campos en secciones por afinidad, no por orden de columna

La pregunta es "¿qué decisión está tomando el usuario acá?", no "¿en qué orden están las columnas en la tabla". Agrupa por tema: datos de identificación va junto, configuración/comportamiento va junto, relaciones/permisos van en su propia sección. Dos-cuatro secciones es lo normal; si te salen más de cinco, probablemente dos de ellas en realidad son una sola.

## Paso 3 — Cada sección es la misma tarjeta que usan las vistas show

Formulario y show comparten **un solo lenguaje visual** (el de `/filament-show`): ícono **outline gris**, título en **tipo oración** (no mayúsculas), encabezado separado del contenido por un borde. Se usa el mismo componente, `x-ui.infolist.section`, con `:cols="0"` (el contenido es libre) y la grilla de campos adentro:

```blade
<x-ui.infolist.section title="Identidad y datos fiscales" icon="heroicon-o-identification" :cols="0"
    description="Opcional: una línea que explique para qué sirve la sección.">
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-5">
        <div class="sm:col-span-2">
            <x-ui.forms.input label="Nombre comercial" name="nombre_empresa" required />
        </div>
        <x-ui.forms.input label="RNC" name="tax_id" />
        {{-- … --}}
    </div>
</x-ui.infolist.section>
```

Ojo: `x-ui.forms.*` pasa `class` al `<input>`/`<select>` interno, no a su contenedor — un `class="sm:col-span-2"` puesto en el componente no ensancha el campo. Para que ocupe varias columnas, envolverlo en un `<div class="sm:col-span-2">`.

- **No** al ícono sólido de color (`heroicon-s-* text-zertix-secondary`) ni al título `uppercase tracking-wider` — era el estilo anterior de este skill y no combina con los show.
- `:cols="0"` es obligatorio: sin él la sección envuelve el contenido en un `<dl>` de 4 columnas (pensado para pares etiqueta/valor), no en un contenedor libre. Ver `docs/ui/infolist.md`.
- El ícono se elige por **tema real de la sección**, versión outline (`heroicon-o-*`): `identification`/`user` (identificación), `phone` (contacto), `map-pin` (ubicación), `cog-6-tooth`/`adjustments-horizontal` (configuración), `key`/`shield-check` (permisos/seguridad), `currency-dollar`/`banknotes` (finanzas, precios), `printer` (hardware), `tag` (descuentos), `globe-americas` (parámetros regionales), `photo` (imágenes).

## Paso 4 — Distribución: secciones apiladas a lo ancho, campos en grilla adentro

Así se ve un formulario Filament: **una sola columna de secciones**, cada sección a todo el ancho, y la grilla está **dentro** de la sección, no entre secciones.

- Secciones apiladas con `flex flex-col gap-6`, cada una a todo el ancho.
- Dentro de cada sección: `grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-5` por defecto; `lg:grid-cols-3` si los campos son cortos (números, fechas, selects pequeños). Un campo largo (nombre, dirección, notas, imagen) ocupa la fila con `sm:col-span-2`/`col-span-full`.
- Orden: lo que identifica el registro primero, configuración/comportamiento después, lo informativo o raramente editado al final.
- **No** partir el formulario en columnas 2/3 + 1/3 por defecto: hace que secciones de distinto alto queden desalineadas y la página se lee "rara". Solo se justifica con una columna lateral de verdad secundaria (ej. resumen en vivo de lo que se está creando) — si no hay eso, una columna.
- Acciones al final, alineadas a la derecha: cancelar/descartar (`appearance="ghost" variant="secondary"`) y guardar (`variant="primary"`, `type="submit"`).

Referencias reales: `resources/views/configuration/general/edit.blade.php` (este patrón). Los formularios de Terminales POS, Roles y Usuarios usan todavía el estilo anterior (íconos sólidos de color, títulos en mayúsculas) — no copiarlos; se irán migrando.

## Paso 5 — El partial reusable: `resources/views/<módulo>/partials/form.blade.php`

Un solo archivo, editado una vez, usado sin cambios desde `create.blade.php` y `edit.blade.php` — nunca dos formularios casi idénticos mantenidos en paralelo. Para que funcione en los dos contextos:

- Todo valor sale con `{{ old('campo', $modelo->campo ?? '') }}` — `old()` gana en un reintento por validación fallida, `$modelo->campo ?? ''` cubre tanto "no existe `$modelo` porque es create" como "existe y es edit".
- Lo que solo aplica a creación (passwords obligatorias, valores por defecto) va detrás de `@unless(isset($modelo))`/`isset($modelo) ? ... : ...` — nunca un flag booleano aparte que haya que recordar pasar.
- Documenta en un comentario al principio del partial qué variables espera y cuáles son opcionales (mismo formato que ya usan `roles/partials/form.blade.php`/`users/partials/form.blade.php`) — el próximo que lo edite no debería tener que leer el controlador para saber qué le llega.

`create.blade.php`/`edit.blade.php` quedan reducidos a cáscara — título, `<form>`, `@include('<módulo>.partials.form')`, botones de acción. Nada de campos sueltos ahí; si aparece un campo directo en create/edit.blade.php en vez de en el partial, es una señal de que el partial no se está usando.

## Paso 6 — Usar siempre los componentes reales, nunca `<input>` crudo

Todo campo pasa por `x-ui.forms.*` (`Input`, `Select`, `Textarea`, `Checkbox`, `Radio`, `Toggle`, `FileInput` — catálogo completo en `docs/ui/forms.md`). Antes de escribir un `<input>`/`<select>` a mano por "no encaja", **lee `docs/ui/forms.md` primero** — casi seguro ya existe el prop que necesitás. Ejemplo real de un error ya cometido y corregido: se reimplementó a mano con Alpine el toggle de mostrar/ocultar contraseña, sin saber que `x-ui.forms.input type="password"` ya lo trae de fábrica (REQ-7.11) — buscá antes de reinventar.

Para toggles que muestran/ocultan otros campos (ej. "Requiere PIN" revela un campo de PIN), el patrón es una tarjeta clickeable completa con Alpine local (`x-data`), como en `terminals/form-fields.blade.php` — no hace falta Livewire para esto, es puramente visual.

## Paso 7 — Cuándo sí hace falta Livewire

Si un campo necesita recalcular algo consultando la base de datos según lo que el usuario elige en OTRO campo (ej. elegir un rol tiene que mostrar en el momento qué permisos ya trae ese rol, sin recargar la página), Alpine solo no alcanza — no tiene forma de ir al servidor. Ahí corresponde un componente Livewire en `app/Livewire/Shared/` (no en el namespace del módulo si es reusable entre formularios), embebido dentro de la sección correspondiente del partial. Referencia real: `app/Livewire/Shared/PermissionSelector.php` + `resources/views/livewire/shared/permission-selector.blade.php` — nota en particular cómo mezcla Alpine (para UI puramente client-side, como pestañas) con `wire:model.live` (solo en el único campo que de verdad necesita ir al servidor), en vez de convertir todo el formulario en Livewire porque un campo lo necesita.

## Checklist antes de dar el formulario por terminado

- [ ] ¿Cada sección es `x-ui.infolist.section :cols="0"` con ícono outline gris por tema real y título en tipo oración?
- [ ] ¿Las secciones van apiladas a lo ancho, con la grilla de campos dentro de cada una (no columnas 2/3 + 1/3 sin motivo)?
- [ ] ¿El partial funciona tal cual en create Y en edit, sin ningún `@if($esEdit)` que debería ser simplemente `isset($modelo)`?
- [ ] ¿Ningún campo usa `<input>`/`<select>` nativo sin haber revisado antes si `x-ui.forms.*` ya lo cubre?
- [ ] ¿`create.blade.php`/`edit.blade.php` son solo cáscara, sin campos sueltos fuera del `@include`?
- [ ] ¿Se probó el render real (no asumido) — al menos un render server-side de create Y edit antes de darlo por terminado, mismo criterio que se usó para `roles`/`users` (renderizar la vista completa y buscar strings clave, sin depender de abrir el navegador salvo que el usuario lo pida)?
