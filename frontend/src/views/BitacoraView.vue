<template>
  <div class="bitacora-view p-4">
    <!-- Header -->
    <div class="flex align-items-center justify-content-between mb-4">
      <div>
        <h1 class="text-2xl font-bold m-0">Bitácora de Tareas</h1>
        <p class="text-color-secondary m-0 mt-1">
          Registro diario de cumplimiento de tareas
        </p>
      </div>
    </div>

    <!-- Fecha y Filtros -->
    <div class="surface-card border-round p-3 mb-4">
      <div class="flex flex-wrap gap-3 align-items-center justify-content-between">
        <div class="flex align-items-center gap-3">
          <label class="font-semibold">Fecha:</label>
          <DatePicker 
            v-model="selectedDate" 
            @date-change="loadData"
            dateFormat="yy-mm-dd"
            class="w-15rem"
          />
        </div>
        
        <div class="flex align-items-center gap-3">
          <!-- Buscador -->
          <IconField>
            <InputIcon>
              <i class="pi pi-search" />
            </InputIcon>
            <InputText
              v-model="searchQuery"
              placeholder="Buscar por tarea o usuario..."
              @input="debouncedSearch"
              class="w-20rem"
            />
          </IconField>
          
          <!-- Filtro por estado -->
          <Select
            v-model="selectedRiskLevel"
            :options="riskLevelFilterOptions"
            option-label="label"
            option-value="value"
            placeholder="Todos los estados"
            class="w-15rem"
            @change="filterLogs"
          >
            <template #option="slotProps">
              <div class="flex align-items-center gap-2">
                <span class="w-3 h-3 border-circle" :class="getRiskLevelColorClass(slotProps.option.value)"></span>
                <span>{{ slotProps.option.label }}</span>
              </div>
            </template>
            <template #value="slotProps">
              <div v-if="slotProps.value" class="flex align-items-center gap-2">
                <span class="w-3 h-3 border-circle" :class="getRiskLevelColorClass(slotProps.value)"></span>
                <span>{{ riskLevelFilterOptions.find(o => o.value === slotProps.value)?.label }}</span>
              </div>
            </template>
          </Select>
        </div>
      </div>
    </div>

    <!-- Cargando -->
    <div v-if="loading" class="flex justify-content-center p-5">
      <ProgressSpinner style="width: 50px; height: 50px" />
    </div>

    <div v-else class="grid">
      <!-- Columna izquierda: Formulario de registro -->
      <div class="col-12 lg:col-5">
        <div class="surface-card border-round-xl p-4 shadow-2">
          <h3 class="text-lg font-bold mb-4">Registrar Cumplimiento</h3>

          <div class="field">
            <label for="task" class="font-bold block mb-2">Tarea *</label>
            <Select
              id="task"
              v-model="logForm.taskId"
              :options="pendingTasks"
              option-label="title"
              option-value="id"
              placeholder="Seleccione una tarea"
              class="w-full"
              @change="onTaskSelect"
            />
            <small v-if="submitted && !logForm.taskId" class="p-error block mt-1">
              La tarea es requerida
            </small>
          </div>

          <div class="field">
            <label class="font-bold block mb-2">Descripción</label>
            <Textarea
              v-model="logForm.description"
              rows="3"
              class="w-full"
              readonly
              disabled
            />
          </div>

          <div class="grid">
            <div class="col-12 md:col-6">
              <label class="font-bold block mb-2">Hora Inicio</label>
              <InputText
                v-model="logForm.startTime"
                class="w-full"
                disabled
              />
            </div>
            <div class="col-12 md:col-6">
              <label for="verificationTime" class="font-bold block mb-2">Hora Verificación *</label>
              <DatePicker
                id="verificationTime"
                v-model="logForm.verificationTime"
                class="w-full"
                timeOnly
                hourFormat="24"
                placeholder="HH:MM"
              />
              <small v-if="submitted && !logForm.verificationTime" class="p-error block mt-1">
                La hora de verificación es requerida
              </small>
            </div>
          </div>

          <div class="field">
            <label for="observations" class="font-bold block mb-2">Observaciones</label>
            <Textarea
              id="observations"
              v-model="logForm.observations"
              rows="3"
              class="w-full"
              placeholder="Observaciones del cumplimiento"
            />
          </div>

          <div class="field">
            <label for="riskLevel" class="font-bold block mb-2">Estado del Turno *</label>
            <Select
              id="riskLevel"
              v-model="logForm.riskLevel"
              :options="riskLevelOptions"
              option-label="label"
              option-value="value"
              placeholder="Seleccione estado"
              class="w-full"
            >
              <template #option="slotProps">
                <div class="flex align-items-center gap-2">
                  <i :class="slotProps.option.icon"></i>
                  <span>{{ slotProps.option.label }}</span>
                </div>
              </template>
              <template #value="slotProps">
                <div v-if="slotProps.value" class="flex align-items-center gap-2">
                  <i :class="riskLevelOptions.find(o => o.value === slotProps.value)?.icon"></i>
                  <span>{{ riskLevelOptions.find(o => o.value === slotProps.value)?.label }}</span>
                </div>
                <span v-else>Seleccione estado</span>
              </template>
            </Select>
          </div>

          <div class="flex justify-content-end gap-2 mt-4">
            <Button 
              label="Limpiar" 
              icon="pi pi-refresh" 
              severity="secondary"
              @click="clearForm"
            />
            <Button 
              label="Guardar Registro" 
              icon="pi pi-check" 
              :loading="saving"
              @click="saveLog"
            />
          </div>
        </div>
      </div>

      <!-- Columna derecha: Logs del día -->
      <div class="col-12 lg:col-7">
        <div class="surface-card border-round-xl p-4 shadow-2">
          <h3 class="text-lg font-bold mb-4">Registros del Día</h3>

          <div v-if="logs.length === 0" class="text-center p-5">
            <i class="pi pi-clipboard text-5xl text-color-secondary mb-3" style="opacity: 0.3"></i>
            <p class="text-color-secondary m-0">No hay registros para esta fecha</p>
          </div>

          <DataTable
            v-else
            :value="filteredLogs"
            :paginator="true"
            :rows="10"
            :rowsPerPageOptions="[5, 10, 20, 50]"
            responsiveLayout="scroll"
            stripedRows
            size="small"
            currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} registros"
            showCurrentPageReport
          >
            <Column field="task.title" header="Tarea" style="min-width: 200px"></Column>
            <Column field="user.fullName" header="Usuario" style="min-width: 150px"></Column>
            <Column field="startTime" header="Hora Inicio" style="min-width: 100px"></Column>
            <Column field="verificationTime" header="Verificación" style="min-width: 100px"></Column>
            <Column field="riskLevel" header="Estado" style="min-width: 150px">
              <template #body="{ data }">
                <div class="flex align-items-center gap-2">
                  <span class="w-4 h-4 border-circle shadow-2" :class="getRiskLevelColorClass(data.riskLevel)"></span>
                  <Tag 
                    :value="getRiskLevelLabel(data.riskLevel)" 
                    :severity="getRiskLevelSeverity(data.riskLevel)"
                    class="px-3 py-2"
                  />
                </div>
              </template>
            </Column>
            <Column header="Acciones" style="min-width: 100px" class="text-center">
              <template #body="{ data }">
                <Button
                  icon="pi pi-pencil"
                  severity="info"
                  text
                  rounded
                  @click="editLog(data)"
                  title="Editar observaciones"
                />
              </template>
            </Column>
          </DataTable>
        </div>
      </div>
    </div>

    <!-- Diálogo Editar -->
    <Dialog
      v-model:visible="editDialogVisible"
      header="Editar Registro"
      :style="{ width: '500px' }"
      :modal="true"
    >
      <div class="flex flex-column gap-3">
        <div class="field">
          <label class="font-bold block mb-2">Tarea</label>
          <InputText v-model="editingLog.taskTitle" class="w-full" disabled />
        </div>

        <div class="field">
          <label for="editVerificationTime" class="font-bold block mb-2">Hora Verificación</label>
          <DatePicker
            id="editVerificationTime"
            v-model="editingLog.verificationTime"
            class="w-full"
            timeOnly
            hourFormat="24"
          />
        </div>

        <div class="field">
          <label for="editObservations" class="font-bold block mb-2">Observaciones</label>
          <Textarea
            id="editObservations"
            v-model="editingLog.observations"
            rows="4"
            class="w-full"
          />
        </div>

        <div class="field">
          <label for="editRiskLevel" class="font-bold block mb-2">Estado del Turno</label>
          <Select
            id="editRiskLevel"
            v-model="editingLog.riskLevel"
            :options="riskLevelOptions"
            option-label="label"
            option-value="value"
            class="w-full"
          >
            <template #option="slotProps">
              <div class="flex align-items-center gap-2">
                <i :class="slotProps.option.icon"></i>
                <span>{{ slotProps.option.label }}</span>
              </div>
            </template>
            <template #value="slotProps">
              <div v-if="slotProps.value" class="flex align-items-center gap-2">
                <i :class="riskLevelOptions.find(o => o.value === slotProps.value)?.icon"></i>
                <span>{{ riskLevelOptions.find(o => o.value === slotProps.value)?.label }}</span>
              </div>
            </template>
          </Select>
        </div>
      </div>

      <template #footer>
        <Button label="Cancelar" text @click="editDialogVisible = false" />
        <Button label="Guardar" icon="pi pi-check" :loading="saving" @click="updateLog" />
      </template>
    </Dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useTaskLogStore } from '@/stores/taskLog.store'
