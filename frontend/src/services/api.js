import axios from 'axios'

const API_URL = import.meta.env.VITE_API_URL

if (!API_URL) {
  console.error('VITE_API_URL no está configurada en el archivo .env')
}

const api = axios.create({
  baseURL: API_URL,
  headers: {
    'Content-Type': 'application/json'
  }
})

// Interceptor para agregar token JWT
api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('token')
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    return config
  },
  (error) => {
    return Promise.reject(error)
  }
)

// Interceptor para escuchar actualizaciones de notificaciones
api.interceptors.response.use(
  (response) => {
    // Verificar si la respuesta indica que las notificaciones se actualizaron
    if (response.headers['x-notifications-updated'] === 'true') {
      // Dispatch custom event for real-time updates
      window.dispatchEvent(new CustomEvent('notifications-updated', {
        detail: { action: 'notifications-updated' }
      }))
      
      // Also dispatch specific events based on the request
      if (response.config.method === 'delete') {
        if (response.config.url.includes('/guards/')) {
          window.dispatchEvent(new CustomEvent('guard-deleted'))
        }
        if (response.config.url.includes('/assignments/')) {
          window.dispatchEvent(new CustomEvent('assignment-deleted'))
        }
      }
    }
    return response
  },
  (error) => {
    if (error.response?.status === 401 && !window.location.pathname.includes('/login')) {
      localStorage.removeItem('token')
      localStorage.removeItem('user')
      window.location.href = '/login'
    }
    return Promise.reject(error)
  }
)

export default api
