import type { Config } from 'tailwindcss'

export default {
    content: ['./index.html', './src/**/*.{vue,ts,tsx}'],
    darkMode: 'class',
    theme: {
        container: {
            center: true,
            padding: {
                DEFAULT: '1rem',
                sm: '1.5rem',
                lg: '2rem',
            },
            screens: {
                sm: '640px',
                md: '768px',
                lg: '1024px',
                xl: '1200px',
            },
        },
        extend: {
            colors: {
                brand: {
                    50: '#eef4ff',
                    100: '#dbe6ff',
                    200: '#bcd1ff',
                    300: '#8eb1ff',
                    400: '#5a87ff',
                    500: '#3461ff',
                    600: '#1f44f0',
                    700: '#1a36c7',
                    800: '#1b309e',
                    900: '#1c2f7d',
                    950: '#131c4a',
                },
                surface: {
                    DEFAULT: 'rgb(var(--color-surface) / <alpha-value>)',
                    muted: 'rgb(var(--color-surface-muted) / <alpha-value>)',
                    subtle: 'rgb(var(--color-surface-subtle) / <alpha-value>)',
                    inverted: '#0b1020',
                },
                ink: {
                    DEFAULT: 'rgb(var(--color-ink) / <alpha-value>)',
                    muted: 'rgb(var(--color-ink-muted) / <alpha-value>)',
                    subtle: 'rgb(var(--color-ink-subtle) / <alpha-value>)',
                    inverted: '#f8fafc',
                },
                border: {
                    DEFAULT: 'rgb(var(--color-border) / <alpha-value>)',
                    strong: 'rgb(var(--color-border-strong) / <alpha-value>)',
                },
                success: {
                    50: '#ecfdf5',
                    500: '#10b981',
                    600: '#059669',
                    700: '#047857',
                },
                warning: {
                    50: '#fffbeb',
                    500: '#f59e0b',
                    600: '#d97706',
                    700: '#b45309',
                },
                danger: {
                    50: '#fef2f2',
                    500: '#ef4444',
                    600: '#dc2626',
                    700: '#b91c1c',
                },
                info: {
                    50: '#eff6ff',
                    500: '#3b82f6',
                    600: '#2563eb',
                    700: '#1d4ed8',
                },
            },
            fontFamily: {
                sans: [
                    'Inter',
                    'ui-sans-serif',
                    'system-ui',
                    '-apple-system',
                    'Segoe UI',
                    'Roboto',
                    'sans-serif',
                ],
                mono: ['JetBrains Mono', 'ui-monospace', 'SFMono-Regular', 'Menlo', 'monospace'],
            },
            fontSize: {
                xs: ['0.75rem', { lineHeight: '1rem' }],
                sm: ['0.875rem', { lineHeight: '1.25rem' }],
                base: ['1rem', { lineHeight: '1.5rem' }],
                lg: ['1.125rem', { lineHeight: '1.75rem' }],
                xl: ['1.25rem', { lineHeight: '1.75rem' }],
                '2xl': ['1.5rem', { lineHeight: '2rem' }],
                '3xl': ['1.875rem', { lineHeight: '2.25rem' }],
                '4xl': ['2.25rem', { lineHeight: '2.5rem' }],
            },
            spacing: {
                '4.5': '1.125rem',
                '18': '4.5rem',
                '68': '17rem',
                '76': '19rem',
            },
            borderRadius: {
                xs: '0.25rem',
                sm: '0.375rem',
                md: '0.5rem',
                lg: '0.75rem',
                xl: '1rem',
                '2xl': '1.5rem',
            },
            boxShadow: {
                xs: '0 1px 2px rgba(15, 23, 42, 0.06)',
                sm: '0 1px 3px rgba(15, 23, 42, 0.08), 0 1px 2px rgba(15, 23, 42, 0.04)',
                md: '0 4px 12px rgba(15, 23, 42, 0.08)',
                lg: '0 12px 32px rgba(15, 23, 42, 0.12)',
                focus: '0 0 0 3px rgba(52, 97, 255, 0.35)',
            },
            transitionTimingFunction: {
                emphasized: 'cubic-bezier(0.2, 0.8, 0.2, 1)',
            },
            zIndex: {
                base: '1',
                dropdown: '20',
                sticky: '30',
                overlay: '40',
                modal: '50',
                toast: '60',
            },
        },
    },
    plugins: [],
} satisfies Config
