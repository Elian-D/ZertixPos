{{--
    x-ui.forms.file-input
    ----------------------
    Props: label, name, id, iconLeft, error, hint, required, disabled, accept, multiple, preview, dropzone
    Estilo "caja" (border+rounded-lg), consistente con el resto de x-ui.forms.*
    — a diferencia del original de Orvian (underline "Line UI"). `dropzone`
    (opt-in) cambia a una caja cuadrada de arrastrar/soltar con ícono+texto
    centrados (logo/foto) — ver docs/ui/forms.md.
--}}
<div class="flex flex-col min-w-0 group w-full"
     x-data="{
        fileName: null,
        previewUrl: null,
        clear() {
            this.fileName = null;
            if (this.previewUrl) { URL.revokeObjectURL(this.previewUrl); this.previewUrl = null; }
            $refs.fileInput.value = '';
        },
        onChange(e) {
            const files = e.target.files;
            this.fileName = files.length > 1 ? files.length + ' archivos' : files[0]?.name ?? null;
            if (this.previewUrl) { URL.revokeObjectURL(this.previewUrl); this.previewUrl = null; }
            @if ($preview)
            if (files[0] && files[0].type.startsWith('image/')) {
                this.previewUrl = URL.createObjectURL(files[0]);
            }
            @endif
        },
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

    @if ($dropzone)
        {{-- Variante "zona de arrastre" cuadrada (logo/foto) — el archivo
             real sigue siendo el mismo <input type="file"> oculto, solo
             cambia la caja clickeable de alrededor. --}}
        <div @class([
                $dropzoneSizeClasses(),
                "relative rounded-xl flex flex-col items-center justify-center p-3 text-center cursor-pointer transition-all overflow-hidden",
                "border-2 border-dashed" => true,
                "border-state-error bg-state-error/5" => $error,
                "border-slate-200 hover:border-zertix-primary/70 bg-slate-50/50 hover:bg-white" => !$error,
                "opacity-50 cursor-not-allowed" => $disabled,
             ])
             @click="$refs.fileInput.click()">

            <template x-if="previewUrl">
                <img :src="previewUrl" class="absolute inset-0 w-full h-full object-cover" />
            </template>

            <template x-if="!previewUrl">
                <div class="flex flex-col items-center">
                    <span @class([
                        "w-9 h-9 rounded-full flex items-center justify-center transition-colors mb-1.5",
                        "text-state-error bg-state-error/10" => $error,
                        "text-slate-400 bg-slate-100" => !$error,
                    ])>
                        <x-heroicon-o-photo class="w-5 h-5" />
                    </span>
                    <span class="text-xs font-semibold text-slate-700" x-text="fileName || 'Subir logo'"></span>
                    @if ($hint)
                        <span class="text-[10px] text-slate-400">{{ $hint }}</span>
                    @endif
                </div>
            </template>

            {{-- Limpiar — solo con archivo elegido, arriba de la miniatura --}}
            <button type="button" x-show="previewUrl || fileName" x-cloak @click.stop="clear()"
                class="absolute top-1.5 right-1.5 w-6 h-6 rounded-full bg-white/90 shadow-sm flex items-center justify-center text-slate-500 hover:text-state-error transition-colors">
                <x-heroicon-o-x-mark class="w-3.5 h-3.5" />
            </button>
        </div>

        <input
            type="file"
            x-ref="fileInput"
            id="{{ $id }}"
            name="{{ $name }}"
            accept="{{ $accept }}"
            {{ $multiple ? 'multiple' : '' }}
            {{ $disabled ? 'disabled' : '' }}
            @change="onChange($event)"
            {{ $attributes->merge(['class' => 'hidden']) }}
        />
    @else
        {{-- Input Container --}}
        <div class="relative flex items-center">
            {{-- Miniatura (preview) — reemplaza iconLeft mientras haya una imagen elegida --}}
            @if ($preview)
                <img x-show="previewUrl" x-cloak :src="previewUrl"
                     class="absolute left-2 top-1/2 -translate-y-1/2 w-7 h-7 rounded object-cover pointer-events-none" />
            @endif

            {{-- Icono Izquierdo --}}
            @if($iconLeft)
                <span @class([
                    "absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 pointer-events-none transition-colors",
                    "text-state-error" => $error,
                    "text-slate-400 group-focus-within:text-zertix-primary" => !$error,
                ])
                @if ($preview) x-show="!previewUrl" @endif
                >
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
                @change="onChange($event)"
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
    @endif

    {{-- Mensajes de Error o Hint — en `dropzone`, el hint ya se muestra
         adentro del cuadro (junto a "Subir logo"), así que acá solo el error
         real de validación, para no repetirlo dos veces. --}}
    @if($error)
        <p class="mt-1.5 text-xs text-state-error font-medium break-words">
            {{ $error }}
        </p>
    @elseif($hint && ! $dropzone)
        <p class="mt-1.5 text-xs text-slate-400 break-words">
            {{ $hint }}
        </p>
    @endif
</div>
