<template>
  <div class="notifications-container">
    <!-- Botón de notificaciones -->
    <div class="notification-bell" @click="toggleDropdown">
      <i class="pi pi-bell" :class="{ 'text-primary': hasUnread }"></i>
      <Badge v-if="hasUnread" :value="unreadCount" severity="danger" class="notification-badge" />
    </div>

    <!-- Dropdown de notificaciones -->
    <div v-if="isOpen" class="notification-dropdown surface-card shadow-4 border-round-xl">
      <div class="dropdown-header flex justify-content-between align-items-center p-3 border-bottom-1 surface-border">
        <span class="font-semibold text-lg">Notificaciones</span>
        <div class="flex gap-2">
          <Button 
            v-if="hasUnread" 
            icon="pi pi-check-all" 
            text 
            rounded 
            size="small" 
            @click="markAllAsRead"
            v-tooltip.top="'Marcar todas como leídas'"
          />
          <Button icon="pi pi-times" text rounded size="small" @click="closeDropdown" />
        </div>
      </div>

      <div class="dropdown-content" style="max-height: 400px; overflow-y: auto;">
        <div v-if="loading" class="flex justify-content-center p-4">
          <ProgressSpinner style="width: 40px; height: 40px" />
        </div>

        <div v-else-if="notifications.length === 0" class="text-center p-4 text-color-secondary">
          <i class="pi pi-bell-slash text-3xl mb-2" style="display:block; opacity:0.3"></i>
          <p>No hay notificaciones</p>
        </div>

        <div
          v-for="notification in notifications"
          :key="notification.id"
          class="notification-item p-3 border-bottom-1 surface-border cursor-pointer"
          :class="{ 'surface-50': !notification.read }"
          @click="viewNotification(notification)"
        >
          <div class="flex gap-3">
            <div class="notification-icon flex align-items-center justify-content-center" style="width: 40px; height: 40px; min-width: 40px;">
              <i
                class="pi text-lg"
                :class="getNotificationIcon(notification.type)"
                :style="{ color: getNotificationColor(notification.type) }"
              ></i>
            </div>

            <div class="notification-content flex-1">
              <div class="flex justify-content-between align-items-start">
                <h4 class="text-sm font-semibold m-0" :class="{ 'text-900': !notification.read, 'text-600': notification.read }">
                  {{ notification.title }}
                </h4>
                <span class="text-xs text-color-secondary ml-2" style="white-space: nowrap;">
                  {{ formatTime(notification.createdAt) }}
                </span>
              </div>

              <p class="text-sm text-color m-0 mt-1">{{ notification.message }}</p>

              <!-- Acciones para swap_request -->
              <div v-if="notification.type === 'swap_request' && !notification.read" class="flex gap-2 mt-2" @click.stop>
                <Button
                  label="Aceptar"
                  icon="pi pi-check"
                  size="small"
                  severity="success"
                  :loading="acceptingSwap"
                  @click="acceptSwap(notification)"
                />
              </div>

              <!-- Información para swap_accepted -->
              <div v-if="notification.type === 'swap_accepted'" class="mt-2 p-2 border-round surface-100">
                <p class="text-xs text-color-secondary m-0">
                  <i class="pi pi-check-circle text-green-600 mr-1"></i>
                  Tu solicitud de cambio ha sido aceptada
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div v-if="notifications.length > 0" class="dropdown-footer p-2 border-top-1 surface-border text-center">
        <Button 
          label="Ver todas" 
          text 
          size="small" 
          @click="openFullNotifications"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useNotificationStore } from '@/stores/notification.store'
import { useAuthStore } from '@/stores/auth.store'
import { useRouter } from 'vue-router'
import Badge from 'primevue/badge'
import Button from 'primevue/button'
import ProgressSpinner from 'primevue/progressspinner'

const notificationStore = useNotificationStore()
const authStore = useAuthStore()
const router = useRouter()

const isOpen = ref(false)
const acceptingSwap = ref(false)

const notifications = computed(() => notificationStore.notifications)
const unreadCount = computed(() => notificationStore.unreadCount)
const hasUnread = computed(() => notificationStore.hasUnreadNotifications)
const loading = computed(() => notificationStore.loading)

let pollingInterval = null

// Watch para actualizar el contador en authStore
watch(unreadCount, (newCount) => {
  authStore.setUnreadNotificationsCount(newCount)
})

