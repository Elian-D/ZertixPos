<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Turno #{{ $session->id }}</title>
    <style>
        @media print {
            .no-print { display: none !important; }
            body, html { background-color: white !important; margin: 0 !important; padding: 0 !important; width: 100%; }
            .print-container { box-shadow: none !important; margin: 0 !important; padding: 0 !important; width: 100% !important; }
        }
        body { background-color: #525659; display: flex; justify-content: center; margin: 0; padding: 20px; font-family: sans-serif; }
        .print-container { background: white; box-shadow: 0 4px 15px rgba(0,0,0,0.3); padding: 0; }
        .no-print { position: fixed; top: 20px; left: 20px; z-index: 100; display: flex; gap: 10px; }
        .no-print button { padding: 12px 24px; cursor: pointer; background: #4f46e5; color: white; border: none; border-radius: 6px; font-weight: bold; box-shadow: 0 2px 4px rgba(0,0,0,0.2); }
        .no-print button.close { background: #6b7280; }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()">IMPRIMIR REPORTE</button>
        <button class="close" onclick="window.close()">CERRAR</button>
    </div>

    <div class="print-container">
        {!! $view !!}
    </div>

    <script>
        window.onload = function() {
            setTimeout(() => { window.print(); }, 500);
        }
    </script>
</body>
</html>
