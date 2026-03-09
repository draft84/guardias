<template>
  <div class="p-4">

    <!-- Header -->
    <div class="flex justify-content-between align-items-center mb-4">
      <div class="flex align-items-center gap-2">
        <i class="pi pi-clock text-3xl text-primary"></i>
        <h2 class="text-2xl font-bold m-0">Turnos</h2>
      </div>
      <Button label="Nuevo Turno" icon="pi pi-plus" @click="openNewShift" />
    </div>

    <!-- DataTable -->
    <DataTable
      :value="shiftStore.shifts"
      dataKey="id"
      :paginator="true"
      :rows="10"
      :filters="filters"
      :loading="shiftStore.loading"
      :globalFilterFields="['name', 'code', 'type']"
      paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
      :rowsPerPageOptions="[5, 10, 25]"
      currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} turnos"
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
      <template #empty>No se encontraron turnos.</template>
      <template #loading>Cargando turnos...</template>

      <Column field="name" header="Nombre" :sortable="true" style="min-width: 12rem" />
      <Column field="code" header="Código" :sortable="true" style="min-width: 8rem" />
      <Column field="startTime" header="Inicio" :sortable="true" style="min-width: 8rem">
        <template #body="{ data }">
          <span class="flex align-items-center gap-2"><i class="pi pi-clock text-primary" />{{ data.startTime }}</span>
        </template>
      </Column>
      <Column field="endTime" header="Fin" :sortable="true" style="min-width: 8rem">
        <template #body="{ data }">
          <span class="flex align-items-center gap-2"><i class="pi pi-clock text-orange-500" />{{ data.endTime }}</span>
        </template>
      </Column>
      <Column field="type" header="Tipo" style="min-width: 10rem">
        <template #body="{ data }">
          <Tag :value="formatType(data.type)" :severity="getTypeSeverity(data.type)" />
        </template>
      </Column>
      <Column field="active" header="Estado" :sortable="true" style="min-width: 8rem">
        <template #body="{ data }">
          <Tag :value="data.active ? 'Activo' : 'Inactivo'" :severity="data.active ? 'success' : 'danger'" />
        </template>
      </Column>
      <Column header="Acciones" :exportable="false" style="min-width: 8rem">
        <template #body="{ data }">
          <Button icon="pi pi-pencil" outlined rounded class="mr-2" @click="editShift(data)" v-tooltip.top="'Editar'" />
          <Button icon="pi pi-trash" outlined rounded severity="danger" @click="confirmDelete(data)" v-tooltip.top="'Eliminar'" />
        </template>
      </Column>
    </DataTable>

    <!-- Dialog -->
    <Dialog v-model:visible="shiftDialog" :style="{width: '550px'}" :header="editingShift ? 'Editar Turno' : 'Nuevo Turno'" :modal="true" class="p-fluid">
      <div class="formgrid grid mt-2">

        <div class="field col-12 md:col-6">
          <label for="shiftName" class="font-bold block mb-2">Nombre *</label>
          <IconField class="w-full">
            <InputIcon><i class="pi pi-tag" /></InputIcon>
            <InputText id="shiftName" v-model.trim="formData.name" :class="{'p-invalid': submitted && !formData.name}" placeholder="Ej. Turno Mañana" class="w-full" />
          </IconField>
          <small class="p-error" v-if="submitted && !formData.name">El nombre es requerido.</small>
        </div>

        <div class="field col-12 md:col-6">
          <label for="shiftCode" class="font-bold block mb-2">Código *</label>
          <IconField class="w-full">
            <InputIcon><i class="pi pi-hashtag" /></InputIcon>
            <InputText id="shiftCode" v-model.trim="formData.code" :class="{'p-invalid': submitted && !formData.code}" placeholder="Ej. TM-01" class="w-full" />
          </IconField>
          <small class="p-error" v-if="submitted && !formData.code">El código es requerido.</small>
        </div>

        <div class="field col-12 md:col-6">
          <label for="startTime" class="font-bold block mb-2">Hora Inicio *</label>
          <IconField class="w-full">
            <InputIcon><i class="pi pi-clock" /></InputIcon>
            <InputText id="startTime" v-model="formData.startTime" type="time" :class="{'p-invalid': submitted && !formData.startTime}" class="w-full" />
          </IconField>
          <small class="p-error" v-if="submitted && !formData.startTime">La hora de inicio es requerida.</small>
        </div>

        <div class="field col-12 md:col-6">
          <label for="endTime" class="font-bold block mb-2">Hora Fin *</label>
          <IconField class="w-full">
            <InputIcon><i class="pi pi-clock" /></InputIcon>
            <InputText id="endTime" v-model="formData.endTime" type="time" :class="{'p-invalid': submitted && !formData.endTime}" class="w-full" />
          </IconField>
          <small class="p-error" v-if="submitted && !formData.endTime">La hora de fin es requerida.</small>
        </div>

        <div class="field col-12">
          <label for="shiftType" class="font-bold block mb-2">Tipo *</label>
          <Dropdown id="shiftType" v-model="formData.type" :options="typeOptions" optionLabel="label" optionValue="value" placeholder="Seleccione un tipo" :class="{'p-invalid': submitted && !formData.type}" class="w-full" />
          <small class="p-error" v-if="submitted && !formData.type">El tipo es requerido.</small>
        </div>

      </div>

      <template #footer>
        <Button label="Cancelar" icon="pi pi-times" text @click="hideDialog" />
        <Button label="Guardar" icon="pi pi-check" :loading="saving" @click="saveShift" />
      </template>
    </Dialog>

  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { FilterMatchMode } from '@primevue/core/api'
