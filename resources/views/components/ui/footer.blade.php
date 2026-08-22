<footer {{ $attributes->merge(['class' => 'w-full transition-colors duration-300']) }}>
    {{-- Línea divisoria de lado a lado --}}
    <div class="w-full border-t border-slate-200"></div>

    <div class="px-4 md:px-8 py-4 flex items-center justify-center">
        {{-- Copyright — solo derechos reservados, sin versión ni "Hecho en RD" (a diferencia del original de Orvian).
             Nombre del sistema (ZertixPOS), no el de la empresa configurada por el cliente. --}}
        <div class="text-[10px] font-bold uppercase tracking-[0.15em] text-slate-400/80">
            © {{ date('Y') }} <span class="text-slate-500">ZertixPOS</span>. Todos los derechos reservados.
        </div>
    </div>
</footer>
