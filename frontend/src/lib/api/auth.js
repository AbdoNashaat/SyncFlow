import client from './client';

export const authApi = {
  register: (data) => client.post('/register', data),
  login: (data) => client.post('/login', data),
  logout: () => client.post('/logout'),
  user: () => client.get('/user'),
};
