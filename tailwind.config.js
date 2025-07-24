// tailwind.config.js
/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue", // Quan trọng: Đảm bảo có Vue files
  ],
  theme: {
    extend: {},
  },
  plugins: [],
}