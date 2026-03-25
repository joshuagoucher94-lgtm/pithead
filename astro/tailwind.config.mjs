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
        stone: '#D88CD1',
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
