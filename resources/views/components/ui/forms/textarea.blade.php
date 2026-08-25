{{--
    x-ui.forms.textarea
    -------------------
    Props: label, name, id, placeholder, rows, error, hint, required, disabled, readonly, resize
--}}

<div class="flex flex-col min-w-0 group">

    {{-- Label --}}
    @if ($label)
        <label
            for="{{ $id }}"
            class="text-xs font-semibold mb-1.5 block transition-colors duration-200
                   {{ $error ? 'text-state-error' : 'text-slate-600' }}"
        >
            {{ $label }}
            @if ($required)
                <span class="text-state-error ml-0.5">*</span>
            @endif
        </label>
    @endif

    <textarea
        name="{{ $name }}"
        id="{{ $id }}"
        rows="{{ $rows }}"
        placeholder="{{ $placeholder }}"
        @disabled($disabled)
        @readonly($readonly)
        @required($required)
        {{ $attributes->merge(['class' => $textareaClasses()]) }}
    ></textarea>

    {{-- Mensaje de error o hint --}}
    @if ($error)
        <p class="mt-1.5 text-xs font-medium text-state-error break-words">
            {{ $error }}
        </p>
    @elseif ($hint)
        <p class="mt-1.5 text-xs text-slate-400 break-words">
            {{ $hint }}
        </p>
    @endif

</div>
