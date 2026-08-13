<?php

namespace App\Http\Controllers;

use App\Exports\Catalogs\StatesCatalogExport;
use App\Exports\Catalogs\TaxTypesCatalogExport;
use Maatwebsite\Excel\Facades\Excel;

class CatalogController extends Controller
{
    /**
     * Catálogo de Provincias/Estados (Basado en Configuración General)
     */
    public function states()
    {
        return Excel::download(new StatesCatalogExport, 'catalogo-provincias.xlsx');
    }

    /**
     * Catálogo de Tipos de Identificación (RNC, Cédula, Pasaporte, etc.)
     */
    public function taxTypes()
    {
        return Excel::download(new TaxTypesCatalogExport, 'catalogo-tipos-identificacion.xlsx');
    }
}
