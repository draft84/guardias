import { defineStore } from 'pinia'
import api from '@/services/api'

const API_URL = 'http://localhost:8000'

export const useTaskStore = defineStore('task', {
  state: () => ({
    tasks: [],
    loading: false,
    error: null
  }),

  getters: {
    pendingTasks: (state) => state.tasks.filter(t => t.status === 'pending'),
    inProgressTasks: (state) => state.tasks.filter(t => t.status === 'in_progress'),
    completedTasks: (state) => state.tasks.filter(t => t.status === 'completed'),
    cancelledTasks: (state) => state.tasks.filter(t => t.status === 'cancelled')
  },

  actions: {
    async fetchTasks(filters = {}) {
      this.loading = true
      this.error = null
      try {
        const token = localStorage.getItem('token')
        const params = new URLSearchParams()
        if (filters.status) params.append('status', filters.status)
        if (filters.department) params.append('department', filters.department)

        const response = await fetch(`${API_URL}/api/tasks?${params.toString()}`, {
          headers: {
            'Authorization': `Bearer ${token}`
          }
        })

        if (!response.ok) throw new Error('Error al cargar tareas')

        const data = await response.json()
        this.tasks = data.tasks || []
        return data
      } catch (error) {
        this.error = error.message
        console.error('❌ [TaskStore] Error:', error)
        throw error
      } finally {
        this.loading = false
      }
    },

    async fetchTaskById(id) {
      try {
        const response = await api.get(`/api/tasks/${id}`)
        return response.data.task
      } catch (error) {
        console.error('Error fetching task:', error)
        throw error
      }
    },

    async createTask(taskData) {
      try {
        const response = await api.post('/api/tasks', taskData)
        await this.fetchTasks()
        return response.data
      } catch (error) {
        console.error('Error creating task:', error)
        throw error
      }
    },

    async updateTask(id, taskData) {
      try {
        const response = await api.put(`/api/tasks/${id}`, taskData)
        await this.fetchTasks()
        return response.data
      } catch (error) {
        console.error('Error updating task:', error)
        throw error
      }
    },

    async updateTaskStatus(id, status, completionNotes = null) {
      try {
        const response = await api.put(`/api/tasks/${id}/status`, {
          status,
          completionNotes
        })
        await this.fetchTasks()
        return response.data
      } catch (error) {
        console.error('Error updating task status:', error)
        throw error
      }
    },

    async deleteTask(id) {
      try {
        await api.delete(`/api/tasks/${id}`)
        this.tasks = this.tasks.filter(t => t.id !== id)
      } catch (error) {
        console.error('Error deleting task:', error)
        throw error
      }
    }
  }
})
