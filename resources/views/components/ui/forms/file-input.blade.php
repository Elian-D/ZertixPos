{{--
    x-ui.forms.file-input
    ----------------------
    Props: label, name, id, iconLeft, error, hint, required, disabled, accept, multiple
    Estilo "caja" (border+rounded-lg), consistente con el resto de x-ui.forms.*
    — a diferencia del original de Orvian (underline "Line UI").
--}}
<div class="flex flex-col min-w-0 group w-full"
     x-data="{
        fileName: null,
        clear() { this.fileName = null; $refs.fileInput.value = ''; }
     }">

    {{-- Label --}}
    @if($label)
        <label for="{{ $id }}"
            @class([
                "text-xs font-semibold mb-1.5 block transition-colors",
                "text-state-error" => $error,
                "text-slate-600" => !$error,
            ])>
            {{ $label }}
            @if($required) <span class="text-state-error ml-0.5">*</span> @endif
        </label>
    @endif

    {{-- Input Container --}}
    <div class="relative flex items-center">
        {{-- Icono Izquierdo --}}
        @if($iconLeft)
            <span @class([
                "absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 pointer-events-none transition-colors",
                "text-state-error" => $error,
                "text-slate-400 group-focus-within:text-zertix-primary" => !$error,
            ])>
                <x-dynamic-component :component="$iconLeft" class="w-5 h-5" />
            </span>
        @endif

        {{-- Fake Input Display (caja clickeable). "bg-white" no va en la clase
             base incondicional: competiría con "bg-state-error/5" en el orden
             de la hoja compilada — cada estado declara su propio fondo. --}}
        <div @class([
            "w-full rounded-lg border pl-10 pr-10 py-2.5 text-sm transition-colors flex items-center cursor-pointer",
            "border-state-error bg-state-error/5" => $error,
            "bg-white border-slate-200 group-focus-within:border-zertix-primary" => !$error,
            "opacity-50 cursor-not-allowed" => $disabled,
        ])
        @click="$refs.fileInput.click()">
            <span x-text="fileName ? fileName : 'Seleccionar archivo...'"
                  :class="fileName ? 'text-slate-800 font-medium' : 'text-slate-400'">
            </span>
        </div>

        {{-- Input Real (Oculto) --}}
        <input
            type="file"
            x-ref="fileInput"
            id="{{ $id }}"
            name="{{ $name }}"
            accept="{{ $accept }}"
            {{ $multiple ? 'multiple' : '' }}
            {{ $disabled ? 'disabled' : '' }}
            @change="fileName = $event.target.files.length > 1 ? $event.target.files.length + ' archivos' : $event.target.files[0]?.name"
            {{ $attributes->merge(['class' => 'hidden']) }}
        />

        {{-- Icono Derecho (Error o Limpiar) --}}
        <div class="absolute right-3 top-1/2 -translate-y-1/2 flex items-center gap-2">
            @if($error)
                <x-heroicon-s-exclamation-circle class="w-5 h-5 text-state-error" />
            @else
                <button type="button" x-show="fileName" x-cloak @click.stop="clear()" class="text-slate-400 hover:text-state-error transition-colors">
                    <x-heroicon-o-x-mark class="w-5 h-5" />
                </button>
            @endif
        </div>
    </div>

    {{-- Mensajes de Error o Hint --}}
    @if($error)
        <p class="mt-1.5 text-xs text-state-error font-medium break-words">
            {{ $error }}
        </p>
    @elseif($hint)
        <p class="mt-1.5 text-xs text-slate-400 break-words">
            {{ $hint }}
        </p>
    @endif
</div>