const toggleDropdown = async () => {
  isOpen.value = !isOpen.value
  if (isOpen.value) {
    await notificationStore.fetchNotifications(false, 10)
    startPolling()
  } else {
    stopPolling()
  }
}

const closeDropdown = () => {
  isOpen.value = false
  stopPolling()
}

const startPolling = () => {
  // Actualizar cada 10 segundos
  pollingInterval = setInterval(() => {
    notificationStore.fetchUnreadCount()
  }, 10000)
}

const stopPolling = () => {
  if (pollingInterval) {
    clearInterval(pollingInterval)
    pollingInterval = null
  }
}

const markAllAsRead = async () => {
  await notificationStore.markAllAsRead()
}

const acceptSwap = async (notification) => {
  acceptingSwap.value = true
  try {
    const result = await notificationStore.acceptSwap(notification.id)

    // Mostrar mensaje de éxito
    alert(result.message || 'Cambio aceptado exitosamente')

    // Recargar notificaciones
    await notificationStore.fetchNotifications(false, 10)

    // Cerrar dropdown
    closeDropdown()
  } catch (error) {
    alert(error.message || 'Error al aceptar el cambio')
  } finally {
    acceptingSwap.value = false
  }
}

const viewNotification = async (notification) => {
  // Marcar como leída
  await notificationStore.markAsRead(notification.id)

  // Navegar a la vista de notificaciones
  router.push('/notifications')
  closeDropdown()
}

const openFullNotifications = () => {
  router.push('/notifications')
  closeDropdown()
}

const getNotificationIcon = (type) => {
  const icons = {
    'swap_request': 'pi-exchange text-orange-500',
    'swap_accepted': 'pi-check-circle text-green-500',
    'swap_rejected': 'pi-times-circle text-red-500',
    'assignment_created': 'pi-calendar-plus text-blue-500',
    'assignment_updated': 'pi-calendar-edit text-purple-500',
  }
  return icons[type] || 'pi-bell'
}

const getNotificationColor = (type) => {
  const colors = {
    'swap_request': '#f59e0b',
    'swap_accepted': '#10b981',
    'swap_rejected': '#ef4444',
    'assignment_created': '#3b82f6',
    'assignment_updated': '#8b5cf6',
  }
  return colors[type] || '#6b7280'
}

const formatTime = (dateString) => {
  const date = new Date(dateString)
  const now = new Date()
  const diffMs = now - date
  const diffMins = Math.floor(diffMs / 60000)
  const diffHours = Math.floor(diffMins / 60)
  const diffDays = Math.floor(diffHours / 24)

  if (diffMins < 1) return 'Ahora'
  if (diffMins < 60) return `hace ${diffMins}m`
  if (diffHours < 24) return `hace ${diffHours}h`
  if (diffDays < 7) return `hace ${diffDays}d`
  return date.toLocaleDateString('es-ES', { day: 'numeric', month: 'short' })
}

onMounted(async () => {
  // Cargar contador inicial
  await notificationStore.fetchUnreadCount()
  // Iniciar polling para actualizar contador
  startPolling()
})

onUnmounted(() => {
  stopPolling()
})
</script>

<style scoped>
.notifications-container {
  position: relative;
}

.notification-bell {
  position: relative;
  cursor: pointer;
  padding: 0.5rem;
  border-radius: 50%;
  transition: background-color 0.2s;
}

.notification-bell:hover {
  background-color: var(--surface-hover);
}

.notification-badge {
  position: absolute;
  top: 0;
  right: 0;
  transform: translate(25%, -25%);
  min-width: 18px;
  height: 18px;
  font-size: 0.7rem;
}

.notification-dropdown {
  position: absolute;
  top: 100%;
  right: 0;
  width: 380px;
  z-index: 1000;
  margin-top: 0.5rem;
}

.dropdown-header {
  border-bottom: 1px solid var(--surface-border);
}

.notification-item {
  transition: background-color 0.2s;
  cursor: pointer;
}

.notification-item:hover {
  background-color: var(--surface-hover);
}

.notification-icon {
  border-radius: 50%;
  background-color: var(--surface-100);
}

.dropdown-footer {
  background-color: var(--surface-50);
  border-top: 1px solid var(--surface-border);
}
</style>
