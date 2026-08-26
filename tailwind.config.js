import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './app/**/*.php',
    ],

    // ====================================================================
    // SAFELIST AGREGADO PARA INCLUIR CLASES DINÁMICAS DE COLOR
    // ====================================================================
    safelist: [
        {
            // REQ-7.10 (barrido de indigo→verde): clases construidas en runtime con
            // interpolación PHP (ej. `bg-{{ $alert['color'] }}-50` en
            // AccountingDashboardController/dashboards de accounting y ncf) — Tailwind
            // no puede extraerlas por regex estático del archivo fuente, así que sin
            // este safelist se purgarían aunque el color resuelto en runtime sea
            // 'zertix-primary'.
            pattern: /(bg|text|border|ring)-zertix-primary-(50|100|200|300|400|500|600|700|800|900)/,
        },
    ],
    // ====================================================================

    theme: {
        extend: {
            colors: {
                'zertix-primary': {
                    // Escala 50-900 (REQ-7.10): generada por interpolación lineal RGB
                    // desde los dos tonos de marca ya definidos (500 = DEFAULT exacto,
                    // 600 = dark exacto) hacia blanco (50-400) y negro (700-900),
                    // manteniendo el mismo matiz (~95°) en toda la escala. Existe para
                    // reemplazar 1:1 cada shade de `indigo-*` que usaba el sistema
                    // (badges, focus rings, botones) sin perder la gradación visual
                    // que Tailwind's indigo sí traía de fábrica.
                    50: '#F8FCF6',
                    100: '#EFF9E8',
                    200: '#DAF0CA',
                    300: '#C3E7AA',
                    400: '#A2D97B',
                    500: '#7AC943',
                    600: '#538331',
                    700: '#426927',
                    800: '#324F1D',
                    900: '#213414',
                    DEFAULT: '#7AC943', // verde de marca — CTAs, estados activos
                    dark: '#538331',    // hover/pressed — lo que en la guía de Stitch aparece como "Tertiary"
                },
                'zertix-secondary': {
                    DEFAULT: '#1E4F8C', // navy del logo — usar como acento, no como color dominante
                    dark: '#0B2E5B', // superficies grandes y oscuras (fondo de sidebar) — no botones
                },
                'state-success': '#10b981',
                'state-warning': '#F59E0B',
                'state-error': '#EF4444',
                'state-info': '#3B82F6',
            },
            fontFamily: {
                sans: ['Poppins', ...defaultTheme.fontFamily.sans],
            },
            
            backgroundImage: {
                'custom-gradient-1': `
                radial-gradient(1100px 600px at 0% 0%, rgba(123, 140, 255, .35) 0%, transparent 45%),
                linear-gradient(135deg, #121a41, #0f1639 60%)
                `,
                'custom-gradient-2': `
                linear-gradient(135deg,#5661ff,#7b8cff)
                `,
            },

            height: {
                '11': '3rem', // 48px
                '12': '4rem',    // 48px
                '13': '5.5rem', // 88px
            },
        },
    },

    plugins: [forms],
};