import { defineStore } from 'pinia';

let nextId = 1;

export const useToastStore = defineStore('toast', {
  state: () => ({
    toasts: [],
  }),
  actions: {
    push(message, type = 'info', timeout = 3000) {
      const id = nextId++;
      this.toasts.push({ id, message, type });

      if (timeout > 0) {
        window.setTimeout(() => {
          this.remove(id);
        }, timeout);
      }

      return id;
    },
    success(message, timeout = 2500) {
      return this.push(message, 'success', timeout);
    },
    error(message, timeout = 4000) {
      return this.push(message, 'error', timeout);
    },
    info(message, timeout = 3000) {
      return this.push(message, 'info', timeout);
    },
    remove(id) {
      this.toasts = this.toasts.filter((toast) => toast.id !== id);
    },
    clear() {
      this.toasts = [];
    },
  },
});
