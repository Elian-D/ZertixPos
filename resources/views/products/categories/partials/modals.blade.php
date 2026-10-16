{{-- Confirmaciones de borrado del listado de categorías. Crear y editar tienen vista propia
     (v1.5.1 REQ-2.3). Recibe $categories (la página actual). --}}
@foreach($categories as $item)
    @if($item->trashed())
        {{-- Papelera (docs/analisis/politica-soft-deletes.md §6) — borrado
             definitivo vía wireConfirm, dispara CategoryTable::forceDelete(). --}}
        <x-ui.confirm-deletion-modal
            :id="$item->id"
            :title="'¿Eliminar Permanentemente?'"
            :itemName="$item->name"
            :type="'la categoría de producto'"
            :wireConfirm="'forceDelete(' . $item->id . ')'"
            :description="'Estás a punto de borrar definitivamente la categoría <strong>' . e($item->name) . '</strong>.'"
        >
            Solo se borra si no tiene productos ni subcategorías, ni siquiera en la papelera. No se puede deshacer.
        </x-ui.confirm-deletion-modal>
    @else
        <x-ui.confirm-deletion-modal
            :id="$item->id"
            :title="'¿Eliminar Categoría de Producto?'"
            :itemName="$item->name"
            :type="'la categoría de producto'"
            :route="route('configuration.categories.destroy', $item)"
        />
    @endif
@endforeach