import { useAuthStore } from '@/stores/auth.store'
import { useToast } from 'primevue/usetoast'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import Select from 'primevue/select'
import DatePicker from 'primevue/datepicker'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import ProgressSpinner from 'primevue/progressspinner'
import Dialog from 'primevue/dialog'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'

const taskLogStore = useTaskLogStore()
const authStore = useAuthStore()
const toast = useToast()

const loading = ref(false)
const saving = ref(false)
const submitted = ref(false)
const selectedDate = ref(new Date())
const editDialogVisible = ref(false)
const searchQuery = ref('')
const selectedRiskLevel = ref(null)
const filteredLogs = ref([])

const riskLevelFilterOptions = [
  { label: 'Todos', value: null },
  { label: 'Normal', value: 'normal' },
  { label: 'Con Observaciones', value: 'warning' },
  { label: 'Con Problemas', value: 'danger' }
]

const logForm = reactive({
  taskId: null,
  description: '',
  startTime: '',
  verificationTime: null,
  observations: ''
})

const editingLog = reactive({
  id: null,
  taskTitle: '',
  verificationTime: null,
  observations: '',
  riskLevel: 'normal'
})

const riskLevelOptions = [
  { label: '✅ Normal - Sin novedades', value: 'normal', icon: 'pi pi-check-circle' },
  { label: '⚠️ Con Observaciones', value: 'warning', icon: 'pi pi-exclamation-triangle' },
  { label: '🚨 Con Problemas', value: 'danger', icon: 'pi pi-times-circle' }
]

