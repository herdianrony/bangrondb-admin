/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./index.html",
    "./src/**/*.{vue,js,ts}",
    "../backend/public/index.php",
    "../backend/src/**/*.php"
  ],
  theme: {
    extend: {
      fontFamily: { sans: ['Inter','Plus Jakarta Sans','sans-serif'] },
      colors: {
        bangron: {
          500: '#6c8cff',
          600: '#5b74ff',
        }
      }
    },
  },
  plugins: [],
}
