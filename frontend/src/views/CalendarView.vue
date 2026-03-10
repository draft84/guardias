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
                <Column header="Acciones" style="min-width: 8rem">
                  <template #body="{ data }">
                    <Button 
                      v-if="canEditAssignment(data)" 
                      icon="pi pi-pencil" 
                      outlined 
                      rounded 
                      severity="info"
                      @click="openSubstituteDialog(data)" 
                      v-tooltip.top="'Solicitar sustitución'" 
                    />
                    <span v-else class="text-color-secondary text-sm">-</span>
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

    <!-- Dialog de Sustitución -->
    <Dialog 
      v-model:visible="substituteDialog" 
      :style="{width: '600px'}" 
      header="Solicitar Sustitución de Guardia" 
      :modal="true"
    >
      <div class="flex flex-column gap-4 py-4">
        <Message severity="info" :closable="false">
          <div class="flex flex-column gap-2">
            <p class="m-0 font-bold">Información de la Guardia</p>
            <p class="m-0 text-sm">Está solicitando un sustituto para la siguiente guardia:</p>
          </div>
        </Message>

        <div class="surface-50 border-round p-3 border-1 surface-border">
          <div class="flex flex-column gap-2">
            <div class="flex justify-content-between">
              <span class="text-color-secondary">Guardia:</span>
              <span class="font-semibold">{{ selectedAssignment?.guard?.name }}</span>
            </div>
            <div class="flex justify-content-between">
              <span class="text-color-secondary">Fecha:</span>
              <span class="font-semibold">{{ selectedAssignment?.date }}</span>
            </div>
            <div class="flex justify-content-between">
              <span class="text-color-secondary">Horario:</span>
              <span class="font-semibold">{{ selectedAssignment?.startTime }} - {{ selectedAssignment?.endTime }}</span>
            </div>
          </div>
        </div>

        <div class="field" v-if="authStore.isAdmin">
          <label for="substituteDepartment" class="font-bold block mb-2">Departamento del Sustituto</label>
          <Dropdown 
            id="substituteDepartment" 
            v-model="substituteDepartmentId" 
            :options="allDepartments" 
            optionLabel="name" 
            optionValue="id" 
            placeholder="Seleccione un departamento" 
            class="w-full"
            @change="onDepartmentChange"
          >
            <template #option="slotProps">
              <div class="flex align-items-center gap-2">
                <i class="pi pi-building" />
                <span>{{ slotProps.option.name }}</span>
              </div>
            </template>
            <template #value="slotProps">
              <div v-if="slotProps.value" class="flex align-items-center gap-2">
                <i class="pi pi-building" />
                <span>{{ allDepartments.find(d => d.id === slotProps.value)?.name }}</span>
              </div>
              <span v-else>Todos los departamentos</span>
            </template>
          </Dropdown>
        </div>

        <div class="field">
          <label for="substituteUser" class="font-bold block mb-2">Usuario Sustituto *</label>
          <Dropdown
            id="substituteUser"
            v-model="substituteUserId"
            :options="usersByDepartment"
            optionLabel="fullName"
            optionValue="id"
            placeholder="Seleccione un sustituto"
            class="w-full"
          >
            <template #option="slotProps">
              <div class="flex flex-column gap-1">
                <span class="font-semibold">{{ slotProps.option.fullName }}</span>
                <span class="text-sm text-color-secondary">{{ slotProps.option.email }}</span>
              </div>
            </template>
            <template #value="slotProps">
              <div v-if="slotProps.value" class="flex flex-column gap-1">
                <span class="font-semibold">{{ usersByDepartment.find(u => u.id === slotProps.value)?.fullName }}</span>
                <span class="text-sm text-color-secondary">{{ usersByDepartment.find(u => u.id === slotProps.value)?.email }}</span>
              </div>
              <span v-else>Seleccione un sustituto</span>
            </template>
          </Dropdown>
          <small class="p-error block mt-1" v-if="submitted && !substituteUserId">Debe seleccionar un usuario sustituto.</small>
        </div>

        <div class="field mt-3">
          <label class="font-bold block mb-2 text-color-secondary" style="font-size: 0.85rem;">Usuario Activo en Sesión</label>
          <div class="flex align-items-center gap-2 p-2 border-round surface-100">
            <i class="pi pi-user text-color-secondary" />
            <div class="flex flex-column">
              <span class="text-sm font-semibold">{{ authStore.userName }}</span>
              <span class="text-xs text-color-secondary">{{ authStore.user?.email }}</span>
            </div>
          </div>
        </div>

        <div class="field">
          <label for="substituteNotes" class="font-bold block mb-2">Motivo de la Sustitución</label>
          <Textarea 
            id="substituteNotes" 
            v-model="substituteNotes" 
            rows="3" 
            placeholder="Describa el motivo de la solicitud (opcional)" 
            class="w-full" 
          />
        </div>

        <div v-if="substituteResult" class="mt-2">
          <Message 
            :severity="substituteResult.success ? 'success' : 'error'" 
            :closable="false"
          >
            {{ substituteResult.message }}
          </Message>
        </div>
      </div>

      <template #footer>
        <Button label="Cancelar" icon="pi pi-times" text @click="hideSubstituteDialog"/>
        <Button 
          label="Solicitar Sustitución" 
          icon="pi pi-send" 
          :loading="requestingSubstitute" 
          @click="requestSubstitute" 
        />
      </template>
    </Dialog>

  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import api from '@/services/api'
