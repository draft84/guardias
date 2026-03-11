<template>
  <div class="notifications-view">
    <div class="flex justify-content-between align-items-center mb-4">
      <div>
        <h1 class="text-2xl font-bold m-0">Notificaciones</h1>
        <p class="text-color-secondary m-0 mt-1">
          Gestiona tus notificaciones y solicitudes de cambio
        </p>
      </div>
      <div class="flex align-items-center gap-2">
        <Tag :value="`${unreadCount} no leídas`" severity="danger" />
        <Button
          icon="pi pi-refresh"
          text
          rounded
          @click="refreshNotifications"
          :loading="loading"
          v-tooltip.top="'Actualizar'"
        />
      </div>
    </div>

    <NotificationList
      :items-per-page="10"
      @notification-action="handleNotificationAction"
    />
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue'
import { useNotificationStore } from '@/stores/notification.store'
import { useToast } from 'primevue/usetoast'
import Tag from 'primevue/tag'
import Button from 'primevue/button'
import NotificationList from '@/components/NotificationList.vue'

const notificationStore = useNotificationStore()
const toast = useToast()

const unreadCount = computed(() => notificationStore.unreadCount)
const loading = computed(() => notificationStore.loading)

const refreshNotifications = async () => {
  await notificationStore.fetchNotifications({
    page: 1,
    limit: 10
  })
}

const handleNotificationAction = () => {
  // Actualizar contador después de cualquier acción
  notificationStore.fetchUnreadCount()
}

onMounted(() => {
  refreshNotifications()
})
</script>

<style scoped>
.notifications-view {
  max-width: 1200px;
  margin: 0 auto;
}
</style>
