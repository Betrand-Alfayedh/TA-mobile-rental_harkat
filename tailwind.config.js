import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js', // kelas yang dibuat lewat JS (mis. booking.js)
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['"Plus Jakarta Sans"', 'Figtree', ...defaultTheme.fontFamily.sans],
                mono: ['"Geist Mono Variable"', '"Geist Mono"', ...defaultTheme.fontFamily.mono],
            },
            colors: {
                // Panel admin: abu hangat dengan aksen hampir hitam.
                hk: { ink: '#17171A', mute: '#86847D', panel: '#F3F2EE', deep: '#EBE9E3', line: '#E2DFD8' },
                // Satu keluarga hijau: gelap untuk permukaan besar, terang untuk aksi.
                ink: '#0E1A14',
                forest: { DEFAULT: '#0B3B2A', deep: '#06261B', soft: '#145A41' },
                leaf: { DEFAULT: '#16A34A', dark: '#12873E' },
                mint: '#EAF6EE',
                paper: '#F5F7F5',
                line: '#DCE5DF',
            },
            boxShadow: {
                // bayangan lebar dan tipis, bukan bayangan gelap
                soft: '0 24px 60px -28px rgba(11, 59, 42, 0.35)',
            },
            transitionTimingFunction: {
                spring: 'cubic-bezier(0.32, 0.72, 0, 1)',
            },
            keyframes: {
                slideIn: {
                    '0%': {
                        transform: 'translateX(100%)',
                        opacity: '0'
                    },
                    '100%': {
                        transform: 'translateX(0)',
                        opacity: '1'
                    },
                },
                slideOut: {
                    '0%': {
                        transform: 'translateX(0)',
                        opacity: '1'
                    },
                    '100%': {
                        transform: 'translateX(100%)',
                        opacity: '0'
                    },
                }
            },
            animation: {
                slideIn: 'slideIn 0.4s ease-out',
                slideOut: 'slideOut 0.4s ease-in',
            }
        },
    },

    plugins: [forms],
};
