# Política de Descuentos — Ítem vs. Global, Regla de Exclusión vs. Cascada

**Fecha:** 2026-08-16
**Motivo:** contrastar contra el código real una discusión de diseño (sostenida en otra sesión, con aportes de experiencia real de mercado dominicano) sobre cómo deben convivir el descuento por ítem y el descuento global en el TPV, y documentar qué falta para soportar una segunda política (`cascade`) el día que un cliente la pida.

**Veredicto corto: ya está construido, y está bien construido.** No es una propuesta a implementar — es una auditoría de algo que ya se resolvió en la Fase 11.2/11.2.5 de `POS-Interfaz.md`, con la Regla de Exclusión ya operativa en las dos capas que importan (frontend para feedback inmediato al cajero, backend como fuente de verdad que no confía en el frontend). Este documento confirma eso con cita exacta de código, y deja registrado el único punto real pendiente: el selector de política en la UI, y el trabajo concreto para activar `cascade` cuando haga falta.

---

## 1. La pregunta de fondo, ya resuelta

Dos reglas de negocio distintas, confundidas si se maneja un solo tope de descuento:

- **Descuento por ítem** (tope bajo, ej. 5%): el cajero lo usa puntual — una caja golpeada, igualar una oferta rápida.
- **Descuento global** (tope más alto, ej. 10%): se aplica a toda la factura — cliente VIP, venta al por mayor, autorización de supervisor.

Si conviven bajo un solo campo, un cajero puede aplicarle el tope alto a un solo producto y destruir el margen de esa línea. **Ya no es así en este código** — están separados desde la migración `2026_08_01_150000_split_discount_limits_on_pos_terminals_table.php`.

El segundo problema —qué pasa cuando un ítem con descuento propio recibe además una porción del descuento global— tiene dos modelos estándar en la industria:

- **Cascada:** el global se aplica sobre el subtotal restante *después* del descuento de ítem, repartido entre **todas** las líneas (incluidas las que ya tenían descuento propio).
- **Exclusión:** "descuentos no acumulables" — el global se reparte únicamente entre las líneas que **no** tienen descuento propio. Confirmado como el estándar real de comercio en República Dominicana (supermercados, ferreterías, electrodomésticos) — no es una suposición de IA, es experiencia de mercado real contrastada.

**El sistema implementa Exclusión, no Cascada, y `Cascada` queda documentada pero deliberadamente sin construir hasta que haya demanda real.**

---

## 2. Estado real del código, con cita exacta

### 2.1 — Configuración por terminal (topes separados)

[`PosTerminal.php:31-35`](app/Models/Sales/Pos/PosTerminal.php:31) — campos separados y con cast propio:

```php
'allow_item_discount',
'allow_global_discount',
'max_item_discount_percentage',
'max_global_discount_percentage',
'discount_policy',
```

La migración que los creó, [`2026_08_01_150000_split_discount_limits_on_pos_terminals_table.php:13-27`](database/migrations/2026_08_01_150000_split_discount_limits_on_pos_terminals_table.php:13), lo deja explícito en su propio comentario:

> *"un solo `max_discount_percentage` no alcanza — el motor de ventas necesita distinguir el tope de descuento por ítem del tope de descuento global... `discount_policy` queda fijo en 'exclusion' (única política operativa por ahora, sin selector en la UI) — el valor 'cascade' está reservado para una política alternativa de reparto documentada pero no implementada."*

Es decir: la puerta que Gemini proponía dejar abierta ("un campo `discount_policy` en base de datos, sin construir el resto todavía") **ya estaba abierta antes de esta conversación** — alguien ya tomó esa decisión de diseño en la Fase 11.2.5.

### 2.2 — Cálculo en el carrito (frontend, feedback inmediato)

`recalculateTotals()` en [`pos-workspace.blade.php:266-334`](resources/views/livewire/sales/pos/pages/pos-workspace.blade.php:266):

