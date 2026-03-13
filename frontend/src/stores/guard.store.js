import { defineStore } from 'pinia'

const API_URL = 'http://localhost:10000'

export const useGuardStore = defineStore('guard', {
  state: () => ({
    guards: [],
    loading: false,
    error: null
  }),

  actions: {
    async fetchGuards() {
      this.loading = true
      try {
        const token = localStorage.getItem('token')
        const response = await fetch(`${API_URL}/api/guards`, {
          headers: { 
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json'
          }
        })
        if (!response.ok) throw new Error('Error')
        const data = await response.json()
        this.guards = data.guards || []
      } finally {
        this.loading = false
      }
    },

    async createGuard(guardData) {
      const token = localStorage.getItem('token')
      console.log('createGuard called with:', guardData)

      const payload = {
        name: guardData.name,
        startTime: guardData.startTime,
        endTime: guardData.endTime,
        active: guardData.active !== undefined ? guardData.active : true,
        weekDays: guardData.weekDays || [],
        validFrom: guardData.validFrom,
        validUntil: guardData.validUntil || null,
        guardLevelId: guardData.guardLevelId || null
      }

      if (guardData.description) {
        payload.description = guardData.description
      }

      if (guardData.departmentId) {
        payload.departmentId = guardData.departmentId
      }

      console.log('Sending payload:', payload)

      const response = await fetch(`${API_URL}/api/guards`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${token}`
        },
        body: JSON.stringify(payload)
      })

      console.log('Response status:', response.status)

      if (!response.ok) {
        const errorData = await response.json()
        console.error('Error data:', errorData)
        throw new Error(errorData.message || errorData.error || 'Error al crear guardia')
      }

      const result = await response.json()
      console.log('Guard created:', result)

      await this.fetchGuards()
      return result
    },

    async updateGuard(id, guardData) {
      const token = localStorage.getItem('token')
      console.log('updateGuard called with:', guardData)

      const payload = {
        name: guardData.name,
        startTime: guardData.startTime,
        endTime: guardData.endTime,
        active: guardData.active !== undefined ? guardData.active : true,
        weekDays: guardData.weekDays !== undefined ? guardData.weekDays : [],
        validFrom: guardData.validFrom,
        validUntil: guardData.validUntil || null,
        guardLevelId: guardData.guardLevelId || null
      }

      if (guardData.description) {
        payload.description = guardData.description
      }

      if (guardData.departmentId !== undefined) {
        payload.departmentId = guardData.departmentId || null
      }

      console.log('Sending payload:', payload)

      const response = await fetch(`${API_URL}/api/guards/${id}`, {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${token}`
        },
        body: JSON.stringify(payload)
      })

      if (!response.ok) {
        const errorData = await response.json().catch(() => ({}))
        throw new Error(errorData.message || 'Error al actualizar guardia')
      }

      await this.fetchGuards()
    },

    async deleteGuard(id) {
      const token = localStorage.getItem('token')
      const response = await fetch(`${API_URL}/api/guards/${id}`, {
        method: 'DELETE',
        headers: { 'Authorization': `Bearer ${token}` }
      })
      if (!response.ok) throw new Error('Error')
      await this.fetchGuards()
    }
  }
})
