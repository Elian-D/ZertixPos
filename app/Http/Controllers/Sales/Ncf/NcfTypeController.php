<?php

namespace App\Http\Controllers\Sales\Ncf;

use App\Http\Controllers\Controller;

/**
 * REQ-7.1 — deja de ser CRUD: un tipo de comprobante nuevo es un cambio de
 * ley dominicana (la DGII), se entrega vía seeder en una actualización del
 * sistema, no algo que el dueño de un negocio escriba a mano. Sin
 * create()/store()/update() — el único campo mutable por tenant (`is_active`,
 * REQ-7.2) se cambia desde App\Livewire\App\Finance\NcfTypeTable::toggleActivo().
 */
class NcfTypeController extends Controller
{
    public function index()
    {
        return view('sales.ncf.types.index');
    }
}
