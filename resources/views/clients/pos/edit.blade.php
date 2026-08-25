<x-app-layout>
    <div class="max-w-4xl mx-auto py-8 px-4">
        <form action="{{ route('clients.delivery_points.update', $pos) }}" method="POST"
            class="bg-white shadow-xl rounded-xl overflow-hidden border border-gray-100">
            @csrf
            @method('PUT')


            <x-form-header
                title="Editar Punto de Venta"
                subtitle="Gestione los detalles del comercio y su ubicación."
                :back-route="route('clients.delivery_points.index')" />

            <div class="p-8 space-y-10">

                {{-- Sección 1: Información Principal --}}
                <section>
                    <div class="flex items-center gap-2 mb-6 border-b border-gray-100 pb-2">
                        <div class="w-7 h-7 bg-indigo-600 text-white rounded-full flex items-center justify-center font-bold text-xs">1</div>
                        <h3 class="font-bold text-gray-800 uppercase text-xs tracking-wider">Información General</h3>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="md:col-span-2">
                            <x-ui.forms.input
                                label="Nombre del Punto de Venta"
                                name="name"
                                value="{{ old('name', $pos->name) }}"
                                :error="$errors->first('name')"
                                required
                            />
                        </div>

                        <div>
                            <x-ui.forms.select
                                label="Cliente Propietario"
                                name="client_id"
                                :error="$errors->first('client_id')"
                                required
                            >
                                @foreach($clients as $client)
                                    <option value="{{ $client->id }}" {{ old('client_id', $pos->client_id) == $client->id ? 'selected' : '' }}>
                                        {{ $client->name }}
                                    </option>
                                @endforeach
                            </x-ui.forms.select>
                        </div>

                        <div>
                            @can('pos regenerate-code')
                                {{-- Usuario con permiso: Puede editar y el Service regenerará el código --}}
                                <x-ui.forms.select
                                    label="Tipo de Negocio"
                                    name="business_type_id"
                                    class="border-amber-300 bg-amber-50"
                                    :error="$errors->first('business_type_id')"
                                    required
                                >
                                    @foreach($businessTypes as $type)
                                        <option value="{{ $type->id }}" {{ old('business_type_id', $pos->business_type_id) == $type->id ? 'selected' : '' }}>
                                            {{ $type->nombre }} ({{ $type->prefix }})
                                        </option>
                                    @endforeach
                                </x-ui.forms.select>
                                <p class="text-[10px] text-amber-600 mt-1 italic">* Tienes permiso para cambiar el tipo. El código se regenerará al actualizar.</p>
                            @else
                                {{-- Usuario sin permiso: Solo lectura --}}
                                <x-ui.forms.select
                                    label="Tipo de Negocio"
                                    class="bg-gray-100 text-gray-500 cursor-not-allowed"
                                    disabled
                                >
                                    @foreach($businessTypes as $type)
                                        <option value="{{ $type->id }}" {{ $pos->business_type_id == $type->id ? 'selected' : '' }}>
                                            {{ $type->nombre }}
                                        </option>
                                    @endforeach
                                </x-ui.forms.select>
                                {{-- Campo oculto para que el valor no se pierda en el request si no tiene permiso --}}
                                <input type="hidden" name="business_type_id" value="{{ $pos->business_type_id }}">
                                <p class="text-[10px] text-gray-400 mt-1 italic">* Para cambiar el tipo, contacte al administrador.</p>
                            @endcan
                        </div>
                    </div>
                </section>

                {{-- Sección 2: Contacto y Estado --}}
                <section>
                    <div class="flex items-center gap-2 mb-6 border-b border-gray-100 pb-2">
                        <div class="w-7 h-7 bg-indigo-600 text-white rounded-full flex items-center justify-center font-bold text-xs">2</div>
                        <h3 class="font-bold text-gray-800 uppercase text-xs tracking-wider">Contacto y Disponibilidad</h3>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        {{-- Nombre del Contacto --}}
                        <div>
                            <x-ui.forms.input
                                label="Nombre del Contacto"
                                name="contact_name"
                                value="{{ old('contact_name', $pos->contact_name) }}"
                                placeholder="Ej: Juan Pérez"
                                :error="$errors->first('contact_name')"
                            />
                        </div>

                        {{-- Teléfono del Contacto --}}
                        <div>
                            <x-ui.forms.input
                                label="Teléfono del Contacto"
                                name="contact_phone"
                                value="{{ old('contact_phone', $pos->contact_phone) }}"
                                placeholder="809-000-0000"
                                :error="$errors->first('contact_phone')"
                            />
                        </div>

                        {{-- Estado del Punto de Venta (Select) --}}
                        <div>
                            <x-ui.forms.select
                                label="Estado del Comercio"
                                name="active"
                                placeholder=""
                                :error="$errors->first('active')"
                            >
                                <option value="1" {{ old('active', $pos->active) == 1 ? 'selected' : '' }}>Activo / Operativo</option>
                                <option value="0" {{ old('active', $pos->active) == 0 ? 'selected' : '' }}>Inactivo / Cerrado</option>
                            </x-ui.forms.select>
                        </div>
                    </div>
                </section>

                {{-- Sección 3: Ubicación Geográfica --}}
                <section>
                    <div class="flex items-center justify-between mb-6 border-b border-gray-100 pb-2">
                        <div class="flex items-center gap-2">
                            <div class="w-7 h-7 bg-emerald-600 text-white rounded-full flex items-center justify-center font-bold text-xs">3</div>
                            <h3 class="font-bold text-gray-800 uppercase text-xs tracking-wider">Localización</h3>
                        </div>
                        <button type="button" id="btnGeo" class="text-[10px] bg-emerald-50 border border-emerald-200 text-emerald-700 px-3 py-1 rounded-full hover:bg-emerald-600 hover:text-white transition-all font-bold">
                            ACTUALIZAR GPS ACTUAL
                        </button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <x-ui.forms.select
                                label="Provincia"
                                name="provincia_id"
                                :error="$errors->first('provincia_id')"
                                required
                            >
                                @foreach($states as $state)
                                    <option value="{{ $state->id }}" {{ old('provincia_id', $pos->provincia_id) == $state->id ? 'selected' : '' }}>
                                        {{ $state->name }}
                                    </option>
                                @endforeach
                            </x-ui.forms.select>
                        </div>
                        <div>
                            <x-ui.forms.input
                                label="Ciudad"
                                name="city"
                                value="{{ old('city', $pos->city) }}"
                                :error="$errors->first('city')"
                                required
                            />
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div id="map" style="width:100%; height:300px; border-radius:12px; border:1px solid #e5e7eb" class="shadow-inner"></div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <x-ui.forms.input
                                    label="Latitud"
                                    name="latitude"
                                    id="lat"
                                    class="bg-gray-50 font-mono text-xs"
                                    value="{{ old('latitude', $pos->latitude) }}"
                                    :error="$errors->first('latitude')"
                                    hint="Se completa automáticamente al usar el mapa"
                                    readonly
                                />
                            </div>
                            <div>
                                <x-ui.forms.input
                                    label="Longitud"
                                    name="longitude"
                                    id="lng"
                                    class="bg-gray-50 font-mono text-xs"
                                    value="{{ old('longitude', $pos->longitude) }}"
                                    :error="$errors->first('longitude')"
                                    hint="Se completa automáticamente al usar el mapa"
                                    readonly
                                />
                            </div>
                        </div>

                        <div>
                            <x-ui.forms.input
                                label="Dirección Descriptiva"
                                name="address"
                                value="{{ old('address', $pos->address) }}"
                                placeholder="Calle, número, referencia..."
                                :error="$errors->first('address')"
                            />
                        </div>
                    </div>
                </section>

                {{-- Sección 4: Notas --}}
                <section>
                    <div class="flex items-center gap-2 mb-4">
                        <div class="w-7 h-7 bg-gray-600 text-white rounded-full flex items-center justify-center font-bold text-xs">4</div>
                        <h3 class="font-bold text-gray-700 uppercase text-xs tracking-wider">Notas Adicionales</h3>
                    </div>
                    <x-ui.forms.textarea
                        name="notes"
                        :rows="3"
                        placeholder="Observaciones sobre este punto de venta..."
                        :error="$errors->first('notes')"
                    >{{ old('notes', $pos->notes) }}</x-ui.forms.textarea>
                </section>
            </div>

            <div class="p-6 bg-gray-50 flex justify-end gap-3 border-t">
                <a href="{{ route('clients.delivery_points.index') }}" class="px-4 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 transition">Cancelar</a>
                <x-ui.button type="submit" variant="primary" class="shadow-lg px-8">Actualizar Punto de Venta</x-ui.button>
            </div>
        </form>
    </div>

    <script>
        function initMap() {
            const latIn = document.getElementById('lat');
            const lngIn = document.getElementById('lng');

            let initialLat = parseFloat(latIn.value) || 18.4861;
            let initialLng = parseFloat(lngIn.value) || -69.9312;

            const center = { lat: initialLat, lng: initialLng };
            const map = new google.maps.Map(document.getElementById('map'), {
                zoom: 15,
                center: center,
                mapTypeControl: false,
                streetViewControl: false,
                fullscreenControl: false
            });

            const marker = new google.maps.Marker({
                position: center,
                map: map,
                draggable: true,
            });

            // Función solo para actualizar inputs numéricos
            function updateLatLngInputs(latLng) {
                latIn.value = latLng.lat().toFixed(6);
                lngIn.value = latLng.lng().toFixed(6);
            }

            map.addListener('click', (e) => {
                marker.setPosition(e.latLng);
                updateLatLngInputs(e.latLng);
            });

            marker.addListener('dragend', (e) => {
                updateLatLngInputs(e.latLng);
            });

            document.getElementById('btnGeo').onclick = function() {
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition((pos) => {
                        const p = { lat: pos.coords.latitude, lng: pos.coords.longitude };
                        map.setCenter(p);
                        map.setZoom(17);
                        marker.setPosition(p);
                        updateLatLngInputs(marker.getPosition());
                    }, (err) => {
                        alert("Error: " + err.message);
                    }, { enableHighAccuracy: true });
                }
            };
        }
    </script>
    <script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google.maps_key') }}&callback=initMap" async defer></script>
</x-app-layout>
