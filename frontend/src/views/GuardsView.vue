<template>
  <div class="p-4">

    <!-- Header -->
    <div class="flex justify-content-between align-items-center mb-4">
      <div class="flex align-items-center gap-2">
        <i class="pi pi-shield text-3xl text-primary"></i>
        <h2 class="text-2xl font-bold m-0">Guardias</h2>
      </div>
      <Button label="Nueva Guardia" icon="pi pi-plus" @click="openNewGuard" />
    </div>

    <!-- DataTable -->
    <DataTable
      :value="guardStore.guards"
      dataKey="id"
      :paginator="true"
      :rows="10"
      :filters="filters"
      :loading="guardStore.loading"
      :globalFilterFields="['name', 'departmentName', 'startTime', 'endTime']"
      paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
      :rowsPerPageOptions="[5, 10, 25]"
      currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} guardias"
      responsiveLayout="scroll"
    >
      <template #header>
        <div class="flex justify-content-end">
          <IconField>
            <InputIcon><i class="pi pi-search" /></InputIcon>
            <InputText v-model="filters['global'].value" placeholder="Búsqueda global" />
          </IconField>
        </div>
      </template>
      <template #empty>No se encontraron guardias.</template>
      <template #loading>Cargando guardias...</template>

      <Column field="name" header="Nombre" :sortable="true" style="min-width: 14rem" />
      <Column field="departmentName" header="Departamento" :sortable="true" style="min-width: 12rem">
        <template #body="{ data }">{{ data.departmentName || '-' }}</template>
      </Column>
      <Column field="startTime" header="Inicio" style="min-width: 8rem">
        <template #body="{ data }">
          <span class="flex align-items-center gap-2"><i class="pi pi-clock text-primary" />{{ data.startTime }}</span>
        </template>
      </Column>
      <Column field="endTime" header="Fin" style="min-width: 8rem">
        <template #body="{ data }">
          <span class="flex align-items-center gap-2"><i class="pi pi-clock text-orange-500" />{{ data.endTime }}</span>
        </template>
      </Column>
      <Column field="duration" header="Duración" style="min-width: 8rem">
        <template #body="{ data }">{{ data.duration }} min</template>
      </Column>
      <Column header="Vigencia" style="min-width: 14rem">
        <template #body="{ data }">
          <span v-if="data.validFrom && data.validUntil" class="flex align-items-center gap-1">
            <i class="pi pi-calendar text-xs" />{{ formatDate(data.validFrom) }} — {{ formatDate(data.validUntil) }}
          </span>
          <span v-else-if="data.validFrom">Desde {{ formatDate(data.validFrom) }}</span>
          <span v-else class="text-color-secondary">-</span>
        </template>
      </Column>
      <Column field="active" header="Estado" :sortable="true" style="min-width: 8rem">
        <template #body="{ data }">
          <Tag :value="data.active ? 'Activa' : 'Inactiva'" :severity="data.active ? 'success' : 'danger'" />
        </template>
      </Column>
      <Column header="Acciones" :exportable="false" style="min-width: 8rem">
        <template #body="{ data }">
          <Button icon="pi pi-pencil" outlined rounded class="mr-2" @click="editGuard(data)" v-tooltip.top="'Editar'" />
          <Button icon="pi pi-trash" outlined rounded severity="danger" @click="confirmDelete(data)" v-tooltip.top="'Eliminar'" />
        </template>
      </Column>
    </DataTable>

    <!-- Dialog Nueva/Editar Guardia -->
    <Dialog v-model:visible="guardDialog" :style="{width: '650px'}" :header="editingGuard ? 'Editar Guardia' : 'Nueva Guardia'" :modal="true" class="p-fluid">
      <div class="formgrid grid mt-2">

        <div class="field col-12">
          <label for="guardName" class="font-bold block mb-2">Nombre *</label>
          <IconField class="w-full">
            <InputIcon><i class="pi pi-shield" /></InputIcon>
            <InputText id="guardName" v-model.trim="formData.name" :class="{'p-invalid': submitted && !formData.name}" placeholder="Ej. Guardia Matutina" class="w-full" />
          </IconField>
          <small class="p-error" v-if="submitted && !formData.name">El nombre es requerido.</small>
        </div>

        <div class="field col-12 md:col-6">
          <label for="guardDept" class="font-bold block mb-2">Departamento</label>
          <Dropdown id="guardDept" v-model="formData.departmentId" :options="departments" optionLabel="name" optionValue="id" placeholder="Seleccione un departamento" showClear class="w-full" @change="loadUsersByDepartment">
            <template #option="slotProps">
              <div class="flex align-items-center gap-2">
                <i class="pi pi-building" />{{ slotProps.option.name }}
              </div>
            </template>
          </Dropdown>
        </div>

        <div class="field col-12 md:col-6" v-if="formData.departmentId">
          <label for="guardUsers" class="font-bold block mb-2">
            Usuarios de Guardia
            <i class="pi pi-info-circle text-primary ml-1" v-tooltip.left="'Seleccione uno o varios usuarios para crear las asignaciones en el calendario'" />
          </label>
          <MultiSelect 
            id="guardUsers" 
            v-model="formData.userIds" 
            :options="usersByDepartment" 
            :optionLabel="u => u.fullName || (u.firstName + ' ' + u.lastName)" 
            optionValue="id" 
            placeholder="Seleccione usuarios" 
            showClear 
            class="w-full"
            display="chip"
          >
            <template #option="slotProps">
              <div class="flex align-items-center gap-2">
                <i class="pi pi-user" />{{ slotProps.option.fullName || (slotProps.option.firstName + ' ' + slotProps.option.lastName) }}
              </div>
            </template>
          </MultiSelect>
          <small v-if="usersByDepartment.length === 0" class="text-orange-500 mt-1 block">⚠️ No hay usuarios en este departamento</small>
          <small v-else class="text-primary mt-1 block">💡 Seleccione uno o más usuarios para asignar las guardias automáticamente</small>
        </div>

        <div class="field col-12 md:col-6">
          <label for="validFrom" class="font-bold block mb-2">Inicio de vigencia *</label>
          <IconField class="w-full">
            <InputIcon><i class="pi pi-calendar" /></InputIcon>
            <InputText id="validFrom" v-model="formData.validFrom" type="date" :min="minDate" :class="{'p-invalid': submitted && !formData.validFrom}" class="w-full" />
          </IconField>
          <small class="p-error" v-if="submitted && !formData.validFrom">La fecha de inicio es requerida.</small>
        </div>

        <div class="field col-12 md:col-6">
          <label for="validUntil" class="font-bold block mb-2">Fin de vigencia *</label>
          <IconField class="w-full">
            <InputIcon><i class="pi pi-calendar" /></InputIcon>
            <InputText id="validUntil" v-model="formData.validUntil" type="date" :min="formData.validFrom || minDate" :class="{'p-invalid': submitted && !formData.validUntil}" class="w-full" />
          </IconField>
          <small class="p-error" v-if="submitted && !formData.validUntil">La fecha de fin es requerida.</small>
        </div>

        <div class="field col-12 md:col-6">
          <label for="startTime" class="font-bold block mb-2">Hora Inicio *</label>
          <IconField class="w-full">
            <InputIcon><i class="pi pi-clock" /></InputIcon>
            <InputText id="startTime" v-model="formData.startTime" type="time" class="w-full" />
          </IconField>
        </div>

        <div class="field col-12 md:col-6">
          <label for="endTime" class="font-bold block mb-2">Hora Fin *</label>
          <IconField class="w-full">
            <InputIcon><i class="pi pi-clock" /></InputIcon>
            <InputText id="endTime" v-model="formData.endTime" type="time" class="w-full" />
          </IconField>
        </div>

        <div class="field col-12">
          <label class="font-bold block mb-2">Días de la semana</label>
          <div class="flex flex-wrap gap-2">
            <div v-for="day in weekDays" :key="day.value" class="flex align-items-center gap-1 p-2 border-round surface-50 border-1 surface-border cursor-pointer" :class="{'surface-200 border-primary': formData.weekDays.includes(day.value)}" @click="toggleDay(day.value)" style="min-width:80px;">
              <Checkbox :modelValue="formData.weekDays.includes(day.value)" :binary="true" @click.stop="toggleDay(day.value)" />
              <span class="text-sm">{{ day.label }}</span>
            </div>
          </div>
          <small class="text-color-secondary mt-1 block">Días en que se repetirá la guardia dentro del período</small>
        </div>

        <div class="field col-12">
          <label for="guardDesc" class="font-bold block mb-2">Descripción</label>
          <Textarea id="guardDesc" v-model="formData.description" rows="2" placeholder="Descripción opcional" class="w-full" />
        </div>

      </div>

      <template #footer>
        <Button label="Cancelar" icon="pi pi-times" text @click="hideDialog" />
        <Button label="Guardar" icon="pi pi-check" :loading="saving" @click="saveGuard" />
      </template>
    </Dialog>

  </div>
