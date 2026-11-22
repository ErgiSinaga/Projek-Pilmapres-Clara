/**
 * Simple reactive store untuk status aplikasi murid.
 */
class Store {
  constructor() {
    this.state = {
      student: null,
      pet: null,
      equipped: {},
      isAudioMuted: false,
    };
    this.listeners = new Set();
  }

  getState() {
    return this.state;
  }

  setState(patch) {
    this.state = { ...this.state, ...patch };
    this.notify();
  }

  subscribe(listener) {
    this.listeners.add(listener);
    return () => this.listeners.delete(listener);
  }

  notify() {
    for (const listener of this.listeners) {
      listener(this.state);
    }
  }
}

export const store = new Store();
