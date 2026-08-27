<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;

/**
 * Categoría C (docs/analisis/politica-soft-deletes.md) — CxC es la bitácora de
 * deuda de un cliente: nunca se borra ni se archiva, ni siquiera cuando el
 * documento origen fue anulado (eso ya lo refleja el `status` de la fila).
 * Sin SoftDeletesTrait, sin destroy() — este controlador es de solo lectura.
 */
class ReceivableController extends Controller
{
    /**
     * Listado migrado a Livewire — ver App\Livewire\App\Finance\ReceivableTable.
     */
    public function index()
    {
        return view('accounting.receivables.index');
    }
}