</template>

<script setup>
import { ref, reactive, onMounted, watch, computed } from 'vue'
import { FilterMatchMode } from '@primevue/core/api'
import { useConfirm } from 'primevue/useconfirm'
import { useToast } from 'primevue/usetoast'
import { useGuardStore } from '@/stores/guard.store'
import { useDepartmentStore } from '@/stores/department.store'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import InputText from 'primevue/inputtext'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import Dialog from 'primevue/dialog'
import Dropdown from 'primevue/dropdown'
import MultiSelect from 'primevue/multiselect'
import Checkbox from 'primevue/checkbox'
import Textarea from 'primevue/textarea'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'

const API_URL = 'http://localhost:8000'

const guardStore = useGuardStore()
const departmentStore = useDepartmentStore()
const confirm = useConfirm()
const toast = useToast()

const guardDialog = ref(false)
const editingGuard = ref(null)
const submitted = ref(false)
const saving = ref(false)
const departments = ref([])
const usersByDepartment = ref([])

const filters = ref({
  global: { value: null, matchMode: FilterMatchMode.CONTAINS }
})

const formData = reactive({
  name: '',
  departmentId: '',
  userIds: [],
  validFrom: new Date().toISOString().split('T')[0],
  validUntil: '',
  startTime: '08:00',
  endTime: '20:00',
  weekDays: [],
  description: ''
})

