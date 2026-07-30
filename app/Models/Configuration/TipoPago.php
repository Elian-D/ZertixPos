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

    public function isSystemProtected(): bool
    {
        // Lista de slugs que no se pueden borrar ni editar nombre
        $protected = ['efectivo', 'transferencia-bancaria', 'cheque', 'tarjeta-de-creditodebito', 'credito'];
        return in_array($this->slug, $protected);
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
