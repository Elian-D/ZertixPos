<?php

namespace App\DTOs\Sales;

/**
 * Contenedor de datos para transportar el contexto del POS
 * hacia el motor de ventas (SaleService).
 */
class PosContext
{
    public function __construct(
        public int $terminal_id,
        public int $session_id,
        public int $cash_account_id,                 // Cuenta contable de la caja de la terminal
        public int $warehouse_id,                    // Almacén vinculado a la terminal
        public string $sale_origin = 'pos',          // 'pos' | 'backoffice'
        public bool $is_walkin_customer = false,     // True si es un cliente casual de mostrador
    ) {}

    /**
     * Crea una instancia de contexto a partir de una sesión de caja activa
     */
    public static function fromSession($session, bool $isWalkIn = false): self
    {
        return new self(
            terminal_id: $session->terminal_id,
            session_id: $session->id,
            cash_account_id: $session->terminal->cash_account_id,
            warehouse_id: $session->terminal->warehouse_id,
            sale_origin: 'pos',
            is_walkin_customer: $isWalkIn
        );
    }
}
