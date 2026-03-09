import api from './api'

export const departmentService = {
  async getAll() {
    const response = await api.get('/api/departments')
    return response.data.departments
  },

  async getActive() {
    const response = await api.get('/api/departments/active')
    return response.data.departments
  },

  async getById(id) {
    const response = await api.get(`/api/departments/${id}`)
    return response.data.department
  },

  async create(data) {
    const response = await api.post('/api/departments', data)
    return response.data
  },

  async update(id, data) {
    const response = await api.put(`/api/departments/${id}`, data)
    return response.data
  },

  async delete(id) {
    const response = await api.delete(`/api/departments/${id}`)
    return response.data
  },

  async getUsers(id) {
    const response = await api.get(`/api/departments/${id}/users`)
    return response.data.users
  }
}
