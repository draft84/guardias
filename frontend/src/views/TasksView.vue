<template>
  <div class="tasks-view">
    <!-- Header -->
    <div class="flex justify-content-between align-items-center mb-4">
      <div>
        <h1 class="text-2xl font-bold m-0">Tareas</h1>
        <p class="text-color-secondary m-0 mt-1">
          Gestiona las tareas de los departamentos
        </p>
      </div>
      <div class="flex align-items-center gap-2">
        <Button
          label="Nueva Tarea"
          icon="pi pi-plus"
          severity="primary"
          @click="showNewTaskDialog"
          v-if="canCreateTasks"
        />
      </div>
    </div>

    <!-- Filtros y búsqueda -->
    <div class="surface-card border-round p-3 mb-4">
      <div class="flex flex-wrap gap-3 align-items-center">
        <!-- Buscador -->
        <div class="flex-1 min-w-250">
          <IconField>
            <InputIcon>
              <i class="pi pi-search" />
            </InputIcon>
            <InputText
              v-model="searchQuery"
              placeholder="Buscar por título o descripción..."
              @input="debouncedSearch"
              class="w-full"
            />
          </IconField>
        </div>

        <!-- Filtro por estado -->
        <div class="min-w-150">
          <Select
            v-model="selectedStatus"
            :options="statusOptions"
            option-label="label"
            option-value="value"
            placeholder="Estado"
            class="w-full"
            @change="loadTasks"
          />
        </div>

        <!-- Filtro por departamento (solo ADMIN) -->
        <div class="min-w-150" v-if="authStore.isAdmin">
          <Select
            v-model="selectedDepartment"
            :options="allDepartments"
            option-label="name"
            option-value="id"
            placeholder="Departamento"
            class="w-full"
            @change="loadTasks"
          />
        </div>

        <!-- Botón recargar -->
        <Button
          icon="pi pi-refresh"
          text
          rounded
          @click="loadTasks"
          :loading="loading"
          v-tooltip.top="'Actualizar'"
        />
      </div>
    </div>

    <!-- Tabla de tareas -->
    <div v-if="loading" class="flex justify-content-center p-5">
      <ProgressSpinner style="width: 50px; height: 50px" />
    </div>

    <div v-else-if="filteredTasks.length === 0" class="text-center p-5 surface-card border-round">
      <i class="pi pi-inbox text-5xl text-color-secondary mb-3" style="display:block; opacity:0.3"></i>
      <p class="text-lg text-color-secondary m-0">
        {{ searchQuery || selectedStatus ? 'No se encontraron tareas' : 'No hay tareas' }}
      </p>
    </div>

    <DataTable
      v-else
      :value="filteredTasks"
      responsiveLayout="scroll"
      stripedRows
      size="small"
      class="tasks-table"
    >
      <Column field="title" header="Título" style="min-width: 200px">
        <template #body="{ data }">
          <div class="flex flex-column gap-1">
            <span class="font-semibold">{{ data.title }}</span>
            <small class="text-color-secondary line-clamp-1">{{ data.description }}</small>
          </div>
        </template>
      </Column>

      <Column field="startTime" header="Horario" style="min-width: 100px">
        <template #body="{ data }">
          {{ data.startTime }} - {{ data.endTime }}
        </template>
      </Column>

      <Column field="department.name" header="Departamento" style="min-width: 150px">
        <template #body="{ data }">
          <Tag :value="data.department.name" severity="info" size="small" />
        </template>
      </Column>

      <Column field="shift.name" header="Turno" style="min-width: 120px">
        <template #body="{ data }">
          <Tag 
            :value="data.shift.name" 
            :severity="getShiftSeverity(data.shift.type)"
            size="small"
          />
        </template>
      </Column>

      <Column field="status" header="Estado" style="min-width: 120px">
        <template #body="{ data }">
          <Tag 
            :value="getStatusLabel(data.status)" 
            :severity="getStatusSeverity(data.status)" 
            size="small"
          />
        </template>
      </Column>

      <Column field="actions" header="Acciones" style="min-width: 180px">
        <template #body="{ data }">
          <div class="flex gap-2">
            <Button
              icon="pi pi-pencil"
              outlined
              rounded
              size="small"
              severity="info"
              @click="editTask(data)"
              v-tooltip.top="'Editar'"
              v-if="canEditTask(data)"
            />
            <Button
              icon="pi pi-check"
              outlined
              rounded
              size="small"
              severity="success"
              @click="completeTask(data)"
              v-tooltip.top="'Completar'"
              v-if="data.status === 'pending' || data.status === 'in_progress'"
            />
            <Button
              icon="pi pi-trash"
              outlined
              rounded
              size="small"
              severity="danger"
              @click="confirmDeleteTask(data)"
              v-tooltip.top="'Eliminar'"
              v-if="canDeleteTask(data)"
            />
          </div>
        </template>
      </Column>
    </DataTable>

    <!-- Diálogo Nueva/Editar Tarea -->
    <Dialog
      v-model:visible="taskDialogVisible"
      :header="editingTask ? 'Editar Tarea' : 'Nueva Tarea'"
      :style="{ width: '600px' }"
      :modal="true"
    >
      <div class="flex flex-column gap-3">
        <div class="field">
          <label for="title" class="font-bold block mb-2">Título *</label>
          <InputText
            id="title"
            v-model="taskForm.title"
            class="w-full"
            placeholder="Título de la tarea"
          />
          <small v-if="submitted && !taskForm.title" class="p-error">El título es requerido</small>
        </div>

        <div class="field">
          <label for="description" class="font-bold block mb-2">Descripción *</label>
          <Textarea
            id="description"
            v-model="taskForm.description"
            rows="3"
            class="w-full"
            placeholder="Descripción de lo que debe hacer el personal de guardia"
          />
          <small v-if="submitted && !taskForm.description" class="p-error">La descripción es requerida</small>
        </div>

        <div class="field" v-if="authStore.isAdmin">
          <label for="department" class="font-bold block mb-2">Departamento *</label>
          <Select
            id="department"
            v-model="taskForm.departmentId"
            :options="allDepartments"
            option-label="name"
            option-value="id"
            placeholder="Seleccione departamento"
            class="w-full"
          />
          <small v-if="submitted && !taskForm.departmentId" class="p-error">El departamento es requerido</small>
        </div>

        <div class="grid">
          <div class="col-12 md:col-6">
            <label for="startTime" class="font-bold block mb-2">Hora Inicio *</label>
            <DatePicker
              id="startTime"
              v-model="taskForm.startTime"
              class="w-full"
              timeOnly
              hourFormat="24"
              placeholder="HH:MM"
            />
          </div>
          <div class="col-12 md:col-6">
            <label for="endTime" class="font-bold block mb-2">Hora Fin *</label>
            <DatePicker
              id="endTime"
              v-model="taskForm.endTime"
              class="w-full"
              timeOnly
              hourFormat="24"
              placeholder="HH:MM"
            />
          </div>
        </div>

        <div class="field">
          <label for="shift" class="font-bold block mb-2">Turno *</label>
          <Select
            id="shift"
            v-model="taskForm.shiftId"
            :options="shifts"
            option-label="name"
            option-value="id"
            placeholder="Seleccione turno"
            class="w-full"
          >
            <template #option="slotProps">
              <div class="flex align-items-center gap-2">
                <span class="w-3 h-3 border-round" :style="{ backgroundColor: slotProps.option.color }"></span>
                <span>{{ slotProps.option.name }} ({{ slotProps.option.startTime }} - {{ slotProps.option.endTime }})</span>
              </div>
            </template>
            <template #value="slotProps">
              <div v-if="slotProps.value" class="flex align-items-center gap-2">
                <span class="w-3 h-3 border-round" :style="{ backgroundColor: shifts.find(s => s.id === slotProps.value)?.color }"></span>
                <span>{{ shifts.find(s => s.id === slotProps.value)?.name }}</span>
              </div>
              <span v-else>Seleccione turno</span>
            </template>
          </Select>
          <small v-if="submitted && !taskForm.shiftId" class="p-error">El turno es requerido</small>
        </div>

        <div class="field">
          <div class="flex align-items-center gap-2">
            <Checkbox
              id="isDaily"
              v-model="taskForm.isDaily"
              :binary="true"
            />
            <label for="isDaily" class="font-bold">Tarea Diaria (Recurrente)</label>
          </div>
          <small class="text-color-secondary">Las tareas diarias se resetean automáticamente al final de cada día para aparecer en la bitácora del día siguiente.</small>
        </div>

        <div class="field">
          <label for="observations" class="font-bold block mb-2">Observaciones (opcional)</label>
          <Textarea
            id="observations"
            v-model="taskForm.observations"
            rows="2"
            class="w-full"
            placeholder="Observaciones adicionales"
          />
        </div>
      </div>

      <template #footer>
        <Button label="Cancelar" text @click="taskDialogVisible = false" />
        <Button
          label="Guardar"
          icon="pi pi-check"
          :loading="savingTask"
          @click="saveTask"
        />
      </template>
    </Dialog>

    <!-- Diálogo Confirmar Eliminación -->
    <Dialog
      v-model:visible="deleteDialogVisible"
      header="Confirmar Eliminación"
      :style="{ width: '400px' }"
      :modal="true"
    >
      <div class="flex align-items-center gap-3">
        <i class="pi pi-exclamation-triangle text-3xl text-red-500"></i>
        <span>¿Está seguro de eliminar esta tarea?</span>
      </div>
      <template #footer>
        <Button label="Cancelar" text @click="deleteDialogVisible = false" />
        <Button
          label="Eliminar"
          severity="danger"
          :loading="deletingTask"
          @click="deleteTask"
        />
      </template>
    </Dialog>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useTaskStore } from '@/stores/task.store'
