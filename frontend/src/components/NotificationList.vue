<template>
  <div class="notification-list">
    <!-- Filtros y búsqueda -->
    <div class="filters-section surface-card border-round p-3 mb-4">
      <div class="flex flex-wrap gap-3 align-items-center">
        <!-- Buscador -->
        <div class="flex-1 min-w-200">
          <IconField>
            <InputIcon>
              <i class="pi pi-search" />
            </InputIcon>
            <InputText
              v-model="searchInput"
              placeholder="Buscar notificaciones..."
              @input="debouncedSearch"
              class="w-full"
            />
          </IconField>
        </div>

        <!-- Filtro por tipo -->
        <div class="min-w-150">
          <Select
            v-model="selectedFilter"
            :options="filterOptions"
            option-label="label"
            option-value="value"
            placeholder="Filtrar por tipo"
            class="w-full"
            @change="onFilterChange"
          />
        </div>

        <!-- Botón marcar todas como leídas -->
        <Button
          v-if="hasUnread"
          label="Marcar todas como leídas"
          icon="pi pi-check-all"
          severity="secondary"
          outlined
          @click="handleMarkAllAsRead"
        />
      </div>
    </div>

    <!-- Lista de notificaciones -->
    <div v-if="loading" class="flex justify-content-center p-5">
      <ProgressSpinner style="width: 50px; height: 50px" />
    </div>

    <div v-else-if="filteredNotifications.length === 0" class="text-center p-5 surface-card border-round">
      <i class="pi pi-inbox text-5xl text-color-secondary mb-3" style="display:block; opacity:0.3"></i>
      <p class="text-lg text-color-secondary m-0">
        {{ searchQuery || filterType !== 'all' ? 'No se encontraron notificaciones' : 'No hay notificaciones' }}
      </p>
    </div>

    <div v-else class="notifications-accordion">
      <Accordion :value="expandedNotification" class="p-0">
        <AccordionPanel
          v-for="notification in filteredNotifications"
          :key="notification.id"
          :value="notification.id"
          class="mb-3"
        >
          <AccordionHeader>
            <div class="notification-summary flex align-items-center gap-3 w-full">
              <!-- Icono -->
              <div
                class="notification-icon flex align-items-center justify-content-center border-round-circle"
                :style="{ 
                  backgroundColor: getNotificationBg(notification.type),
                  color: getNotificationColor(notification.type),
                  minWidth: '45px',
                  height: '45px'
                }"
              >
                <i :class="getNotificationIcon(notification.type)" class="text-xl"></i>
              </div>

              <!-- Contenido -->
              <div class="notification-info flex-1">
                <div class="flex justify-content-between align-items-center gap-3">
                  <h4
                    class="text-base font-semibold m-0"
                    :class="{ 'text-900': !notification.read, 'text-600': notification.read }"
                  >
                    {{ notification.title }}
                  </h4>
                  <div class="flex align-items-center gap-2">
                    <Tag
                      :value="getNotificationTypeLabel(notification.type)"
                      :severity="getNotificationSeverity(notification.type)"
                      size="small"
                    />
                    <span class="text-xs text-color-secondary">
                      {{ formatTime(notification.createdAt) }}
                    </span>
                  </div>
                </div>
                <p class="text-sm text-color m-0 mt-1 line-clamp-1">
                  {{ notification.message }}
                </p>
              </div>

              <!-- Indicador de no leído -->
              <div v-if="!notification.read" class="unread-indicator">
                <span class="dot bg-primary"></span>
              </div>
            </div>
          </AccordionHeader>
          <AccordionContent>
            <div class="p-4 pt-0">
              <NotificationDetail
                :notification="notification"
                @accepted="handleNotificationAction"
                @rejected="handleNotificationAction"
                @updated="handleNotificationAction"
              />
            </div>
          </AccordionContent>
        </AccordionPanel>
      </Accordion>
    </div>

    <!-- Paginación -->
    <div v-if="totalPages > 1" class="pagination flex justify-content-center align-items-center gap-2 mt-4">
      <Button
        icon="pi pi-chevron-left"
        text
        rounded
        :disabled="currentPage === 1"
        @click="goToPage(currentPage - 1)"
      />
      
      <template v-for="page in visiblePages" :key="page">
        <Button
          v-if="page === currentPage"
          :label="page.toString()"
          severity="primary"
          class="w-3 h-3"
        />
        <Button
          v-else
          :label="page.toString()"
          text
          class="w-3 h-3"
          @click="goToPage(page)"
        />
      </template>

      <Button
        icon="pi pi-chevron-right"
        text
        rounded
        :disabled="currentPage === totalPages"
        @click="goToPage(currentPage + 1)"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { useNotificationStore } from '@/stores/notification.store'
