import { defineConfig } from 'vite';

export default defineConfig({
  build: {
    outDir: 'resources/dist',
    emptyOutDir: false,
    lib: {
      entry: 'resources/js/livewire-calendar.ts',
      name: 'LivewireCalendar',
      formats: ['iife'],
      fileName: () => 'livewire-calendar.js',
    },
    rollupOptions: {
      output: {
        assetFileNames: () => 'livewire-calendar.css',
      },
    },
  },
});

