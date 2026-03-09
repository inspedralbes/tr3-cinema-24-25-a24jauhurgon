import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import tailwindcss from '@tailwindcss/vite'

// Configuració Vite amb TailwindCSS
export default defineConfig({
  plugins: [
    vue(),
    tailwindcss(),
  ],
})
