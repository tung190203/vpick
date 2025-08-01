// tailwind.config.js
/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue", // Quan trọng: Đảm bảo có Vue files
  ],
  theme: {
    extend: {
      colors: {
        primary: '#BA110B',
        secondary: '#D72D36',
        'primary-light': '#F0807C',
      },
      backgroundImage: {
        'gradient-red': 'linear-gradient(180deg, #D72D36 0%, #520011 100%)',
      },
    },
  },
  plugins: [],
}