import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

export default {
    content: [
        "./resources/**/**/*.blade.php",
        "./resources/**/*.blade.php",
        "./resources/**/*.js",
        "./resources/**/*.vue",
        "./resources/**/*.blade.php",
        "./resources/**/*.js",
        "./resources/**/*.vue",
        "./app/Livewire/**/*.php",
        "./app/View/Components/**/*.php",
    ],
    darkMode: 'class',
    safelist: [
        // Altura del sidebar colapsado en desktop (calc no es detectado por el extractor JIT)
        'sm:h-[calc(100vh-3.5rem)]',
    ],
    theme: {
        extend: {
            colors: {
                background: '#f6f2e9',
                surface: '#fbfaf7',
                'surface-dim': '#ded8ca',
                'surface-bright': '#fffdf8',
                'surface-container-lowest': '#ffffff',
                'surface-container-low': '#f1ede4',
                'surface-container': '#e9e2d5',
                'surface-container-high': '#ded5c4',
                'surface-container-highest': '#d2c6b2',
                'surface-variant': '#e5dece',
                'on-surface': '#121826',
                'on-surface-variant': '#4d5668',
                // Escala derivada del azul institucional UNAH (#001b3d).
                // Debe ser un objeto con DEFAULT: el sidebar usa text-primary-600 /
                // text-primary-400 / bg-primary-50 (utilidades que no existían cuando
                // `primary` era un string plano), y varias vistas usan `bg-primary` y
                // `hover:border-primary/40`, que dependen de DEFAULT.
                primary: {
                    DEFAULT: '#001b3d',
                    50: '#eef4ff',
                    100: '#d8e6ff',
                    200: '#b9d3ff',
                    300: '#a9c6f5',
                    400: '#6f9fe0',
                    500: '#3f74bd',
                    600: '#1f5397',
                    700: '#123f77',
                    800: '#0a2e5c',
                    900: '#001b3d',
                    950: '#00122a',
                },
                'primary-container': '#0a2e5c',
                'primary-fixed': '#d8e6ff',
                'primary-fixed-dim': '#a9c6f5',
                'on-primary': '#ffffff',
                'on-primary-container': '#d8e6ff',
                secondary: '#a77a00',
                'secondary-container': '#f4d06f',
                tertiary: '#4a3200',
                'tertiary-container': '#7b5400',
                outline: '#6f7380',
                'outline-variant': '#c8c0ae',
                error: '#b42318',
                'error-container': '#fee4d8',
            },
            fontFamily: {
                sans: ['"Helvetica Neue"', 'Arial', ...defaultTheme.fontFamily.sans],
                body: ['"Helvetica Neue"', 'Arial', ...defaultTheme.fontFamily.sans],
                headline: ['"Helvetica Neue"', 'Arial', ...defaultTheme.fontFamily.sans],
                label: ['"Helvetica Neue"', 'Arial', ...defaultTheme.fontFamily.sans],
            },
            gridTemplateColumns: {
                // Matriz de los 17 Objetivos de Desarrollo Sostenible.
                17: 'repeat(17, minmax(0, 1fr))',
            },
            borderRadius: {
                DEFAULT: '0.125rem',
                lg: '0.25rem',
                xl: '0.5rem',
                '2xl': '0.75rem',
                '3xl': '1rem',
            },
            boxShadow: {
                soft: '0 18px 45px rgba(0, 27, 61, 0.10)',
                institutional: '0 24px 70px rgba(0, 27, 61, 0.16)',
            },
        },
    },
    plugins: [forms],
}
