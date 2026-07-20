import { defineConfig } from 'vite'
import path from 'path'

export default defineConfig({
  root: 'assets/modern',
  publicDir: '../public',
  build: {
    outDir: '../../assets/dist',
    emptyOutDir: true,
    rollupOptions: {
      input: {
        main: path.resolve(__dirname, 'js/main.js'),
        pos: path.resolve(__dirname, 'js/pos.js'),
        dashboard: path.resolve(__dirname, 'js/dashboard.js'),
        style: path.resolve(__dirname, 'css/style.css')
      },
      output: {
        entryFileNames: 'js/[name].[hash].js',
        chunkFileNames: 'js/[name].[hash].js',
        assetFileNames: (assetInfo) => {
          if (assetInfo.name.endsWith('.css')) return 'css/[name].[hash][extname]'
          return 'assets/[name].[hash][extname]'
        }
      }
    },
    minify: 'terser',
    terserOptions: {
      compress: {
        drop_console: true,
        drop_debugger: true
      }
    },
    sourcemap: false,
    target: 'es2015'
  },
  server: {
    port: 3000,
    strictPort: false,
    hmr: {
      host: 'localhost',
      port: 3000
    }
  },
  css: {
    postcss: './postcss.config.js'
  }
})