import { useToast } from 'primevue/usetoast'
import Accordion from 'primevue/accordion'
import AccordionPanel from 'primevue/accordionpanel'
import AccordionHeader from 'primevue/accordionheader'
import AccordionContent from 'primevue/accordioncontent'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import Select from 'primevue/select'
import Tag from 'primevue/tag'
import ProgressSpinner from 'primevue/progressspinner'
import NotificationDetail from './NotificationDetail.vue'

const props = defineProps({
  itemsPerPage: {
    type: Number,
    default: 10
  }
})

const emit = defineEmits(['notification-action'])

const notificationStore = useNotificationStore()
const toast = useToast()

const searchInput = ref('')
const selectedFilter = ref('all')
const expandedNotification = ref(null)
let searchTimeout = null

// Computed del store
const notifications = computed(() => notificationStore.notifications)
const filteredNotifications = computed(() => notificationStore.filteredNotifications)
const unreadCount = computed(() => notificationStore.unreadCount)
const hasUnread = computed(() => notificationStore.hasUnreadNotifications)
const loading = computed(() => notificationStore.loading)
const currentPage = computed(() => notificationStore.currentPage)
const totalPages = computed(() => notificationStore.totalPages)
const searchQuery = computed(() => notificationStore.searchQuery)
const filterType = computed(() => notificationStore.filterType)

// Opciones de filtro
const filterOptions = [
  { label: 'Todas', value: 'all' },
  { label: 'No leídas', value: 'unread' },
  { label: 'Leídas', value: 'read' },
  { label: 'Cambios', value: 'swap_request' },
  { label: 'Aceptadas', value: 'swap_accepted' }
]

// Búsqueda con debounce
const debouncedSearch = () => {
  if (searchTimeout) clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    loadNotifications(1)
  }, 300)
}

// Cambio de filtro
const onFilterChange = () => {
  loadNotifications(1)
}

// Cargar notificaciones
const loadNotifications = async (page = 1) => {
  await notificationStore.fetchNotifications({
    page,
    limit: props.itemsPerPage,
    search: searchInput.value,
    filterType: selectedFilter.value
  })
}

// Ir a página
const goToPage = (page) => {
  if (page >= 1 && page <= totalPages.value) {
    loadNotifications(page)
  }
}

// Páginas visibles para paginación
const visiblePages = computed(() => {
  const pages = []
  const total = totalPages.value
  const current = currentPage.value
  
  if (total <= 7) {
    for (let i = 1; i <= total; i++) pages.push(i)
  } else {
    if (current <= 4) {
      for (let i = 1; i <= 5; i++) pages.push(i)
      pages.push('...')
      pages.push(total)
    } else if (current >= total - 3) {
      pages.push(1)
      pages.push('...')
      for (let i = total - 4; i <= total; i++) pages.push(i)
    } else {
      pages.push(1)
      pages.push('...')
      for (let i = current - 1; i <= current + 1; i++) pages.push(i)
      pages.push('...')
      pages.push(total)
    }
  }
  
  return pages.filter(p => p !== '...')
})

