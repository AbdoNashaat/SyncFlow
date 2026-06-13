import { writable } from 'svelte/store';

export const toasts = writable([]);
export const loading = writable(false);

export function addToast(message, type = 'info', duration = 4000) {
  const id = Date.now();
  toasts.update((t) => [...t, { id, message, type }]);
  setTimeout(() => {
    toasts.update((t) => t.filter((toast) => toast.id !== id));
  }, duration);
}

export function dismissToast(id) {
  toasts.update((t) => t.filter((toast) => toast.id !== id));
}
