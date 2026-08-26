<x-app-layout>
    <div class="max-w-7xl mx-auto py-8 px-4"
        x-cloak
        x-data="{
            isService: {{ old('is_stockable') !== null ? (old('is_stockable') == '0' ? 'true' : 'false') : ($product->is_stockable ? 'false' : 'true') }},
            categoryId: '{{ old('category_id', $product->category_id) }}',
            unitId: '{{ old('unit_id', $product->unit_id) }}',
            servicesCategoryId: @js($categories->firstWhere('name', 'Servicios')?->id),
            unidadUnitId: @js($units->firstWhere('name', 'Unidad')?->id),
            imagePreview: @js($product->image_path ? $product->image_url : null),
            activeTab: 'identificacion',
            selectType(service) {
                this.isService = service;
                if (service) {
                    if (this.servicesCategoryId) this.categoryId = this.servicesCategoryId;
                    if (this.unidadUnitId) this.unitId = this.unidadUnitId;
                }
            },
            updateImagePreview(event) {
                const file = event.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = (e) => { this.imagePreview = e.target.result; };
                    reader.readAsDataURL(file);
                }
            },
        }">
        {{-- Método PUT para actualización y enctype para la imagen --}}
        <form action="{{ route('inventory.products.update', $product) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')


            <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
                <x-form-header
                    :title="'Editar: ' . $product->name"
                    subtitle="Modifique los parámetros del producto/servicio y actualice el catálogo."
                    :back-route="route('inventory.products.index')" />

                {{-- Navegación por tabs (mismo patrón de configuration/general/edit.blade.php) --}}
                <div class="flex border-b border-slate-100 overflow-x-auto">
                    <button type="button" @click="activeTab = 'identificacion'"
                        :class="activeTab === 'identificacion' ? 'text-zertix-primary-dark border-zertix-primary' : 'text-slate-400 border-transparent hover:text-slate-600'"
                        class="flex items-center gap-2 px-6 py-4 text-sm font-bold border-b-2 transition-colors whitespace-nowrap shrink-0">
                        <x-heroicon-s-cube class="w-4 h-4" />
                        Identificación
                    </button>
                    <button type="button" @click="activeTab = 'precios'"
                        :class="activeTab === 'precios' ? 'text-zertix-primary-dark border-zertix-primary' : 'text-slate-400 border-transparent hover:text-slate-600'"
                        class="flex items-center gap-2 px-6 py-4 text-sm font-bold border-b-2 transition-colors whitespace-nowrap shrink-0">
                        <x-heroicon-s-tag class="w-4 h-4" />
                        Precios
                    </button>
                </div>

                {{-- PANEL 1: Identificación --}}
                <div x-show="activeTab === 'identificacion'" x-cloak>
                    <div class="p-8 space-y-6">

                        {{-- Foto del producto --}}
                        <div>
                            <label class="text-xs font-bold text-slate-400 uppercase mb-2 block tracking-wider">Foto del producto</label>
                            <label for="image-input"
                                class="relative flex flex-col items-center justify-center gap-2 border-2 border-dashed border-slate-200 rounded-2xl bg-slate-50 hover:bg-zertix-primary/5 hover:border-zertix-primary/40 transition-colors cursor-pointer overflow-hidden"
                                :class="imagePreview ? 'p-3' : 'py-10'">
                                <template x-if="!imagePreview">
                                    <div class="flex flex-col items-center gap-2 text-center px-4">
                                        <x-heroicon-s-photo class="w-8 h-8 text-slate-300" />
                                        <p class="text-sm text-slate-500">
                                            Arrastra y suelta tus archivos o <span class="text-zertix-primary-dark font-bold">Examina</span>
                                        </p>
                                    </div>
                                </template>
                                <template x-if="imagePreview">
                                    <img :src="imagePreview" class="max-h-40 rounded-xl object-contain" />
                                </template>
                                <input type="file" name="image" id="image-input" accept="image/*"
                                    @change="updateImagePreview" class="sr-only" />
                            </label>
                            <p class="mt-2 text-xs text-slate-400">Si no seleccionas un archivo, se mantiene la imagen actual. Formatos: JPG, PNG o WEBP, máx. 2MB.</p>
                        </div>

                        {{-- Nombre --}}
                        <x-ui.forms.input
                            label="Nombre del producto"
                            name="name"
                            value="{{ old('name', $product->name) }}"
                            placeholder="Ej: Funda de Hielo 10lb"
                            :error="$errors->first('name')"
                            required
                        />

                        {{-- Tipo de Ítem --}}
                        <div>
                            <label class="text-xs font-bold text-slate-400 uppercase mb-2 block tracking-wider">Tipo de Ítem</label>
                            <input type="hidden" name="is_stockable" :value="isService ? 0 : 1">
                            <div class="grid grid-cols-2 gap-2 p-1 bg-slate-100 rounded-2xl">
                                <button type="button" @click="selectType(false)"
                                    :class="!isService ? 'bg-white shadow-sm text-zertix-primary-dark' : 'text-slate-500 hover:text-slate-700'"
                                    class="flex items-center justify-center gap-2 py-2.5 rounded-xl text-sm font-bold transition-all">
                                    <x-heroicon-s-cube class="w-4 h-4" />
                                    Producto
                                </button>
                                <button type="button" @click="selectType(true)"
                                    :class="isService ? 'bg-white shadow-sm text-zertix-primary-dark' : 'text-slate-500 hover:text-slate-700'"
                                    class="flex items-center justify-center gap-2 py-2.5 rounded-xl text-sm font-bold transition-all">
                                    <x-heroicon-s-wrench-screwdriver class="w-4 h-4" />
                                    Servicio
                                </button>
                            </div>
                            <p class="mt-2 text-xs text-slate-400 italic" x-show="isService">
                                Un servicio no descuenta inventario ni requiere existencias en almacén (ej. instalación, flete, mano de obra).
                            </p>
                        </div>

                        {{-- Categoría y Unidad --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <x-ui.forms.select
                                label="Categoría"
                                name="category_id"
                                x-model="categoryId"
                                placeholder=""
                                :error="$errors->first('category_id')"
                                required
                            >
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </x-ui.forms.select>

                            <div>
                                <template x-if="!isService">
                                    <x-ui.forms.select
                                        label="Unidad de Medida"
                                        name="unit_id"
                                        x-model="unitId"
                                        placeholder=""
                                        :error="$errors->first('unit_id')"
                                        required
                                    >
                                        @foreach($units as $unit)
                                            <option value="{{ $unit->id }}">{{ $unit->name }} ({{ $unit->abbreviation }})</option>
                                        @endforeach
                                    </x-ui.forms.select>
                                </template>
                                <template x-if="isService">
                                    <div>
                                        <label class="text-xs font-bold text-slate-400 uppercase mb-2 block tracking-wider">Unidad de Medida</label>
                                        <input type="hidden" name="unit_id" :value="unitId">
                                        <div class="w-full bg-slate-50 border-2 border-slate-100 rounded-2xl px-5 py-4 text-sm text-slate-400">
                                            {{ $units->firstWhere('name', 'Unidad')?->name ?? 'Unidad' }} (fija para servicios)
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        {{-- Descripción --}}
                        <x-ui.forms.textarea
                            label="Descripción"
                            name="description"
                            :rows="3"
                            placeholder="Detalles adicionales..."
                            :error="$errors->first('description')"
                        >{{ old('description', $product->description) }}</x-ui.forms.textarea>

                        {{-- Activo --}}
                        <div class="bg-zertix-primary/5 p-5 rounded-2xl border border-zertix-primary/20">
                            <input type="hidden" name="is_active" value="0">
                            <x-ui.forms.toggle
                                label="Activo"
                                name="is_active"
                                value="1"
                                description="Determina si el producto/servicio aparece en el POS."
                                :checked="(bool) old('is_active', $product->is_active)"
                            />
                        </div>
                    </div>
                </div>

                {{-- PANEL 2: Precios --}}
                <div x-show="activeTab === 'precios'" x-cloak>
                    <div class="p-8 space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <x-ui.forms.input
                                    label="Precio de Costo ({{ config('regional.currency_symbol') }})"
                                    name="cost"
                                    type="number"
                                    step="0.01"
                                    value="{{ old('cost', $product->cost) }}"
                                    placeholder="0.00"
                                    required
                                    :error="$errors->first('cost')"
                                />
                                <p class="mt-2 text-xs text-slate-400" x-show="!isService">El precio pagado al proveedor por la compra de este artículo de inventario.</p>
                                <p class="mt-2 text-xs text-slate-400" x-show="isService">Deja en 0 si es mano de obra propia. Si el servicio es subcontratado o pagas una comisión fija, escribe aquí ese costo directo.</p>
                            </div>

                            <div>
                                <x-ui.forms.input
                                    label="Precio de Venta ({{ config('regional.currency_symbol') }})"
                                    name="price"
                                    type="number"
                                    step="0.01"
                                    value="{{ old('price', $product->price) }}"
                                    placeholder="0.00"
                                    required
                                    hint="Precio neto — sin impuesto incluido. Los impuestos marcados abajo se suman al cobrar."
                                    :error="$errors->first('price')"
                                />
                            </div>
                        </div>

                        {{-- Impuestos --}}
                        <div class="bg-zertix-primary/5 rounded-2xl p-6 border border-zertix-primary/20">
                            <h4 class="text-[11px] font-black text-zertix-primary-dark uppercase mb-4 flex items-center gap-2 tracking-widest">
                                <x-heroicon-s-receipt-percent class="w-4 h-4" />
                                Impuestos
                            </h4>

                            @php $oldTaxKeys = old('tax_keys', $product->taxes()); @endphp

                            {{-- ITBIS: mutuamente excluyente por regla de la DGII (un producto no
                                 puede ser Exento y estar gravado al 18% a la vez) — radio buttons,
                                 mismo name="tax_keys[]" que los checkboxes de abajo para que el radio
                                 marcado se combine con ellos en un solo array al enviar el form. --}}
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">ITBIS (elige uno)</p>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-5">
                                @foreach($itbisTaxes as $key => $tax)
                                    <x-ui.forms.radio
                                        label="{{ $tax['label'] }}"
                                        name="tax_keys[]"
                                        id="tax_key_{{ $key }}"
                                        value="{{ $key }}"
                                        :checked="in_array($key, $oldTaxKeys)"
                                    />
                                @endforeach
                                <x-ui.forms.radio
                                    label="Sin ITBIS"
                                    name="tax_keys[]"
                                    id="tax_key_none"
                                    value=""
                                    :checked="collect($oldTaxKeys)->intersect($itbisTaxes->keys())->isEmpty()"
                                />
                            </div>

                            {{-- Aditivos: se apilan libremente entre sí y con el ITBIS elegido
                                 arriba (ej. ITBIS 18% + ISC 10%) — checkboxes. --}}
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Otros impuestos (opcional, apilables)</p>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                @foreach($addonTaxes as $key => $tax)
                                    <x-ui.forms.checkbox
                                        label="{{ $tax['label'] }}"
                                        name="tax_keys[]"
                                        id="tax_key_{{ $key }}"
                                        value="{{ $key }}"
                                        :checked="in_array($key, $oldTaxKeys)"
                                    />
                                @endforeach
                            </div>
                            <p class="mt-4 text-xs text-slate-500">
                                El ITBIS es único por producto (no se puede combinar con otro ITBIS). Los demás sí se suman libremente — ej. Internet suele llevar ITBIS 18% + ISC 10%.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex items-center justify-between gap-3">
                <span class="text-[10px] text-slate-400 uppercase font-bold">Última actualización: {{ $product->updated_at->format('d/m/Y H:i') }}</span>
                <div class="flex gap-3">
                    <x-ui.button href="{{ route('inventory.products.index') }}" appearance="ghost" variant="secondary">Cancelar</x-ui.button>
                    <x-ui.button type="submit" variant="primary" class="rounded-2xl px-8 py-3">
                        Actualizar Producto/Servicio
                    </x-ui.button>
                </div>
            </div>
        </form>
    </div>
</x-app-layout>
