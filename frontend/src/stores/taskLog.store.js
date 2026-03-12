import { defineStore } from 'pinia'
import api from '@/services/api'

export const useTaskLogStore = defineStore('taskLog', {
  state: () => ({
    logs: [],
    pendingTasks: [],
    loading: false,
    error: null
  }),

  getters: {
    todayLogs: (state) => {
      const today = new Date().toISOString().split('T')[0]
      return state.logs.filter(log => log.date === today)
    },
    completedLogs: (state) => state.logs.filter(log => log.status === 'completed'),
    pendingLogs: (state) => state.logs.filter(log => log.status === 'pending')
  },

  actions: {
    async fetchLogs(date = null) {
      this.loading = true
      this.error = null
      try {
        const params = {}
        if (date) params.date = date
        
        const response = await api.get('/api/task-logs', { params })
        this.logs = response.data.logs || []
        return this.logs
      } catch (error) {
        this.error = error.message
        console.error('Error fetching task logs:', error)
        throw error
      } finally {
        this.loading = false
      }
    },

    async fetchPendingTasks(date = null) {
      this.loading = true
      try {
        const params = {}
        if (date) params.date = date
        
        const response = await api.get('/api/task-logs/pending-tasks', { params })
        this.pendingTasks = response.data.tasks || []
        return this.pendingTasks
      } catch (error) {
        console.error('Error fetching pending tasks:', error)
        throw error
      } finally {
        this.loading = false
      }
    },

    async createLog(logData) {
      try {
        const response = await api.post('/api/task-logs', logData)
        await this.fetchLogs(logData.date)
        await this.fetchPendingTasks(logData.date)
        return response.data
      } catch (error) {
        console.error('Error creating task log:', error)
        throw error
      }
    },

    async updateLog(id, logData) {
      try {
        const response = await api.put(`/api/task-logs/${id}`, logData)
        await this.fetchLogs()
        return response.data
      } catch (error) {
        console.error('Error updating task log:', error)
        throw error
      }
    }
  }
})
