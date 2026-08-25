{{--
    resources/views/examples/form-components-demo.blade.php
    -------------------------------------------------------
    Formulario de demostración de los componentes x-ui.forms.* (Fase 7, REQ-7.5).
    Sin lógica real — solo para revisar el diseño en contexto.
    Ruta temporal: Route::view('/demo/form', 'examples.form-components-demo').
--}}

<x-app-layout>

<div class="max-w-2xl mx-auto py-12 px-4 space-y-12">

    {{-- ── Encabezado ─────────────────────────────────────────────── --}}
    <div class="space-y-1">
        <p class="text-[11px] font-bold uppercase tracking-widest text-zertix-primary">
            Componentes UI
        </p>
        <h1 class="text-2xl font-bold text-zertix-secondary">
            Formulario de Demostración
        </h1>
        <p class="text-sm text-slate-500">
            Revisión visual de los componentes <code class="text-zertix-primary">x-ui.forms.*</code>
        </p>
    </div>

    {{-- Separador de sección --}}
    <div class="flex items-center gap-4">
        <div class="h-px flex-1 bg-slate-100"></div>
        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">01 · Inputs de Texto</span>
        <div class="h-px flex-1 bg-slate-100"></div>
    </div>

    {{-- ── Inputs ──────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-8">

        {{-- Estado normal --}}
        <x-ui.forms.input
            label="Nombre del Cliente"
            name="name"
            placeholder="Ej. Ferretería Duarte SRL"
            icon-left="heroicon-o-building-storefront"
            hint="Nombre comercial o razón social"
        />

        {{-- Con icono derecho --}}
        <x-ui.forms.input
            label="RNC / Cédula"
            name="tax_id"
            placeholder="Ej. 130-12345-6"
            icon-left="heroicon-o-identification"
            icon-right="heroicon-o-information-circle"
            hint="Identificador fiscal DGII"
        />

        {{-- Estado error --}}
        <x-ui.forms.input
            label="Correo Electrónico"
            name="email"
            type="email"
            placeholder="contacto@empresa.com"
            icon-left="heroicon-o-envelope"
            error="Este correo ya está registrado en el sistema"
        />

        {{-- Password con slot --}}
        <div x-data="{ show: false }" class="flex flex-col group">
            <label class="text-xs font-semibold mb-1.5 block text-slate-600">
                Contraseña <span class="text-state-error ml-0.5">*</span>
            </label>
            <div class="relative flex items-center">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 pointer-events-none text-slate-400 group-focus-within:text-zertix-primary transition-colors">
                    <x-heroicon-o-lock-closed class="w-5 h-5" />
                </span>
                <input
                    :type="show ? 'text' : 'password'"
                    name="password"
                    placeholder="Mínimo 8 caracteres"
                    class="w-full rounded-lg border border-slate-200 bg-white pl-10 pr-10 py-2.5 text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-1 focus:border-zertix-primary focus:ring-zertix-primary/20 transition-colors"
                />
                <button
                    type="button"
                    @click="show = !show"
                    class="absolute right-3 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400 hover:text-zertix-primary transition-colors"
                >
                    <x-heroicon-o-eye x-show="!show" class="w-5 h-5" />
                    <x-heroicon-o-eye-slash x-show="show" x-cloak class="w-5 h-5" />
                </button>
            </div>
        </div>

        {{-- Disabled --}}
        <x-ui.forms.input
            label="Código de Cliente"
            name="code"
            placeholder="CLI-000042"
            icon-left="heroicon-o-hashtag"
            hint="Se genera automáticamente"
            :disabled="true"
        />

        {{-- Número --}}
        <x-ui.forms.input
            label="Teléfono"
            name="phone"
            type="tel"
            placeholder="(809) 000-0000"
            icon-left="heroicon-o-phone"
        />

    </div>

    {{-- Separador de sección --}}
    <div class="flex items-center gap-4">
        <div class="h-px flex-1 bg-slate-100"></div>
        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">02 · Selects</span>
        <div class="h-px flex-1 bg-slate-100"></div>
    </div>

    {{-- ── Selects ─────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-8">

        <x-ui.forms.select
            label="Almacén de Origen"
            name="warehouse_id"
            icon-left="heroicon-o-building-office"
            required
        >
            <option value="1">Planta de Producción Principal</option>
            <option value="2">Bodega Secundaria</option>
        </x-ui.forms.select>

        <x-ui.forms.select
            label="Tipo de Comprobante"
            name="ncf_type_id"
            icon-left="heroicon-o-document-text"
            error="Debes seleccionar un tipo de comprobante"
        >
            <option value="01">Crédito Fiscal</option>
            <option value="02">Consumo</option>
        </x-ui.forms.select>

        <x-ui.forms.select
            label="Provincia"
            name="province_id"
            icon-left="heroicon-o-map"
            hint="Filtrará los municipios disponibles"
        >
            <option value="1">Santiago</option>
            <option value="2">Santo Domingo</option>
        </x-ui.forms.select>

        <x-ui.forms.select
            label="Plan"
            name="plan_id"
            icon-left="heroicon-o-credit-card"
            :disabled="true"
            hint="Seleccionado en el paso anterior"
        >
            <option value="pro">Pro — RD$ 2,500/mes</option>
        </x-ui.forms.select>

    </div>

    {{-- Separador --}}
    <div class="flex items-center gap-4">
        <div class="h-px flex-1 bg-slate-100"></div>
        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">03 · Textarea</span>
        <div class="h-px flex-1 bg-slate-100"></div>
    </div>

    {{-- ── Textarea ─────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-8">

        <x-ui.forms.textarea
            label="Referencias de Ubicación"
            name="address_reference"
            placeholder="Ej. Frente al parque central, al lado del banco..."
            :rows="3"
            hint="Opcional — ayuda a localizar al cliente"
        />

        <x-ui.forms.textarea
            label="Observaciones"
            name="observations"
            placeholder="Comentarios adicionales..."
            :rows="3"
            :resize="true"
            error="El campo no puede superar 500 caracteres"
        />

    </div>

    {{-- Separador --}}
    <div class="flex items-center gap-4">
        <div class="h-px flex-1 bg-slate-100"></div>
        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">04 · Checkboxes &amp; Radios</span>
        <div class="h-px flex-1 bg-slate-100"></div>
    </div>

    {{-- ── Checkboxes ─────────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">

        <div class="flex flex-col gap-4">
            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Métodos de Pago Aceptados</p>
            <x-ui.forms.checkbox
                label="Efectivo"
                name="methods[]"
                value="1"
                description="Pago en el momento de la entrega"
            />
            <x-ui.forms.checkbox
                label="Tarjeta de Crédito/Débito"
                name="methods[]"
                value="2"
                :checked="true"
            />
            <x-ui.forms.checkbox
                label="Transferencia Bancaria"
                name="methods[]"
                value="3"
                :checked="true"
            />
            <x-ui.forms.checkbox
                label="Crédito (no disponible)"
                name="methods[]"
                value="4"
                :disabled="true"
            />
        </div>

        <div class="flex flex-col gap-4">
            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Tipo de Cliente</p>
            <x-ui.forms.radio
                label="Persona Física"
                name="client_type"
                value="individual"
                description="Identificado con Cédula"
                :checked="true"
            />
            <x-ui.forms.radio
                label="Empresa"
                name="client_type"
                value="company"
                description="Identificado con RNC"
            />
            <x-ui.forms.radio
                label="Consumidor Final"
                name="client_type"
                value="final"
                description="Sin identificación fiscal"
            />
        </div>

    </div>

    {{-- Separador --}}
    <div class="flex items-center gap-4">
        <div class="h-px flex-1 bg-slate-100"></div>
        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">05 · Toggles</span>
        <div class="h-px flex-1 bg-slate-100"></div>
    </div>

    {{-- ── Toggles ─────────────────────────────────────────────────── --}}
    <div class="flex flex-col gap-6">

        <x-ui.forms.toggle
            label="Notificaciones por Correo"
            name="email_notifications"
            description="Recibe alertas de facturas vencidas y nuevos cobros"
        />

        <div class="h-px bg-slate-100"></div>

        <x-ui.forms.toggle
            label="Permite Cobro de Cuentas por Cobrar"
            name="allow_receivable_collection"
            description="Habilita el botón 'Cobrar Deudas' en esta terminal"
            :checked="true"
        />

        <div class="h-px bg-slate-100"></div>

        <x-ui.forms.toggle
            label="Sincronización Automática"
            name="auto_sync"
            description="No disponible en tu plan actual"
            :disabled="true"
        />

    </div>

    {{-- Separador --}}
    <div class="flex items-center gap-4">
        <div class="h-px flex-1 bg-slate-100"></div>
        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">06 · Archivo</span>
        <div class="h-px flex-1 bg-slate-100"></div>
    </div>

    {{-- ── File Input ──────────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-8">
        <x-ui.forms.file-input
            label="Foto del Producto"
            name="image"
            accept="image/*"
            hint="JPG, PNG o WEBP, máx. 2MB"
        />

        <x-ui.forms.file-input
            label="Comprobante de Pago"
            name="receipt"
            error="El archivo supera el tamaño máximo permitido"
        />
    </div>

    {{-- Separador --}}
    <div class="h-px bg-slate-100"></div>

    {{-- ── Footer del formulario ──────────────────────────────────── --}}
    <div class="flex items-center justify-between gap-4 pt-2">
        <x-ui.button variant="secondary" appearance="outline" icon-left="heroicon-s-arrow-left">
            Cancelar
        </x-ui.button>
        <x-ui.button variant="primary" :hover-effect="true" icon-right="heroicon-s-arrow-right">
            Guardar Cambios
        </x-ui.button>
    </div>

</div>

</x-app-layout>
