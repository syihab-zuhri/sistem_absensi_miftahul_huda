import config from './tailwind.config.js';

/** @type {import('tailwindcss').Config} */
export default {
    ...config,
    content: [
        './resources/views/auth/*.blade.php',
        './resources/views/layouts/guest.blade.php',
        './resources/views/components/*.blade.php',
    ],
};
