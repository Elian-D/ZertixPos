<?php

namespace App\Models\Inventory;

use App\Models\Accounting\AccountingAccount;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Warehouse extends Model
{
    use SoftDeletes;

    protected $table = 'warehouses';

    protected $fillable = [
        'code',
        'name',
        'type',
        'address',
        'description',
        'is_active',
        'accounting_account_id', // Nuevo campos
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /* ===========================
     |  CONSTANTES DE TIPOS
     =========================== */

    const TYPE_STATIC = 'static';

    const TYPE_MOBILE = 'mobile';

    const TYPE_POS = 'pos';

    public static function getTypes(): array
    {
        return [
            self::TYPE_STATIC => 'Estático (Bodega/Fábrica)',
            self::TYPE_POS => 'Estático (Tienda/Mostrador)',
            self::TYPE_MOBILE => 'Móvil (Camión/Ruta)',
        ];
    }

    /**
     * Eventos del Modelo
     */
    protected static function booted()
    {
        static::created(function (Warehouse $warehouse) {
            // La subcuenta contable solo se crea si la instalación tiene contabilidad
            // formal activa — CxC/CxP/Cotizaciones son base y no deben depender de esto.
            if (module_enabled('accounting.advanced')) {
                $warehouse->createAccountingAccount();
            }

            // Ejecutamos la lógica del código
            $prefix = strtoupper(substr(Str::slug($warehouse->name), 0, 3));
            $warehouse->updateQuietly([
                'code' => $prefix.'-'.$warehouse->id,
            ]);
        });
    }

    /**
     * Crea la subcuenta contable automáticamente bajo la cuenta de Inventarios (1.1.03)
     */
    public function createAccountingAccount(): void
    {
        if ($this->accounting_account_id) {
            return;
        }

        DB::transaction(function () {
            $parent = AccountingAccount::where('code', '1.1.03')->first();

            if (! $parent) {
                return;
            }

            // Buscar el último correlativo de este padre
            $lastChild = AccountingAccount::where('parent_id', $parent->id)
                ->orderBy('code', 'desc')
                ->first();

            if (! $lastChild) {
                $newCode = $parent->code.'.01';
            } else {
                $parts = explode('.', $lastChild->code);
                $lastPart = (int) end($parts);
                $newCode = $parent->code.'.'.str_pad($lastPart + 1, 2, '0', STR_PAD_LEFT);
            }

            $account = AccountingAccount::create([
                'parent_id' => $parent->id,
                'code' => $newCode,
                'name' => 'Inventario: '.$this->name,
                'type' => AccountingAccount::TYPE_ASSET,
                'level' => $parent->level + 1,
                'is_selectable' => true,
            ]);

            $this->updateQuietly(['accounting_account_id' => $account->id]);
        });
    }

    /* ===========================
     |  RELACIONES
     =========================== */

    /**
     * Relación con la cuenta contable de activo que representa este almacén
     */
    public function accountingAccount(): BelongsTo
    {
        return $this->belongsTo(AccountingAccount::class, 'accounting_account_id');
    }

    /**
     * Relación: Un almacén tiene muchos balances de stock
     */
    public function stocks()
    {
        return $this->hasMany(InventoryStock::class);
    }

    /* ===========================
     |  SCOPES
     =========================== */

    public function scopeActivos($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /* ===========================
     |  COMPORTAMIENTO Y LÓGICA
     =========================== */

    /**
     * Generar código automático basado en nombre e ID.
     */
    public function generateCode(): void
    {
        if (! $this->id) {
            $this->save();
        }

        // Tomamos las primeras 3 letras del slug del nombre (ej: "Bodega Central" -> "BOD")
        $prefix = strtoupper(substr(Str::slug($this->name), 0, 3));

        // Si el nombre es muy corto, Str::slug podría dar menos de 3 letras,
        // pero substr lo maneja bien.
        $this->code = $prefix.'-'.$this->id;

        $this->save();
    }

    /**
     * Alternar el estado activo/inactivo
     */
    public function toggleActivo(): bool
    {
        $this->is_active = ! $this->is_active;
        $this->save();

        return $this->is_active; // Ahora devuelve el nuevo estado (true/false)
    }

    /**
     * Formatear el nombre del tipo para la UI
     */
    protected function typeLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => self::getTypes()[$this->type] ?? $this->type,
        );
    }
}
