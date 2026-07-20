/**
 * Reactive State Management
 * Simple state management for modern UI components
 */

class StateManager {
  constructor(initialState = {}) {
    this.state = initialState
    this.listeners = []
  }

  getState() {
    return this.state
  }

  setState(newState) {
    this.state = { ...this.state, ...newState }
    this.notifyListeners()
  }

  subscribe(listener) {
    this.listeners.push(listener)
    return () => {
      this.listeners = this.listeners.filter(l => l !== listener)
    }
  }

  notifyListeners() {
    this.listeners.forEach(listener => listener(this.state))
  }

  // Helper methods for common operations
  set(key, value) {
    this.setState({ [key]: value })
  }

  get(key) {
    return this.state[key]
  }

  toggle(key) {
    this.setState({ [key]: !this.state[key] })
  }
}

// Create global state instance
const state = new StateManager({
  loading: false,
  currentUser: null,
  currentStore: null,
  theme: 'light',
  notifications: []
})

export default state
