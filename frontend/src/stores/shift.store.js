import { defineStore } from 'pinia'
import api from '@/services/api'

const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:10000'

export const useShiftStore = defineStore('shift', {
  state: () => ({
    shifts: [],
    assignments: [],
    loading: false,
    error: null
  }),

  actions: {
    async fetchShifts() {
      this.loading = true
      try {
        const response = await api.get('/api/shifts')
        this.shifts = response.data.shifts || []
      } catch (error) {
        console.error('Error fetching shifts:', error)
        this.error = error.message
        throw error
      } finally {
        this.loading = false
      }
    },

    async fetchAssignments(month, year) {
      this.loading = true
      try {
        const params = {}
        if (month) params.month = month
        if (year) params.year = year

        const response = await api.get('/api/assignments/calendar', { params })
        this.assignments = response.data.events || []
        console.log('📅 [ShiftStore] Assignments loaded:', this.assignments.length)
      } catch (error) {
        console.error('❌ [ShiftStore] Error loading assignments:', error)
      } finally {
        this.loading = false
      }
    },

    async createShift(name, code, startTime, endTime, type = 'custom', color = '#3498db', description = null) {
      const response = await api.post('/api/shifts', { name, code, startTime, endTime, type, color, description })
      await this.fetchShifts()
    },

    async updateShift(id, name, code, startTime, endTime, type = 'custom', color = '#3498db', description = null) {
      const response = await api.put(`/api/shifts/${id}`, { name, code, startTime, endTime, type, color, description })

      if (!response.data) {
        throw new Error('Error al actualizar turno')
      }

      await this.fetchShifts()
    },

    async deleteShift(id) {
      const response = await api.delete(`/api/shifts/${id}`)

      if (!response.data) {
        throw new Error('Error al eliminar turno')
      }

      await this.fetchShifts()
    }
  }
})
