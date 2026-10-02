---
name: filament-pdf
description: Usar este skill cuando el usuario pida crear o rehacer un documento imprimible tamaño carta/A4/PDF de un registro — frases como "hazme el PDF de X", "el formato carta de X", "el reporte en PDF", "la factura/recibo/cotización en carta", "rediseña el documento de X", o cualquier vista que se genere con DomPDF (Pdf::loadView). Preferir siempre este skill sobre escribir un <html> con su propio <style> a mano. NO se activa para tickets térmicos (58/80mm), vistas show (eso es /filament-show) ni formularios (/filament-form).
---

# Documento PDF tamaño carta (`x-pdf.*`)

Hermano de `/filament-show` para papel. Los documentos carta del sistema comparten un diseño base (cabecera, metadatos, secciones, tabla, totales, notas y pie) armado con componentes `x-pdf.*`, y cada uno conserva su identidad: qué secciones tiene y en qué orden. **Antes de escribir nada, lee `docs/ui/pdf-documents.md`**: ahí están las piezas, sus props y las reglas de DomPDF aprendidas con errores reales.

## Paso 1 — Leer los datos reales

- Lee el servicio o controlador que genera el PDF (`*PrintService::generateLetterPDF()` o el `print()` del controlador): qué relaciones carga y qué variables pasa.
- Si la vista también se usa como vista previa en el navegador (`view(...)->render()` en un controlador), no debe depender de nada que solo exista en PDF.
- No inventes campos: lee el modelo.

## Paso 2 — Decidir la identidad del documento

Responde antes de maquetar:
- **Título y número** que van en la cabecera. El número es el correlativo con prefijo (`FAC-…`, `COT-…`, `TRN-…`), nunca el id con ceros.
- **Estado que importa** como badge (anulada, vencida, cuadrada/faltante). Si el estado no le cambia nada al lector, no hay badge.
- **Metadatos:** quién, con qué documento relacionado y en qué condiciones. Incluye las referencias cruzadas (venta, CxC, cotización).
- **Secciones del cuerpo** y sus columnas.
- **Totales**, y qué va a su izquierda (forma de pago, notas).
- **Firmas**, solo si el documento se firma de verdad.

## Paso 3 — Armar con las piezas

```blade
@php
    $metaItems = [ /* arreglos label/value; los vacíos se omiten solos */ ];
    $totalRows = [ /* ... 'variant' => 'grand' para el total */ ];
@endphp

<x-pdf.document title="Factura" :number="$doc->number" :date="..." :status="..." statusVariant="bad" :logoSrc="$logoSrc ?? null" footer="...">
    <x-pdf.meta :items="$metaItems" />
    {{-- avisos: x-pdf.note --}}
    <table class="data-table">
        <thead><tr>
            <th class="center" style="width: 9%">Cant.</th>
            <th style="width: 55%">Descripción</th>
            <th class="num" style="width: 18%">Precio unit.</th>
            <th class="num" style="width: 18%">Importe</th>
        </tr></thead>
        <tbody>...</tbody>
    </table>
    <x-pdf.totals :rows="$totalRows">forma de pago / notas</x-pdf.totals>
</x-pdf.document>
```

Referencias reales: `sales/invoices/formats/full.blade.php` (el más completo), `sales/pos/sessions/formats/full.blade.php` (varias tablas y arqueo), `finance/collections/pdf.blade.php` (corto, con firmas), `sales/quotes/formats/pdf.blade.php` (columna condicional y vigencia).

## Paso 4 — El servicio pasa el logo como ruta local

```php
$config = general_config();
$logoSrc = $config->logo ? storage_path('app/public/'.$config->logo) : null;
return Pdf::loadView('...', compact('modelo', 'logoSrc'))->setPaper('letter', 'portrait');
```

## Checklist antes de darlo por terminado

- [ ] ¿Ningún `<style>` propio? Si falta un estilo, se agrega una vez en `x-pdf.document`, no en la vista.
- [ ] ¿Los anchos están en los `<th>` de la primera fila y no en `<colgroup>`?
- [ ] ¿Las columnas numéricas usan `class="num"` tanto en el `th` como en el `td`?
- [ ] ¿Ningún "N/A", "S/N" ni texto de relleno para datos vacíos?
- [ ] ¿Usa el número con prefijo y las referencias cruzadas a los documentos relacionados?
- [ ] ¿Se generó el PDF real en `demo` y se miró, cubriendo los casos que cambian el documento (anulado, crédito, con o sin NCF, sin logo)? La vista HTML sola no alcanza, porque DomPDF se comporta distinto que el navegador.