// Marcar todas como leídas
const handleMarkAllAsRead = async () => {
  await notificationStore.markAllAsRead()
  toast.add({
    severity: 'success',
    summary: 'Éxito',
    detail: 'Todas las notificaciones marcadas como leídas',
    life: 3000
  })
  loadNotifications(currentPage.value)
}

// Actualizar después de acción
const handleNotificationAction = () => {
  loadNotifications(currentPage.value)
  emit('notification-action')
}

// Utilidades
const getNotificationIcon = (type) => {
  const icons = {
    'swap_request': 'pi pi-exchange',
    'swap_accepted': 'pi pi-check-circle',
    'swap_rejected': 'pi pi-times-circle',
    'assignment_created': 'pi pi-calendar-plus',
    'assignment_updated': 'pi pi-calendar-edit'
  }
  return icons[type] || 'pi pi-bell'
}

const getNotificationColor = (type) => {
  const colors = {
    'swap_request': '#f59e0b',
    'swap_accepted': '#10b981',
    'swap_rejected': '#ef4444',
    'assignment_created': '#3b82f6',
    'assignment_updated': '#8b5cf6'
  }
  return colors[type] || '#6b7280'
}

const getNotificationBg = (type) => {
  const colors = {
    'swap_request': '#fef3c7',
    'swap_accepted': '#d1fae5',
    'swap_rejected': '#fee2e2',
    'assignment_created': '#dbeafe',
    'assignment_updated': '#ede9fe'
  }
  return colors[type] || '#f3f4f6'
}

const getNotificationTypeLabel = (type) => {
  const labels = {
    'swap_request': 'Solicitud de Cambio',
    'swap_accepted': 'Cambio Aceptado',
    'swap_rejected': 'Cambio Rechazado',
    'assignment_created': 'Nueva Asignación',
    'assignment_updated': 'Asignación Actualizada'
  }
  return labels[type] || 'Notificación'
}

const getNotificationSeverity = (type) => {
  const severities = {
    'swap_request': 'warning',
    'swap_accepted': 'success',
    'swap_rejected': 'danger',
    'assignment_created': 'info',
    'assignment_updated': 'info'
  }
  return severities[type] || 'info'
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

// Watch para cambios en filtro y búsqueda
watch(() => selectedFilter.value, () => {
  loadNotifications(1)
})

// Escuchar eventos personalizados para actualizaciones inmediatas
const handleGlobalNotificationUpdate = (event) => {
  console.log('🔔 Event received:', event.type, event.detail)
  notificationStore.reloadNotifications()
}

onMounted(() => {
  loadNotifications(1)
  
  // Escuchar eventos de actualización
  window.addEventListener('notifications-updated', handleGlobalNotificationUpdate)
  window.addEventListener('guard-deleted', handleGlobalNotificationUpdate)
  window.addEventListener('assignment-deleted', handleGlobalNotificationUpdate)
})

onUnmounted(() => {
  // Limpiar listeners
  window.removeEventListener('notifications-updated', handleGlobalNotificationUpdate)
  window.removeEventListener('guard-deleted', handleGlobalNotificationUpdate)
  window.removeEventListener('assignment-deleted', handleGlobalNotificationUpdate)
})
</script>

<style scoped>
.notification-list {
  width: 100%;
}

.filters-section {
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.notifications-accordion {
  background: transparent;
}

.notification-summary {
  cursor: pointer;
  padding: 0.5rem 0;
}

.notification-icon {
  flex-shrink: 0;
}

.notification-info {
  min-width: 0;
}

.line-clamp-1 {
  display: -webkit-box;
  -webkit-line-clamp: 1;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.unread-indicator {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 20px;
}

.unread-indicator .dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  animation: pulse 2s infinite;
}

@keyframes pulse {
  0%, 100% {
    opacity: 1;
    transform: scale(1);
  }
  50% {
    opacity: 0.7;
    transform: scale(1.1);
  }
}

.pagination {
  padding: 1rem;
}

:deep(.p-accordion-header-link) {
  padding: 1rem;
}

:deep(.p-accordion-content) {
  padding: 0;
}
</style>
