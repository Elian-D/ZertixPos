{{-- Formulario de categoría (v1.5.1 REQ-2.3, /filament-form). Mismo partial para crear y
     editar. Dos niveles: la principal solo agrupa; la subcategoría recibe productos.
     Recibe $parents (principales); opcional $category (editar, con children_count y
     products_count). --}}
@php
    $editing = isset($category);
    $kind = old('kind', $editing ? ($category->isParent() ? 'parent' : 'child') : 'parent');
@endphp

<div x-data="{ kind: @js($kind) }">
    <x-ui.infolist.section title="Categoría" icon="heroicon-o-tag" :cols="0"
        description="Cómo vas a agrupar lo que vendes: la principal agrupa y la subcategoría recibe los productos.">

        <div class="flex flex-col gap-5">
            <div>
                <span class="block text-xs font-semibold text-slate-600 mb-2">¿Qué estás creando? <span class="text-state-error">*</span></span>
                <div class="flex flex-col gap-3">
                    <x-ui.forms.radio label="Categoría principal" name="kind" value="parent" x-model="kind"
                        description="Agrupa, no recibe productos. Ej: Agua, Laptops." />
                    <x-ui.forms.radio label="Subcategoría" name="kind" value="child" x-model="kind"
                        description="Aquí van los productos. Ej: Botellones, dentro de Agua." />
                </div>
                @error('kind')
                    <p class="mt-1.5 text-xs font-medium text-state-error">{{ $message }}</p>
                @enderror
                @if($editing && $category->children_count > 0)
                    <p class="mt-1.5 text-xs text-slate-400">Tiene {{ $category->children_count }} subcategorías: sigue siendo principal mientras las tenga.</p>
                @elseif($editing && $category->products_count > 0)
                    <p class="mt-1.5 text-xs text-slate-400">Tiene {{ $category->products_count }} productos: sigue siendo subcategoría mientras los tenga.</p>
                @endif
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-5">
                <x-ui.forms.input label="Nombre" name="name" required maxlength="100" iconLeft="heroicon-o-tag"
                    :value="old('name', $category->name ?? '')"
                    x-bind:placeholder="kind === 'parent' ? 'Ej: Agua' : 'Ej: Botellones'"
                    :error="$errors->first('name')" />

                <div x-show="kind === 'child'" x-cloak>
                    <x-ui.forms.select label="Categoría principal" name="parent_id" iconLeft="heroicon-o-squares-2x2"
                        placeholder="Seleccione una opción" x-bind:required="kind === 'child'"
                        hint="De cuál categoría principal cuelga."
                        :error="$errors->first('parent_id')">
                        @foreach($parents as $parent)
                            <option value="{{ $parent->id }}" @selected((string) old('parent_id', $category->parent_id ?? '') === (string) $parent->id)>
                                {{ $parent->name }}{{ $parent->is_active ? '' : ' (inactiva)' }}
                            </option>
                        @endforeach
                    </x-ui.forms.select>
                    @if($parents->isEmpty())
                        <p class="mt-1.5 text-xs text-slate-400">Todavía no hay categorías principales: crea una primero.</p>
                    @endif
                </div>
            </div>

            <x-ui.forms.textarea label="Descripción" name="description" :rows="2" maxlength="255"
                placeholder="Opcional" :error="$errors->first('description')">{{ old('description', $category->description ?? '') }}</x-ui.forms.textarea>

            <x-ui.forms.toggle label="Activa" name="is_active" value="1"
                :checked="(bool) old('is_active', $category->is_active ?? true)"
                description="Inactiva no aparece al cargar productos ni en el TPV. Una principal inactiva oculta sus subcategorías." />
        </div>
    </x-ui.infolist.section>
</div>
