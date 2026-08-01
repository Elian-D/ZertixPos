<?php

namespace App\Services\Client;

use App\DTOs\Clients\QuickClientDTO;
use App\Models\Clients\Client;
use App\Models\Accounting\AccountingAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ClientService
{
    /**
         * Genera un código contable único considerando incluso registros eliminados (SoftDeletes).
         */
        private function generateUniqueAccountingCode(AccountingAccount $parent): string
        {
            // Usamos withTrashed() para que el contador incluya códigos de cuentas borradas
            $lastChild = AccountingAccount::withTrashed()
                ->where('parent_id', $parent->id)
                ->orderBy('code', 'desc')
                ->first();

            // Extraemos el correlativo final (ej: de 1.1.02.0003 toma el 0003)
            $nextNumber = $lastChild ? (int) substr($lastChild->code, -4) + 1 : 1;
            
            return $parent->code . '.' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
        }

        public function createClient(array $data): Client
        {
            return DB::transaction(function () use ($data) {
                if (!empty($data['create_accounting_account'])) {
                    $parentAccount = AccountingAccount::where('code', '1.1.02')->first();
                    
                    if ($parentAccount) {
                        $newCode = $this->generateUniqueAccountingCode($parentAccount);

                        $newAccount = AccountingAccount::create([
                            'parent_id'     => $parentAccount->id,
                            'code'          => $newCode,
                            // Añadimos un pequeño identificador aleatorio o el ID si fuera posible 
                            // para evitar el error de nombre duplicado en la UI
                            'name'          => "CxC - " . $data['name'],
                            'type'          => $parentAccount->type,
                            'level'         => $parentAccount->level + 1,
                            'is_selectable' => true
                        ]);

                        $data['accounting_account_id'] = $newAccount->id;
                    }
                }

                return Client::create($data);
            });
        }

    public function createQuickClient(array $data): Client
    {
        // 1. Transformar datos usando el DTO para asegurar campos obligatorios
        $dto = QuickClientDTO::fromRequest($data);
        
        // 2. Lógica de Identificador Fiscal Automático si no viene el tipo
        $clientData = $dto->toArray();
        if (empty($clientData['tax_identifier_type_id']) && !empty($clientData['tax_id'])) {
            $clientData['tax_identifier_type_id'] = $this->resolveTaxType($clientData['tax_id']);
        }

        // 3. Llamar al método de creación estándar (que maneja transacciones y contabilidad)
        return $this->createClient($clientData);
    }

    /**
     * Lógica básica para RD: RNC (9) o Cédula (11)
     */
    private function resolveTaxType(?string $taxId): ?int
    {
        if (!$taxId) return null;
        $length = strlen(preg_replace('/[^0-9]/', '', $taxId));
        
        // Aquí deberías buscar en tu tabla tax_identifier_types según la longitud
        // Ejemplo hipotético:
        return match($length) {
            9 => 198,  // RNC
            11 => 197, // Cédula
            default => null
        };
    }

    public function updateClient(Client $client, array $data): bool
    {
        return DB::transaction(function () use ($client, $data) {
            $oldAccountId = $client->accounting_account_id;

            // 1. Crear nueva cuenta si se solicitó
            if (!empty($data['create_accounting_account'])) {
                $parentAccount = AccountingAccount::where('code', '1.1.02')->first();
                if ($parentAccount) {
                    $newCode = $this->generateUniqueAccountingCode($parentAccount);
                    $newAccount = AccountingAccount::create([
                        'parent_id' => $parentAccount->id,
                        'code'      => $newCode,
                        'name'      => "CxC - " . ($data['name'] ?? $client->name),
                        'type'      => $parentAccount->type,
                        'level'     => $parentAccount->level + 1,
                        'is_selectable' => true
                    ]);
                    $data['accounting_account_id'] = $newAccount->id;
                }
            }

            // 2. Lógica de limpieza mejorada
            // Verificamos si la cuenta cambió (incluso si cambió a null/general)
            if ($oldAccountId && array_key_exists('accounting_account_id', $data)) {
                
                // Si el ID nuevo es diferente al viejo (ej: eligió General o creó una nueva)
                if (empty($data['accounting_account_id']) || $oldAccountId != $data['accounting_account_id']) {
                    
                    $oldAccount = AccountingAccount::find($oldAccountId);
                    
                    // Validamos que sea una cuenta de cliente antes de borrar
                    // (ID 4 es tu padre 1.1.02 según logs)
                    if ($oldAccount && $oldAccount->parent_id == 4) {
                        // VALIDACIÓN CRÍTICA: 
                        // Asumiendo que tienes una columna 'balance' o una relación con transacciones
                        $balance = DB::table('accounting_entries') // O tu tabla de transacciones
                                    ->where('accounting_account_id', $oldAccountId)
                                    ->sum('amount'); // (Débitos - Créditos)

                        if ($balance != 0) {
                            throw new \Exception("No se puede desvincular la cuenta contable porque aún tiene un saldo pendiente de: " . number_format($balance, 2));
                        }
                        
                        $oldAccount->delete(); 
                    }
                }
            }

            return $client->update($data);
        });
    }

    public function performBulkAction(array $ids, string $action, $value = null): int
    {
        return DB::transaction(function () use ($ids, $action, $value) {
            $query = Client::whereIn('id', $ids);
            $count = count($ids);

            match ($action) {
                'delete'           => $query->delete(),
                'change_status'    => $query->update(['estado_cliente_id' => $value]),
                'change_geo_state' => $query->update(['state_id' => $value]),
                'reset_credit'     => $query->update(['credit_limit' => 0]),
                default => throw new \InvalidArgumentException("Acción no soportada"),
            };

            return $count;
        });
    }

    public function getActionLabel(string $action): string
    {
        return match ($action) {
            'delete'           => 'eliminado',
            'change_status'    => 'actualizado el estado',
            'change_geo_state' => 'actualizado la ubicación',
            'reset_credit'     => 'removido el límite de crédito de',
            default            => 'procesado',
        };
    }
}