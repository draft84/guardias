import { defineStore } from 'pinia'

const API_URL = 'http://localhost:10000'

export const useDepartmentStore = defineStore('department', {
  state: () => ({
    departments: [],
    loading: false,
    error: null
  }),

  getters: {
    activeDepartments: (state) => state.departments.filter(d => d.active)
  },

  actions: {
    async fetchDepartments() {
      this.loading = true
      this.error = null
      try {
        const token = localStorage.getItem('token')
        const response = await fetch(`${API_URL}/api/departments`, {
          headers: {
            'Authorization': `Bearer ${token}`
          }
        })
        
        if (!response.ok) throw new Error('Error al cargar departamentos')
        
        const data = await response.json()
        this.departments = data.departments || []
      } catch (error) {
        this.error = error.message
        console.error('Error:', error)
      } finally {
        this.loading = false
      }
    },

    async createDepartment(dept) {
      const token = localStorage.getItem('token')
      const response = await fetch(`${API_URL}/api/departments`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${token}`
        },
        body: JSON.stringify(dept)
      })
      
      if (!response.ok) throw new Error('Error al crear')
      
      await this.fetchDepartments()
    },

    async updateDepartment(id, dept) {
      const token = localStorage.getItem('token')
      const response = await fetch(`${API_URL}/api/departments/${id}`, {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${token}`
        },
        body: JSON.stringify(dept)
      })
      
      if (!response.ok) throw new Error('Error al actualizar')
      
      await this.fetchDepartments()
    },

    async deleteDepartment(id) {
      const token = localStorage.getItem('token')
      const response = await fetch(`${API_URL}/api/departments/${id}`, {
        method: 'DELETE',
        headers: {
          'Authorization': `Bearer ${token}`
        }
      })

      if (!response.ok) throw new Error('Error al eliminar')

      await this.fetchDepartments()
    },

    async downloadTemplate() {
      const token = localStorage.getItem('token')
      const response = await fetch(`${API_URL}/api/departments/export-template`, {
        headers: {
          'Authorization': `Bearer ${token}`
        }
      })

      if (!response.ok) throw new Error('Error al descargar plantilla')

      const blob = await response.blob()
      const url = window.URL.createObjectURL(blob)
      const link = document.createElement('a')
      link.href = url
      link.download = 'plantilla_departamentos.xlsx'
      document.body.appendChild(link)
      link.click()
      document.body.removeChild(link)
      window.URL.revokeObjectURL(url)
    },

    async exportDepartments() {
      const token = localStorage.getItem('token')
      const response = await fetch(`${API_URL}/api/departments/export`, {
        headers: {
          'Authorization': `Bearer ${token}`
        }
      })

      if (!response.ok) throw new Error('Error al exportar departamentos')

      const blob = await response.blob()
      const url = window.URL.createObjectURL(blob)
      const link = document.createElement('a')
      link.href = url
      link.download = `departamentos_export_${new Date().toISOString().split('T')[0]}.xlsx`
      document.body.appendChild(link)
      link.click()
      document.body.removeChild(link)
      window.URL.revokeObjectURL(url)
    },

    async importDepartments(file) {
      const token = localStorage.getItem('token')
      const formData = new FormData()
      formData.append('file', file)

      const response = await fetch(`${API_URL}/api/departments/import`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`
        },
        body: formData
      })

      const data = await response.json()

      if (!response.ok) {
        throw new Error(data.error || 'Error al importar departamentos')
      }

      await this.fetchDepartments()
      return data
    }
  }
})
