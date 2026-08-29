<?php

namespace App\Http\Requests\Accounting\AccountingAccount;

use App\Models\Accounting\AccountingAccount;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAccountingAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('accounting_accounts.manage');
    }

    public function rules(): array
    {
        return [
            'parent_id'     => 'nullable|exists:accounting_accounts,id',
            'code'          => [
                'required', 
                'string', 
                'max:20', 
                Rule::unique('accounting_accounts', 'code')->ignore($this->accounting_account)
            ],
            'name'          => 'required|string|max:150',
            'type'          => ['required', Rule::in(array_keys(AccountingAccount::getTypes()))],
            'is_selectable' => 'boolean',
            'is_active'     => 'boolean',
        ];
    }
}