import { useShiftStore } from '@/stores/shift.store'
import { useGuardStore } from '@/stores/guard.store'
import { useDepartmentStore } from '@/stores/department.store'
import { useUserStore } from '@/stores/user.store'
import { useAuthStore } from '@/stores/auth.store'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Accordion from 'primevue/accordion'
import AccordionPanel from 'primevue/accordionpanel'
import AccordionHeader from 'primevue/accordionheader'
import AccordionContent from 'primevue/accordioncontent'
import Message from 'primevue/message'
import Dropdown from 'primevue/dropdown'
import Textarea from 'primevue/textarea'

const shiftStore = useShiftStore()
const guardStore = useGuardStore()
const departmentStore = useDepartmentStore()
const userStore = useUserStore()
const authStore = useAuthStore()

const showModal = ref(false)
const selectedDayGuards = ref([])
const selectedDate = ref('')
const currentDate = ref(new Date())

// Variables para sustitución
const substituteDialog = ref(false)
const selectedAssignment = ref(null)
const substituteUserId = ref(null)
const substituteDepartmentId = ref(null)
const substituteNotes = ref('')
const submitted = ref(false)
const requestingSubstitute = ref(false)
const substituteResult = ref(null)
const usersByDepartment = ref([])
const allDepartments = ref([])

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
          id: assignment.user?.id || null,
          fullName: assignment.user?.fullName || a.user || '-',
          guardLevel: assignment.user?.guardLevel || null,
          phone: assignment.user?.phone || null
        },
        startTime,
        endTime,
        status: assignment.status || a.status || 'scheduled',
        notes: assignment.notes,
        isRecurrent: false,
        id: assignment.id,
        date: a.start?.split(' ')[0] || day.date
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

// Funciones para sustitución
const canEditAssignment = (assignment) => {
  // Solo puede editar si el usuario asignado es el mismo que está en sesión
  const currentUserId = authStore.user?.id
  const assignedUserId = assignment.user?.id || assignment.userId
  
  console.log('canEditAssignment:', {
    currentUserId,
    assignedUserId,
    assignmentUser: assignment.user,
    result: currentUserId && currentUserId === assignedUserId
  })
  
  return currentUserId && currentUserId === assignedUserId
}

const openSubstituteDialog = async (assignment) => {
  console.log('=== OPEN SUBSTITUTE DIALOG ===')
  console.log('Assignment:', assignment)
  console.log('Assignment guard:', assignment.guard)
  console.log('Assignment guard departmentId:', assignment.guard?.departmentId)
  console.log('Assignment user:', assignment.user)
  
  selectedAssignment.value = assignment
  substituteUserId.value = null
  substituteNotes.value = ''
  submitted.value = false
  substituteResult.value = null
  
  // Obtener el departmentId de la guardia
  const deptId = assignment.guard?.departmentId
  console.log('Department ID de la guardia:', deptId)
  console.log('Is ADMIN:', authStore.isAdmin)
  console.log('Auth user:', authStore.user)
  
  substituteDepartmentId.value = deptId
  
  // Cargar departamentos si es ADMIN
  if (authStore.isAdmin) {
    await loadAllDepartments()
  }
  
  // Cargar usuarios del departamento - USAR EL DEPARTMENT ID CORRECTO
  if (deptId) {
    console.log('Cargando usuarios del departamento:', deptId)
    await loadUsersByDepartment(deptId)
  } else {
    console.error('NO HAY DEPARTMENT ID en esta asignación!')
    // Intentar obtener del departamento del usuario en sesión
    if (authStore.user?.department) {
      console.log('Usando departamento del usuario en sesión:', authStore.user.department)
      await loadUsersByDepartment(authStore.user.department)
    }
  }
  
  substituteDialog.value = true
}

const onDepartmentChange = () => {
  substituteUserId.value = null
  if (substituteDepartmentId.value) {
    loadUsersByDepartment(substituteDepartmentId.value)
  } else {
    usersByDepartment.value = []
  }
}

