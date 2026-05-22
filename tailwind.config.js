/** @type {import('tailwindcss').Config} */
export default {
  content: [
    './resources/**/*.blade.php',
    './resources/**/*.js',
    './resources/**/*.vue',
  ],
  theme: {
    extend: {
      fontFamily: {
        sans: ['"Plus Jakarta Sans"', 'ui-sans-serif', 'system-ui', 'sans-serif'],
      },
      backgroundImage: {
        mesh: 'radial-gradient(circle at 20% 20%, rgba(34,211,238,.16), transparent 35%), radial-gradient(circle at 80% 0%, rgba(14,165,233,.14), transparent 35%), radial-gradient(circle at 80% 80%, rgba(16,185,129,.14), transparent 30%)',
      },
    },
  },
  plugins: [],
};
