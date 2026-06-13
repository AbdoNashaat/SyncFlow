import client from './client';

export const attachmentsApi = {
  upload: (projectId, taskId, files) => {
    const formData = new FormData();
    files.forEach((file) => formData.append('attachments[]', file));
    return client.post(`/projects/${projectId}/tasks/${taskId}/attachments`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
  },
  delete: (taskId, attachmentId) => client.delete(`/tasks/${taskId}/attachments/${attachmentId}`),
};
