# Documentos PDF tamaño carta (`x-pdf.*`)

Diseño base de los documentos que el sistema genera en carta con DomPDF (v1.4.0 REQ-3.20 f). Es el equivalente de `x-ui.infolist.*` para papel: no es una plantilla única, sino piezas comunes que cada documento arma con su propia identidad.

## Documentos que lo usan

| Documento | Vista | Generado en | Vista previa en navegador |
|---|---|---|---|
| Reporte de turno | `sales/pos/sessions/formats/full.blade.php` | `PosSessionController::print()` | No |
| Factura | `sales/invoices/formats/full.blade.php` | `InvoicePrintService::generateLetterPDF()` | Sí (`InvoiceController`) |
| Recibo de cobro | `finance/collections/pdf.blade.php` | `CollectionPrintService::generateLetterPDF()` | Sí (`CollectionController`) |
| Cotización | `sales/quotes/formats/pdf.blade.php` | `QuotePrintService::generateLetterPDF()` | No |

**Fuera de este patrón:**
- **Tickets térmicos** (`formats/ticket.blade.php`, el ticket de devolución): otro ancho y otro formato.
- **`billing/invoice-pdf.blade.php`**: la factura de suscripción del lado central, con la marca de ZertixPOS.

## Las piezas

Componentes anónimos en `resources/views/components/pdf/`.

| Componente | Qué pinta |
|---|---|
| `x-pdf.document` | El `<html>` completo: estilos base, cabecera, el slot y el pie. Props: `title`, `number`, `date`, `dateLabel`, `status`, `statusVariant`, `subtitle`, `logoSrc`, `footer` |
| `x-pdf.meta` | Caja gris de datos en grilla (`:items`, `:cols` default 4). Cada item: `label`, `value` o `html`, `span`, `strong`, `sub` |
| `x-pdf.section` | Título de sección en mayúsculas con línea, más su contenido |
| `x-pdf.totals` | Totales a la derecha (`:rows` con `variant` `discount`, `grand` o `sep`) y el slot a la izquierda (forma de pago, notas) |
| `x-pdf.note` | Caja de nota: `warn` (notas), `alert` (pendiente), `danger` (anulado) o `info` (neutro) |

Las tablas de líneas se escriben en cada documento con `<table class="data-table">`, porque cada uno tiene sus propias columnas.

## Estructura de un documento

1. **Cabecera** (`x-pdf.document`):
   - A la izquierda, el logo, la empresa y sus datos.
   - A la derecha, el tipo de documento, el número, la fecha y el badge de estado si aplica.
   - Debajo, una línea gruesa.
2. **Metadatos** (`x-pdf.meta`): quién, con qué y cuándo (cliente, venta, condición, NCF, vendedor…).
3. **Avisos** (`x-pdf.note`): anulado, vencido o a crédito, justo después de los metadatos o donde aplique.
4. **Cuerpo**: tabla de líneas y secciones (`x-pdf.section`).
5. **Totales** (`x-pdf.totals`), con la forma de pago y las observaciones a la izquierda.
6. **Firmas** (`<table class="signature">`) solo donde se firma: factura a crédito y recibo.
7. **Pie**: la empresa y el tipo de comprobante a la izquierda, la fecha de generación a la derecha. Lo pinta `x-pdf.document`.

## Reglas de DomPDF (aprendidas a golpes)

- **Anchos en el `<th>` de la primera fila, nunca en `<colgroup>`.** DomPDF ignora `<col style="width">` y reparte las columnas en partes iguales. Esa era la causa de fondo del desalineado del reporte de turno viejo.
  ```blade
  <th class="num" style="width: 18%">Importe</th>
  ```
- **La alineación va por clase con suficiente especificidad.** `table.data-table th.num` sí funciona. Una clase suelta `.text-right` pierde contra `table.data-table th { text-align: left }`, y el encabezado queda a la izquierda mientras el valor queda a la derecha.
- **La línea del total es una fila propia de una sola celda** (`td.grand-rule`). Como `border-top` de dos celdas, DomPDF la dibuja con un escalón en la unión. `x-pdf.totals` ya lo hace solo.
- **Maquetar solo con `<table>`.** DomPDF no soporta flex ni grid. Tampoco usar `float` para columnas.
- **Logo por ruta local.** DomPDF no sigue URLs (`enable_remote` está apagado):
  - El servicio que genera el PDF pasa `$logoSrc = storage_path('app/public/'.$config->logo)`.
  - La vista previa del navegador no lo pasa, y `x-pdf.document` usa `tenant_asset()` (REQ-1.14).
- **El pie va al final del flujo, no `position: fixed`:** la misma vista se muestra en el navegador, donde un `fixed` taparía el contenido.
- **Una sola fuente:** Helvetica, que soporta tildes y ñ. Escribir con tildes ("Cotización", no "Cotizacion").
- **HTML dentro de `:items`:** las comillas dobles rompen el atributo del componente. Arma el arreglo en el bloque `@php` y pásalo como variable (`:items="$metaItems"`).

## Criterio visual

- **Un solo color de acento:** slate oscuro `#1e293b` para la línea de la cabecera, los títulos de sección y los encabezados de tabla. Así se lee igual impreso en blanco y negro.
- **Badges solo para estados que importan:** anulada, cuadrada/faltante, vencida, estado de la cotización. Llevan fondo suave y texto oscuro, y nunca son decoración.
- **Datos vacíos se omiten**, etiqueta incluida. Nunca "N/A", "S/N" ni "Dirección no configurada": `x-pdf.meta` descarta solo los items vacíos, y la cabecera arma solo las líneas de empresa que existen. En una celda de tabla vacía se usa "—".
- **Referencias cruzadas con los números del sistema** (REQ-3.19):
  - La factura muestra su venta `VTA-…` (y su `CXC-…` si fue a crédito).
  - El recibo muestra la `CXC-…` y la venta.
  - La cotización muestra la venta si fue convertida.
- **Cantidades sin decimales de relleno** (`rtrim` de ceros), montos siempre con 2 decimales y el símbolo de `config('regional.currency_symbol')`.

## Probar

Genera el PDF real en `demo`, no solo la vista HTML: DomPDF se comporta distinto que el navegador. Cubre los casos que cambian el documento: con y sin NCF, contado y crédito, anulado, cotización vigente y vencida, y turno con ventas a crédito.
