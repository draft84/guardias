<template>
  <div class="public-container">
    <!-- Header Bar -->
    <div class="header-bar surface-card shadow-2 px-4 py-3 mb-4">
      <div class="container flex justify-content-between align-items-center">
        <div class="flex align-items-center gap-3">
          <i class="pi pi-shield text-2xl text-primary"></i>
          <h1 class="text-xl font-bold text-primary m-0">Guardias de Hoy</h1>
        </div>
        <RouterLink to="/login">
          <Button label="Iniciar Sesión" icon="pi pi-sign-in" />
        </RouterLink>
      </div>
    </div>

    <!-- Contenido Principal -->
    <div class="container py-4">
      <div class="text-center mb-4">
        <p class="text-xl text-500 m-0">{{ formattedDate }}</p>
      </div>

    <div v-if="loading" class="flex justify-content-center p-8">
      <i class="pi pi-spin pi-spinner text-4xl text-primary"></i>
    </div>

    <div v-else-if="guardsByDepartment.length === 0" class="surface-card p-8 border-round-xl shadow-2 text-center">
      <i class="pi pi-calendar-times text-6xl text-300 mb-4"></i>
      <h2 class="text-2xl font-bold text-700">No hay guardias programadas para hoy</h2>
      <p class="text-500">Vuelve a consultar más tarde.</p>
    </div>

    <div v-else class="surface-card border-round-xl shadow-4 overflow-hidden border-1 surface-border mx-auto" style="max-width: 900px;">
      <!-- Accordion por departamento -->
      <Accordion :activeIndexes="[0]" multiple>
        <AccordionPanel v-for="(dept, index) in guardsByDepartment" :key="dept.departmentId" :value="index">
          <AccordionHeader class="flex align-items-center gap-2">
            <i class="pi pi-building text-primary" />
            <span class="font-semibold">{{ dept.departmentName }}</span>
            <Tag :value="dept.guards.length" severity="secondary" class="ml-auto" />
          </AccordionHeader>
          <AccordionContent>
            <DataTable :value="dept.guards" responsiveLayout="scroll" size="small">
              <Column header="Nivel" style="min-width: 5rem;">
                <template #body="{ data }">
                  <Tag v-if="data.userLevel" :value="data.userLevel" severity="info" />
                  <span v-else class="text-color-secondary">-</span>
                </template>
              </Column>
              <Column header="Usuario" style="min-width: 12rem;">
                <template #body="{ data }">
                  <div class="flex align-items-center gap-2">
                    <i class="pi pi-user text-color-secondary" />
                    <span>{{ data.userName }}</span>
                  </div>
                </template>
              </Column>
              <Column header="Teléfono" style="min-width: 9rem;">
                <template #body="{ data }">
                  <span v-if="data.userPhone" class="text-sm">
                    <i class="pi pi-phone mr-1 text-color-secondary" />{{ data.userPhone }}
                  </span>
                  <span v-else class="text-color-secondary">-</span>
                </template>
              </Column>
              <Column header="Horario" style="min-width: 10rem;">
                <template #body="{ data }">
                  <span class="flex align-items-center gap-2">
                    <i class="pi pi-clock text-primary" />
                    {{ data.startTime }} — {{ data.endTime }}
                  </span>
                </template>
              </Column>
            </DataTable>
          </AccordionContent>
        </AccordionPanel>
      </Accordion>
    </div>
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
import Accordion from 'primevue/accordion'
import AccordionPanel from 'primevue/accordionpanel'
import AccordionHeader from 'primevue/accordionheader'
import AccordionContent from 'primevue/accordioncontent'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'

const API_URL = 'http://localhost:10000'
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

// Los datos ya vienen agrupados por departamento desde el backend
const guardsByDepartment = computed(() => {
  return groupedGuards.value || []
})

onMounted(() => {
  fetchPublicGuards()
})
</script>

<style scoped>
.public-container {
  min-height: 100vh;
  background-color: var(--surface-ground);
}

.header-bar {
  background: linear-gradient(135deg, #059669 0%, #10b981 100%);
  color: white;
  height: 64px;
}

.header-bar .container {
  height: 100%;
}

.header-bar .text-primary {
  color: white !important;
}

.header-bar i {
  color: rgba(255, 255, 255, 0.9);
}

.header-bar :deep(.p-button) {
  background-color: #047857;
  color: white;
  border: none;
  font-weight: 600;
  padding: 0.5rem 1rem;
}

.header-bar :deep(.p-button:hover) {
  background-color: #065f46;
}

.header-bar :deep(.p-button .p-button-label) {
  color: white;
}

.header-bar :deep(.p-button .p-button-icon) {
  color: white;
}

.container {
  max-width: 1200px;
  margin: 0 auto;
}
</style>
