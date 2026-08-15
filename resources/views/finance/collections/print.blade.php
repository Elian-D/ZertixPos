<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>COBRO - {{ $payment->receipt_number }}</title>
    <style>
        /* RESET TOTAL PARA IMPRESIÓN */
        @media print {
            .no-print { display: none !important; }
            body, html { 
                background-color: white !important; 
                margin: 0 !important; 
                padding: 0 !important; 
                width: 100%;
            }
            .print-container { 
                box-shadow: none !important; 
                margin: 0 !important; 
                padding: 0 !important;
                width: 100% !important;
            }
        }

        /* VISTA EN PANTALLA (Estilo Visor PDF) */
        body { 
            display: flex; 
            justify-content: center; 
            margin: 0;
            padding: 20px;
            font-family: sans-serif;
        }

        .print-container { 
            background: white; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.3); 
            padding: 0;
            /* Si el ticket es de 72mm-80mm, esto ayuda a visualizarlo correctamente */
            min-width: 300px; 
        }

        .no-print {
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 100;
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 12px 24px;
            cursor: pointer;
            border: none;
            border-radius: 6px;
            font-weight: bold;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
            text-transform: uppercase;
            font-size: 13px;
            transition: opacity 0.2s;
        }

        .btn:hover {
            opacity: 0.9;
        }

        .btn-primary {
            background: #4f46e5;
            color: white;
        }

        .btn-secondary {
            background: #6b7280;
            color: white;
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()" class="btn btn-primary">
            IMPRIMIR RECIBO
        </button>
        <button onclick="window.close()" class="btn btn-secondary">
            CERRAR
        </button>
    </div>

    <div class="print-container">
        {{-- Incluimos el formato de ticket detallado que creamos anteriormente --}}
        @include('finance.collections.ticket')
    </div>

    <script>
        window.onload = function() {
            // Delay para asegurar que los estilos de fuente y tablas carguen antes del print
            setTimeout(() => {
                window.print();
            }, 600);
        }
    </script>
</body>
</html>