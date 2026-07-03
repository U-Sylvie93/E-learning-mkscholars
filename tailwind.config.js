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
                    success: '#047857',
                    successSoft: '#D1FAE5',
                    danger: '#B91C1C',
                    dangerSoft: '#FEE2E2',
                    warning: '#B45309',
                    warningSoft: '#FEF3C7',
                },
            },
            boxShadow: {
                soft: '0 18px 60px -32px rgba(7, 54, 83, 0.35)',
            },
            borderRadius: {
                'mk-sm': '0.5rem',
                'mk-md': '0.75rem',
                'mk-lg': '1rem',
                'mk-xl': '1.5rem',
            },
        },
    },
    plugins: [forms],
};
