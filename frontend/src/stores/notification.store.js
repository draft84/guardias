import { defineStore } from 'pinia'

const API_URL = 'http://localhost:8000'

export const useNotificationStore = defineStore('notification', {
  state: () => ({
    notifications: [],
    unreadCount: 0,
    loading: false,
    error: null,
    // Paginación
    currentPage: 1,
    totalPages: 1,
    totalItems: 0,
    itemsPerPage: 10,
    // Búsqueda
    searchQuery: '',
    filterType: 'all' // all, unread, read, swap_request, swap_accepted
  }),

  getters: {
    unreadNotifications: (state) => state.notifications.filter(n => !n.read),
    hasUnreadNotifications: (state) => state.unreadCount > 0,
    filteredNotifications: (state) => {
      let filtered = state.notifications

      // Filtrar por tipo
      if (state.filterType !== 'all') {
        if (state.filterType === 'unread') {
          filtered = filtered.filter(n => !n.read)
        } else if (state.filterType === 'read') {
          filtered = filtered.filter(n => n.read)
        } else {
          filtered = filtered.filter(n => n.type === state.filterType)
        }
      }

      // Filtrar por búsqueda
      if (state.searchQuery.trim()) {
        const query = state.searchQuery.toLowerCase()
        filtered = filtered.filter(n =>
          n.title.toLowerCase().includes(query) ||
          n.message.toLowerCase().includes(query)
        )
      }

      return filtered
    }
  },

  actions: {
    async fetchNotifications(options = {}) {
      const {
        unreadOnly = false,
        limit = this.itemsPerPage,
        page = 1,
        search = '',
        filterType = 'all'
      } = options

      this.loading = true
      this.error = null
      this.currentPage = page
      this.searchQuery = search
      this.filterType = filterType

      try {
        const token = localStorage.getItem('token')
        console.log('🔔 [NotificationStore] fetchNotifications - Token:', token ? '✅ Presente' : '❌ Ausente')

        // Construir URL con parámetros
        const params = new URLSearchParams()
        params.append('limit', limit)
        params.append('unread', unreadOnly)
        params.append('page', page)
        if (search) params.append('search', search)
        if (filterType !== 'all') params.append('type', filterType)

        const url = `${API_URL}/api/notifications?${params.toString()}`
        console.log('🔔 [NotificationStore] URL:', url)

        const response = await fetch(url, {
          headers: {
            'Authorization': `Bearer ${token}`
          }
        })

        console.log('🔔 [NotificationStore] Response status:', response.status)

        if (!response.ok) throw new Error('Error al cargar notificaciones')

        const data = await response.json()
        console.log('🔔 [NotificationStore] Data received:', data)

        this.notifications = data.notifications || []
        this.unreadCount = data.unreadCount || 0
        this.totalItems = data.totalItems || data.notifications?.length || 0
        this.totalPages = data.totalPages || Math.ceil(this.totalItems / limit) || 1

        console.log('🔔 [NotificationStore] Notifications:', this.notifications.length)
        console.log('🔔 [NotificationStore] Unread count:', this.unreadCount)

        return data
      } catch (error) {
        this.error = error.message
        console.error('❌ [NotificationStore] Error:', error)
        throw error
      } finally {
        this.loading = false
      }
    },

    async fetchUnreadCount() {
      try {
        const token = localStorage.getItem('token')
        console.log('🔔 [NotificationStore] fetchUnreadCount - Token:', token ? '✅ Presente' : '❌ Ausente')

        const response = await fetch(`${API_URL}/api/notifications/count`, {
          headers: {
            'Authorization': `Bearer ${token}`
          }
        })

        if (response.ok) {
          const data = await response.json()
          console.log('🔔 [NotificationStore] Unread count data:', data)
          this.unreadCount = data.unreadCount || 0
        }
      } catch (error) {
        console.error('❌ [NotificationStore] Error fetching unread count:', error)
      }
    },

    async markAsRead(notificationId) {
      const token = localStorage.getItem('token')
      try {
        const response = await fetch(
          `${API_URL}/api/notifications/${notificationId}/read`,
          {
            method: 'PUT',
            headers: {
              'Authorization': `Bearer ${token}`
            }
          }
        )

        if (response.ok) {
          // Actualizar estado local
          const index = this.notifications.findIndex(n => n.id === notificationId)
          if (index !== -1) {
            this.notifications[index].read = true
          }
          this.unreadCount = Math.max(0, this.unreadCount - 1)
        }
      } catch (error) {
        console.error('Error marking notification as read:', error)
      }
    },

    async markAllAsRead() {
      const token = localStorage.getItem('token')
      try {
        const response = await fetch(
          `${API_URL}/api/notifications/read-all`,
          {
            method: 'PUT',
            headers: {
              'Authorization': `Bearer ${token}`
            }
          }
        )

        if (response.ok) {
          this.notifications.forEach(n => n.read = true)
          this.unreadCount = 0
        }
      } catch (error) {
        console.error('Error marking all as read:', error)
      }
    },

    async acceptSwap(notificationId) {
      const token = localStorage.getItem('token')
      try {
        const response = await fetch(
          `${API_URL}/api/notifications/${notificationId}/accept-swap`,
          {
            method: 'POST',
            headers: {
              'Authorization': `Bearer ${token}`,
              'Content-Type': 'application/json'
            }
          }
        )

        const data = await response.json()

        if (!response.ok) {
          throw new Error(data.error || 'Error al aceptar el cambio')
        }

        // Marcar notificación como leída
        await this.markAsRead(notificationId)

        return data
      } catch (error) {
        console.error('Error accepting swap:', error)
        throw error
      }
    },

    async rejectSwap(notificationId, reason = '') {
      const token = localStorage.getItem('token')
      try {
        const response = await fetch(
          `${API_URL}/api/notifications/${notificationId}/reject-swap`,
          {
            method: 'POST',
            headers: {
              'Authorization': `Bearer ${token}`,
              'Content-Type': 'application/json'
            },
            body: JSON.stringify({ reason })
          }
        )

        const data = await response.json()

        if (!response.ok) {
          throw new Error(data.error || 'Error al rechazar el cambio')
        }

        // Marcar notificación como leída
        await this.markAsRead(notificationId)

        return data
      } catch (error) {
        console.error('Error rejecting swap:', error)
        throw error
      }
    },

    setSearchQuery(query) {
      this.searchQuery = query
    },

    setFilterType(type) {
      this.filterType = type
    },

    setPage(page) {
      this.currentPage = page
    }
  }
})
