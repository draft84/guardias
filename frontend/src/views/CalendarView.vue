<template>
  <div class="p-4">

    <!-- Header -->
    <div class="flex justify-content-between align-items-center mb-4">
      <div class="flex align-items-center gap-2">
        <i class="pi pi-calendar text-3xl text-primary"></i>
        <h2 class="text-2xl font-bold m-0">Calendario de Guardias</h2>
      </div>
      <div class="flex align-items-center gap-3">
        <Button icon="pi pi-chevron-left" outlined rounded @click="changeMonth(-1)" v-tooltip.top="'Mes anterior'" />
        <span class="text-xl font-semibold" style="min-width:220px; text-align:center;">{{ currentMonthName }} {{ currentYear }}</span>
        <Button icon="pi pi-chevron-right" outlined rounded @click="changeMonth(1)" v-tooltip.top="'Mes siguiente'" />
        <Button icon="pi pi-refresh" outlined rounded severity="secondary" @click="refreshAssignments" v-tooltip.top="'Recargar'" />
      </div>
    </div>

    <!-- Calendar Card -->
    <div class="surface-card border-round-xl shadow-4 overflow-hidden border-1 surface-border">
      <div class="calendar-grid">
        <div class="calendar-header">
          <div class="day-name" v-for="d in ['DOM','LUN','MAR','MIÉ','JUE','VIE','SÁB']" :key="d">{{ d }}</div>
        </div>
        <div class="calendar-days">
          <div
            v-for="day in calendarDays"
            :key="day.date"
            :class="['calendar-day', !day.currentMonth ? 'other-month' : '', day.isToday ? 'today' : '', day.hasGuards ? 'has-guards' : '']"
            @click="selectDay(day)"
          >
            <div class="day-header">
              <span class="day-number">{{ day.day }}</span>
              <div v-if="day.isToday" class="today-badge">HOY</div>
            </div>
            
            <div class="day-content">
              <div v-if="day.hasGuards" class="guards-indicator">
                <div class="guard-dot"></div>
                <span class="guards-text">{{ day.guardsCount }} guardia{{ day.guardsCount !== 1 ? 's' : '' }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Dialog detalle del día -->
    <Dialog v-model:visible="showModal" :style="{width: '1100px'}" :header="'Guardias del ' + selectedDate" :modal="true">
      <div v-if="selectedDayGuards.length > 0">
        <!-- Accordion por departamento -->
        <Accordion :activeIndexes="[0]" multiple>
          <AccordionPanel v-for="(dept, index) in departmentList" :key="dept" :value="index">
            <AccordionHeader class="flex align-items-center gap-2">
              <i class="pi pi-building text-primary" />
              <span class="font-semibold">{{ dept }}</span>
              <Tag :value="assignmentsByDepartment[dept].length" severity="secondary" class="ml-auto" />
            </AccordionHeader>
            <AccordionContent>
              <DataTable :value="assignmentsByDepartment[dept]" responsiveLayout="scroll" size="small">
                <Column header="Guardia">
                  <template #body="{ data }">{{ data.guard?.name || '-' }}</template>
                </Column>
                <Column header="Nivel">
                  <template #body="{ data }">
                    <Tag v-if="data.user?.guardLevel" :value="data.user.guardLevel" severity="info" />
                    <span v-else class="text-color-secondary">-</span>
                  </template>
                </Column>
                <Column header="Usuario">
                  <template #body="{ data }">{{ data.user?.fullName || '-' }}</template>
                </Column>
                <Column header="Teléfono">
                  <template #body="{ data }">
                    <span v-if="data.user?.phone" class="text-sm">
                      <i class="pi pi-phone mr-1 text-color-secondary" />{{ data.user.phone }}
                    </span>
                    <span v-else class="text-color-secondary">-</span>
                  </template>
                </Column>
                <Column header="Horario">
                  <template #body="{ data }">{{ data.startTime }} — {{ data.endTime }}</template>
                </Column>
                <Column header="Estado">
                  <template #body="{ data }">
                    <Tag :value="data.status" severity="success" />
                  </template>
                </Column>
              </DataTable>
            </AccordionContent>
          </AccordionPanel>
        </Accordion>
      </div>
      <div v-else class="text-center p-5 text-color-secondary">
        <i class="pi pi-calendar text-5xl mb-3" style="display:block; opacity:0.3"></i>
        <p>No hay guardias programadas para este día.</p>
      </div>
      <template #footer>
        <Button label="Cerrar" icon="pi pi-times" text @click="showModal = false" />
      </template>
    </Dialog>

  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import api from '@/services/api'
import { useShiftStore } from '@/stores/shift.store'
import { useGuardStore } from '@/stores/guard.store'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Accordion from 'primevue/accordion'
import AccordionPanel from 'primevue/accordionpanel'
import AccordionHeader from 'primevue/accordionheader'
import AccordionContent from 'primevue/accordioncontent'

const shiftStore = useShiftStore()
const guardStore = useGuardStore()
const showModal = ref(false)
const selectedDayGuards = ref([])
const selectedDate = ref('')
const currentDate = ref(new Date())

const currentMonthName = computed(() => {
  const monthName = currentDate.value.toLocaleString('es-ES', { month: 'long' })
  const capitalized = monthName.charAt(0).toUpperCase() + monthName.slice(1)
  console.log('Mes actual:', currentDate.value, '->', capitalized)
  return capitalized
})

const currentYear = computed(() => currentDate.value.getFullYear())

const calendarDays = computed(() => {
  const year = currentDate.value.getFullYear()
  const month = currentDate.value.getMonth()
  const firstDay = new Date(year, month, 1)
  const lastDay = new Date(year, month + 1, 0)
  const startDate = new Date(firstDay)
  startDate.setDate(startDate.getDate() - firstDay.getDay())

  const days = []
  const today = new Date()
  today.setHours(0, 0, 0, 0)

  for (let i = 0; i < 42; i++) {
    const date = new Date(startDate)
    date.setDate(date.getDate() + i)
    const dayOfWeek = date.getDay()
    const dayDate = date.toISOString().split('T')[0]

    // Buscar asignaciones específicas para este día
    // El endpoint calendar devuelve 'start' en formato 'YYYY-MM-DD HH:MM:SS'
    const assignmentsForDay = shiftStore.assignments.filter(a => {
      if (!a.start) return false
      const assignDate = a.start.split(' ')[0] // Extraer solo la fecha
      return assignDate === dayDate
    })

    // NO mostramos guardias recurrentes, solo asignaciones específicas
    const totalCount = assignmentsForDay.length

    days.push({
      date: dayDate,
      day: date.getDate(),
      currentMonth: date.getMonth() === month,
      isToday: date.getTime() === today.getTime(),
      hasGuards: totalCount > 0,
      guardsCount: totalCount,
      assignments: assignmentsForDay,
      guards: []
    })
  }

  return days
})

const changeMonth = (delta) => {
  const newDate = new Date(currentDate.value)
  newDate.setMonth(newDate.getMonth() + delta)
  currentDate.value = newDate
  loadAssignments()
}

const refreshAssignments = async () => {
  await loadAssignments()
  await guardStore.fetchGuards()
  alert(`Asignaciones cargadas: ${shiftStore.assignments.length}, Guardias: ${guardStore.guards.length}`)
}

const formatDate = (dateString) => {
  if (!dateString) return ''
  const [year, month, day] = dateString.split('-')
  return `${day}/${month}/${year}`
}

const selectDay = async (day) => {
  selectedDate.value = day.date.split('-').reverse().join('/')

  console.log('Asignaciones del día:', day.assignments)

  // Solo mostramos asignaciones específicas, NO guardias recurrentes
  const specificAssignments = await Promise.all(day.assignments.map(async (a) => {
    try {
      console.log('Obteniendo detalles de asignación:', a.id)
      // Obtener detalles completos de la asignación
      const response = await api.get(`/api/assignments/${a.id}`)
      const assignment = response.data.assignment

      console.log('Respuesta del backend:', assignment)

      // Extraer hora del campo start (formato: 'YYYY-MM-DD HH:MM:SS')
      const startTime = a.start ? a.start.split(' ')[1].substring(0, 5) : '--:--'
      const endTime = a.end ? a.end.split(' ')[1].substring(0, 5) : '--:--'

      return {
        guard: {
          name: assignment.guard?.name || a.guard || '-',
          id: assignment.guard?.id,
          validFrom: null,
          validUntil: null,
          departmentName: assignment.guard?.departmentName || 'Sin Departamento',
          departmentId: assignment.guard?.departmentId || null
        },
        user: {
          fullName: assignment.user?.fullName || a.user || '-',
          guardLevel: assignment.user?.guardLevel || null,
          phone: assignment.user?.phone || null
        },
        startTime,
        endTime,
        status: assignment.status || a.status || 'scheduled',
        notes: assignment.notes,
        isRecurrent: false
      }
    } catch (error) {
      console.error('Error loading assignment details:', error)
      // Fallback a los datos limitados
      const startTime = a.start ? a.start.split(' ')[1].substring(0, 5) : '--:--'
      const endTime = a.end ? a.end.split(' ')[1].substring(0, 5) : '--:--'
      return {
        guard: { name: a.guard || '-', validFrom: null, validUntil: null, departmentName: 'Sin Departamento', departmentId: null },
        user: { fullName: a.user || '-', guardLevel: null, phone: null },
        startTime,
        endTime,
        status: a.status || 'scheduled',
        isRecurrent: false
      }
    }
  }))

  console.log('Asignaciones procesadas:', specificAssignments)
  selectedDayGuards.value = [...specificAssignments]
  showModal.value = true
}

// Agrupar asignaciones por departamento
const assignmentsByDepartment = computed(() => {
  const grouped = {}
  selectedDayGuards.value.forEach(assignment => {
    const deptName = assignment.guard.departmentName || 'Sin Departamento'
    if (!grouped[deptName]) {
      grouped[deptName] = []
    }
    grouped[deptName].push(assignment)
  })
  return grouped
})

// Obtener lista de departamentos ordenados
const departmentList = computed(() => {
  return Object.keys(assignmentsByDepartment.value).sort()
})

const loadAssignments = async () => {
  try {
    const month = currentDate.value.getMonth() + 1
    const year = currentDate.value.getFullYear()
    const response = await api.get('/api/assignments/calendar', {
      params: { month, year }
    })
    if (response.data) {
      console.log('Eventos del calendario cargados:', response.data.events)
      shiftStore.assignments = response.data.events || []
    }
  } catch (error) {
    console.error('Error loading calendar events:', error)
  }
}

const handleAssignmentsUpdate = (event) => {
  shiftStore.assignments = event.detail
}

onMounted(async () => {
  await loadAssignments()
  await guardStore.fetchGuards()
  window.addEventListener('assignments-updated', handleAssignmentsUpdate)
})

onUnmounted(() => {
  window.removeEventListener('assignments-updated', handleAssignmentsUpdate)
})
</script>

<style scoped>
.calendar-grid {
  background: var(--surface-border);
}

.calendar-header {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  background: var(--surface-50);
  border-bottom: 1px solid var(--surface-border);
}

.day-name {
  padding: 1rem 0;
  text-align: center;
  font-size: 0.75rem;
  font-weight: 700;
  color: var(--text-color-secondary);
  letter-spacing: 0.1em;
}

.calendar-days {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 1px;
}

.calendar-day {
  min-height: 120px;
  background: var(--surface-card);
  padding: 0.75rem;
  cursor: pointer;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  display: flex;
  flex-direction: column;
  position: relative;
}

.calendar-day:hover {
  background: var(--surface-hover);
  z-index: 1;
  box-shadow: inset 0 0 0 1px var(--primary-color);
}

.calendar-day.other-month {
  background: var(--surface-50);
  color: var(--text-color-secondary);
  opacity: 0.5;
}

.calendar-day.today {
  background: var(--primary-50);
}

.calendar-day.today .day-number {
  color: var(--primary-color);
}

.day-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.5rem;
}

.day-number {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--text-color);
}

.today-badge {
  font-size: 0.65rem;
  background: var(--primary-color);
  color: white;
  padding: 0.15rem 0.4rem;
  border-radius: 4px;
  font-weight: 700;
}

.day-content {
  flex-grow: 1;
  display: flex;
  flex-direction: column;
  justify-content: flex-end;
}

.guards-indicator {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  background: var(--blue-100);
  padding: 0.35rem 0.6rem;
  border-radius: 6px;
  border-left: 3px solid var(--blue-500);
}

.guard-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: var(--blue-500);
}

.guards-text {
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--blue-500);
}

.calendar-day.other-month .guards-indicator {
  opacity: 0.4;
  background: var(--surface-200);
  border-left-color: var(--surface-300);
}

.calendar-day.other-month .guard-dot,
.calendar-day.other-month .guards-text {
  background: var(--surface-300);
  color: var(--text-color-secondary);
}
</style>
