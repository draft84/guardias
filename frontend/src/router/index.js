import { createRouter, createWebHistory } from 'vue-router'

// Importar vistas directamente
import LoginView from '@/views/LoginView.vue'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import DashboardView from '@/views/DashboardView.vue'
import DepartmentsView from '@/views/DepartmentsView.vue'
import UsersView from '@/views/UsersView.vue'
import GuardsView from '@/views/GuardsView.vue'
import CalendarView from '@/views/CalendarView.vue'
import SettingsView from '@/views/SettingsView.vue'
import NotFoundView from '@/views/NotFoundView.vue'
import PublicGuardsView from '@/views/PublicGuardsView.vue'

const routes = [
  {
    path: '/day-shifts',
    name: 'DayShifts',
    component: PublicGuardsView,
    meta: { requiresGuest: true }
  },
  {
    path: '/login',
    name: 'Login',
    component: LoginView,
    meta: { requiresGuest: true }
  },
  {
    path: '/',
    component: DashboardLayout,
    redirect: '/dashboard',
    meta: { requiresAuth: true },
    children: [
      {
        path: '',
        name: 'Dashboard',
        component: DashboardView
      },
      {
        path: 'departments',
        name: 'Departments',
        component: DepartmentsView
      },
      {
        path: 'users',
        name: 'Users',
        component: UsersView
      },
      {
        path: 'guards',
        name: 'Guards',
        component: GuardsView
      },
      {
        path: 'calendar',
        name: 'Calendar',
        component: CalendarView
      },
      {
        path: 'settings',
        name: 'Settings',
        component: SettingsView,
        meta: { requiresManagerOrAdmin: true }
      }
    ]
  },
  {
    path: '/:pathMatch(.*)*',
    name: 'NotFound',
    component: NotFoundView
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

// Guard de navegación
router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('token')
  const user = JSON.parse(localStorage.getItem('user') || 'null')
  const isAuthenticated = !!token
  const requiresAuth = to.matched.some(record => record.meta.requiresAuth)
  const requiresGuest = to.matched.some(record => record.meta.requiresGuest)
  const requiresManagerOrAdmin = to.matched.some(record => record.meta.requiresManagerOrAdmin)
  const requiresAdmin = to.path === '/departments' || to.path === '/settings'

  // Verificar si requiere autenticación
  if (requiresAuth && !isAuthenticated) {
    next('/day-shifts')
    return
  }

  // Verificar si es guest y ya está autenticado
  if (requiresGuest && isAuthenticated && to.path !== '/day-shifts') {
    next('/')
    return
  }

  // Verificar si requiere rol ADMIN (Departamentos y Configuración)
  if (requiresAdmin) {
    if (!isAuthenticated) {
      next('/day-shifts')
      return
    }

    const roles = user?.roles || []
    const isAdmin = roles.includes('ROLE_ADMIN')

    if (!isAdmin) {
      // Redirigir al dashboard si no es ADMIN
      next({ name: 'Dashboard', query: { access: 'denied', section: to.name } })
      return
    }
  }

  // Verificar si requiere rol MANAGER o ADMIN (otras secciones protegidas)
  if (requiresManagerOrAdmin) {
    if (!isAuthenticated) {
      next('/day-shifts')
      return
    }

    const roles = user?.roles || []
    const isManagerOrAdmin = roles.includes('ROLE_ADMIN') || roles.includes('ROLE_MANAGER')

    if (!isManagerOrAdmin) {
      // Redirigir al dashboard si no tiene permisos
      next({ name: 'Dashboard', query: { access: 'denied', section: to.name } })
      return
    }
  }

  next()
})

export default router
