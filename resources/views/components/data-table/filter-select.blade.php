@props([
    'label', 
    'name', 
    'formId' => ''
])

<x-ui.forms.select
    :label="$label"
    :name="$name"
    :form="$formId ?: null"
    placeholder=""
>
    {{ $slot }}
</x-ui.forms.select>