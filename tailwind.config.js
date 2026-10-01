import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import flowbite from 'flowbite/plugin';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './node_modules/flowbite/**/*.js',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Vazirmatn', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                primary: { DEFAULT: '#eb073f', 50: '#fff1f4', 100: '#ffe0e6', 500: '#eb073f', 600: '#c90535', 700: '#a6042c' },
                secondary: { DEFAULT: '#00c761', 500: '#00c761', 600: '#00a851' },
                accent: { DEFAULT: '#6c5ffa', 500: '#6c5ffa', 600: '#4f42e8' },
                neutral: { DEFAULT: '#1c1a26', 800: '#2a2833', 900: '#1c1a26' },
                error: { DEFAULT: '#ed1f3f' }, warning: { DEFAULT: '#fbba29' }, success: { DEFAULT: '#107f46' }, info: { DEFAULT: '#075deb' }, brandtext: { DEFAULT: '#232226' },
            },
        },
    },

    plugins: [forms, flowbite],
};
