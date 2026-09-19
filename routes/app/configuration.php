<?php

use App\Http\Controllers\Accounting\DocumentTypeController;
use App\Http\Controllers\Clients\BusinessTypeController;
use App\Http\Controllers\Clients\EquipmentTypeController;
use App\Http\Controllers\Configuration\ConfiguracionGeneralController;
use App\Http\Controllers\Configuration\ConfigurationHubController;
use App\Http\Controllers\Configuration\TipoPagoController;
use App\Http\Controllers\Products\CategoryController;
use App\Http\Controllers\Products\UnitController;
use App\Http\Controllers\Sales\Ncf\NcfTypeController;
use Illuminate\Support\Facades\Route;

Route::prefix('config')->as('configuration.')->group(function () {

    // REQ-7.4 — reemplaza el dropdown "Configuración" del sidebar (que
    // vivía disperso entre config.general/users.view/roles.view/etc.) por
    // un único punto de entrada. Gateado por el mismo OR de permisos que
    // antes decidía si el dropdown aparecía — cada tarjeta adentro sigue
    // su propio @can, así que un usuario con un solo permiso ve el hub
    // igual, solo con menos tarjetas.
    Route::get('/', [ConfigurationHubController::class, 'index'])
        ->middleware('permission:config.general|config.payment_types|config.billing|ncf_types.manage|document_types.view|categories.manage|units.manage|business_types.manage|equipment_types.manage|users.view|roles.view|config.modules')
        ->name('index');

    Route::middleware('permission:config.general')->group(function () {

        Route::get('general', [ConfiguracionGeneralController::class, 'edit'])
            ->name('general.edit');

        Route::put('general', [ConfiguracionGeneralController::class, 'update'])
            ->name('general.update');
    });

    // Catálogo fijo de 2 filas (FAC/PAG) que el sistema sembró y sabe usar — sin
    // create/destroy/papelera, lo único legítimo es ajustar el correlativo (REQ-1.7).
    Route::middleware(['auth'])->group(function () {

        Route::get('document-types', [DocumentTypeController::class, 'index'])
            ->middleware('permission:document_types.view')
            ->name('document_types.index');

        Route::get('document-types/{document_type}/edit', [DocumentTypeController::class, 'edit'])
            ->middleware('permission:document_types.edit')
            ->name('document_types.edit');

        Route::put('document-types/{document_type}', [DocumentTypeController::class, 'update'])
            ->middleware('permission:document_types.edit')
            ->name('document_types.update');
    });

    Route::middleware('permission:config.payment_types')->group(function () {

        Route::get('tipo-pagos/eliminados', [TipoPagoController::class, 'eliminadas'])
            ->name('pagos.eliminados');

        Route::resource('tipo-pagos', TipoPagoController::class)
            ->parameters(['tipo-pagos' => 'tipoPago'])
            ->names('pagos');

        Route::patch('tipo-pagos/{tipoPago}/estado', [TipoPagoController::class, 'toggleEstado'])
            ->name('pagos.toggle');

        Route::patch('tipo-pagos/{id}/restaurar', [TipoPagoController::class, 'restaurar'])
            ->name('pagos.restaurar');

        Route::delete('tipo-pagos/{id}/borrar', [TipoPagoController::class, 'borrarDefinitivo'])
            ->name('pagos.borrarDefinitivo');
    });

    // Quinta pantalla de Configuración (REQ-10.6) — activar/desactivar módulos
    // satélite/flexibles. Vista delgada + componente Livewire, mismo patrón que
    // sales.quotes.create (resources/views/sales/quotes/create.blade.php).
    Route::middleware('permission:config.modules')
        ->get('features', fn () => view('configuration.features'))
        ->name('features');

    // REQ-7.4 (2026-09-14, a pedido del usuario) — "Catálogos del Sistema" deja
    // de ser tarjetas que enlazan a rutas ajenas (Finanzas/Inventario/CRM): las
    // rutas en sí se mudan para acá. Controladores/vistas/permisos sin cambios,
    // solo el prefijo de URL y el nombre de ruta (inventory.products.categories.*
    // →configuration.categories.*, etc.) — ver docs/features/v1.3.0.md
    // §7.4 para el detalle completo de qué se movió y por qué.
    //
    // Sin sub-prefijo 'catalogs' (revertido 2026-09-18, a pedido del usuario):
    // un segmento de URL sin su propia página rompe el breadcrumb — clickear
    // "Catalogs" ahí no llevaba a ningún lado porque no existía una ruta
    // config/catalogs/ real, solo sus hijos. Las 5 rutas van directas bajo
    // config/ (mismo nivel que general/document-types/tipo-pagos/features).

    // NCF — antes routes/app/finance.php (module:sales.ncf, permission
    // propia ncf_types.manage, ver REQ-7.3). Solo lectura, sin
    // store/update/destroy — ver App\Livewire\App\Finance\NcfTypeTable.
    Route::middleware(['auth', 'permission:ncf_types.manage', 'module:sales.ncf'])
        ->prefix('ncf-types')->as('ncf_types.')->group(function () {
            Route::get('/', [NcfTypeController::class, 'index'])->name('index');
        });

    // Categorías/Unidades — antes routes/app/inventory.php, dentro de
    // products.*. Módulo base fijo (docs/analisis/modulos-base-satelite.md
    // §2.1), sin gate de module_enabled.
    Route::middleware('permission:categories.manage')->group(function () {
        Route::resource('categories', CategoryController::class)
            ->parameters(['categories' => 'category'])
            ->only(['index', 'store', 'update', 'destroy'])
            ->names('categories');
    });

    Route::middleware('permission:units.manage')->group(function () {
        Route::resource('units', UnitController::class)
            ->parameters(['units' => 'unit'])
            ->only(['index', 'store', 'update', 'destroy'])
            ->names('units');
    });

    // Tipos de Negocio/Equipo — antes routes/app/clients.php.
    Route::middleware('module:sales.delivery_points')->group(function () {
        Route::middleware('permission:business_types.manage')->group(function () {
            Route::resource('business-types', BusinessTypeController::class)
                ->parameters(['business-types' => 'negocio'])
                ->only(['index', 'store', 'update', 'destroy'])
                ->names('business_types');
        });
    });

    Route::middleware('module:clients.field_assets')->group(function () {
        Route::middleware('permission:equipment_types.manage')->group(function () {
            Route::resource('equipment-types', EquipmentTypeController::class)
                ->parameters(['equipment-types' => 'equipo'])
                ->only(['index', 'store', 'update', 'destroy'])
                ->names('equipment_types');
        });
    });
});