import { useDepartmentStore } from '@/stores/department.store'
import { useUserStore } from '@/stores/user.store'
import { useAuthStore } from '@/stores/auth.store'
import { useShiftStore } from '@/stores/shift.store'
import { useToast } from 'primevue/usetoast'
import { taskService } from '@/services/taskService'
import Button from 'primevue/button'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import Select from 'primevue/select'
import DatePicker from 'primevue/datepicker'
import Dialog from 'primevue/dialog'
import ProgressSpinner from 'primevue/progressspinner'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import Checkbox from 'primevue/checkbox'

const taskStore = useTaskStore()
const departmentStore = useDepartmentStore()
const userStore = useUserStore()
const authStore = useAuthStore()
const shiftStore = useShiftStore()
const toast = useToast()

const API_URL = 'http://localhost:8000'

const loading = ref(false)
const savingTask = ref(false)
const deletingTask = ref(false)
const submitted = ref(false)
const taskDialogVisible = ref(false)
const deleteDialogVisible = ref(false)
const editingTask = ref(null)
const taskToDelete = ref(null)
const searchQuery = ref('')
const selectedStatus = ref(null)
const selectedDepartment = ref(null)
let searchTimeout = null

const taskForm = ref({
  title: '',
  description: '',
  startTime: null,
  endTime: null,
  departmentId: null,
  shiftId: null,
  observations: ''
})

