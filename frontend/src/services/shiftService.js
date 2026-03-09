import api from './api'

export const shiftService = {
  async getAll() {
    const response = await api.get('/api/shifts')
    return response.data.shifts
  },

  async getActive() {
    const response = await api.get('/api/shifts/active')
    return response.data.shifts
  },

  async getById(id) {
    const response = await api.get(`/api/shifts/${id}`)
    return response.data.shift
  },

  async create(data) {
    const response = await api.post('/api/shifts', data)
    return response.data
  },

  async update(id, data) {
    const response = await api.put(`/api/shifts/${id}`, data)
    return response.data
  },

  async delete(id) {
    const response = await api.delete(`/api/shifts/${id}`)
    return response.data
  }
}

export const assignmentService = {
  async getAll() {
    const response = await api.get('/api/assignments')
    return response.data.assignments
  },

  async getById(id) {
    const response = await api.get(`/api/assignments/${id}`)
    return response.data.assignment
  },

  async create(data) {
    const response = await api.post('/api/assignments', data)
    return response.data
  },

  async update(id, data) {
    const response = await api.put(`/api/assignments/${id}`, data)
    return response.data
  },

  async delete(id) {
    const response = await api.delete(`/api/assignments/${id}`)
    return response.data
  },

  async getCalendar(month, year) {
    const response = await api.get('/api/assignments/calendar', { params: { month, year } })
    return response.data.events
  },

  async getByDate(date) {
    const response = await api.get(`/api/assignments/date/${date}`)
    return response.data.assignments
  },

  async getByUser(userId, start, end) {
    const response = await api.get(`/api/assignments/user/${userId}`, { params: { start, end } })
    return response.data.assignments
  },

  async requestSwap(assignmentId, newUserId, reason) {
    const response = await api.post(`/api/assignments/${assignmentId}/swap`, {
      newUserId,
      reason
    })
    return response.data
  },

  async approveSwap(swapId) {
    const response = await api.put(`/api/assignments/swap/${swapId}/approve`)
    return response.data
  },

  async rejectSwap(swapId, reason) {
    const response = await api.put(`/api/assignments/swap/${swapId}/reject`, { reason })
    return response.data
  }
}
