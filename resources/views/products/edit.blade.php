<x-app-layout>
    <div class="max-w-7xl mx-auto py-8 px-4"
        x-cloak
        x-data="{
            isService: {{ old('is_stockable') !== null ? (old('is_stockable') == '0' ? 'true' : 'false') : ($product->is_stockable ? 'false' : 'true') }},
            categoryId: '{{ old('category_id', $product->category_id) }}',
            unitId: '{{ old('unit_id', $product->unit_id) }}',
            servicesCategoryId: @js($categories->firstWhere('name', 'Servicios')?->id),
            unidadUnitId: @js($units->firstWhere('name', 'Unidad')?->id),
            isActive: {{ old('is_active', $product->is_active) ? 'true' : 'false' }},
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

            <x-ui.toasts />

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
                        <div>
                            <label class="text-xs font-bold text-slate-400 uppercase mb-2 block tracking-wider">Nombre del producto <span class="text-red-500">*</span></label>
                            <input type="text" name="name" value="{{ old('name', $product->name) }}" required placeholder="Ej: Funda de Hielo 10lb"
                                class="w-full bg-slate-50 border-2 border-slate-100 rounded-2xl px-5 py-4 text-sm font-semibold text-slate-700 focus:border-zertix-primary focus:ring-0" />
                        </div>

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
                            <div>
                                <label class="text-xs font-bold text-slate-400 uppercase mb-2 block tracking-wider">Categoría</label>
                                <select name="category_id" x-model="categoryId" required
                                    class="w-full bg-slate-50 border-2 border-slate-100 rounded-2xl px-5 py-4 text-sm font-semibold text-slate-700 focus:border-zertix-primary">
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="text-xs font-bold text-slate-400 uppercase mb-2 block tracking-wider">Unidad de Medida</label>
                                <template x-if="!isService">
                                    <select name="unit_id" x-model="unitId" required
                                        class="w-full bg-slate-50 border-2 border-slate-100 rounded-2xl px-5 py-4 text-sm font-semibold text-slate-700 focus:border-zertix-primary">
                                        @foreach($units as $unit)
                                            <option value="{{ $unit->id }}">{{ $unit->name }} ({{ $unit->abbreviation }})</option>
                                        @endforeach
                                    </select>
                                </template>
                                <template x-if="isService">
                                    <div>
                                        <input type="hidden" name="unit_id" :value="unitId">
                                        <div class="w-full bg-slate-50 border-2 border-slate-100 rounded-2xl px-5 py-4 text-sm text-slate-400">
                                            {{ $units->firstWhere('name', 'Unidad')?->name ?? 'Unidad' }} (fija para servicios)
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        {{-- Descripción --}}
                        <div>
                            <label class="text-xs font-bold text-slate-400 uppercase mb-2 block tracking-wider">Descripción</label>
                            <textarea name="description" rows="3" placeholder="Detalles adicionales..."
                                class="w-full bg-slate-50 border-2 border-slate-100 rounded-2xl px-5 py-4 text-sm font-semibold text-slate-700 focus:border-zertix-primary focus:ring-0">{{ old('description', $product->description) }}</textarea>
                        </div>

                        {{-- Activo --}}
                        <div class="flex items-center gap-4 bg-zertix-primary/5 p-5 rounded-2xl border border-zertix-primary/20">
                            <input type="hidden" name="is_active" value="0">
                            <label class="relative inline-flex items-center cursor-pointer shrink-0">
                                <input type="checkbox" name="is_active" value="1" x-model="isActive" class="sr-only peer">
                                <div class="w-11 h-6 bg-slate-200 rounded-full peer peer-checked:bg-zertix-primary transition-colors"></div>
                                <div class="absolute left-1 top-1 bg-white w-4 h-4 rounded-full transition-transform peer-checked:translate-x-5"></div>
                            </label>
                            <div>
                                <span class="text-sm font-bold text-slate-700 block">Activo</span>
                                <span class="text-xs text-slate-400">Determina si el producto/servicio aparece en el POS.</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- PANEL 2: Precios --}}
                <div x-show="activeTab === 'precios'" x-cloak>
                    <div class="p-8 space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="text-xs font-bold text-slate-400 uppercase mb-2 block tracking-wider">Precio de Costo ({{ config('regional.currency_symbol') }})</label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-5 flex items-center text-slate-400 text-sm font-bold">{{ config('regional.currency_symbol') }}</span>
                                    <input type="number" step="0.01" name="cost" value="{{ old('cost', $product->cost) }}" required
                                        class="w-full bg-slate-50 border-2 border-slate-100 rounded-2xl pl-14 pr-5 py-4 text-sm font-semibold text-slate-700 focus:border-zertix-primary focus:ring-0" />
                                </div>
                                <p class="mt-2 text-xs text-slate-400" x-show="!isService">El precio pagado al proveedor por la compra de este artículo de inventario.</p>
                                <p class="mt-2 text-xs text-slate-400" x-show="isService">Deja en 0 si es mano de obra propia. Si el servicio es subcontratado o pagas una comisión fija, escribe aquí ese costo directo.</p>
                            </div>

                            <div>
                                <label class="text-xs font-bold text-slate-400 uppercase mb-2 block tracking-wider">Precio de Venta ({{ config('regional.currency_symbol') }}) <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-5 flex items-center text-slate-400 text-sm font-bold">{{ config('regional.currency_symbol') }}</span>
                                    <input type="number" step="0.01" name="price" value="{{ old('price', $product->price) }}" required
                                        class="w-full bg-slate-50 border-2 border-slate-100 rounded-2xl pl-14 pr-5 py-4 text-sm font-semibold text-slate-700 focus:border-zertix-primary focus:ring-0" />
                                </div>
                                <p class="mt-2 text-xs text-slate-400">Precio neto — sin impuesto incluido. Los impuestos marcados abajo se suman al cobrar.</p>
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
                                    <label class="flex items-center gap-2 text-sm font-semibold text-slate-700 cursor-pointer">
                                        <input type="radio" name="tax_keys[]" value="{{ $key }}"
                                            {{ in_array($key, $oldTaxKeys) ? 'checked' : '' }}
                                            class="border-slate-300 text-zertix-primary focus:ring-zertix-primary w-4 h-4">
                                        {{ $tax['label'] }}
                                    </label>
                                @endforeach
                                <label class="flex items-center gap-2 text-sm font-semibold text-slate-700 cursor-pointer">
                                    <input type="radio" name="tax_keys[]" value=""
                                        {{ collect($oldTaxKeys)->intersect($itbisTaxes->keys())->isEmpty() ? 'checked' : '' }}
                                        class="border-slate-300 text-zertix-primary focus:ring-zertix-primary w-4 h-4">
                                    Sin ITBIS
                                </label>
                            </div>

                            {{-- Aditivos: se apilan libremente entre sí y con el ITBIS elegido
                                 arriba (ej. ITBIS 18% + ISC 10%) — checkboxes. --}}
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Otros impuestos (opcional, apilables)</p>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                @foreach($addonTaxes as $key => $tax)
                                    <label class="flex items-center gap-2.5 text-sm font-semibold text-slate-700 cursor-pointer">
                                        <input type="checkbox" name="tax_keys[]" value="{{ $key }}"
                                            {{ in_array($key, $oldTaxKeys) ? 'checked' : '' }}
                                            class="rounded border-slate-300 text-zertix-primary focus:ring-zertix-primary w-4 h-4">
                                        {{ $tax['label'] }}
                                    </label>
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
                    <a href="{{ route('inventory.products.index') }}" class="px-4 py-2 text-sm font-medium text-slate-500 hover:text-slate-700 transition">Cancelar</a>
                    <button type="submit" class="bg-zertix-primary hover:bg-zertix-primary-dark text-white font-bold text-sm px-8 py-3 rounded-2xl shadow-lg shadow-zertix-primary/20 transition-colors">
                        Actualizar Producto/Servicio
                    </button>
                </div>
            </div>
        </form>
    </div>
</x-app-layout>
