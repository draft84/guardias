<template>
  <Card class="notification-detail">
    <template #header>
      <div class="notification-header" :class="headerClass">
        <i :class="iconClass" class="text-4xl"></i>
      </div>
    </template>
    <template #title>
      <div class="flex justify-content-between align-items-start gap-3">
        <h3 class="text-xl font-semibold m-0">{{ notification.title }}</h3>
        <Tag :value="notificationTypeLabel" :severity="typeSeverity" />
      </div>
    </template>
    <template #subtitle>
      <div class="flex flex-wrap gap-3 text-sm text-color-secondary">
        <span>
          <i class="pi pi-calendar mr-1"></i>
          {{ formatDate(notification.createdAt) }}
        </span>
        <span v-if="notification.read">
          <i class="pi pi-check mr-1 text-green-600"></i>
          Leída
        </span>
        <span v-else class="text-primary font-semibold">
          <i class="pi pi-circle-fill mr-1"></i>
          No leída
        </span>
      </div>
    </template>
    <template #content>
      <div class="notification-content">
        <p class="text-color mb-4">{{ notification.message }}</p>

        <!-- Detalles adicionales para swap_request -->
        <div v-if="notification.type === 'swap_request'" class="surface-100 border-round p-3 mb-3">
          <h4 class="text-sm font-semibold mb-2">Detalles del cambio:</h4>
          <div class="grid">
            <div class="col-12 md:col-6">
              <p class="text-sm m-0">
                <strong>Solicitante:</strong> {{ notification.data?.requestedBy?.fullName || 'N/A' }}
              </p>
            </div>
            <div class="col-12 md:col-6">
              <p class="text-sm m-0">
                <strong>Total de días:</strong> {{ notification.data?.totalAssignments || notification.data?.guard?.count || 1 }}
              </p>
            </div>
            <div class="col-12 md:col-12 mt-2">
              <p class="text-sm m-0 mb-2">
                <strong>Días a cambiar:</strong>
              </p>
              <div class="flex flex-wrap gap-2">
                <Tag 
                  v-for="(date, index) in (notification.data?.guard?.dates || [notification.data?.guard?.date])" 
                  :key="index"
                  :value="formatDateForDisplay(date)"
                  severity="info" 
                />
              </div>
            </div>
            <div class="col-12 md:col-12 mt-2">
              <p class="text-sm m-0">
                <strong>Estado:</strong> 
                <Tag :value="notification.read ? 'Leída' : 'Pendiente'" :severity="notification.read ? 'info' : 'warning'" size="small" />
              </p>
            </div>
          </div>
        </div>

        <!-- Detalles para swap_accepted -->
        <div v-if="notification.type === 'swap_accepted'" class="surface-100 border-round p-3 mb-3">
          <p class="text-sm m-0 text-green-700">
            <i class="pi pi-check-circle mr-1"></i>
            El cambio ha sido aceptado por 
            <strong>{{ notification.data?.acceptedBy?.fullName || 'el usuario' }}</strong>
          </p>
        </div>

        <!-- Acciones para swap_request (solo si no es informativa para managers) -->
        <div v-if="notification.type === 'swap_request' && !notification.data?.type?.includes('info')" class="flex gap-2 mt-4">
          <Button
            label="Aceptar"
            icon="pi pi-check"
            severity="success"
            :loading="acceptingSwap"
            @click="handleAcceptSwap"
            size="small"
            class="flex-1"
          />
          <Button
            label="Rechazar"
            icon="pi pi-times"
            severity="danger"
            :loading="rejectingSwap"
            @click="showRejectDialog"
            size="small"
            class="flex-1"
          />
        </div>

        <!-- Mensaje para notificaciones informativas de managers -->
        <Message v-if="notification.data?.type?.includes('info')" severity="info" class="mt-3">
          <p class="m-0">
            <i class="pi pi-info-circle mr-1"></i>
            Notificación informativa - Usted no puede aceptar o rechazar este cambio
          </p>
        </Message>

        <!-- Mensaje de confirmación -->
        <Message v-if="swapMessage" :severity="swapMessageType" :text="swapMessage" class="mt-3" />
      </div>
    </template>
  </Card>

  <!-- Diálogo de rechazo -->
  <Dialog v-model:visible="rejectDialogVisible" modal header="Rechazar Cambio" :style="{ width: '450px' }">
    <div class="flex flex-col gap-3">
      <p>¿Estás seguro de que deseas rechazar este cambio?</p>
      <div class="field">
        <label for="reason" class="block mb-2">Motivo del rechazo (opcional):</label>
        <Textarea
          id="reason"
          v-model="rejectReason"
          rows="3"
          placeholder="Ej: Tengo un compromiso personal..."
          class="w-full"
        />
      </div>
    </div>
    <template #footer>
      <Button label="Cancelar" text @click="rejectDialogVisible = false" />
      <Button 
        label="Rechazar" 
        severity="danger" 
        :loading="rejectingSwap" 
        @click="handleRejectSwap" 
      />
    </template>
  </Dialog>
</template>