const minDate = new Date().toISOString().split('T')[0]

const weekDays = [
  { value: 1, label: 'Lunes' },
  { value: 2, label: 'Martes' },
  { value: 3, label: 'Miércoles' },
  { value: 4, label: 'Jueves' },
  { value: 5, label: 'Viernes' },
  { value: 6, label: 'Sábado' },
  { value: 0, label: 'Domingo' }
]

const toggleDay = (val) => {
  const idx = formData.weekDays.indexOf(val)
  if (idx === -1) formData.weekDays.push(val)
  else formData.weekDays.splice(idx, 1)
}

const formatDate = (dateString) => {
  if (!dateString) return ''
  const [year, month, day] = dateString.split('-')
  return `${day}/${month}/${year}`
}

const resetForm = () => {
  formData.name = ''
  formData.departmentId = ''
  formData.userIds = []
  formData.validFrom = new Date().toISOString().split('T')[0]
  formData.validUntil = ''
  formData.startTime = '08:00'
  formData.endTime = '20:00'
  formData.weekDays = []
  formData.description = ''
  editingGuard.value = null
  usersByDepartment.value = []
}

const hideDialog = () => {
  guardDialog.value = false
  submitted.value = false
}

const openNewGuard = () => {
  resetForm()
  submitted.value = false
  guardDialog.value = true
}

const editGuard = (guard) => {
  editingGuard.value = guard
  formData.name = guard.name
  formData.departmentId = guard.department || ''
  formData.userId = guard.userId || ''
  formData.validFrom = guard.validFrom || ''
  formData.validUntil = guard.validUntil || ''
  formData.startTime = guard.startTime
  formData.endTime = guard.endTime
  formData.weekDays = guard.weekDays || []
  formData.description = guard.description || ''
  
  if (formData.departmentId) {
    loadUsersByDepartment()
  }
  
  submitted.value = false
  guardDialog.value = true
}

const confirmDelete = (guard) => {
  confirm.require({
    message: `¿Eliminar "${guard.name}"?`,
    header: 'Confirmación',
    icon: 'pi pi-exclamation-triangle',
    acceptClass: 'p-button-danger',
    accept: async () => {
      try {
        await guardStore.deleteGuard(guard.id)
        toast.add({ severity: 'success', summary: 'Éxito', detail: 'Guardia eliminada correctamente', life: 3000 })
      } catch (error) {
        toast.add({ severity: 'error', summary: 'Error', detail: 'Error: ' + error.message, life: 3000 })
      }
    }
  })
}

const loadUsersByDepartment = async () => {
  if (!formData.departmentId) {
    usersByDepartment.value = []
    return
  }
  const token = localStorage.getItem('token')
  try {
    const response = await fetch(`${API_URL}/api/users/department/${formData.departmentId}`, {
      headers: { 'Authorization': `Bearer ${token}`, 'Content-Type': 'application/json' }
    })
    if (response.ok) {
      const data = await response.json()
      usersByDepartment.value = data.users || []
    }
  } catch (error) {
    console.error('Error loading users:', error)
  }
}

