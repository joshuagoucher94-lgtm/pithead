/** @type {import('tailwindcss').Config} */
export default {
  content: [
    './src/**/*.{astro,html,js,ts,jsx,tsx}',
    '../php/public/**/*.php',
    '!../php/public/_inc/vendor/**',
  ],
  theme: {
    extend: {
      colors: {
        coal: '#111111',
        offwhite: '#F4F2ED',
        imperial: '#7A1F1F',
        /** Brand pack — cool grey for panels / secondary surfaces */
        stone: '#D8DCD1',
        /** Menu print — sand panels & add-on band */
        sand: '#E8E4DC',
        /** Muted gold/bronze — add-ons & Shiver Shake titling */
        bronze: '#8F734A',
      },
      fontFamily: {
        sans: [
          'Inter',
          'ui-sans-serif',
          'system-ui',
          'sans-serif',
        ],
      },
    },
  },
  plugins: [],
};