1. Primero calcula el descuento de cada ítem individual (líneas 270-280).
2. Después, el reparto del global — [líneas 298-299](resources/views/livewire/sales/pos/pages/pos-workspace.blade.php:298):
   ```js
   const eligibleItems = this.items.filter(item => (item.discount_percentage || 0) === 0);
   const eligibleBase = eligibleItems.reduce((sum, item) => sum + (item.price * item.quantity), 0);
   ```
   Esto **es** la Regla de Exclusión: filtra a las líneas sin descuento propio, y solo reparte el monto global proporcionalmente entre ellas ([líneas 301-309](resources/views/livewire/sales/pos/pages/pos-workspace.blade.php:301)).
3. El comentario de las líneas 282-292 documenta la misma decisión que la migración — incluye una nota explícita de que `cascade` "queda documentada pero sin implementar ni selector en la UI hasta que haya demanda real".

### 2.3 — Validación en el servidor (fuente de verdad)

`SaleService::validateDiscounts()`, [`SaleService.php:357-414`](app/Services/Sales/SalesServices/SaleService.php:357) — el backend **no confía** en los totales que manda el frontend, revalida todo:

- Descuento por ítem: valida `discount_percentage` de cada línea contra `max_item_discount_percentage` ([líneas 376-391](app/Services/Sales/SalesServices/SaleService.php:376)).
- Descuento global: reconstruye el monto global como `discount_total` (declarado) menos la suma de descuentos de ítem ya validados — [línea 396](app/Services/Sales/SalesServices/SaleService.php:396) — y valida el % resultante contra el remanente de líneas elegibles ([líneas 403-411](app/Services/Sales/SalesServices/SaleService.php:403)), replicando la misma regla del frontend.
- El docblock de las [líneas 337-356](app/Services/Sales/SalesServices/SaleService.php:337) explica por qué `discount_percentage` es la única señal confiable (nunca la toca el reparto del global) y por qué nunca se lee `discount_amount` directo (mezcla ambos orígenes).

Este es exactamente el patrón correcto que se esperaría: **JS para UX inmediata, PHP como autoridad final**, sin que un payload manipulado a mano pueda saltarse el límite.

### 2.4 — UI: sin tooltip explicativo (pendiente real, menor)

Lo único de la conversación original que **no** está construido: el tooltip/texto de ayuda junto al input de descuento global explicando "se aplica solo a productos sin descuento individual". Confirmado — [`cart-item.blade.php`](resources/views/livewire/sales/pos/pages/pos-workspace/partials/cart-item.blade.php) tiene el input de descuento por ítem sin ningún texto de ayuda, y [`totals-summary.blade.php`](resources/views/livewire/sales/pos/pages/pos-workspace/partials/totals-summary.blade.php)/[`checkout-modal.blade.php`](resources/views/livewire/sales/pos/pages/pos-workspace/checkout-modal.blade.php) solo muestran el monto total de descuento, sin explicar la regla. Hoy la explicación vive únicamente en comentarios de código — invisible para el cajero.

Es cosmético y de bajo riesgo (nadie pierde dinero por no tener el tooltip, la regla ya se aplica igual), pero vale la pena resolverlo cuando se toquen estos mismos inputs en la Fase 7 de `v1.2.0.md` (§7.5, migración a `x-ui.forms.*`, que ya trae hint/ícono de ayuda por componente) — no como fase aparte.

---

## 3. Hallazgo no pedido, pero del mismo tipo que ya se audita en este proyecto

`discount_policy` es una columna sembrada, `fillable`, con default `'exclusion'` — pero **no se lee en ningún punto de decisión del código**. Confirmado con grep en `app/` y `resources/`: la única referencia fuera del modelo y la migración es un comentario (`pos-workspace.blade.php:292`), nunca una condición `if`. Ni `recalculateTotals()` ni `validateDiscounts()` la consultan — ambos ejecutan Exclusión de forma incondicional, sin ramificar sobre el valor de la columna.

**A diferencia de `DocumentType.is_active`** (documentado en `politica-soft-deletes.md` y eliminado en `v1.2.0.md` REQ-1.1 por no tener ningún plan real detrás), acá **sí hay intención documentada y explícita** de para qué existe la columna — la migración y el comentario del blade lo dicen con todas las letras. No es una columna huérfana, es una semilla consciente. Recomendación: **dejarla como está** — es una sola columna, bajo costo de mantenerla inerte, y evita rehacer una migración cuando llegue el cliente que pida `cascade`. Se deja anotado acá únicamente para que quede clara la diferencia entre "columna sembrada a propósito, con roadmap" y "columna huérfana" — no para actuar sobre ella ahora.

