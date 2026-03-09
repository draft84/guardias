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
        component: SettingsView
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
  const isAuthenticated = !!token
  const requiresAuth = to.matched.some(record => record.meta.requiresAuth)
  const requiresGuest = to.matched.some(record => record.meta.requiresGuest)

  if (requiresAuth && !isAuthenticated) {
    next('/day-shifts')
  } else if (requiresGuest && isAuthenticated && to.path !== '/day-shifts') {
    next('/')
  } else {
    next()
  }
})

export default router
