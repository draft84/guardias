<template>
  <div class="p-4">
    <!-- Mensaje de Acceso Denegado -->
    <Message v-if="$route.query.access === 'denied'" severity="warn" :closable="false" class="mb-4">
      <template #icon>
        <i class="pi pi-exclamation-triangle"></i>
      </template>
      <div class="flex flex-column gap-2">
        <p class="m-0 font-bold">Acceso Denegado</p>
        <p class="m-0">
          No tienes permisos para acceder a la sección de 
          <strong>{{ getSectionName($route.query.section) }}</strong>. 
          Solo los usuarios con rol <strong>ADMINISTRADOR</strong> pueden acceder.
        </p>
      </div>
    </Message>

    <!-- Header -->
    <div class="flex align-items-center gap-2 mb-5">
      <i class="pi pi-home text-3xl text-primary"></i>
      <h2 class="text-2xl font-bold m-0">Dashboard</h2>
    </div>

    <!-- Stats Cards -->
    <div class="grid mb-5">
      <div class="col-12 sm:col-6 lg:col-3">
        <div class="surface-card border-round-xl shadow-2 p-4 flex align-items-center gap-4">
          <div class="flex align-items-center justify-content-center border-round-xl" style="width:56px;height:56px; background-color: var(--blue-100);">
            <i class="pi pi-users text-2xl" style="color: var(--blue-500);"></i>
          </div>
          <div>
            <p class="text-color-secondary text-sm m-0">Usuarios</p>
            <p class="text-color text-3xl font-bold m-0">{{ userStore.users.length }}</p>
          </div>
        </div>
      </div>
      <div class="col-12 sm:col-6 lg:col-3">
        <div class="surface-card border-round-xl shadow-2 p-4 flex align-items-center gap-4">
          <div class="flex align-items-center justify-content-center border-round-xl" style="width:56px;height:56px; background-color: var(--green-100);">
            <i class="pi pi-building text-2xl" style="color: var(--green-500);"></i>
          </div>
          <div>
            <p class="text-color-secondary text-sm m-0">Departamentos</p>
            <p class="text-color text-3xl font-bold m-0">{{ departmentStore.departments.length }}</p>
          </div>
        </div>
      </div>
      <div class="col-12 sm:col-6 lg:col-3">
        <div class="surface-card border-round-xl shadow-2 p-4 flex align-items-center gap-4">
          <div class="flex align-items-center justify-content-center border-round-xl" style="width:56px;height:56px; background-color: var(--purple-100);">
            <i class="pi pi-shield text-2xl" style="color: var(--purple-500);"></i>
          </div>
          <div>
            <p class="text-color-secondary text-sm m-0">Guardias</p>
            <p class="text-color text-3xl font-bold m-0">{{ guardStore.guards.length }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Quick links -->
    <div class="surface-card border-round-xl shadow-2 p-4">
      <h3 class="text-lg font-bold text-700 mt-0 mb-4">Accesos rápidos</h3>
      <div class="flex flex-wrap gap-3">
        <RouterLink to="/departments">
          <Button label="Departamentos" icon="pi pi-building" outlined />
        </RouterLink>
        <RouterLink to="/users">
          <Button label="Usuarios" icon="pi pi-users" outlined />
        </RouterLink>
        <RouterLink to="/guards">
          <Button label="Guardias" icon="pi pi-shield" outlined />
        </RouterLink>
        <RouterLink to="/calendar">
          <Button label="Calendario" icon="pi pi-calendar" outlined />
        </RouterLink>
      </div>
    </div>

  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import { useUserStore } from '@/stores/user.store'
import { useDepartmentStore } from '@/stores/department.store'
import { useGuardStore } from '@/stores/guard.store'
import Button from 'primevue/button'
import Message from 'primevue/message'

const userStore = useUserStore()
const departmentStore = useDepartmentStore()
const guardStore = useGuardStore()

const getSectionName = (section) => {
  const names = {
    'Departments': 'Departamentos',
    'Settings': 'Configuración'
  }
  return names[section] || section || 'esta sección'
}

onMounted(() => {
  userStore.fetchUsers()
  departmentStore.fetchDepartments()
  guardStore.fetchGuards()
})
</script>
