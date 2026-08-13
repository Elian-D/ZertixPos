<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class AccountingAccountRole extends Model
{
    protected $fillable = ['role', 'accounting_account_id'];

    public function account()
    {
        return $this->belongsTo(AccountingAccount::class, 'accounting_account_id');
    }

    public static function resolve(string $role): ?int
    {
        return Cache::rememberForever("accounting_role_{$role}", function () use ($role) {
            return static::where('role', $role)->value('accounting_account_id');
        });
    }

    public static function resolveCode(string $role): ?string
    {
        return Cache::rememberForever("accounting_role_code_{$role}", function () use ($role) {
            return static::where('role', $role)->with('account')->first()?->account?->code;
        });
    }
}