import { useShiftStore } from '@/stores/shift.store'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import InputText from 'primevue/inputtext'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import Dialog from 'primevue/dialog'
import Dropdown from 'primevue/dropdown'

const shiftStore = useShiftStore()
const shiftDialog = ref(false)
const editingShift = ref(null)
const submitted = ref(false)
const saving = ref(false)

const filters = ref({
  global: { value: null, matchMode: FilterMatchMode.CONTAINS }
})

const typeOptions = [
  { label: 'Mañana', value: 'morning' },
  { label: 'Tarde', value: 'afternoon' },
  { label: 'Noche', value: 'night' },
  { label: 'Personalizado', value: 'custom' }
]

const formData = reactive({
  name: '',
  code: '',
  startTime: '08:00',
  endTime: '20:00',
  type: 'custom'
})

const formatType = (type) => {
  const map = { morning: 'Mañana', afternoon: 'Tarde', night: 'Noche', custom: 'Personalizado' }
  return map[type] || type
}

const getTypeSeverity = (type) => {
  const map = { morning: 'info', afternoon: 'warning', night: 'secondary', custom: 'contrast' }
  return map[type] || 'info'
}

const hideDialog = () => {
  shiftDialog.value = false
  submitted.value = false
}

const openNewShift = () => {
  formData.name = ''
  formData.code = ''
  formData.startTime = '08:00'
  formData.endTime = '20:00'
  formData.type = 'custom'
  editingShift.value = null
  submitted.value = false
  shiftDialog.value = true
}

const editShift = (shift) => {
  editingShift.value = shift
  formData.name = shift.name
  formData.code = shift.code
  formData.startTime = shift.startTime
  formData.endTime = shift.endTime
  formData.type = shift.type
  submitted.value = false
  shiftDialog.value = true
}

const confirmDelete = async (shift) => {
  if (confirm(`¿Eliminar "${shift.name}"?`)) {
    try {
      await shiftStore.deleteShift(shift.id)
    } catch (error) {
      alert('Error: ' + error.message)
    }
  }
}

const saveShift = async () => {
  submitted.value = true
  if (!formData.name || !formData.code || !formData.startTime || !formData.endTime || !formData.type) return

  saving.value = true
  try {
    if (editingShift.value) {
      await shiftStore.updateShift(editingShift.value.id, formData)
    } else {
      await shiftStore.createShift(formData)
    }
    shiftDialog.value = false
  } catch (error) {
    alert('Error: ' + error.message)
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  shiftStore.fetchShifts()
})
</script>
