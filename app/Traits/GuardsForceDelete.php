<?php

namespace App\Traits;

use DomainException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Borrado definitivo seguro de un catálogo Categoría A (v1.5.1 REQ-1.1).
 *
 * El modelo declara en deletionBlockers() qué lo ata, y forceDeleteGuarded() solo borra si
 * nada lo ata. Si algo lo ata, lanza un DomainException que dice qué; la tabla lo atrapa y
 * avisa con $this->notify('error', ...). Nunca un 500. Si una llave restrict que no se
 * declaró rechaza el borrado, también termina en DomainException.
 */
trait GuardsForceDelete
{
    /**
     * Lo que impide borrar el registro, como 'singular|plural' => cantidad. Contar con
     * DB::table() para incluir las filas en la papelera: la llave también las ve.
     *
     * @return array<string, int>
     */
    abstract public function deletionBlockers(): array;

    /** @param  string  $subject  Sujeto del aviso, ej. 'El cliente "Juan"'. */
    public function forceDeleteGuarded(string $subject): void
    {
        $found = collect($this->deletionBlockers())
            ->filter()
            ->map(function (int $count, string $label) {
                [$one, $many] = explode('|', $label);

                return $count.' '.($count === 1 ? $one : $many);
            })
            ->values();

        if ($found->isNotEmpty()) {
            throw new DomainException("{$subject} tiene {$found->join(', ', ' y ')}: no se puede borrar definitivo y se queda en la papelera.");
        }

        try {
            DB::transaction(fn () => $this->forceDelete());
        } catch (QueryException $e) {
            if ($e->getCode() !== '23000') {
                throw $e;
            }

            throw new DomainException("{$subject} tiene registros relacionados: no se puede borrar definitivo y se queda en la papelera.");
        }
    }
}
