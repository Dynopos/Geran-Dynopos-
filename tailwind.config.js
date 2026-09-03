/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './app/Livewire/**/*.php',
    ],
    theme: {
        extend: {
            colors: {
                dyno: {
                    50: '#fff7ed',
                    500: '#f97316',
                    600: '#ea580c',
                    700: '#c2410c',
                },
            },
        },
    },
    plugins: [],
};
