<template>
  <div class="public-container p-4">
    <div class="header mb-6 text-center">
      <h1 class="text-4xl font-bold text-primary mb-2">🛡️ Guardias de Hoy</h1>
      <p class="text-xl text-500">{{ formattedDate }}</p>
    </div>

    <div v-if="loading" class="flex justify-content-center p-8">
      <i class="pi pi-spin pi-spinner text-4xl text-primary"></i>
    </div>

    <div v-else-if="groupedGuards.length === 0" class="surface-card p-8 border-round-xl shadow-2 text-center">
      <i class="pi pi-calendar-times text-6xl text-300 mb-4"></i>
      <h2 class="text-2xl font-bold text-700">No hay guardias programadas para hoy</h2>
      <p class="text-500">Vuelve a consultar más tarde.</p>
    </div>

    <div v-else class="grid justify-content-center">
      <div v-for="group in groupedGuards" :key="group.departmentId" class="col-12 md:col-8 lg:col-6 mb-4">
        <div class="surface-card border-round-xl shadow-2 overflow-hidden border-left-3 border-primary">
          <div class="p-4 bg-primary-reverse flex align-items-center justify-content-between">
            <h3 class="text-xl font-bold m-0 flex align-items-center gap-2">
              <i class="pi pi-building"></i>
              {{ group.departmentName }}
            </h3>
            <Tag :value="group.guards.length + ' guardia(s)'" severity="info" rounded />
          </div>
          
          <div class="p-0">
            <div v-for="guard in group.guards" :key="guard.id" 
                 class="p-4 border-bottom-1 border-100 hover:bg-primary-50 transition-colors cursor-pointer"
                 @click="showGuardDetail(guard)">
              <div class="flex justify-content-between align-items-start mb-3">
                <div>
                  <h4 class="text-lg font-bold m-0 text-900">{{ guard.guardName }}</h4>
                  <div class="flex align-items-center gap-2 mt-1 text-600">
                    <i class="pi pi-clock text-sm"></i>
                    <span>{{ guard.startTime }} — {{ guard.endTime }}</span>
                  </div>
                </div>
                <Tag v-if="guard.status === 'scheduled'" value="Programada" severity="success" outlined />
              </div>
              
              <div class="flex align-items-center gap-3">
                <div class="flex align-items-center justify-content-center border-round-full bg-blue-100" style="width:40px; height:40px;">
                  <i class="pi pi-user text-blue-600"></i>
                </div>
                <div>
                  <p class="m-0 font-bold text-800">{{ guard.userName }}</p>
                  <Tag v-if="guard.userLevel" :value="guard.userLevel" severity="secondary" class="mt-1" style="font-size: 0.7rem" />
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Dialogo de Detalle -->
    <Dialog v-model:visible="showDetail" modal header="Detalle de la Guardia" :style="{ width: '90vw', maxWidth: '450px' }">
      <div v-if="selectedGuard" class="p-2">
        <div class="text-center mb-4">
          <div class="inline-flex align-items-center justify-content-center border-round-circle bg-primary-100 mb-3" style="width:80px; height:80px;">
            <i class="pi pi-shield text-4xl text-primary"></i>
          </div>
          <h2 class="text-2xl font-bold m-0 text-900">{{ selectedGuard.guardName }}</h2>
        </div>

        <Divider />

        <div class="grid">
          <div class="col-6 mb-3">
            <span class="block text-500 font-bold mb-1 text-xs uppercase">HORARIO</span>
            <span class="text-900 font-medium">{{ selectedGuard.startTime }} — {{ selectedGuard.endTime }}</span>
          </div>
          <div class="col-6 mb-3">
            <span class="block text-500 font-bold mb-1 text-xs uppercase">ESTADO</span>
            <Tag value="ACTIVA" severity="success" />
          </div>
          <div class="col-12">
            <span class="block text-500 font-bold mb-2 text-xs uppercase text-center">PERSONAL ASIGNADO</span>
            <div class="surface-100 p-3 border-round-lg text-center">
              <div class="text-xl font-bold text-900">{{ selectedGuard.userName }}</div>
              <div class="text-600 mt-1">{{ selectedGuard.userLevel || 'Personal' }}</div>
            </div>
          </div>
        </div>
      </div>
    </Dialog>

    <div class="fixed bottom-0 left-0 w-full p-4 flex justify-content-center bg-white-alpha-90" style="backdrop-filter: blur(8px)">
      <RouterLink to="/login">
        <Button label="Acceso Administrador" icon="pi pi-lock" text severity="secondary" />
      </RouterLink>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import axios from 'axios'
import Tag from 'primevue/tag'
import Dialog from 'primevue/dialog'
import Divider from 'primevue/divider'
import Button from 'primevue/button'

const API_URL = 'http://localhost:8000'
const groupedGuards = ref([])
const loading = ref(true)
const showDetail = ref(false)
const selectedGuard = ref(null)

const formattedDate = computed(() => {
  return new Intl.DateTimeFormat('es-ES', { 
    weekday: 'long', 
    year: 'numeric', 
    month: 'long', 
    day: 'numeric' 
  }).format(new Date())
})

const fetchPublicGuards = async () => {
  loading.value = true
  try {
    const response = await axios.get(`${API_URL}/api/public/today-guards`)
    groupedGuards.value = response.data.data || []
  } catch (error) {
    console.error('Error fetching public guards:', error)
  } finally {
    loading.value = false
  }
}

const showGuardDetail = (guard) => {
  selectedGuard.value = guard
  showDetail.value = true
}

onMounted(() => {
  fetchPublicGuards()
})
</script>

<style scoped>
.public-container {
  min-height: 100vh;
  background-color: var(--surface-ground);
  padding-bottom: 80px !important;
}

.bg-primary-reverse {
  background-color: var(--primary-50);
  color: var(--primary-700);
}

.hover\:bg-primary-50:hover {
  background-color: var(--primary-50);
}
</style>