const shifts = ref([])
const statusOptions = [
  { label: 'Todos', value: null },
  { label: 'Pendientes', value: 'pending' },
  { label: 'En Progreso', value: 'in_progress' },
  { label: 'Completadas', value: 'completed' },
  { label: 'Canceladas', value: 'cancelled' }
]

const tasks = computed(() => taskStore.tasks)
const allDepartments = computed(() => departmentStore.departments)

const availableDepartments = computed(() => {
  if (authStore.isAdmin) {
    return allDepartments.value
  }
  if (authStore.isManager && authStore.user?.department) {
    return allDepartments.value.filter(d => d.id === authStore.user.department.id)
  }
  return []
})

const usersByDepartment = ref([])

const canCreateTasks = computed(() => {
  return authStore.isAdmin || authStore.isManager
})

const filteredTasks = computed(() => {
  let filtered = tasks.value
  
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter(t =>
      t.title.toLowerCase().includes(query) ||
      t.description.toLowerCase().includes(query)
    )
  }
  
  if (selectedStatus.value) {
    filtered = filtered.filter(t => t.status === selectedStatus.value)
  }
  
  if (selectedDepartment.value) {
    filtered = filtered.filter(t => t.department.id === selectedDepartment.value)
  }
  
  return filtered
})

const canEditTask = (task) => {
  if (authStore.isAdmin) return true
  if (authStore.isManager && authStore.user?.department?.id === task.department.id) return true
  return task.createdBy?.id === authStore.user?.id
}

const canDeleteTask = (task) => {
  return canEditTask(task)
}

const getStatusLabel = (status) => {
  const labels = {
    pending: 'Pendiente',
    in_progress: 'En Progreso',
    completed: 'Completada',
    cancelled: 'Cancelada'
  }
  return labels[status] || status
}

const getStatusSeverity = (status) => {
  const severities = {
    pending: 'warning',
    in_progress: 'info',
    completed: 'success',
    cancelled: 'danger'
  }
  return severities[status] || 'info'
}

const getShiftSeverity = (type) => {
  const severities = {
    morning: 'success',
    afternoon: 'warning',
    night: 'danger',
    custom: 'info'
  }
  return severities[type] || 'info'
}

const formatDate = (dateString) => {
  if (!dateString) return ''
  const [year, month, day] = dateString.split('-')
  return `${day}/${month}/${year}`
}

const debouncedSearch = () => {
  if (searchTimeout) clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    // La búsqueda es reactiva con filteredTasks
  }, 300)
}

const loadShifts = async () => {
  try {
    await shiftStore.fetchShifts()
    shifts.value = shiftStore.shifts
  } catch (error) {
    console.error('Error loading shifts:', error)
    shifts.value = []
  }
}

