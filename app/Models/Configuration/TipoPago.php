<?php

namespace App\Models\Configuration;

use App\Models\Accounting\AccountingAccount;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TipoPago extends Model
{
    use HasFactory, SoftDeletes;

    // Constantes para lógica de negocio (Slugs)
    const EFECTIVO = 'efectivo';
    const TRANSFERENCIA = 'transferencia';
    const TARJETA = 'tarjeta';
    const CHEQUE = 'cheque';
    const CREDITO = 'credito'; // Para ventas que generan CxC

    // Pseudo-clave: no existe fila de TipoPago para "Mixto" (una venta con pago
    // dividido no tiene un solo método), pero las vistas sí necesitan mostrarlo
    // como badge — se le da su propio diseño para no caer en el estilo genérico.
    const MIXTO = 'mixto';

    protected $fillable = ['nombre', 'slug', 'estado', 'accounting_account_id'];

    protected $casts = ['estado' => 'boolean'];

    // Lista de métodos que el sistema necesita para funcionar
    public static function getSystemMethods(): array
    {
        return [self::EFECTIVO, self::TRANSFERENCIA, self::TARJETA, self::CHEQUE, self::CREDITO];
    }

    /**
     * Jerarquía de uso real en caja: los del día a día primero, los que requieren
     * conciliación bancaria manual después. Cualquier método nuevo no listado aquí
     * cae al final, en vez de romper el orden.
     */
    public const PRIORITY_ORDER = [
        'efectivo',
        'tarjeta-de-creditodebito',
        'transferencia-bancaria',
        'deposito-bancario',
        'cheque',
    ];

    /**
     * Ordena una colección de TipoPago según la jerarquía de uso (ver PRIORITY_ORDER).
     */
    public static function sortByPriority($tipoPagos)
    {
        return $tipoPagos
            ->sortBy(function ($tipoPago) {
                $position = array_search($tipoPago->slug, self::PRIORITY_ORDER, true);

                return $position === false ? count(self::PRIORITY_ORDER) : $position;
            })
            ->values();
    }

    /**
     * Helper para verificar si es efectivo sin importar el nombre o ID
     */
    public function isCash(): bool {
        return $this->slug === self::EFECTIVO;
    }

    /**
     * Colores hex para <x-ui.badge :hex="...">, un color por método de pago —
     * los 7 métodos no entran en las 6 variantes semánticas del badge
     * (success/warning/error/info/slate/primary) sin perder la distinción visual
     * entre métodos, así que usan `hex` en vez de `variant`. Claves por slug,
     * igual que PRIORITY_ORDER. Incluye MIXTO y CREDITO aunque no sean filas
     * reales de TipoPago, porque sí aparecen como "método" en las vistas (venta
     * con pago dividido, venta a crédito) — ver
     * resources/views/sales/partials/table.blade.php. Tomado del tono "600" de
     * cada familia de color de Tailwind.
     */
    public static function getBadgeHexColors(): array
    {
        return [
            self::EFECTIVO       => '#2563EB', // blue-600
            'tarjeta'            => '#9333EA', // purple-600 — slug real de TipoPagoSeeder (Str::slug('Tarjeta')), ver REQ-2.5 más abajo
            'transferencia'      => '#0891B2', // cyan-600 — slug real (Str::slug('Transferencia'))
            'deposito'           => '#0D9488', // teal-600 — slug real (Str::slug('Depósito'))
            self::CHEQUE          => '#475569', // slate-600
            self::CREDITO         => '#D97706', // amber-600
            self::MIXTO           => '#C026D3', // fuchsia-600
        ];
    }

    public static function getDefaultBadgeHex(): string
    {
        return '#6B7280'; // gray-500
    }

    public static function getBadgeIcons(): array
    {
        return [
            self::EFECTIVO       => 'heroicon-s-banknotes',
            'tarjeta'            => 'heroicon-s-credit-card',
            'transferencia'      => 'heroicon-s-arrows-right-left',
            'deposito'           => 'heroicon-s-building-library',
            self::CHEQUE          => 'heroicon-s-document-text',
            self::CREDITO         => 'heroicon-s-clock',
            self::MIXTO           => 'heroicon-s-squares-2x2',
        ];
    }

    public static function getDefaultBadgeIcon(): string
    {
        return 'heroicon-s-question-mark-circle';
    }

    /**
     * Corrección (REQ-2.5): la lista original de slugs protegidos
     * ('transferencia-bancaria', 'tarjeta-de-creditodebito') nunca coincidía con
     * los slugs reales que siembra TipoPagoSeeder (Str::slug('Transferencia') =
     * 'transferencia', Str::slug('Tarjeta') = 'tarjeta') — la protección para
     * esos dos métodos nunca se disparaba. El único método que el sistema
     * realmente necesita que no se pueda borrar es 'efectivo' — SaleService
     * lo asume implícitamente en varios puntos (ver SaleService::processPayments()/
     * generateSaleAccountingEntry(), $tipo->slug === 'efectivo'), el resto son
     * catálogo editable normal.
     */
    public function isSystemProtected(): bool
    {
        return $this->slug === self::EFECTIVO;
    }

    // Scopes para filtrar por estado
    public function scopeActivo($query)
    {
        return $query->where('estado', true);
    }

    public function scopeInactivo($query)
    {
        return $query->where('estado', false);
    }

    public function toggleEstado(): void
    {
        $this->estado = ! $this->estado;
        $this->save();
    }

        public function account()
    {
        return $this->belongsTo(AccountingAccount::class, 'accounting_account_id');
    }
}
