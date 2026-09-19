<?php

namespace App\Http\Controllers\Configuration;

use App\Http\Controllers\Controller;
use App\Models\User;

/**
 * REQ-7.4 — generaliza el patrón que ya tenía `CatalogsController` (hub de
 * solo Métodos de Pago/Tipos de Documento) a TODA la configuración dispersa
 * del sistema, reemplazando los links sueltos que tenía el dropdown
 * "Configuración" del sidebar (ver resources/views/layouts/sidebar.blade.php).
 * Sin lógica de negocio real — cada tarjeta navega a su página existente, el
 * hub solo agrupa. El único dato dinámico es el conteo de Usuarios (mockup
 * de Stitch, "ZertixPOS - Configuration Hub").
 */
class ConfigurationHubController extends Controller
{
    public function index()
    {
        return view('configuration.index', [
            'usersCount' => User::count(),
        ]);
    }
}