const loadTasks = async () => {
  loading.value = true
  try {
    const filters = {}
    if (selectedStatus.value) filters.status = selectedStatus.value
    if (selectedDepartment.value) filters.department = selectedDepartment.value
    await taskStore.fetchTasks(filters)
  } finally {
    loading.value = false
  }
}

const showNewTaskDialog = async () => {
  editingTask.value = null
  taskForm.value = {
    title: '',
    description: '',
    startTime: null,
    endTime: null,
    departmentId: authStore.user?.department?.id || null,
    shiftId: null,
    isDaily: false,
    observations: ''
  }
  submitted.value = false
  
  // Cargar turnos
  console.log('🔄 Cargando turnos...')
  await loadShifts()
  console.log('📋 Turnos disponibles:', shifts.value)
  
  taskDialogVisible.value = true
}

const editTask = async (task) => {
  editingTask.value = task
  taskForm.value = {
    title: task.title,
    description: task.description,
    startTime: parseTime(task.startTime),
    endTime: parseTime(task.endTime),
    departmentId: task.department.id,
    shiftId: task.shift.id,
    isDaily: task.isDaily || false,
    observations: task.observations || ''
  }
  submitted.value = false
  
  // Cargar turnos
  await loadShifts()
  
  taskDialogVisible.value = true
}

const saveTask = async () => {
  submitted.value = true

  console.log('💾 saveTask - taskForm:', taskForm.value)
  console.log('💾 saveTask - shifts:', shifts.value)

  if (!taskForm.value.title || !taskForm.value.description || !taskForm.value.shiftId || !taskForm.value.departmentId) {
    console.error('❌ Faltan datos requeridos')
    return
  }

  savingTask.value = true

  try {
    const data = {
      title: taskForm.value.title,
      description: taskForm.value.description,
      startTime: formatTime(taskForm.value.startTime),
      endTime: formatTime(taskForm.value.endTime),
      departmentId: taskForm.value.departmentId,
      shiftId: taskForm.value.shiftId,
      isDaily: taskForm.value.isDaily,
      observations: taskForm.value.observations
    }

    console.log('💾 saveTask - Enviando datos:', data)

    if (editingTask.value) {
      await taskStore.updateTask(editingTask.value.id, data)
      toast.add({
        severity: 'success',
        summary: 'Éxito',
        detail: 'Tarea actualizada correctamente',
        life: 3000
      })
    } else {
      await taskStore.createTask(data)
      toast.add({
        severity: 'success',
        summary: 'Éxito',
        detail: 'Tarea creada correctamente',
        life: 3000
      })
    }

    taskDialogVisible.value = false
    loadTasks()
  } catch (error) {
    console.error('❌ Error saveTask:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.error || 'Error al guardar la tarea',
      life: 3000
    })
  } finally {
    savingTask.value = false
  }
}

const formatTime = (date) => {
  if (!date) return null
  const hours = date.getHours().toString().padStart(2, '0')
  const minutes = date.getMinutes().toString().padStart(2, '0')
  return `${hours}:${minutes}`
}

const parseTime = (timeString) => {
  if (!timeString) return null
  const [hours, minutes] = timeString.split(':')
  const date = new Date()
  date.setHours(parseInt(hours), parseInt(minutes))
  return date
}

const completeTask = async (task) => {
  try {
    await taskStore.updateTaskStatus(task.id, 'completed')
    toast.add({
      severity: 'success',
      summary: 'Éxito',
      detail: 'Tarea completada',
      life: 3000
    })
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Error al completar la tarea',
      life: 3000
    })
  }
}

const confirmDeleteTask = (task) => {
  taskToDelete.value = task
  deleteDialogVisible.value = true
}

const deleteTask = async () => {
  if (!taskToDelete.value) return
  
  deletingTask.value = true
  
  try {
    await taskStore.deleteTask(taskToDelete.value.id)
    toast.add({
      severity: 'success',
      summary: 'Éxito',
      detail: 'Tarea eliminada',
      life: 3000
    })
    deleteDialogVisible.value = false
    taskToDelete.value = null
    loadTasks()
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Error al eliminar la tarea',
      life: 3000
    })
  } finally {
    deletingTask.value = false
  }
}

onMounted(async () => {
  await departmentStore.fetchDepartments()
  await loadShifts()
  await loadTasks()
})
</script>

<style scoped>
.tasks-view {
  max-width: 1400px;
  margin: 0 auto;
}

.tasks-table {
  background: var(--surface-card);
}

.line-clamp-1 {
  display: -webkit-box;
  -webkit-line-clamp: 1;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
