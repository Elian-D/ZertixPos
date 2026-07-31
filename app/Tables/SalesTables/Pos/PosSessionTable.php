<?php

namespace App\Tables\SalesTables\Pos;

class PosSessionTable
{
    /**
     * Definición de todas las columnas disponibles para las sesiones de POS.
     */
    public static function allColumns(): array
    {
        return [
            'id'                => 'ID Turno',
            'terminal_id'       => 'Terminal/Caja',
            'opened_by_user_id' => 'Abierto Por',
            'closed_by_user_id' => 'Cerrado Por',
            'status'            => 'Estado',
            'opened_at'         => 'Fecha Apertura',
            'closed_at'         => 'Fecha Cierre',
            'opening_balance'   => 'Balance Inicial',
            'closing_balance'   => 'Balance Final (Arqueo)',
            'expected_balance'  => 'Balance Esperado',
            'difference'        => 'Diferencia', // <--- NUEVO
            'notes'             => 'Notas/Observaciones',
            'created_at'        => 'Fecha Registro',
        ];
    }

    /**
     * Columnas visibles por defecto en escritorio.
     * Mostramos el flujo completo del dinero y tiempos.
     */
    public static function defaultDesktop(): array
    {
        return [
            'terminal_id',
            'opened_by_user_id',
            'closed_by_user_id',
            'status',
            'opened_at',
            'opening_balance',
            'closing_balance',
            'closed_at',
        ];
    }

    /**
     * Columnas críticas para móviles.
     * En móvil priorizamos quién, dónde y si está abierta.
     */
    public static function defaultMobile(): array
    {
        return [
            'terminal_id',
            'opened_by_user_id',
            'status',
            'opening_balance',
        ];
    }
}