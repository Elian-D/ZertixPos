<?php

namespace App\Http\Controllers\Configuration;

use App\Http\Controllers\Controller;

class CatalogsController extends Controller
{
    /**
     * Hub de catálogos de bajo volumen (Métodos de Pago, Tipos de Documento)
     * — reemplaza los 2 links sueltos que tenía el sidebar.
     */
    public function index()
    {
        return view('configuration.catalogs.index');
    }
}
