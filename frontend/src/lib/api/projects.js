import client from './client';

export const projectsApi = {
  list: (page = 1) => client.get(`/projects?page=${page}`),
  show: (id) => client.get(`/projects/${id}`),
  create: (data) => client.post('/projects', data),
  update: (id, data) => client.put(`/projects/${id}`, data),
  delete: (id) => client.delete(`/projects/${id}`),
};
