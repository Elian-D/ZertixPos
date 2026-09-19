{{-- resources/views/configuration/index.blade.php — Centro de Configuración (REQ-7.4) --}}
<x-app-layout title="Centro de Configuración">
    <div class="flex flex-col gap-8">

        {{-- HERO — mismo degradado de marca que resources/views/layouts/guest.blade.php
             (panel izquierdo del login), no un color nuevo inventado del mockup. --}}
        <div class="relative overflow-hidden p-8 md:p-10 text-white rounded-lg shadow-md"
             style="background: linear-gradient(135deg, #0B2E5B 0%, #1E4F8C 100%);">
            <div class="absolute -bottom-32 -left-32 w-96 h-96 rounded-full bg-zertix-primary mix-blend-overlay opacity-20 blur-3xl pointer-events-none"></div>
            <div class="absolute top-0 right-0 w-64 h-64 rounded-full bg-white mix-blend-overlay opacity-10 blur-3xl pointer-events-none"></div>

            <div class="relative z-10 max-w-2xl">
                <div class="inline-flex items-center gap-2 bg-white/10 px-3 py-1 rounded-full text-xs font-semibold uppercase tracking-wider mb-5 border border-white/20">
                    <x-heroicon-s-cog-6-tooth class="w-4 h-4" />
                    Centro de Control
                </div>
                <h1 class="text-2xl md:text-3xl font-bold leading-tight mb-2">
                    Configura tu negocio a tu manera.
                </h1>
                <p class="text-white/80 text-sm md:text-base mb-6">
                    Organiza tu empresa, tus catálogos y protege tus datos desde un solo lugar.
                </p>
                <div class="flex flex-wrap gap-2">
                    <a href="#negocio" class="px-3 py-1.5 bg-white/10 hover:bg-white/20 border border-white/20 rounded-full text-xs font-medium transition-colors flex items-center gap-1.5">
                        <x-heroicon-s-building-office class="w-3.5 h-3.5" /> Negocio
                    </a>
                    <a href="#catalogos" class="px-3 py-1.5 bg-white/10 hover:bg-white/20 border border-white/20 rounded-full text-xs font-medium transition-colors flex items-center gap-1.5">
                        <x-heroicon-s-rectangle-stack class="w-3.5 h-3.5" /> Catálogos
                    </a>
                    <a href="#sistema" class="px-3 py-1.5 bg-white/10 hover:bg-white/20 border border-white/20 rounded-full text-xs font-medium transition-colors flex items-center gap-1.5">
                        <x-heroicon-s-shield-check class="w-3.5 h-3.5" /> Sistema
                    </a>
                </div>
            </div>
        </div>

        <div class="p-4 md:p-6 pt-0 flex flex-col gap-12">

            {{-- SECCIÓN 1: Negocio y Operación --}}
            @canany(['config.general', 'config.payment_types', 'config.billing'])
                <section id="negocio" class="scroll-mt-20">
                    <div class="flex items-start gap-3 mb-5">
                        <div class="w-10 h-10 rounded-lg bg-zertix-secondary/10 flex items-center justify-center flex-shrink-0">
                            <x-heroicon-s-briefcase class="w-5 h-5 text-zertix-secondary" />
                        </div>
                        <div>
                            <h2 class="text-sm font-bold uppercase tracking-wider text-zertix-secondary">Negocio y Operación</h2>
                            <p class="text-sm text-slate-400">Define cómo trabaja tu empresa y cómo factura.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                        @can('config.general')
                            <x-configuration.hub-card
                                href="{{ route('configuration.general.edit') }}"
                                icon="heroicon-s-building-office"
                                iconClass="bg-blue-50 text-blue-600"
                                title="Empresa"
                                description="Identidad, datos fiscales y logo"
                            />
                        @endcan

                        @can('config.payment_types')
                            <x-configuration.hub-card
                                href="{{ route('configuration.pagos.index') }}"
                                icon="heroicon-s-credit-card"
                                iconClass="bg-amber-50 text-amber-600"
                                title="Tipos de Pago"
                                description="Métodos disponibles para cobrar"
                            />
                        @endcan

                        @can('config.billing')
                            <x-configuration.hub-card
                                href="{{ route('billing.manage') }}"
                                icon="heroicon-s-rocket-launch"
                                iconClass="bg-purple-50 text-purple-600"
                                title="Plan y Suscripción"
                                description="Tu plan actual y límites de uso"
                                badge="PRO"
                                actionLabel="Actualizar"
                            />
                        @endcan
                    </div>
                </section>
            @endcanany

            {{-- SECCIÓN 2: Catálogos del Sistema --}}
            @canany(['ncf_types.manage', 'document_types.view', 'categories.manage', 'units.manage', 'business_types.manage', 'equipment_types.manage'])
                <section id="catalogos" class="scroll-mt-20">
                    <div class="flex items-start gap-3 mb-5">
                        <div class="w-10 h-10 rounded-lg bg-zertix-primary/10 flex items-center justify-center flex-shrink-0">
                            <x-heroicon-s-rectangle-stack class="w-5 h-5 text-zertix-primary" />
                        </div>
                        <div>
                            <h2 class="text-sm font-bold uppercase tracking-wider text-zertix-secondary">Catálogos del Sistema</h2>
                            <p class="text-sm text-slate-400">Datos regulados o compartidos entre módulos.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                        @if(module_enabled('sales.ncf'))
                            @can('ncf_types.manage')
                                <x-configuration.hub-card
                                    href="{{ route('configuration.ncf_types.index') }}"
                                    icon="heroicon-s-document-text"
                                    iconClass="bg-slate-100 text-slate-600"
                                    lockIcon
                                    title="Tipos de Comprobante (NCF)"
                                    description="Catálogo DGII — solo activar/desactivar"
                                />
                            @endcan
                        @endif

                        @can('document_types.view')
                            <x-configuration.hub-card
                                href="{{ route('configuration.document_types.index') }}"
                                icon="heroicon-s-clipboard-document-list"
                                iconClass="bg-emerald-50 text-emerald-600"
                                title="Tipos de Documento"
                                description="Facturas, cotizaciones, recibos"
                            />
                        @endcan

                        @can('categories.manage')
                            <x-configuration.hub-card
                                href="{{ route('configuration.categories.index') }}"
                                icon="heroicon-s-squares-2x2"
                                iconClass="bg-indigo-50 text-indigo-600"
                                title="Categorías"
                                description="Clasificación de productos"
                            />
                        @endcan

                        @can('units.manage')
                            <x-configuration.hub-card
                                href="{{ route('configuration.units.index') }}"
                                icon="heroicon-s-scale"
                                iconClass="bg-indigo-50 text-indigo-600"
                                title="Unidades de Medida"
                                description="Cómo se venden tus productos"
                            />
                        @endcan

                        @if(module_enabled('sales.delivery_points'))
                            @can('business_types.manage')
                                <x-configuration.hub-card
                                    href="{{ route('configuration.business_types.index') }}"
                                    icon="heroicon-s-building-storefront"
                                    iconClass="bg-cyan-50 text-cyan-600"
                                    title="Tipos de Negocio"
                                    description="Clasificación de clientes en ruta"
                                />
                            @endcan
                        @endif

                        @if(module_enabled('clients.field_assets'))
                            @can('equipment_types.manage')
                                <x-configuration.hub-card
                                    href="{{ route('configuration.equipment_types.index') }}"
                                    icon="heroicon-s-wrench-screwdriver"
                                    iconClass="bg-cyan-50 text-cyan-600"
                                    title="Tipos de Equipo"
                                    description="Equipos que se instalan en campo"
                                />
                            @endcan
                        @endif
                    </div>
                </section>
            @endcanany

            {{-- SECCIÓN 3: Sistema y Accesos --}}
            @canany(['users.view', 'roles.view', 'config.modules'])
                <section id="sistema" class="scroll-mt-20">
                    <div class="flex items-start gap-3 mb-5">
                        <div class="w-10 h-10 rounded-lg bg-slate-800 flex items-center justify-center flex-shrink-0">
                            <x-heroicon-s-shield-check class="w-5 h-5 text-white" />
                        </div>
                        <div>
                            <h2 class="text-sm font-bold uppercase tracking-wider text-zertix-secondary">Sistema y Accesos</h2>
                            <p class="text-sm text-slate-400">Quién entra y qué puede hacer.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                        @can('users.view')
                            <x-configuration.hub-card
                                href="{{ route('config.users.index') }}"
                                icon="heroicon-s-users"
                                iconClass="bg-teal-50 text-teal-600"
                                title="Usuarios"
                                description="Cuentas del equipo"
                                :count="$usersCount"
                            />
                        @endcan

                        @can('roles.view')
                            <x-configuration.hub-card
                                href="{{ route('config.roles.index') }}"
                                icon="heroicon-s-key"
                                iconClass="bg-orange-50 text-orange-600"
                                title="Roles y Permisos"
                                description="Qué puede hacer cada rol"
                            />
                        @endcan

                        @can('config.modules')
                            <x-configuration.hub-card
                                href="{{ route('configuration.features') }}"
                                icon="heroicon-s-adjustments-horizontal"
                                iconClass="bg-rose-50 text-rose-600"
                                title="Funcionalidades del Sistema"
                                description="Activa o desactiva módulos según tu plan"
                            />
                        @endcan
                    </div>
                </section>
            @endcanany

        </div>
    </div>
</x-app-layout>