const logs = ref([])
const pendingTasks = ref([])

const getStatusLabel = (status) => {
  const labels = {
    pending: 'Pendiente',
    completed: 'Completado',
    skipped: 'Omitido'
  }
  return labels[status] || status
}

const getStatusSeverity = (status) => {
  const severities = {
    pending: 'warning',
    completed: 'success',
    skipped: 'danger'
  }
  return severities[status] || 'secondary'
}

const getRiskLevelLabel = (riskLevel) => {
  const option = riskLevelOptions.find(o => o.value === riskLevel)
  return option ? option.label : riskLevel
}

const getRiskLevelSeverity = (riskLevel) => {
  const severities = {
    normal: 'success',
    warning: 'warning',
    danger: 'danger'
  }
  return severities[riskLevel] || 'secondary'
}

const getRiskLevelColorClass = (riskLevel) => {
  const colors = {
    normal: 'bg-green-500',
    warning: 'bg-yellow-500',
    danger: 'bg-red-500'
  }
  return colors[riskLevel] || 'bg-gray-500'
}

const filterLogs = () => {
  let result = logs.value
  
  // Filtro por búsqueda
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    result = result.filter(log => 
      log.task?.title?.toLowerCase().includes(query) ||
      log.user?.fullName?.toLowerCase().includes(query)
    )
  }
  
  // Filtro por estado
  if (selectedRiskLevel.value) {
    result = result.filter(log => log.riskLevel === selectedRiskLevel.value)
  }
  
  filteredLogs.value = result
}