const loadAllDepartments = async () => {
  const token = localStorage.getItem('token')
  try {
    const response = await fetch(`${API_URL}/api/departments`, {
      headers: { 'Authorization': `Bearer ${token}` }
    })
    if (response.ok) {
      const data = await response.json()
      allDepartments.value = data.departments || []
      console.log('Departments loaded:', allDepartments.value.length)
    }
  } catch (error) {
    console.error('Error loading departments:', error)
  }
}

const hideSubstituteDialog = () => {
  substituteDialog.value = false
  selectedAssignment.value = null
  substituteUserId.value = null
  substituteNotes.value = ''
  submitted.value = false
  substituteResult.value = null
}

const API_URL = 'http://localhost:8000'

const loadUsersByDepartment = async (departmentId) => {
  console.log('=== LOAD USERS BY DEPARTMENT ===')
  
  if (!departmentId) {
    console.error('❌ NO DEPARTMENT ID PROVIDED!')
    usersByDepartment.value = []
    return
  }
  
  const token = localStorage.getItem('token')
  console.log('🔑 Token:', token ? '✅ PRESENTE (' + token.substring(0, 20) + '...)' : '❌ NO PRESENTE')
  console.log('📂 Department ID:', departmentId)
  
  try {
    const url = `${API_URL}/api/users/department/${departmentId}`
    console.log('🌐 URL:', url)
    
    const response = await fetch(url, {
      headers: { 
        'Authorization': `Bearer ${token}`, 
        'Content-Type': 'application/json' 
      }
    })
    
    console.log('📡 Response status:', response.status)
    
    if (response.status === 401) {
      console.error('❌ ERROR 401: Token inválido o expirado')
      return
    }
    
    if (response.status === 404) {
      console.error('❌ ERROR 404: Departamento no encontrado')
      return
    }
    
    if (response.ok) {
      const data = await response.json()
      console.log('📦 Data received:', data)
      console.log('📦 Data.users:', data.users)
      
      if (!data.users || data.users.length === 0) {
        console.warn('⚠️ NO HAY USUARIOS en este departamento')
        usersByDepartment.value = []
        return
      }
      
      // Filtrar para no mostrar el usuario actual
      const filteredUsers = (data.users || []).filter(
        u => {
          const isCurrentUser = u.id === authStore.user?.id
          if (isCurrentUser) {
            console.log('🚫 Excluyendo usuario actual:', u.fullName)
          }
          return !isCurrentUser
        }
      )
      
      usersByDepartment.value = filteredUsers
      console.log('✅ Usuarios cargados:', usersByDepartment.value.length)
      console.log('👥 Usuarios:', usersByDepartment.value.map(u => u.fullName))
    } else {
      const errorData = await response.json()
      console.error('❌ Error response:', errorData)
    }
  } catch (error) {
    console.error('❌ ERROR loading users:', error.message)
    console.error('Stack:', error.stack)
  }
}

const requestSubstitute = async () => {
  submitted.value = true

  if (!substituteUserId.value) {
    return
  }

  requestingSubstitute.value = true
  substituteResult.value = null
  
  console.log('=== REQUEST SUBSTITUTE ===')
  console.log('Assignment ID:', selectedAssignment.value?.id)
  console.log('Substitute User ID:', substituteUserId.value)
  console.log('Notes:', substituteNotes.value)

  try {
    // Crear solicitud de sustitución
    const token = localStorage.getItem('token')
    const assignmentId = selectedAssignment.value?.id
    
    if (!assignmentId) {
      throw new Error('No assignment ID available')
    }
    
    const url = `${API_URL}/api/assignments/${assignmentId}/swap`
    console.log('URL:', url)
    
    const body = {
      newUserId: substituteUserId.value,
      reason: substituteNotes.value || null
    }
    console.log('Body:', body)
    
    const response = await fetch(url, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(body)
    })
    
    console.log('Response status:', response.status)

    if (response.status === 401) {
      throw new Error('Token inválido o expirado')
    }
    
    if (response.status === 404) {
      throw new Error('Asignación no encontrada')
    }

    const data = await response.json()
    console.log('Response data:', data)

    if (response.ok) {
      substituteResult.value = {
        success: true,
        message: '✅ Solicitud de cambio enviada. El usuario seleccionado recibirá una notificación y deberá aceptar el cambio.'
      }

      // Cerrar dialog después de 3 segundos
      setTimeout(() => {
        hideSubstituteDialog()
      }, 3000)
    } else {
      substituteResult.value = {
        success: false,
        message: data.error || data.message || 'Error al crear la solicitud'
      }
    }
  } catch (error) {
    console.error('❌ ERROR requesting substitute:', error.message)
    console.error('Stack:', error.stack)
    substituteResult.value = {
      success: false,
      message: error.message || 'Error de conexión al procesar la solicitud'
    }
  } finally {
    requestingSubstitute.value = false
  }
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
