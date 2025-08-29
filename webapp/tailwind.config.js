/** @type {import('tailwindcss').Config} */
export default {
  content: [
    './resources/**/*.{js,jsx,ts,tsx,vue,blade.php}',
    '../../../vk-0abb-do-screens/src/**/*.{js,jsx,ts,tsx}',
  ],
  theme: {
    extend: {
      borderRadius: {
        DEFAULT: '0px', // Square corners everywhere
      },
    },
  },
  plugins: [
    // Optional plugins - remove if they cause issues
    // require('@tailwindcss/container-queries'),
    // require('tailwindcss-animate'),
  ],
};