import { defineStore } from 'pinia'
import api from '@/services/api'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: JSON.parse(localStorage.getItem('user') || 'null'),
    token: localStorage.getItem('token') || null
  }),

  getters: {
    isAuthenticated: (state) => !!state.token,
    isAdmin: (state) => state.user?.roles?.includes('ROLE_ADMIN'),
    isManager: (state) => state.user?.roles?.includes('ROLE_MANAGER'),
    isManagerOrAdmin: (state) => {
      return state.user?.roles?.includes('ROLE_ADMIN') || state.user?.roles?.includes('ROLE_MANAGER')
    },
    userName: (state) => {state.user?.fullName || state.user?.email || 'Usuario'; console.log("state.user =======> ", state.user); return state.user?.fullName || state.user?.email || 'Usuario';}
  },

  actions: {
    async login(email, password) {
      try {
        console.log('Intentando login con:', email)

        const response = await api.post('/api/auth/login', { email, password })

        console.log('Login exitoso, token recibido:', response.data.token ? 'SI' : 'NO')

        const data = response.data
        if (data.token) {
          this.token = data.token
          localStorage.setItem('token', data.token)
          console.log('Token guardado en localStorage')

          // Siempre hacer fetch del usuario para obtener información completa
          await this.fetchUser()
        }

        return data
      } catch (error) {
        console.error('Login error completo:', error)
        throw error
      }
    },

    async logout() {
      try {
        await api.post('/api/auth/logout')
      } catch (error) {
        console.error('Logout error:', error)
      } finally {
        this.token = null
        this.user = null
        localStorage.removeItem('token')
        localStorage.removeItem('user')
      }
    },

    async fetchUser() {
      try {
        const response = await api.get('/api/auth/me')

        if (response.data) {
          this.user = response.data.user
          localStorage.setItem('user', JSON.stringify(response.data.user))
        }
      } catch (error) {
        console.error('Fetch user error:', error)
        this.token = null
        this.user = null
        localStorage.removeItem('token')
        localStorage.removeItem('user')
        throw error
      }
    },

    async changePassword(currentPassword, newPassword, confirmPassword) {
      try {
        const response = await api.post('/api/auth/profile/change-password', {
          currentPassword,
          newPassword,
          confirmPassword
        })
        return response.data
      } catch (error) {
        throw error
      }
    },

    getToken() {
      return localStorage.getItem('token')
    }
  }
})
