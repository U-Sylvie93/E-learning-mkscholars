import forms from '@tailwindcss/forms';

export default {
    content: [
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './app/**/*.php',
        './vendor/filament/**/*.blade.php',
    ],
    theme: {
        extend: {
            colors: {
                mk: {
                    navy: '#073653',
                    blue: '#0E4A72',
                    gold: '#FFC40C',
                    goldSoft: '#FFF2B8',
                    cloud: '#F3F8FB',
                    ink: '#102A3A',
                },
            },
            boxShadow: {
                soft: '0 18px 60px -32px rgba(7, 54, 83, 0.35)',
            },
        },
    },
    plugins: [forms],
};
