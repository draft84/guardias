import api from './api'

export const guardService = {
  async getAll() {
    const response = await api.get('/api/guards')
    return response.data.guards
  },

  async getActive() {
    const response = await api.get('/api/guards/active')
    return response.data.guards
  },

  async getById(id) {
    const response = await api.get(`/api/guards/${id}`)
    return response.data.guard
  },

  async create(data) {
    const response = await api.post('/api/guards', data)
    return response.data
  },

  async update(id, data) {
    const response = await api.put(`/api/guards/${id}`, data)
    return response.data
  },

  async delete(id) {
    const response = await api.delete(`/api/guards/${id}`)
    return response.data
  },

  async getAssignments(id) {
    const response = await api.get(`/api/guards/${id}/assignments`)
    return response.data.assignments
  }
}
