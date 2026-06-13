import { writable, derived } from 'svelte/store';

const storedUser = localStorage.getItem('user');
const storedToken = localStorage.getItem('token');

export const currentUser = writable(storedUser ? JSON.parse(storedUser) : null);
export const token = writable(storedToken || null);

export const isAuthenticated = derived(currentUser, ($user) => $user !== null);

token.subscribe((value) => {
  if (value) {
    localStorage.setItem('token', value);
  } else {
    localStorage.removeItem('token');
  }
});

currentUser.subscribe((value) => {
  if (value) {
    localStorage.setItem('user', JSON.stringify(value));
  } else {
    localStorage.removeItem('user');
  }
});

export function setAuth(user, authToken) {
  currentUser.set(user);
  token.set(authToken);
}

export function clearAuth() {
  currentUser.set(null);
  token.set(null);
}
