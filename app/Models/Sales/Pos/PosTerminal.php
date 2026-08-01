<?php

namespace App\Models\Sales\Pos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Inventory\Warehouse;
use App\Models\Accounting\AccountingAccount;
use App\Models\Sales\Ncf\NcfType;
use App\Models\Clients\Client;
use App\Models\Sales\Sale;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class PosTerminal extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'warehouse_id',
        'cash_account_id',
        'default_ncf_type_id',
        'default_client_id',
        'is_mobile',
        'printer_format',
        'is_active',
        'access_pin',   // Añadido
        'requires_pin', // Añadido
        'allow_item_discount',
        'allow_global_discount',
        'max_item_discount_percentage',
        'max_global_discount_percentage',
        'discount_policy',
    ];

    protected $casts = [
        'is_mobile'                        => 'boolean',
        'is_active'                        => 'boolean',
        'requires_pin'                     => 'boolean', // Añadido
        'allow_item_discount'              => 'boolean',
        'allow_global_discount'            => 'boolean',
        'max_item_discount_percentage'     => 'float',
        'max_global_discount_percentage'   => 'float',
    ];

    protected $hidden = [
        'access_pin', // Ocultar de arrays/JSON para seguridad
    ];

    /**
     * Eventos del Modelo
     */
    protected static function booted()
    {
        static::created(function (PosTerminal $terminal) {
            // Generar cuenta contable de efectivo si no se especificó una
            if (!$terminal->cash_account_id) {
                $terminal->createAccountingAccount();
            }
        });
    }

    /**
     * Crea la subcuenta contable automáticamente bajo Caja y Bancos (1.1.01)
     */
    public function createAccountingAccount(): void
    {
        DB::transaction(function () {
            // 1. Localizar la cuenta padre (Caja y Bancos)
            $parent = AccountingAccount::where('code', '1.1.01')->first();
            if (!$parent) return;

            // 2. Determinar el nuevo código correlativo
            $lastChild = AccountingAccount::where('parent_id', $parent->id)
                ->orderBy('code', 'desc')
                ->first();

            if (!$lastChild) {
                $newCode = $parent->code . '.01';
            } else {
                $parts = explode('.', $lastChild->code);
                $lastPart = (int) end($parts);
                $newCode = $parent->code . '.' . str_pad($lastPart + 1, 2, '0', STR_PAD_LEFT);
            }

            // 3. Crear la cuenta
            $account = AccountingAccount::create([
                'parent_id'     => $parent->id,
                'code'          => $newCode,
                'name'          => 'Caja: ' . $this->name,
                'type'          => AccountingAccount::TYPE_ASSET,
                'level'         => $parent->level + 1,
                'is_selectable' => true,
            ]);

            // 4. Asignar a la terminal
            $this->updateQuietly(['cash_account_id' => $account->id]);
        });
    }

    // ===== LÓGICA DE SEGURIDAD (PIN) =====

    /**
     * Mutator para hashear el PIN automáticamente al asignarlo.
     */
    protected function setAccessPinAttribute($value)
    {
        if (!empty($value)) {
            $this->attributes['access_pin'] = Hash::make($value);
        }
    }

    /**
     * Indica si la terminal realmente exige verificación de PIN.
     * Si requires_pin está activo pero no hay access_pin configurado,
     * no hay nada contra qué verificar, así que se trata como sin PIN
     * (de lo contrario el cajero queda bloqueado sin ninguna clave válida).
     */
    public function requiresPinVerification(): bool
    {
        return $this->requires_pin && !empty($this->access_pin);
    }

    /**
     * Verifica si el PIN proporcionado es correcto.
     */
    public function verifyPin(string $pin): bool
    {
        if (!$this->requiresPinVerification()) return true;

        return Hash::check($pin, $this->access_pin);
    }

    // ===== RELACIONES =====

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function cashAccount()
    {
        return $this->belongsTo(AccountingAccount::class, 'cash_account_id');
    }

    public function defaultNcfType()
    {
        return $this->belongsTo(NcfType::class, 'default_ncf_type_id');
    }

    public function defaultClient()
    {
        return $this->belongsTo(Client::class, 'default_client_id');
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class, 'pos_terminal_id');
    }

    // Una terminal puede tener muchas sesiones (aperturas/cierres)

    public function sessions(): HasMany
    {
        // El segundo parámetro es la columna real en la tabla pos_sessions
        return $this->hasMany(PosSession::class, 'terminal_id');
    }
    /**
     * Obtiene una configuración específica resolviendo la jerarquía:
     * Terminal (si existe) -> Global (fallback)
     */
    public function getSetting(string $key)
    {
        return match($key) {
            'printer_format' => $this->printer_format ?? pos_config('receipt_size'),
            'default_client' => $this->default_client_id ?? pos_config('default_walkin_customer_id'),
            'auto_print'     => pos_config('auto_print_receipt'), // No sobreescribible por ahora
            default          => pos_config($key)
        };
    }
}