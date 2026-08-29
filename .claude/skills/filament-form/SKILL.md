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

## Paso 3 — Cada sección es una tarjeta con ícono + título

Este es el patrón visual completo — todas las secciones de todos los formularios del sistema lo siguen, cópialo tal cual, no lo reinventes por formulario:

```blade
<section class="bg-white border border-gray-100 rounded-2xl shadow-sm p-5 sm:p-6">
    <div class="flex items-center gap-2 mb-6 border-b border-gray-100 pb-3">
        <x-heroicon-s-{icono} class="w-5 h-5 text-zertix-secondary" />
        <h3 class="font-bold text-gray-800 uppercase text-xs tracking-wider">{Título de la Sección}</h3>
    </div>

    {{-- campos de la sección --}}
</section>
```

El ícono se elige por **tema real de la sección**, no al azar ni siempre el mismo: `heroicon-s-identification`/`heroicon-s-user` para datos de identificación, `heroicon-s-cog-6-tooth`/`heroicon-s-adjustments-horizontal` para configuración, `heroicon-s-key` para permisos/seguridad, `heroicon-s-currency-dollar`/`heroicon-s-building-library` para finanzas, `heroicon-s-printer` para hardware, `heroicon-s-tag` para descuentos/precios — si ninguno de estos calza, busca el heroicon sólido (`heroicon-s-*`) más literal al concepto de la sección antes de conformarte con uno genérico.

Referencias reales ya en el repo, revisalas antes de improvisar algo distinto:
- `resources/views/sales/pos/terminals/partials/form-fields.blade.php` — formulario grande y pesado, toggles con tarjetas clickeables completas.
- `resources/views/roles/partials/form.blade.php` y `resources/views/users/partials/form.blade.php` — formularios chicos, con un componente Livewire embebido dentro de una sección.

## Paso 4 — Decidir columnas por peso real del formulario

- **1-2 secciones cortas** (pocos campos, nada que amerite scroll largo) → una sola columna, secciones apiladas con `space-y-6`. Es el caso de Roles/Usuarios.
- **4+ secciones, con toggles/relaciones pesadas** → grid de 3 columnas en desktop (`grid-cols-1 lg:grid-cols-3 gap-6`), 2/3 para las secciones grandes a la izquierda, 1/3 para las cortas apiladas a la derecha. Es el caso de Terminales POS.

No hay una tercera opción por default — si el formulario está en el medio, contá los campos reales antes de decidir, no adivines.

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

- [ ] ¿Cada sección tiene un ícono que corresponde a su tema real, no genérico?
- [ ] ¿El partial funciona tal cual en create Y en edit, sin ningún `@if($esEdit)` que debería ser simplemente `isset($modelo)`?
- [ ] ¿Ningún campo usa `<input>`/`<select>` nativo sin haber revisado antes si `x-ui.forms.*` ya lo cubre?
- [ ] ¿`create.blade.php`/`edit.blade.php` son solo cáscara, sin campos sueltos fuera del `@include`?
- [ ] ¿Se probó el render real (no asumido) — al menos un render server-side de create Y edit antes de darlo por terminado, mismo criterio que se usó para `roles`/`users` (renderizar la vista completa y buscar strings clave, sin depender de abrir el navegador salvo que el usuario lo pida)?
