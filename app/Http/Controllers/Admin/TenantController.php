<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

/**
 * Listado migrado a Livewire — ver App\Livewire\Admin\TenantsTable.
 * Controlador reducido a la vista (ARCHITECTURE.md): sin create/edit/store —
 * REQ-5.2 es de solo lectura, y REQ-5.6 (crear tenant) enlaza al wizard
 * público existente en pestaña nueva, no un formulario propio de este panel.
 */
class TenantController extends Controller
{
    public function index()
    {
        return view('admin.tenants.index');
    }
}
