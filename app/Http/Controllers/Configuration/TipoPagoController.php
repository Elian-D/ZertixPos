<?php

namespace App\Http\Controllers\Configuration;

use App\Http\Controllers\Controller;
use App\Models\Accounting\AccountingAccount;
use App\Models\Configuration\TipoPago;
use App\Traits\SoftDeletesTrait;
use Illuminate\Http\Request;

class TipoPagoController extends Controller
{
     use SoftDeletesTrait;
    /**
     * Muestra el listado de tipos de pagos con filtros y paginación
     */
    public function index(Request $request) {
        $search = $request->query('search'); 
        $estado = $request->query('estado'); 

        $tipoPago = TipoPago::with('account') // Agregamos eager loading
            ->when($search, fn($q) => $q->where('nombre', 'like', "%{$search}%"))
            ->when($estado === 'activo', fn($q) => $q->activo())
            ->when($estado === 'inactivo', fn($q) => $q->inactivo())
            ->orderBy('nombre')
            ->paginate(10)
            ->withQueryString();

        // Obtenemos solo las cuentas seleccionables para los selects
        $cuentasContables = AccountingAccount::where('is_selectable', true)->orderBy('code')->get();

        return view('configuration.pagos.index', compact('tipoPago', 'search', 'estado', 'cuentasContables'));
    }

    /**
     * Almacena un nuevo Tipo de pago en la base de datos.
     * El estado se establece por defecto en ACTIVO (true).
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|unique:tipo_pagos,nombre',
            'accounting_account_id' => 'nullable|exists:accounting_accounts,id' // Nueva validación
        ]);

        $pago = TipoPago::create([
            'nombre' => $request->nombre,
            'accounting_account_id' => $request->accounting_account_id, // Nuevo campo
            'estado' => true 
        ]);

        return redirect()->route('configuration.pagos.index')
            ->with('success', 'Tipo de pago creado exitosamente.');
    }

    /**
     * Actualiza el TipoPago especificado en la base de datos.
     * Permite cambiar el nombre y el estado (activar/desactivar).
     */
    public function update(Request $request, TipoPago $tipoPago)
    {
        $request->validate([
            'nombre' => ['required','string','unique:tipo_pagos,nombre,' . $tipoPago->id],
            'accounting_account_id' => 'nullable|exists:accounting_accounts,id' // Nueva validación
        ]);
        
        $tipoPago->update([
            'nombre' => $request->nombre,
            'accounting_account_id' => $request->accounting_account_id // Nuevo campo
        ]);

        return redirect()->route('configuration.pagos.index')
            ->with('success', 'Tipo de pago actualizado exitosamente.');
    }

    public function toggleEstado(TipoPago $tipoPago) {
    $tipoPago->toggleEstado();

    return redirect()
        ->route('configuration.pagos.index')
        ->with(
            'success',
            'Estado actualizado para "' . $tipoPago->nombre . '".'
        );
    }


    // Elimina el Tipo Pago si no tiene relaciones (o desactiva la eliminación por defecto).
    public function destroy(TipoPago $tipoPago)
    {
        if ($tipoPago->isSystemProtected()) {
            return back()->with('error', 'No puedes eliminar un método de pago protegido del sistema.');
        }
        return $this->destroyTrait($tipoPago, null);
    }

    // Métodos abstractos que el trait necesita
    protected function getModelClass(): string { return \App\Models\Configuration\TipoPago::class; }
    protected function getViewFolder(): string { return 'configuration.pagos'; }
    protected function getRouteIndex(): string { return 'configuration.pagos.index'; }
    protected function getRouteEliminadas(): string { return 'configuration.pagos.eliminados'; }
    protected function getEntityName(): string { return 'Tipo Pago'; }
}