const loadAssignments = async () => {
  const token = localStorage.getItem('token')
  try {
    // Usar el endpoint calendar para obtener los eventos en el formato correcto
    const currentMonth = new Date().getMonth() + 1
    const currentYear = new Date().getFullYear()
    const response = await fetch(`${API_URL}/api/assignments/calendar?month=${currentMonth}&year=${currentYear}`, {
      headers: { 'Authorization': `Bearer ${token}` }
    })
    if (response.ok) {
      const data = await response.json()
      // Disparar evento con los eventos del calendario
      window.dispatchEvent(new CustomEvent('assignments-updated', { detail: data.events || [] }))
    }
  } catch (error) {
    console.error('Error loading assignments:', error)
  }
}

const saveGuard = async () => {
  submitted.value = true
  if (!formData.name || !formData.validFrom || !formData.validUntil) {
    toast.add({ severity: 'warn', summary: 'Advertencia', detail: 'Complete los campos requeridos', life: 3000 })
    return
  }
  if (formData.weekDays.length === 0) {
    toast.add({ severity: 'warn', summary: 'Advertencia', detail: 'Seleccione al menos un día de la semana', life: 3000 })
    return
  }
  if (!formData.userIds || formData.userIds.length === 0) {
    toast.add({ severity: 'warn', summary: 'Advertencia', detail: 'Seleccione al menos un usuario para crear las asignaciones', life: 3000 })
    return
  }

  saving.value = true
  try {
    const payload = {
      name: formData.name,
      startTime: formData.startTime,
      endTime: formData.endTime,
      active: true,
      weekDays: formData.weekDays,
      validFrom: formData.validFrom,
      validUntil: formData.validUntil || null,
      description: formData.description || '',
      departmentId: formData.departmentId || null
    }

    console.log('Payload enviado al crear guardia:', payload)

    if (editingGuard.value) {
      await guardStore.updateGuard(editingGuard.value.id, payload)
      toast.add({ severity: 'success', summary: 'Éxito', detail: 'Guardia actualizada correctamente', life: 3000 })
      await loadAssignments()
    } else {
      const result = await guardStore.createGuard(payload)
      console.log('Guardia creada, ID:', result.guard.id)
      toast.add({ severity: 'success', summary: 'Éxito', detail: 'Guardia creada correctamente', life: 3000 })

      // Crear las asignaciones para cada usuario seleccionado
      const token = localStorage.getItem('token')
      const datesToAssign = []

      let currentDate = new Date(formData.validFrom + 'T00:00:00')
      const endDate = new Date(formData.validUntil + 'T00:00:00')

      while (currentDate <= endDate) {
        if (formData.weekDays.includes(currentDate.getDay())) {
          datesToAssign.push(currentDate.toISOString().split('T')[0])
        }
        currentDate.setDate(currentDate.getDate() + 1)
      }

      console.log('Fechas a asignar:', datesToAssign)
      console.log('Guard ID:', result.guard.id)
      console.log('Users IDs:', formData.userIds)

      let totalCreated = 0
      for (const userId of formData.userIds) {
        for (const dateStr of datesToAssign) {
          const body = {
            guardId: result.guard.id,
            userId: userId,
            date: dateStr,
            startTime: formData.startTime,
            endTime: formData.endTime,
            status: 'scheduled'
          }
          console.log('Creando asignación:', body)

          const response = await fetch(`${API_URL}/api/assignments`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${token}` },
            body: JSON.stringify(body)
          })

          if (!response.ok) {
            const errorData = await response.json()
            console.error('Error al crear asignación:', errorData)
            throw new Error(errorData.message || 'Error al crear asignación')
          }
          totalCreated++
        }
      }

      const usersCount = formData.userIds.length
      const datesCount = datesToAssign.length
      toast.add({ severity: 'success', summary: 'Éxito', detail: `${totalCreated} asignaciones creadas (${usersCount} usuarios × ${datesCount} fechas)`, life: 5000 })
      await loadAssignments()
    }

    guardDialog.value = false
    resetForm()
  } catch (error) {
    console.error('Error al guardar guardia:', error)
    toast.add({ severity: 'error', summary: 'Error', detail: error.message, life: 5000 })
  } finally {
    saving.value = false
  }
}

onMounted(async () => {
  guardStore.fetchGuards()
  await departmentStore.fetchDepartments()
  departments.value = departmentStore.departments || []
})
</script>
