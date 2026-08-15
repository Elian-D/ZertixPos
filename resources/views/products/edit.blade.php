<x-app-layout>
    <div class="max-w-4xl mx-auto py-8 px-4"
        x-data="{
            isService: {{ old('is_stockable') !== null ? (old('is_stockable') == '0' ? 'true' : 'false') : ($product->is_stockable ? 'false' : 'true') }},
            categoryId: '{{ old('category_id', $product->category_id) }}',
            unitId: '{{ old('unit_id', $product->unit_id) }}',
            servicesCategoryId: @js($categories->firstWhere('name', 'Servicios')?->id),
            unidadUnitId: @js($units->firstWhere('name', 'Unidad')?->id),
            selectType(service) {
                this.isService = service;
                if (service) {
                    if (this.servicesCategoryId) this.categoryId = this.servicesCategoryId;
                    if (this.unidadUnitId) this.unitId = this.unidadUnitId;
                }
            },
        }">
        {{-- Método PUT para actualización y enctype para la imagen --}}
        <form action="{{ route('inventory.products.update', $product) }}" method="POST" enctype="multipart/form-data"
            class="bg-white shadow-xl rounded-xl overflow-hidden border border-gray-100">
            @csrf
            @method('PUT')

            <x-ui.toasts />

            <x-form-header
                :title="'Editar: ' . $product->name"
                subtitle="Modifique los parámetros del producto/servicio y actualice el catálogo."
                :back-route="route('inventory.products.index')" />

            <div class="p-8 space-y-10">

                {{-- Sección 0: Tipo de Ítem --}}
                <section>
                    <div class="flex items-center gap-2 mb-6 border-b border-gray-100 pb-2">
                        <div class="w-7 h-7 bg-gray-700 text-white rounded-full flex items-center justify-center font-bold text-xs">0</div>
                        <h3 class="font-bold text-gray-800 uppercase text-xs tracking-wider">Tipo de Ítem</h3>
                    </div>

                    <input type="hidden" name="is_stockable" :value="isService ? 0 : 1">

                    <div class="grid grid-cols-2 gap-2 p-1 bg-gray-100 rounded-xl">
                        <button type="button" @click="selectType(false)"
                            :class="!isService ? 'bg-white shadow-sm text-indigo-600' : 'text-gray-500 hover:text-gray-700'"
                            class="flex items-center justify-center gap-2 py-2.5 rounded-lg text-sm font-bold transition-all">
                            <x-heroicon-s-cube class="w-4 h-4"/>
                            Producto
                        </button>
                        <button type="button" @click="selectType(true)"
                            :class="isService ? 'bg-white shadow-sm text-indigo-600' : 'text-gray-500 hover:text-gray-700'"
                            class="flex items-center justify-center gap-2 py-2.5 rounded-lg text-sm font-bold transition-all">
                            <x-heroicon-s-wrench-screwdriver class="w-4 h-4"/>
                            Servicio
                        </button>
                    </div>
                    <p class="mt-2 text-xs text-gray-400 italic" x-show="isService">
                        Un servicio no descuenta inventario ni requiere existencias en almacén (ej. instalación, flete, mano de obra).
                    </p>
                </section>

                {{-- Sección 1: Identificación y Categoría --}}
                <section>
                    <div class="flex items-center gap-2 mb-6 border-b border-gray-100 pb-2">
                        <div class="w-7 h-7 bg-indigo-600 text-white rounded-full flex items-center justify-center font-bold text-xs">1</div>
                        <h3 class="font-bold text-gray-800 uppercase text-xs tracking-wider">Información General</h3>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="md:col-span-2">
                            <x-input-label value="Nombre" />
                            <x-text-input name="name" class="w-full mt-1" :value="old('name', $product->name)" placeholder="Ej: Funda de Hielo 10lb" required />
                        </div>

                        <div>
                            <x-input-label value="Categoría" />
                            <select name="category_id" x-model="categoryId" class="w-full mt-1 rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 text-sm" required>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}">
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <x-input-label value="Unidad de Medida" />
                            <template x-if="!isService">
                                <select name="unit_id" x-model="unitId" class="w-full mt-1 rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 text-sm" required>
                                    @foreach($units as $unit)
                                        <option value="{{ $unit->id }}">
                                            {{ $unit->name }} ({{ $unit->abbreviation }})
                                        </option>
                                    @endforeach
                                </select>
                            </template>
                            <template x-if="isService">
                                <div>
                                    <input type="hidden" name="unit_id" :value="unitId">
                                    <div class="w-full mt-1 rounded-md border-gray-200 bg-gray-50 text-sm text-gray-500 px-3 py-2">
                                        {{ $units->firstWhere('name', 'Unidad')?->name ?? 'Unidad' }} (fija para servicios)
                                    </div>
                                </div>
                            </template>
                        </div>

                        <div class="md:col-span-2">
                            <x-input-label value="Descripción" />
                            <textarea name="description" rows="2" class="w-full mt-1 rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 text-sm" placeholder="Detalles adicionales...">{{ old('description', $product->description) }}</textarea>
                        </div>
                    </div>
                </section>

                {{-- Sección 2: Precios e Inventario --}}
                <section>
                    <div class="flex items-center gap-2 mb-6 border-b border-gray-100 pb-2">
                        <div class="w-7 h-7 bg-emerald-600 text-white rounded-full flex items-center justify-center font-bold text-xs">2</div>
                        <h3 class="font-bold text-gray-800 uppercase text-xs tracking-wider">Precios y Stock</h3>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                        <div class="md:col-span-2">
                            <x-input-label value="Precio de Venta" />
                            <div class="relative mt-1">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500 text-sm">{{ config('regional.currency_symbol') }}</span>
                                <x-text-input type="number" step="0.01" name="price" class="w-full pl-14" :value="old('price', $product->price)" required />
                            </div>
                        </div>

                        <div class="md:col-span-2">
                            <x-input-label value="Costo" />
                            <div class="relative mt-1">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500 text-sm">{{ config('regional.currency_symbol') }}</span>
                                <x-text-input type="number" step="0.01" name="cost" class="w-full pl-14" :value="old('cost', $product->cost)" required />
                            </div>
                            <p class="mt-1 text-xs text-gray-800" x-show="!isService">
                                El precio pagado al proveedor por la compra de este artículo de inventario.
                            </p>
                            <p class="mt-1 text-xs text-gray-800" x-show="isService">
                                Deja en 0 si es mano de obra propia. Si el servicio es subcontratado (tercerizado) o pagas una comisión fija al técnico, escribe aquí ese costo directo.
                            </p>
                        </div>

                        <div class="md:col-span-4 flex items-center gap-4 bg-gray-50 p-4 rounded-lg border border-gray-100">
                            <x-input-label value="Estado Activo" class="mb-0" />
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $product->is_active) ? 'checked' : '' }} class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 w-5 h-5 cursor-pointer">
                        </div>
                    </div>
                </section>

                {{-- Sección 3: Imagen del Producto --}}
                <section>
                    <div class="flex items-center gap-2 mb-6 border-b border-gray-100 pb-2">
                        <div class="w-7 h-7 bg-amber-500 text-white rounded-full flex items-center justify-center font-bold text-xs">3</div>
                        <h3 class="font-bold text-gray-800 uppercase text-xs tracking-wider">Imagen del Producto</h3>
                    </div>

                    <div class="flex flex-col md:flex-row items-center gap-8">
                        <div class="w-full md:w-1/3 flex justify-center">
                            <div id="image-preview-container" class="w-48 h-48 rounded-2xl border-2 border-solid border-indigo-100 flex items-center justify-center bg-gray-50 overflow-hidden relative group">
                                @if($product->image_path)
                                    <img id="image-preview" src="{{ $product->image_url }}" alt="{{ $product->name }}" class="w-full h-full object-cover" />
                                    <div id="placeholder-icon" class="hidden flex flex-col items-center text-gray-400">
                                        <x-heroicon-s-photo class="w-12 h-12" />
                                    </div>
                                @else
                                    <img id="image-preview" src="#" alt="Vista previa" class="hidden w-full h-full object-cover" />
                                    <div id="placeholder-icon" class="flex flex-col items-center text-gray-400">
                                        <x-heroicon-s-photo class="w-12 h-12" />
                                        <span class="text-[10px] uppercase font-bold mt-2">Sin imagen</span>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="flex-1 w-full">
                            <x-input-label value="Cambiar Imagen" />
                            <input type="file" name="image" id="image-input" accept="image/*"
                                class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 transition-all cursor-pointer" />
                            <p class="mt-2 text-xs text-gray-400">Si no selecciona un archivo, se mantendrá la imagen actual.</p>
                        </div>
                    </div>
                </section>
            </div>

            <div class="p-6 bg-gray-50 flex justify-end gap-3 border-t">
                <div class="flex-1">
                    <span class="text-[10px] text-gray-400 uppercase font-bold">Última actualización: {{ $product->updated_at->format('d/m/Y H:i') }}</span>
                </div>
                <a href="{{ route('inventory.products.index') }}" class="px-4 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 transition">Cancelar</a>
                <x-primary-button class="bg-indigo-600 hover:bg-indigo-700 shadow-lg px-8">Actualizar Producto/Servicio</x-primary-button>
            </div>
        </form>
    </div>

    <script>
        document.getElementById('image-input').onchange = function (evt) {
            const [file] = this.files;
            if (file) {
                const preview = document.getElementById('image-preview');
                const placeholder = document.getElementById('placeholder-icon');

                preview.src = URL.createObjectURL(file);
                preview.classList.remove('hidden');
                if(placeholder) placeholder.classList.add('hidden');
            }
        }
    </script>
</x-app-layout>
