# Componente Toast (`x-ui.toasts`)

Sistema de notificaciones toast de ZertixPOS. Completamente reactivo vía eventos Alpine.js. Soporta cuatro tipos semánticos, duración configurable por tipo, cascada acumulativa con agrupación, swipe-to-dismiss táctil, pausa al hover, barra de progreso animada y persistencia entre redirecciones mediante `sessionStorage`.

---

## Tabla de Contenido

- [Estructura de Archivos](#estructura-de-archivos)
- [Instalación y Uso Global](#instalación-y-uso-global)
- [API de Eventos](#api-de-eventos)
- [Tipos y Apariencia](#tipos-y-apariencia)
- [Duración por Tipo](#duración-por-tipo)
- [Cascada y Agrupación](#cascada-y-agrupación)
- [Swipe-to-Dismiss](#swipe-to-dismiss)
- [Disparar desde JavaScript / Alpine](#disparar-desde-javascript--alpine)
- [Disparar desde Livewire](#disparar-desde-livewire)
- [Disparar desde PHP (Sesión Laravel)](#disparar-desde-php-sesión-laravel)
- [Persistencia en Redirecciones](#persistencia-en-redirecciones)
- [Comportamiento Visual](#comportamiento-visual)
- [Notas Adicionales](#notas-adicionales)

---

## Estructura de Archivos

```plaintext
app/
└── View/
    └── Components/
        └── Ui/
            └── Toasts.php
resources/
└── views/
    └── components/
        └── ui/
            └── toasts.blade.php
```

---

## Instalación y Uso Global

El componente vive **una sola vez** en `resources/views/components/app-layout.blade.php`, fuera de cualquier contenedor con `overflow: hidden` — a propósito, fuera del wrapper `.h-screen.overflow-hidden` del layout, para no depender de que ningún ancestro con `transform`/`filter` (que rompería su `position: fixed`) se agregue después dentro del contenido. Posición fija: esquina superior derecha, por encima de todo el contenido.

```html
{{-- En resources/views/components/app-layout.blade.php --}}
<x-ui.toasts />
```

> [!IMPORTANT]
> El componente debe vivir dentro del scope de Alpine.js (dentro del `<body>` y después de que Alpine esté cargado, vía `@livewireScripts`).

### Prop `suppress-validation-toast`

| Prop | Tipo | Default | Descripción |
|------|------|---------|-------------|
| `suppress-validation-toast` | `bool` | `false` | Omite el toast automático de `$errors->any()`. Úsalo en vistas donde `x-ui.forms.*` ya muestra el error inline bajo cada campo (ver `docs/ui/forms.md`), para no duplicar el mismo mensaje en dos lugares. |

```html
<x-ui.toasts :suppress-validation-toast="true" />
```

---

## API de Eventos

El sistema escucha tres eventos globales de `window`:

| Evento | Payload | Descripción |
|--------|---------|-------------|
| `notify` | `{ type, title, message, duration? }` | Muestra un toast inmediatamente |
| `notify-redirect` | `{ type, title, message, duration? }` | Guarda el toast en `sessionStorage` para mostrarlo tras una redirección |
| `remove-toast` | `id` (number) | Elimina un toast específico por su ID interno |

### Estructura del Payload

| Campo | Tipo | Requerido | Default | Descripción |
|-------|------|-----------|---------|-------------|
| `type` | `string` | No | `info` | Tipo visual: `success`, `error`, `warning`, `info` |
| `title` | `string` | Sí | — | Texto del encabezado del toast |
| `message` | `string` | Sí | — | Texto descriptivo del cuerpo |
| `duration` | `number` | No | según `type` (ver [Duración por Tipo](#duración-por-tipo)) | Duración en milisegundos antes de auto-cerrar. Si se omite, se usa el default del `type` |

---

## Tipos y Apariencia

| Tipo | Color del borde | Fondo | Ícono |
|------|----------------|-------|-------|
| `success` | `emerald-500` | `emerald-50` | `check-circle` |
| `error` | `red-500` | `red-50` | `x-circle` |
| `warning` | `amber-500` | `amber-50` | `exclamation-triangle` |
| `info` | `blue-500` | `blue-50` | `information-circle` |

Todos los tipos incluyen:
- Borde izquierdo de 3px con el color de la variante
- Ícono sólido de Heroicons en el color correspondiente
- Barra de progreso en la parte inferior que se vacía según la duración
- Botón de cierre manual (`x-mark`)

> ZertixPOS no tiene modo oscuro (ver `CLAUDE.md` — los tokens `.dark` están reservados pero nada los renderiza todavía), así que el componente no define ninguna clase `dark:*` para estos fondos/íconos.

---

## Duración por Tipo

Cada `type` tiene una duración por defecto — pensada para dar tiempo de lectura proporcional a la severidad del mensaje, sin que el usuario tenga que cerrarlo manualmente:

| Tipo | Duración por defecto | Razón |
| :--- | :--- | :--- |
| `success` | 5000ms | Confirmación rápida, no requiere acción |
| `info` | 5000ms | Mensaje informativo, lectura rápida |
| `warning` | 7000ms | Uso poco frecuente pero requiere más tiempo de lectura |
| `error` | 10000ms | Suele reportar un fallo de request (POST/GET); tiempo suficiente para leer sin cerrarlo a mano |

Los valores son fijos por tipo (no escalan según la longitud del mensaje). Siempre puedes sobreescribir con el campo `duration` en el payload.

---

## Cascada y Agrupación

Solo se muestran **hasta 3 toasts completos** a la vez, en cascada: el más reciente al frente (con contenido completo, ícono, barra de progreso y temporizador activo), y hasta 2 detrás, colapsados a una tira de color que asoma bajo el frente — ese asomo es intencional: comunica "hay más" sin necesidad de texto ni de `hover` (no disponible en móvil).

**Del 4to toast en adelante**, se agrupan en un chip contador (`+N más`) debajo de la cascada. Tocar el chip descarta todos los toasts agrupados de una vez (`dismissOverflow()`). No se agrega una vista expandible para revisarlos uno por uno — eso reintroduciría la fricción que este diseño busca evitar.

**Temporizador y posición:** el countdown de un toast solo corre mientras es el que está al frente de la cascada (posición 0). Si un toast queda colapsado detrás o agrupado en el chip, su temporizador se pausa por completo — nunca expira ni desaparece mientras está oculto. Al llegar al frente, su temporizador arranca desde el `duration` completo.

---

## Swipe-to-Dismiss

Gesto táctil **secundario** — el botón "×" sigue siendo el mecanismo principal de cierre, documentado y siempre visible. El swipe es un atajo opcional para quien lo descubra, solo disponible en el toast al frente de la cascada:

- Se puede deslizar en **ambos sentidos** (izquierda o derecha); superar 100px de desplazamiento cierra el toast.
- El gesto detecta el eje dominante (horizontal vs. vertical) en el primer movimiento, para no competir con el scroll vertical de la página cuando el usuario intenta hacer scroll cerca del toast.
- Iniciar un swipe pausa el temporizador; soltar sin llegar al umbral lo reanuda.

---

## Disparar desde JavaScript / Alpine

```javascript
// Desde cualquier evento o función JavaScript
window.dispatchEvent(new CustomEvent('notify', {
    detail: {
        type: 'success',
        title: '¡Guardado!',
        message: 'Los cambios han sido aplicados correctamente.',
    }
}));

// Con duración personalizada (8 segundos)
window.dispatchEvent(new CustomEvent('notify', {
    detail: {
        type: 'error',
        title: 'Error de conexión',
        message: 'No se pudo conectar con el servidor. Intenta de nuevo.',
        duration: 8000,
    }
}));
```

**Desde un elemento Alpine inline:**

```html
<button @click="$dispatch('notify', {
    type: 'success',
    title: '¡Copiado!',
    message: 'El código ha sido copiado al portapapeles.'
})">
    Copiar
</button>
```

---

## Disparar desde Livewire

Usa `$this->dispatch()` dentro de cualquier método del componente Livewire (Livewire 4 — el proyecto no usa el antiguo `$this->emit()` de Livewire 2):

```php
// En un componente Livewire
public function save(): void
{
    // ... lógica de guardado

    $this->dispatch('notify',
        type: 'success',
        title: '¡Guardado!',
        message: 'El registro fue actualizado correctamente.',
    );
}

public function delete(): void
{
    // ... lógica de eliminación

    $this->dispatch('notify',
        type: 'warning',
        title: 'Registro eliminado',
        message: 'Esta acción no puede deshacerse.',
        duration: 8000,
    );
}
```

Este es el mecanismo que usa `App\Livewire\Base\DataTable::notify()` (ver `docs/ui/datatables.md`) para cualquier acción que no navega (`restore()`, `forceDelete()`, `toggleActivo()`, `export()`, etc.) — un `session()->flash()` ahí no produciría ningún toast porque no hay redirección de página detrás.

---

## Disparar desde PHP (Sesión Laravel)

Para redirecciones de controlador o después de acciones de formulario clásicas, usa los flash de sesión de Laravel. El componente los detecta automáticamente al renderizar:

```php
// En un Controller
return redirect()->route('clients.index')
    ->with('success', '¡Cliente registrado exitosamente!');

return redirect()->back()
    ->with('error', 'No tienes permiso para realizar esta acción.');

return redirect()->route('dashboard')
    ->with('info', 'Tu sesión fue restaurada.');

return redirect()->back()
    ->with('warning', 'El archivo supera el límite de tamaño permitido.');
```

**Claves de sesión reconocidas:**

| Clave | Tipo del toast | Título automático | Duración |
|-------|---------------|-------------------|----------|
| `success` | `success` | `¡Éxito!` | 5000ms (default de `success`) |
| `error` | `error` | `Error` | 10000ms (default de `error`) |
| `info` | `info` | `Información` | 5000ms (default de `info`) |
| `warning` | `warning` | `Advertencia` | 7000ms (default de `warning`) |

**Errores de validación de Laravel:**

Si el bag `$errors` tiene contenido (tras un formulario con `validate()`), se muestra automáticamente el primer error (`type: 'error'`, duración 10000ms) con el título indicando cuántos errores adicionales hay:

```
// 1 error  → título: "Error de validación"
// 3 errores → título: "Error de validación (+2 más)"
```

Si la vista ya muestra el error inline bajo cada campo (`x-ui.forms.*`, ver `docs/ui/forms.md`), pasa `:suppress-validation-toast="true"` a `<x-ui.toasts>` para no duplicar el mensaje entre el campo y el toast.

---

## Persistencia en Redirecciones

Para mostrar un toast **después** de una redirección JavaScript (como `window.location.href`), usa el evento `notify-redirect`. El toast se guarda en `sessionStorage` (clave `zertix_pending_toast`) y se muestra 300ms después de que la nueva página cargue:

```javascript
// Guardar toast para mostrar después de redirigir
window.dispatchEvent(new CustomEvent('notify-redirect', {
    detail: {
        type: 'success',
        title: '¡Turno cerrado!',
        message: 'El cierre de caja se registró correctamente.',
    }
}));

// Luego redirigir
window.location.href = '/app/sales/pos/sessions';
```

---

## Comportamiento Visual

### Animaciones

El montaje/desmontaje, la apertura y la acumulación en cascada se controlan desde un único punto (el getter `cardStyle` de `toastItem`, vía `:style` inline) — no con `x-transition` de Alpine: mezclar los dos sistemas sobre las mismas propiedades (`opacity`/`transform`) dejaba la tarjeta con opacidad 0 tras la promoción al frente de la cascada.

| Estado | Efecto |
|--------|--------|
| Entrando / saliendo | `opacity: 0` + `translateX(24px)` (transición `transform`/`opacity` 0.4s) |
| Abierto (frente de la cascada) | `opacity: 1` + `translateX(0)`, con el espaciado (`margin-bottom`) reducido si hay cascada activa |
| Acumulado (detrás, colapsado) | `max-height: 14px`, ligero `translateY`/`scale` según profundidad, `opacity` decreciente |

### Barra de Progreso

La barra inferior se vacía linealmente desde el 100% hasta el 0% en el tiempo definido por `duration`. Se actualiza cada 10ms para una animación suave.

### Pausa al Hover (solo desktop, no bloqueante)

Al pasar el cursor sobre el toast al frente de la cascada, el temporizador se pausa. Al salir, el conteo continúa desde donde se detuvo. En móvil no hay `hover` — ahí la pausa ocurre al iniciar un swipe (ver [Swipe-to-Dismiss](#swipe-to-dismiss)), y el botón "×" sigue disponible como cierre inmediato sin depender de ningún gesto.

### Cierre Manual

El botón `×` cierra el toast inmediatamente con la animación de salida. El intervalo del temporizador se limpia automáticamente. Es el mecanismo principal de cierre — swipe es un atajo opcional adicional, no un reemplazo.

### Múltiples Toasts

Ver [Cascada y Agrupación](#cascada-y-agrupación): máximo 3 toasts completos visibles, el resto se agrupa en un chip contador.

---

## Notas Adicionales

- El componente usa `sessionStorage` (no `localStorage`) para la persistencia entre redirecciones. Los datos se eliminan automáticamente al leerlos — no persisten entre sesiones del navegador.
- El ID interno de cada toast se genera con `Date.now() + Math.random()` para garantizar unicidad incluso si se disparan múltiples toasts en el mismo milisegundo.
- El componente es `pointer-events-none` en su contenedor padre para no bloquear clics en el contenido subyacente. El toast al frente de la cascada restaura `pointer-events-auto`; los colapsados detrás quedan `pointer-events-none` (no son interactivos).
- El orden de aparición es LIFO (último en entrar, al frente de la cascada). Los toasts nuevos aparecen encima de los anteriores.
- El chip de agrupación (`+N más`) usa `x-cloak` además de `x-show`, ya que es el único elemento del componente presente en el HTML estático desde el primer render (antes de que Alpine inicialice) — sin `x-cloak` parpadearía visible un instante. Si agregas nuevos elementos `x-show` a este componente que existan en el HTML estático (no generados dinámicamente dentro de un `x-for`), agrégales `x-cloak` también. La regla `[x-cloak] { display: none !important; }` ya está definida globalmente en `resources/css/app.css`.
