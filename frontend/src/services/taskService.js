import api from './api'

export const taskService = {
  async getAll(filters = {}) {
    const params = new URLSearchParams()
    if (filters.status) params.append('status', filters.status)
    if (filters.department) params.append('department', filters.department)
    
    const response = await api.get(`/api/tasks?${params.toString()}`)
    return response.data.tasks
  },

  async getById(id) {
    const response = await api.get(`/api/tasks/${id}`)
    return response.data.task
  },

  async create(data) {
    const response = await api.post('/api/tasks', data)
    return response.data
  },

  async update(id, data) {
    const response = await api.put(`/api/tasks/${id}`, data)
    return response.data
  },

  async updateStatus(id, status, completionNotes = null) {
    const response = await api.put(`/api/tasks/${id}/status`, {
      status,
      completionNotes
    })
    return response.data
  },

  async delete(id) {
    const response = await api.delete(`/api/tasks/${id}`)
    return response.data
  }
}
