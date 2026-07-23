// OneBox Modern Entry Point
import './css/style.css'
import Alpine from 'alpinejs'

// Initialize Alpine.js for reactive UI components
window.Alpine = Alpine
Alpine.start()

// Export utilities for legacy code migration
export { default as api } from './utils/api.js'
export { default as notifications } from './utils/notifications.js'
export { default as state } from './utils/state.js'

console.log('OneBox Modern Assets Loaded')
