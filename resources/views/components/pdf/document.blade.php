{{--
    x-pdf.document — esqueleto común de los documentos tamaño carta (v1.4.0 REQ-3.20 f).
    Ver docs/ui/pdf-documents.md.

    Pinta el <html> completo: estilos base, cabecera (logo y empresa a la izquierda;
    título, número, fecha y estado a la derecha), el slot y el pie. Cada documento
    arma su cuerpo con x-pdf.meta / x-pdf.section / x-pdf.totals / x-pdf.note y sus
    propias tablas .data-table.

    Funciona igual en DomPDF y en la vista previa del navegador: solo <table> para
    maquetar (DomPDF no soporta flex ni grid) y el pie va al final del flujo, no fixed.

    PROPS:
      title        — tipo de documento ("Factura", "Recibo de cobro"…).
      number       — correlativo (FAC-000112).
      date         — fecha ya formateada.
      dateLabel    — etiqueta de la fecha (default "Fecha").
      status       — texto del badge de estado (opcional; solo estados que importan).
      statusVariant— ok | bad | warn | info | muted.
      subtitle     — línea extra bajo el número (ej. tipo de NCF), opcional.
      logoSrc      — ruta local para DomPDF; si falta se usa tenant_asset() (vista previa).
      footer       — texto del pie, a la izquierda de la fecha de generación.
--}}
@props([
    'title',
    'number' => null,
    'date' => null,
    'dateLabel' => 'Fecha',
    'status' => null,
    'statusVariant' => 'muted',
    'subtitle' => null,
    'logoSrc' => null,
    'footer' => null,
])

