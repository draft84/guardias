import api from './api'

export const userService = {
  async getAll() {
    const response = await api.get('/api/users')
    return response.data.users
  },

  async getActive() {
    const response = await api.get('/api/users/active')
    return response.data.users
  },

  async getById(id) {
    const response = await api.get(`/api/users/${id}`)
    return response.data.user
  },

  async create(data) {
    const response = await api.post('/api/users', data)
    return response.data
  },

  async update(id, data) {
    const response = await api.put(`/api/users/${id}`, data)
    return response.data
  },

  async delete(id) {
    const response = await api.delete(`/api/users/${id}`)
    return response.data
  },

  async getByDepartment(departmentId) {
    const response = await api.get(`/api/users/department/${departmentId}`)
    return response.data.users
  }
}