<script setup>
import { ref, computed, defineEmits } from 'vue'
import { useToast } from 'primevue/usetoast'
import Card from 'primevue/card'
import Tag from 'primevue/tag'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import Textarea from 'primevue/textarea'
import Message from 'primevue/message'

const props = defineProps({
  notification: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['accepted', 'rejected', 'updated'])

const toast = useToast()
const acceptingSwap = ref(false)
const rejectingSwap = ref(false)
const rejectDialogVisible = ref(false)
const rejectReason = ref('')
const swapMessage = ref('')
const swapMessageType = ref('success')

const headerClass = computed(() => {
  const classes = {
    'swap_request': 'bg-orange-100',
    'swap_accepted': 'bg-green-100',
    'swap_rejected': 'bg-red-100',
    'assignment_created': 'bg-blue-100',
    'assignment_updated': 'bg-purple-100'
  }
  return classes[props.notification.type] || 'bg-gray-100'
})

const iconClass = computed(() => {
  const icons = {
    'swap_request': 'pi pi-exchange text-orange-500',
    'swap_accepted': 'pi pi-check-circle text-green-500',
    'swap_rejected': 'pi pi-times-circle text-red-500',
    'assignment_created': 'pi pi-calendar-plus text-blue-500',
    'assignment_updated': 'pi pi-calendar-edit text-purple-500'
  }
  return icons[props.notification.type] || 'pi pi-bell text-gray-500'
})

const notificationTypeLabel = computed(() => {
  const labels = {
    'swap_request': 'Solicitud de Cambio',
    'swap_accepted': 'Cambio Aceptado',
    'swap_rejected': 'Cambio Rechazado',
    'assignment_created': 'Nueva Asignación',
    'assignment_updated': 'Asignación Actualizada'
  }
  return labels[props.notification.type] || 'Notificación'
})

const typeSeverity = computed(() => {
  const severities = {
    'swap_request': 'warning',
    'swap_accepted': 'success',
    'swap_rejected': 'danger',
    'assignment_created': 'info',
    'assignment_updated': 'info'
  }
  return severities[props.notification.type] || 'info'
})

const formatDate = (dateString) => {
  if (!dateString) return 'N/A'
  const date = new Date(dateString)
  return date.toLocaleDateString('es-ES', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const formatDateForDisplay = (dateString) => {
  if (!dateString) return 'N/A'
  // Asegurarse de que es un string
  const dateStr = String(dateString)
  // Usar la fecha directamente sin conversión de zona horaria
  const parts = dateStr.split(' ')[0].split('-')
  const date = new Date(parts[0], parts[1] - 1, parts[2])
  return date.toLocaleDateString('es-ES', {
    day: 'numeric',
    month: 'numeric',
    year: 'numeric'
  })
}

const handleAcceptSwap = async () => {
  acceptingSwap.value = true
  swapMessage.value = ''
  
  try {
    const token = localStorage.getItem('token')
    const response = await fetch(
      `http://localhost:8000/api/notifications/${props.notification.id}/accept-swap`,
      {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        }
      }
    )

    const data = await response.json()

    if (!response.ok) {
      throw new Error(data.error || 'Error al aceptar el cambio')
    }

    swapMessage.value = data.message || 'Cambio aceptado exitosamente'
    swapMessageType.value = 'success'
    
    toast.add({
      severity: 'success',
      summary: 'Éxito',
      detail: data.message || 'Cambio aceptado exitosamente',
      life: 3000
    })

    // Marcar como leída
    await markAsRead()

    emit('accepted', data)
  } catch (error) {
    swapMessage.value = error.message || 'Error al aceptar el cambio'
    swapMessageType.value = 'error'
    
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.message || 'Error al aceptar el cambio',
      life: 3000
    })
  } finally {
    acceptingSwap.value = false
  }
}

const showRejectDialog = () => {
  rejectReason.value = ''
  rejectDialogVisible.value = true
}

const handleRejectSwap = async () => {
  rejectingSwap.value = true
  
  try {
    const token = localStorage.getItem('token')
    const response = await fetch(
      `http://localhost:8000/api/notifications/${props.notification.id}/reject-swap`,
      {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({ reason: rejectReason.value })
      }
    )

    const data = await response.json()

    if (!response.ok) {
      throw new Error(data.error || 'Error al rechazar el cambio')
    }

    toast.add({
      severity: 'success',
      summary: 'Éxito',
      detail: 'Cambio rechazado',
      life: 3000
    })

    // Marcar como leída
    await markAsRead()

    rejectDialogVisible.value = false
    emit('rejected', data)
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.message || 'Error al rechazar el cambio',
      life: 3000
    })
  } finally {
    rejectingSwap.value = false
  }
}

const markAsRead = async () => {
  try {
    const token = localStorage.getItem('token')
    await fetch(
      `http://localhost:8000/api/notifications/${props.notification.id}/read`,
      {
        method: 'PUT',
        headers: {
          'Authorization': `Bearer ${token}`
        }
      }
    )
  } catch (error) {
    console.error('Error marking notification as read:', error)
  }
}
</script>

<style scoped>
.notification-detail {
  width: 100%;
}

.notification-header {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem;
}

.notification-header i {
  animation: pulse 2s infinite;
}

@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.7;
  }
}
</style>
