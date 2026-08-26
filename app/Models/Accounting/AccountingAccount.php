<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountingAccount extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'parent_id',
        'code',
        'name',
        'type',
        'level',
        'is_selectable',
        'is_active'
    ];

    protected $casts = [
    'is_active' => 'boolean',
    'is_selectable' => 'boolean',
];

    // Constantes de Tipo
    const TYPE_ASSET     = 'asset';
    const TYPE_LIABILITY = 'liability';
    const TYPE_EQUITY    = 'equity';
    const TYPE_REVENUE   = 'revenue';
    const TYPE_COST      = 'cost';
    const TYPE_EXPENSE   = 'expense';

    public static function getTypes(): array
    {
        return [
            self::TYPE_ASSET     => 'Activo',
            self::TYPE_LIABILITY => 'Pasivo',
            self::TYPE_EQUITY    => 'Patrimonio',
            self::TYPE_REVENUE   => 'Ingreso',
            self::TYPE_COST      => 'Costo',
            self::TYPE_EXPENSE   => 'Gasto',
        ];
    }

    public static function getTypeStyles(): array
{
    return [
        self::TYPE_ASSET     => 'bg-emerald-100 text-emerald-700 border-emerald-200 from-emerald-50',
        self::TYPE_LIABILITY => 'bg-red-100 text-red-700 border-red-200 from-red-50',
        self::TYPE_EQUITY    => 'bg-blue-100 text-blue-700 border-blue-200 from-blue-50',
        self::TYPE_REVENUE   => 'bg-zertix-primary-100 text-zertix-primary-700 border-zertix-primary-200 from-zertix-primary-50',
        self::TYPE_COST      => 'bg-orange-100 text-orange-700 border-orange-200 from-orange-50',
        self::TYPE_EXPENSE   => 'bg-amber-100 text-amber-700 border-amber-200 from-amber-50',
    ];
}

    // Relaciones
    public function parent(): BelongsTo 
    { 
        return $this->belongsTo(AccountingAccount::class, 'parent_id'); 
    }

    public function children(): HasMany 
    { 
        return $this->hasMany(AccountingAccount::class, 'parent_id')->orderBy('code'); 
    }

    public function client(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        // Una cuenta "tiene un" cliente vinculado a través de accounting_account_id
        return $this->hasOne(\App\Models\Clients\Client::class, 'accounting_account_id');
    }
    // Scopes
    public function scopeRoots($query) 
    { 
        return $query->whereNull('parent_id'); 
    }
}