@php
    $config = general_config();
    $logoSrc ??= $config->logo ? tenant_asset($config->logo) : null;

    // Datos vacíos se omiten (con su etiqueta), nunca "N/A" ni "no configurada".
    $location = collect([$config->direccion, $config->municipio?->name ?? $config->provincia?->name])->filter()->join(', ');
    $companyLines = collect([
        $config->tax_id ? ($config->tax_identifier_type?->value ?? 'RNC').': '.$config->tax_id : null,
        $location ?: null,
        collect([$config->telefono ? 'Tel. '.$config->telefono : null, $config->email])->filter()->join(' · ') ?: null,
    ])->filter();
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ trim($title.' '.$number) }}</title>
    <style>
        @page { margin: 34px 38px 38px 38px; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; color: #1f2937; font-size: 10.5px; line-height: 1.4; margin: 0; padding: 0; }
        table { border-collapse: collapse; }
        .w-full { width: 100%; }
        .num { text-align: right; white-space: nowrap; }
        .center { text-align: center; }
        .bold { font-weight: bold; }
        .muted { color: #64748b; }
        .small { font-size: 8.5px; }
        .mono { font-family: 'Courier New', Courier, monospace; }
        .discount { color: #b91c1c; }

        /* Cabecera */
        .doc-header { width: 100%; margin-bottom: 18px; }
        .doc-header td { vertical-align: top; }
        .company-name { font-size: 17px; font-weight: bold; text-transform: uppercase; color: #0f172a; margin-top: 4px; }
        .company-line { font-size: 9px; color: #64748b; }
        .doc-title { font-size: 15px; font-weight: bold; text-transform: uppercase; color: #0f172a; letter-spacing: 0.5px; }
        .doc-number { font-size: 13px; font-weight: bold; color: #1e293b; margin-top: 2px; }
        .doc-subtitle { font-size: 9px; color: #475569; margin-top: 1px; }
        .doc-date { font-size: 9px; color: #64748b; margin-top: 3px; }
        .doc-rule { border-bottom: 2px solid #1e293b; margin-bottom: 14px; }

        /* Caja de metadatos */
        .meta-box { width: 100%; background: #f8fafc; border: 1px solid #e2e8f0; margin-bottom: 16px; }
        .meta-box td { padding: 7px 12px; vertical-align: top; }
        .label { display: block; font-size: 7.5px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.4px; color: #64748b; margin-bottom: 1px; }
        .value { font-size: 10.5px; color: #0f172a; }

        /* Secciones */
        .section-title { font-size: 10.5px; font-weight: bold; text-transform: uppercase; color: #1e293b; border-bottom: 1.5px solid #1e293b; padding-bottom: 3px; margin: 18px 0 8px 0; }

        /* Tablas de datos — anchos en <colgroup> + table-layout fixed, y la
           alineación por clase con más especificidad que th/td: si no, el
           encabezado queda a la izquierda y el valor a la derecha (el desalineado
           que tenía el reporte de turno). */
        table.data-table { width: 100%; table-layout: fixed; margin-bottom: 8px; }
        table.data-table th { background: #1e293b; color: #ffffff; font-size: 7.5px; text-transform: uppercase; letter-spacing: 0.3px; padding: 6px 7px; text-align: left; vertical-align: middle; }
        table.data-table td { padding: 6px 7px; font-size: 10px; border-bottom: 1px solid #e5e7eb; vertical-align: top; word-wrap: break-word; }
        table.data-table tbody tr:nth-child(even) td { background: #f9fafb; }
        table.data-table tfoot td { font-weight: bold; background: #f1f5f9; border-top: 1.5px solid #1e293b; border-bottom: none; }
        table.data-table th.num, table.data-table td.num { text-align: right; }
        table.data-table th.center, table.data-table td.center { text-align: center; }
        .cell-sub { display: block; font-size: 8.5px; color: #64748b; }

        /* Totales */
        /* separate + spacing 0: con collapse DomPDF parte el border-top de la fila
           del total entre las dos celdas (la línea "cortada" del arqueo viejo). */
        table.totals { width: 100%; border-collapse: separate; border-spacing: 0; }
        table.totals td { padding: 3px 0; font-size: 10.5px; }
        table.totals td.t-label { color: #475569; }
        table.totals tr.grand td { font-size: 14px; font-weight: bold; color: #0f172a; padding-top: 5px; }
        table.totals td.grand-rule { border-top: 1.5px solid #1e293b; padding: 0; height: 2px; font-size: 1px; }
        table.totals tr.sep td { padding-top: 7px; }

        /* Notas y avisos */
        .note { border: 1px solid; padding: 8px 12px; margin: 10px 0 14px 0; font-size: 10px; }
        .note .label { margin-bottom: 2px; }
        .note-warn { background: #fffbeb; border-color: #fde68a; color: #78350f; }
        .note-alert { background: #fff7ed; border-color: #fdba74; color: #7c2d12; }
        .note-info { background: #f8fafc; border-color: #e2e8f0; color: #334155; }
        .note-danger { background: #fef2f2; border-color: #fecaca; color: #7f1d1d; }

        /* Badges — fondo suave + texto oscuro: legibles también en blanco y negro */
        .badge { display: inline-block; padding: 2px 8px; border-radius: 9px; font-size: 8px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.3px; }
        .badge-ok { background: #dcfce7; color: #14532d; }
        .badge-bad { background: #fee2e2; color: #7f1d1d; }
        .badge-warn { background: #fef3c7; color: #78350f; }
        .badge-info { background: #e0f2fe; color: #0c4a6e; }
        .badge-muted { background: #f1f5f9; color: #334155; }

        /* Firmas */
        .signature { width: 100%; margin-top: 46px; }
        .signature td { width: 50%; text-align: center; vertical-align: top; padding: 0 24px; }
        .signature-line { border-top: 1px solid #94a3b8; padding-top: 4px; font-size: 10px; color: #0f172a; }

        /* Pie */
        .doc-footer { margin-top: 26px; border-top: 1px solid #e5e7eb; padding-top: 7px; font-size: 8px; color: #94a3b8; }
    </style>
</head>
<body>
    <table class="doc-header">
        <tr>
            <td style="width: 58%;">
                @if($logoSrc)
                    <img src="{{ $logoSrc }}" style="max-height: 54px; max-width: 180px;"><br>
                @endif
                <div class="company-name">{{ $config->nombre_empresa }}</div>
                @foreach($companyLines as $line)
                    <div class="company-line">{{ $line }}</div>
                @endforeach
            </td>
            <td style="width: 42%; text-align: right;">
                <div class="doc-title">{{ $title }}</div>
                @if($number)
                    <div class="doc-number">{{ $number }}</div>
                @endif
                @if($subtitle)
                    <div class="doc-subtitle">{{ $subtitle }}</div>
                @endif
                @if($date)
                    <div class="doc-date">{{ $dateLabel }}: {{ $date }}</div>
                @endif
                @if($status)
                    <div style="margin-top: 5px;"><span class="badge badge-{{ $statusVariant }}">{{ $status }}</span></div>
                @endif
            </td>
        </tr>
    </table>
    <div class="doc-rule"></div>

    {{ $slot }}

    <table class="doc-footer w-full">
        <tr>
            <td>{{ collect([$config->nombre_empresa, $footer])->filter()->join(' · ') }}</td>
            <td class="num">Generado el {{ now()->format('d/m/Y h:i A') }}</td>
        </tr>
    </table>
</body>
</html>
