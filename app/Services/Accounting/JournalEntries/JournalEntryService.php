<?php

namespace App\Services\Accounting\JournalEntries;

use App\Models\Accounting\JournalEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Exception;

class JournalEntryService
{
    /**
     * Registra un nuevo asiento contable con sus líneas de detalle.
     */
    public function create(array $data): JournalEntry
    {
        return DB::transaction(function () use ($data) {
            
            // 1. Validar Partida Doble
            $items = collect($data['items']);
            $totalDebit = $items->sum('debit');
            $totalCredit = $items->sum('credit');

            // Permitimos un margen de error mínimo por decimales
            if (abs($totalDebit - $totalCredit) > 0.001) {
                throw new Exception("Error contable: El asiento no está cuadrado. Débito: {$totalDebit}, Crédito: {$totalCredit}");
            }

            if ($totalDebit <= 0) {
                throw new Exception("El monto del asiento debe ser mayor a cero.");
            }

            // 2. Crear Cabecera del Asiento
            $entry = JournalEntry::create([
                'entry_date'  => $data['entry_date'],
                'reference'   => $data['reference'] ?? null,
                'description' => $data['description'],
                'status'      => $data['status'] ?? JournalEntry::STATUS_DRAFT,
                'created_by'  => Auth::id(),
            ]);

            // 3. Registrar cada línea de detalle (JournalItems)
            foreach ($data['items'] as $item) {
                $entry->items()->create([
                    'accounting_account_id' => $item['accounting_account_id'],
                    'debit'                 => $item['debit'] ?? 0,
                    'credit'                => $item['credit'] ?? 0,
                    'note'                  => $item['note'] ?? null,
                ]);
            }

            return $entry;
        });
    }

    /**
     * Actualiza un asiento contable existente y sincroniza sus líneas.
     */
    public function update(JournalEntry $entry, array $data): JournalEntry
    {
        return DB::transaction(function () use ($entry, $data) {
            if ($entry->status !== JournalEntry::STATUS_DRAFT) {
                throw new Exception("No se puede editar un asiento que ya ha sido asentado o anulado.");
            }

            // 1. Validar Partida Doble
            $items = collect($data['items']);
            $totalDebit = $items->sum('debit');
            $totalCredit = $items->sum('credit');

            if (abs($totalDebit - $totalCredit) > 0.001) {
                throw new Exception("Error: El asiento no está cuadrado. Diferencia: " . abs($totalDebit - $totalCredit));
            }

            // 2. Actualizar Cabecera
            $entry->update([
                'entry_date'  => $data['entry_date'],
                'reference'   => $data['reference'] ?? null,
                'description' => $data['description'],
            ]);

            // 3. Sincronizar Detalles (Eliminar antiguos y crear nuevos)
            $entry->items()->delete();

            foreach ($data['items'] as $item) {
                $entry->items()->create([
                    'accounting_account_id' => $item['accounting_account_id'],
                    'debit'                 => $item['debit'] ?? 0,
                    'credit'                => $item['credit'] ?? 0,
                    'note'                  => $item['note'] ?? null,
                ]);
            }

            return $entry;
        });
    }

    /**
     * Cambia el estado de un asiento a 'Asentado' (Posted).
     * Una vez asentado, contablemente no debería ser editado.
     */
    public function post(JournalEntry $entry): bool
    {
        if ($entry->status !== JournalEntry::STATUS_DRAFT) {
            throw new Exception("Solo se pueden asentar documentos en estado Borrador.");
        }

        return $entry->update(['status' => JournalEntry::STATUS_POSTED]);
    }

    /**
     * Anula un asiento contable.
     */
    public function cancel(JournalEntry $entry): bool
    {
        return $entry->update(['status' => JournalEntry::STATUS_CANCELLED]);
    }

    /**
     * Crea un asiento de reversión basado en uno existente.
     */
    public function reverse(int $entryId, array $overrides = []): JournalEntry
    {
        return DB::transaction(function () use ($entryId, $overrides) {
            $original = JournalEntry::with('items')->findOrFail($entryId);

            $items = $original->items->map(function ($item) {
                return [
                    'accounting_account_id' => $item->accounting_account_id,
                    'debit'                 => $item->credit, // Inversión
                    'credit'                => $item->debit, // Inversión
                    'note'                  => "Reversión de item #{$item->id}"
                ];
            })->toArray();

            return $this->create([
                'entry_date'  => $overrides['entry_date'] ?? now(),
                'reference'   => $overrides['reference'] ?? "REV-{$original->reference}",
                'description' => $overrides['description'] ?? "Asiento de reversión de: {$original->description}",
                'status'      => JournalEntry::STATUS_POSTED,
                'items'       => $items
            ]);
        });
    }
}