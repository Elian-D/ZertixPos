@props([
    'placeholder' => 'Buscar...',
    'name' => 'search',
    'formId' => ''
])

{{--
    Botón de lupa clickeable: como iconLeft de x-ui.forms.input solo renderiza un ícono
    estático (pointer-events-none, ver Input::iconWrapClasses()), superponemos un botón
    real en las mismas coordenadas (left-3/top-1/2, z-10) que dispara el submit del form
    vía x-ref apuntando al <input> real que el componente renderiza — más robusto que el
    querySelector anterior, que dependía de la estructura DOM plana que este componente
    ya no tiene. NO se pasa `icon-left` al input: el componente pintaría su propio ícono
    estático encima de este botón (misma posición) duplicando la lupa visualmente — la
    clase `pl-10` se pasa a mano para conservar el espacio que ese prop reservaría.
--}}
<div class="relative w-full md:w-72" x-data>
    <button
        type="button"
        class="absolute left-3 top-1/2 -translate-y-1/2 z-10 flex items-center cursor-pointer hover:text-zertix-primary-600 transition-colors"
        @click="$refs.dtSearchInput.form.dispatchEvent(new Event('submit'))"
        title="Buscar"
    >
        <x-heroicon-s-magnifying-glass class="w-4 h-4 text-gray-400" />
    </button>
    <x-ui.forms.input
        type="text"
        name="{{ $name }}"
        x-ref="dtSearchInput"
        :form="$formId ?: null"
        value="{{ request($name) }}"
        placeholder="{{ $placeholder }}"
        class="pl-10"
    />
</div>