import client from './client';

export const commentsApi = {
  list: (projectId, taskId) => client.get(`/projects/${projectId}/tasks/${taskId}/comments`),
  create: (projectId, taskId, content) =>
    client.post(`/projects/${projectId}/tasks/${taskId}/comments`, { content }),
  delete: (commentId) => client.delete(`/comments/${commentId}`),
};
