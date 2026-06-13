import client from './client';

export const tasksApi = {
  list: (projectId, params = {}) => {
    const query = new URLSearchParams();
    if (params.status) query.set('status', params.status);
    if (params.priority) query.set('priority', params.priority);
    if (params.assigned_user) query.set('assigned_user', params.assigned_user);
    if (params.search) query.set('search', params.search);
    const qs = query.toString();
    return client.get(`/projects/${projectId}/tasks${qs ? '?' + qs : ''}`);
  },
  show: (projectId, taskId) => client.get(`/projects/${projectId}/tasks/${taskId}`),
  create: (projectId, data) => client.post(`/projects/${projectId}/tasks`, data),
  update: (projectId, taskId, data) => client.put(`/projects/${projectId}/tasks/${taskId}`, data),
  delete: (projectId, taskId) => client.delete(`/projects/${projectId}/tasks/${taskId}`),
  updatePosition: (projectId, taskId, data) =>
    client.patch(`/projects/${projectId}/tasks/${taskId}/position`, data),
};
