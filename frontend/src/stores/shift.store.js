import { defineStore } from 'pinia'

const API_URL = 'http://localhost:8000'

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
        const token = localStorage.getItem('token')
        const response = await fetch(`${API_URL}/api/shifts`, {
          headers: { 'Authorization': `Bearer ${token}` }
        })
        if (!response.ok) throw new Error('Error')
        const data = await response.json()
        this.shifts = data.shifts || []
      } finally {
        this.loading = false
      }
    },

    async createShift(shift) {
      const token = localStorage.getItem('token')
      const response = await fetch(`${API_URL}/api/shifts`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${token}`
        },
        body: JSON.stringify(shift)
      })
      if (!response.ok) throw new Error('Error')
      await this.fetchShifts()
    },

    async updateShift(id, shift) {
      const token = localStorage.getItem('token')
      const response = await fetch(`${API_URL}/api/shifts/${id}`, {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${token}`
        },
        body: JSON.stringify(shift)
      })
      if (!response.ok) throw new Error('Error')
      await this.fetchShifts()
    },

    async deleteShift(id) {
      const token = localStorage.getItem('token')
      const response = await fetch(`${API_URL}/api/shifts/${id}`, {
        method: 'DELETE',
        headers: { 'Authorization': `Bearer ${token}` }
      })
      if (!response.ok) throw new Error('Error')
      await this.fetchShifts()
    }
  }
})
