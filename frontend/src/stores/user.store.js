import { defineStore } from 'pinia'

const API_URL = 'http://localhost:8000'

export const useUserStore = defineStore('user', {
  state: () => ({
    users: [],
    levels: [],
    loading: false,
    error: null
  }),

  actions: {
    async fetchLevels() {
      this.loading = true
      try {
        const token = localStorage.getItem('token')
        const response = await fetch(`${API_URL}/api/guard-levels`, {
          headers: { 
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json'
          }
        })
        if (!response.ok) throw new Error('Error al obtener niveles')
        const data = await response.json()
        this.levels = data.levels || []
      } finally {
        this.loading = false
      }
    },

    async createLevel(name) {
      const token = localStorage.getItem('token')
      const response = await fetch(`${API_URL}/api/guard-levels`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${token}`
        },
        body: JSON.stringify({ name })
      })

      if (!response.ok) {
        const errorData = await response.json()
        throw new Error(errorData.error || 'Error al crear nivel')
      }

      await this.fetchLevels()
    },

    async updateLevel(id, name) {
      const token = localStorage.getItem('token')
      const response = await fetch(`${API_URL}/api/guard-levels/${id}`, {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${token}`
        },
        body: JSON.stringify({ name })
      })

      if (!response.ok) {
        const errorData = await response.json()
        throw new Error(errorData.error || 'Error al actualizar nivel')
      }

      await this.fetchLevels()
    },

    async deleteLevel(id) {
      const token = localStorage.getItem('token')
      const response = await fetch(`${API_URL}/api/guard-levels/${id}`, {
        method: 'DELETE',
        headers: { 'Authorization': `Bearer ${token}` }
      })

      if (!response.ok) {
        const errorData = await response.json()
        throw new Error(errorData.error || 'Error al eliminar nivel')
      }

      await this.fetchLevels()
    },

    async fetchUsers() {
      this.loading = true
      this.error = null
      
      const token = localStorage.getItem('token')
      console.log('Fetch users - Token:', token ? 'ENCONTRADO' : 'NO ENCONTRADO')
      
      try {
        const response = await fetch(`${API_URL}/api/users`, {
          headers: { 
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json'
          }
        })
        
        console.log('Response status:', response.status)
        
        if (!response.ok) {
          const errorData = await response.json().catch(() => ({}))
          console.error('Error response:', errorData)
          throw new Error(errorData.message || `Error ${response.status}: ${response.statusText}`)
        }
        
        const data = await response.json()
        console.log('Usuarios recibidos:', data)
        this.users = data.users || []
      } catch (error) {
        this.error = error.message
        console.error('Error fetching users:', error)
        this.users = []
      } finally {
        this.loading = false
      }
    },

    async createUser(userData) {
      const token = localStorage.getItem('token')
      
      const payload = {
        email: userData.email,
        password: userData.password,
        firstName: userData.firstName,
        lastName: userData.lastName,
        phone: userData.phone,
        roles: userData.roles || ['ROLE_USER'],
        departmentId: userData.departmentId || null,
        guardLevelId: userData.guardLevelId || null,
        active: userData.active !== undefined ? userData.active : true
      }
      
      const response = await fetch(`${API_URL}/api/users`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${token}`
        },
        body: JSON.stringify(payload)
      })
      
      if (!response.ok) {
        const errorData = await response.json().catch(() => ({}))
        throw new Error(errorData.error || errorData.message || 'Error al crear usuario')
      }
      
      await this.fetchUsers()
    },
 
    async updateUser(id, userData) {
      const token = localStorage.getItem('token')
      
      const payload = {
        firstName: userData.firstName,
        lastName: userData.lastName,
        email: userData.email,
        phone: userData.phone,
        active: userData.active !== undefined ? userData.active : true,
        roles: userData.roles || ['ROLE_USER']
      }
      
      if (userData.password && userData.password.trim() !== '') {
        payload.password = userData.password
      }
      
      if (userData.departmentId !== undefined) {
        payload.departmentId = userData.departmentId || null
      }
      
      if (userData.guardLevelId !== undefined) {
        payload.guardLevelId = userData.guardLevelId || null
      }
      
      console.log('Actualizando usuario:', id, payload)
      
      const response = await fetch(`${API_URL}/api/users/${id}`, {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${token}`
        },
        body: JSON.stringify(payload)
      })
      
      if (!response.ok) {
        const errorData = await response.json().catch(() => ({}))
        console.error('Error response:', errorData)
        throw new Error(errorData.error || errorData.message || 'Error al actualizar usuario')
      }
      
      const result = await response.json()
      console.log('Usuario actualizado:', result)
      
      // Refrescar la lista completa
      await this.fetchUsers()
    },

    async deleteUser(id) {
      const token = localStorage.getItem('token')
      const response = await fetch(`${API_URL}/api/users/${id}`, {
        method: 'DELETE',
        headers: { 
          'Authorization': `Bearer ${token}`
        }
      })
      
      if (!response.ok) {
        const errorData = await response.json().catch(() => ({}))
        throw new Error(errorData.message || 'Error al eliminar usuario')
      }
      
      await this.fetchUsers()
    }
  }
})