const debouncedSearch = () => {
  setTimeout(() => {
    filterLogs()
  }, 300)
}

const loadData = async () => {
  loading.value = true
  try {
    const date = selectedDate.value.toISOString().split('T')[0]
    await Promise.all([
      taskLogStore.fetchLogs(date),
      taskLogStore.fetchPendingTasks(date)
    ])
    logs.value = taskLogStore.logs
    pendingTasks.value = taskLogStore.pendingTasks
    filterLogs()
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Error al cargar datos',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

const onTaskSelect = () => {
  const task = pendingTasks.value.find(t => t.id === logForm.taskId)
  if (task) {
    logForm.description = task.description
    logForm.startTime = task.startTime
  }
}

const saveLog = async () => {
  submitted.value = true
  
  if (!logForm.taskId || !logForm.verificationTime) {
    toast.add({
      severity: 'warn',
      summary: 'Advertencia',
      detail: 'Complete los campos requeridos',
      life: 3000
    })
    return
  }

  saving.value = true
  try {
    const date = selectedDate.value.toISOString().split('T')[0]
    const verificationTime = logForm.verificationTime

    await taskLogStore.createLog({
      taskId: logForm.taskId,
      date: date,
      verificationTime: formatTime(verificationTime),
      observations: logForm.observations,
      status: 'completed',
      riskLevel: logForm.riskLevel || 'normal'
    })

    toast.add({
      severity: 'success',
      summary: 'Éxito',
      detail: 'Registro guardado correctamente',
      life: 3000
    })

    clearForm()
    await loadData()
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.error || 'Error al guardar registro',
      life: 3000
    })
  } finally {
    saving.value = false
    submitted.value = false
  }
}

const clearForm = () => {
  logForm.taskId = null
  logForm.description = ''
  logForm.startTime = ''
  logForm.verificationTime = null
  logForm.observations = ''
  logForm.riskLevel = 'normal'
  submitted.value = false
}

const editLog = (log) => {
  editingLog.id = log.id
  editingLog.taskTitle = log.task.title
  editingLog.verificationTime = parseTime(log.verificationTime)
  editingLog.observations = log.observations || ''
  editingLog.riskLevel = log.riskLevel || 'normal'
  editDialogVisible.value = true
}

const updateLog = async () => {
  saving.value = true
  try {
    await taskLogStore.updateLog(editingLog.id, {
      verificationTime: formatTime(editingLog.verificationTime),
      observations: editingLog.observations,
      riskLevel: editingLog.riskLevel
    })

    toast.add({
      severity: 'success',
      summary: 'Éxito',
      detail: 'Registro actualizado correctamente',
      life: 3000
    })

    editDialogVisible.value = false
    await loadData()
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Error al actualizar registro',
      life: 3000
    })
  } finally {
    saving.value = false
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

onMounted(() => {
  loadData()
})
</script>

<style scoped>
.bitacora-view {
  min-height: 100vh;
  background-color: var(--surface-ground);
}
</style>