---

## 4. Qué falta, en concreto, para activar `cascade`

La arquitectura actual sí logra lo que buscaba la conversación original — encapsular el cálculo para no reescribir el sistema el día de mañana — pero **no es un cambio de 10 minutos**. Es acotado y de bajo riesgo, no trivial. Puntos reales de cambio:

### 4.1 — La diferencia matemática exacta entre las dos políticas

El validador de **límite** (`SaleService::validateDiscounts()`) no cambiaría — `remaining = grossTotal - itemDiscountTotal` es el mismo número bajo cualquiera de las dos políticas, porque solo representa "cuánto del descuento total no se explica por descuentos de ítem". Lo que sí cambia es **qué líneas reciben el reparto y sobre qué base**:

| | Exclusión (hoy) | Cascada (a construir) |
|---|---|---|
| Líneas que reciben porción del global | Solo las que tienen `discount_percentage == 0` | **Todas**, incluidas las que ya tenían descuento propio |
| Base sobre la que se calcula el monto global | Suma bruta de las líneas elegibles (`eligibleBase`) | Neto total del carrito ya con descuento de ítem aplicado (`bruto - itemDiscounts`) |

### 4.2 — Cambios de código necesarios

1. **UI del formulario de terminal** (`create.blade.php`/`edit.blade.php` de POS Terminal) — hoy no existe campo para elegir política; `StorePosTerminalRequest.php:32-33` lo dice explícito: *"`discount_policy` no es campo de formulario — queda fijo en 'exclusion'"*. Agregar el selector (`<select>` con las dos opciones) y la regla de validación correspondiente.
2. **Frontend** — en `recalculateTotals()`, bifurcar sobre `this.discountPolicy` (habría que pasarlo al `x-data` de Alpine igual que ya se pasan `allowItemDiscount`/`maxItemDiscountPct`): bajo `cascade`, la base del reparto pasa a ser el neto total, no solo `eligibleItems`.
3. **Backend** — `validateDiscounts()` no necesita cambiar su matemática de validación de tope (ver 4.1), pero si se quiere blindar el reparto por línea (no solo el total) contra un payload manipulado, conviene que el backend recalcule el reparto completo según la política del terminal, en vez de confiar en los `discount_amount` que ya vienen calculados por línea en el payload — hoy no se re-deriva el reparto, solo se valida el total. Es una nota de seguridad razonable para cuando exista más de una política real, no un problema mientras solo exista `exclusion`.
4. **Activar la lectura de `discount_policy`** en ambos puntos de decisión (2) y (3) — hoy la columna existe pero, como se documentó en la sección 3, nadie la consulta.

### 4.3 — Estimación honesta

No son 10 minutos — son dos métodos de cálculo a bifurcar, un campo de formulario nuevo con su validación, y sobre todo **probar los tres escenarios reales** antes de dar por bueno el cambio (ningún ítem con descuento propio, todos los ítems con descuento propio, mezcla de ambos) tanto en frontend como en backend, porque un desajuste entre cómo reparte el JS y cómo valida el PHP produciría rechazos de venta que el cajero no entendería. Realista: un bloque de trabajo enfocado, no una tarea de minutos — pero sí confirma el punto real de la conversación original: la arquitectura actual no exige rediseñar el motor de ventas, exige extender dos funciones ya aisladas y acotadas. Eso es mérito real de cómo quedó construida la Fase 11.2.5, no una promesa vacía.

---

## 5. Conclusión

No hay nada que implementar hoy. El sistema ya resuelve correctamente el problema real (límites separados + Regla de Exclusión, validada en dos capas, alineada con el estándar de mercado dominicano). Lo único pendiente y de bajo riesgo es el tooltip de UI (§2.4), a resolver de forma natural en la Fase 7 de `v1.2.0.md` cuando se toquen esos mismos inputs — no antes, no como fase aparte. `cascade` se activa el día que aparezca un cliente real que la necesite, siguiendo el plan de la sección 4 — hasta entonces, no hay ninguna razón para tocar este código.
