@props([
    'options' => [10, 20, 50, 100],
    'formId'  => '' {{-- Valor por defecto si no se envía --}}
])

<div class="flex items-center gap-2 text-sm text-gray-600">
    <label for="per_page_selector" class="font-medium whitespace-nowrap">Mostrar:</label>
    <div class="w-24">
        <x-ui.forms.select
            name="per_page"
            id="per_page_selector"
            form="{{ $formId }}"
            placeholder=""
        >
            @foreach($options as $value)
                <option value="{{ $value }}" @selected(request('per_page') == $value)>
                    {{ $value }}
                </option>
            @endforeach
        </x-ui.forms.select>
    </div>
</div>