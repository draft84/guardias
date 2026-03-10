<template>
  <div class="dashboard-layout">
    <!-- Sidebar -->
    <aside class="sidebar" :class="{ 'sidebar-collapsed': sidebarCollapsed }">
      <div class="sidebar-header">
        <h2 v-if="!sidebarCollapsed">🛡️ Guardias</h2>
        <h2 v-else>🛡️</h2>
      </div>

      <nav class="sidebar-nav">
        <router-link to="/" class="nav-item">
          <i class="pi pi-home"></i>
          <span v-if="!sidebarCollapsed">Dashboard</span>
        </router-link>

        <router-link v-if="authStore.isAdmin" to="/departments" class="nav-item">
          <i class="pi pi-building"></i>
          <span v-if="!sidebarCollapsed">Departamentos</span>
        </router-link>

        <router-link to="/users" class="nav-item">
          <i class="pi pi-users"></i>
          <span v-if="!sidebarCollapsed">Usuarios</span>
        </router-link>

        <router-link to="/guards" class="nav-item">
          <i class="pi pi-shield"></i>
          <span v-if="!sidebarCollapsed">Guardias</span>
        </router-link>

        <router-link to="/calendar" class="nav-item">
          <i class="pi pi-calendar"></i>
          <span v-if="!sidebarCollapsed">Calendario</span>
        </router-link>

        <router-link v-if="authStore.isAdmin" to="/settings" class="nav-item">
          <i class="pi pi-cog"></i>
          <span v-if="!sidebarCollapsed">Configuración</span>
        </router-link>
      </nav>

      <div class="sidebar-footer">
        <button @click="toggleSidebar" class="btn-toggle">
          <i :class="sidebarCollapsed ? 'pi pi-angle-right' : 'pi pi-angle-left'"></i>
        </button>
      </div>
    </aside>

    <!-- Main Content -->
    <div class="main-content">
      <!-- Header -->
      <header class="header">
        <div class="header-left">
          <button @click="toggleSidebar" class="btn-menu">
            <i class="pi pi-bars"></i>
          </button>
        </div>

        <div class="header-right">
          <NotificationsBell class="mr-2" />
          
          <Button
            :icon="layoutStore.isDarkTheme ? 'pi pi-moon' : 'pi pi-sun'"
            text
            rounded
            @click="toggleTheme"
            title="Cambiar tema"
            class="mr-2"
          />

          <div class="user-info">
            <span class="user-name">{{ authStore.userName }}</span>
          </div>

          <Button
            icon="pi pi-sign-out"
            text
            rounded
            @click="logout"
            title="Cerrar sesión"
          />
        </div>
      </header>

      <!-- Page Content -->
      <main class="page-content">
        <router-view />
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth.store'
import { useLayoutStore } from '@/stores/layout.store'
import Button from 'primevue/button'
import NotificationsBell from '@/components/NotificationsBell.vue'

const router = useRouter()
const authStore = useAuthStore()
const layoutStore = useLayoutStore()
const sidebarCollapsed = ref(false)

const toggleSidebar = () => {
  sidebarCollapsed.value = !sidebarCollapsed.value
}

const toggleTheme = () => {
  layoutStore.toggleTheme()
}

const logout = async () => {
  await authStore.logout()
  router.push('/login')
}
</script>

<style scoped>
.dashboard-layout {
  display: flex;
  height: 100vh;
  overflow: hidden;
}

.sidebar {
  width: 260px;
  background: var(--surface-ground);
  border-right: 1px solid var(--surface-border);
  display: flex;
  flex-direction: column;
  transition: width 0.3s ease;
}

.sidebar-collapsed {
  width: 80px;
}

.sidebar-header {
  padding: 1.5rem;
  border-bottom: 1px solid var(--surface-border);
}

.sidebar-header h2 {
  margin: 0;
  font-size: 1.25rem;
  color: var(--text-color);
}

.sidebar-nav {
  flex: 1;
  padding: 1rem 0;
  overflow-y: auto;
}

.nav-item {
  display: flex;
  align-items: center;
  padding: 0.75rem 1.5rem;
  color: var(--text-color-secondary);
  text-decoration: none;
  transition: all 0.2s;
}

.nav-item:hover {
  background: var(--surface-hover);
  color: var(--text-color);
}

.nav-item.router-link-exact-active {
  background: var(--p-primary-color);
  color: #ffffff;
  font-weight: 600;
  border-radius: 6px;
  margin: 0 0.5rem;
  padding: 0.75rem 1rem;
}

.nav-item.router-link-exact-active i {
  color: #ffffff;
}

.nav-item i {
  font-size: 1.25rem;
  width: 24px;
  margin-right: 0.75rem;
}

.sidebar-collapsed .nav-item span {
  display: none;
}

.sidebar-collapsed .nav-item i {
  margin-right: 0;
}

.sidebar-footer {
  padding: 1rem;
  border-top: 1px solid var(--surface-border);
}

.btn-toggle {
  width: 100%;
  padding: 0.5rem;
  background: transparent;
  border: 1px solid var(--surface-border);
  border-radius: var(--border-radius);
  cursor: pointer;
  color: var(--text-color-secondary);
}

.btn-toggle:hover {
  background: var(--surface-hover);
}

.main-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.header {
  height: 64px;
  background: var(--surface-card);
  border-bottom: 1px solid var(--surface-border);
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 1.5rem;
}

.header-left {
  display: flex;
  align-items: center;
}

.btn-menu {
  background: transparent;
  border: none;
  cursor: pointer;
  padding: 0.5rem;
  color: var(--text-color-secondary);
}

.btn-menu:hover {
  color: var(--text-color);
}

.header-right {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.user-info {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
}

.user-name {
  font-weight: 600;
  color: var(--text-color);
}

.page-content {
  flex: 1;
  overflow-y: auto;
  padding: 1.5rem;
  background: var(--surface-ground);
}
</style>
