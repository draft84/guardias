import { defineStore } from 'pinia'

const API_URL = 'http://localhost:8000'

export const useNotificationStore = defineStore('notification', {
  state: () => ({
    notifications: [],
    unreadCount: 0,
    loading: false,
    error: null
  }),

  getters: {
    unreadNotifications: (state) => state.notifications.filter(n => !n.read),
    hasUnreadNotifications: (state) => state.unreadCount > 0
  },

  actions: {
    async fetchNotifications(unreadOnly = false, limit = 20) {
      this.loading = true
      this.error = null
      try {
        const token = localStorage.getItem('token')
        console.log('🔔 [NotificationStore] fetchNotifications - Token:', token ? '✅ Presente' : '❌ Ausente')
        
        const url = `${API_URL}/api/notifications?unread=${unreadOnly}&limit=${limit}`
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
    }
  }
})
