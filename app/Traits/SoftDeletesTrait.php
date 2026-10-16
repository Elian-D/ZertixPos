<?php

namespace App\Traits;

use Illuminate\Http\Request;

/**
 * Papelera de un catálogo Categoría A (docs/analisis/politica-soft-deletes.md).
 *
 * El borrado definitivo NO va por borrarDefinitivo() (vestigial en los módulos Livewire):
 * la tabla llama a forceDeleteGuarded() de App\Traits\GuardsForceDelete, que revisa qué ata
 * al registro y avisa con un toast en vez de dar un 500. Ninguna llave hacia un catálogo es
 * CASCADE (v1.5.1 REQ-1.2). Primer caso: VendorService::forceDelete().
 */
trait SoftDeletesTrait
{
    /**
     * Obtiene el nombre del registro de forma dinámica (nombre o name)
     */
    protected function getItemDisplayName($item): string
    {
        // Prioriza 'name', luego 'nombre', si no existe ninguno devuelve vacío
        return $item->name ?? $item->nombre ?? '';
    }
    
    public function destroyTrait($item, $relationCheck = null)
    {
        $displayName = $this->getItemDisplayName($item);

        if ($relationCheck && $item->$relationCheck()->exists()) {
            return redirect()
                ->route($this->getRouteIndex())
                ->with('error', $this->getEntityName() . ' "' . $displayName . '" tiene relaciones. No se puede mover a la papelera.');
        }

        try {
            $item->delete();
            return redirect()
                ->route($this->getRouteIndex())
                ->with('success', $this->getEntityName() . ' "' . $displayName . '" movida a la papelera correctamente.');
        } catch (\Exception $e) {
            return redirect()
                ->route($this->getRouteIndex())
                ->with('error', 'Error al mover a la papelera. Contacte soporte.');
        }
    }

    public function eliminadas()
    {
        $modelClass = $this->getModelClass();
        // Cambié orderBy('id') por latest('deleted_at') para ver los últimos eliminados arriba
        $items = $modelClass::onlyTrashed()->latest('deleted_at')->paginate(10);
        return view($this->getViewFolder() . '.eliminadas', compact('items'));
    }

    public function restaurar($id)
    {
        $modelClass = $this->getModelClass();
        $item = $modelClass::onlyTrashed()->findOrFail($id);
        
        $displayName = $this->getItemDisplayName($item); // Capturamos el nombre antes o después de restaurar
        $item->restore();

        return redirect()
            ->route($this->getRouteIndex())
            ->with('success', $this->getEntityName() . ' "' . $displayName . '" restaurado correctamente.');
    }

    public function borrarDefinitivo($id)
    {
        $modelClass = $this->getModelClass();
        $item = $modelClass::onlyTrashed()->findOrFail($id);
        
        $displayName = $this->getItemDisplayName($item);
        $item->forceDelete();

        return redirect()
            ->route($this->getRouteEliminadas())
            ->with('success', $this->getEntityName() . ' "' . $displayName . '" eliminada definitivamente.');
    }

    abstract protected function getModelClass(): string;
    abstract protected function getViewFolder(): string;
    abstract protected function getRouteIndex(): string;
    abstract protected function getRouteEliminadas(): string;
    abstract protected function getEntityName(): string;
}