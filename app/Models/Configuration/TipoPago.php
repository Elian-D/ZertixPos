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
     * Diseño de badge por método de pago — mismo patrón que
     * Sale::getPaymentTypeStyles()/getPaymentTypeIcons() para Contado/Crédito,
     * pero para el método real (Efectivo/Tarjeta/Transferencia/etc.). Claves por
     * slug, igual que PRIORITY_ORDER. Incluye MIXTO y CREDITO aunque no sean
     * filas reales de TipoPago, porque sí aparecen como "método" en las vistas
     * (venta con pago dividido, venta a crédito) — ver
     * resources/views/sales/partials/table.blade.php.
     */
    public static function getBadgeStyles(): array
    {
        return [
            self::EFECTIVO               => 'bg-blue-100 text-blue-700 border-blue-200 ring-blue-500/10',
            'tarjeta-de-creditodebito'    => 'bg-purple-100 text-purple-700 border-purple-200 ring-purple-500/10',
            'transferencia-bancaria'      => 'bg-cyan-100 text-cyan-700 border-cyan-200 ring-cyan-500/10',
            'deposito-bancario'           => 'bg-teal-100 text-teal-700 border-teal-200 ring-teal-500/10',
            self::CHEQUE                  => 'bg-slate-100 text-slate-700 border-slate-200 ring-slate-500/10',
            self::CREDITO                 => 'bg-amber-100 text-amber-700 border-amber-200 ring-amber-500/10',
            self::MIXTO                   => 'bg-fuchsia-100 text-fuchsia-700 border-fuchsia-200 ring-fuchsia-500/10',
        ];
    }

    public static function getBadgeIcons(): array
    {
        return [
            self::EFECTIVO               => 'heroicon-s-banknotes',
            'tarjeta-de-creditodebito'    => 'heroicon-s-credit-card',
            'transferencia-bancaria'      => 'heroicon-s-arrows-right-left',
            'deposito-bancario'           => 'heroicon-s-building-library',
            self::CHEQUE                  => 'heroicon-s-document-text',
            self::CREDITO                 => 'heroicon-s-clock',
            self::MIXTO                   => 'heroicon-s-squares-2x2',
        ];
    }

    // Fallback para slugs no listados arriba (métodos nuevos que un admin cree
    // desde Configuración) — gris neutro en vez de romper la vista.
    public static function getDefaultBadgeStyle(): string
    {
        return 'bg-gray-100 text-gray-500 border-gray-200 ring-gray-500/10';
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
