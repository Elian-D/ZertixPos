{{--
    resources/views/components/data-table/row-checkbox.blade.php
    ---------------------------------------------------------------
    Checkbox de fila para la selección masiva (REQ-0.5). Se coloca como
    primera celda de cada <tr> del @forelse del módulo — no es automático,
    cada tabla lo declara igual que hace con x-data-table.cell.

    PROPS:
      id — id de la fila (se agrega/quita de $wire.selected)

    USO:
      <tr>
          <x-data-table.row-checkbox :id="$product->id" />
          <x-data-table.cell column="name" :visible="$visibleColumns">...</x-data-table.cell>
          ...
      </tr>
--}}

@props(['id'])

<td class="px-4 py-3.5 w-10">
    <input
        type="checkbox"
        wire:model.live="selected"
        value="{{ $id }}"
        class="w-4 h-4 rounded border-slate-300
               text-zertix-primary focus:ring-zertix-primary focus:ring-offset-0
               cursor-pointer"
    />
</td>
