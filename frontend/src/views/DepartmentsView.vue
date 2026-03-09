<template>
  <div class="p-4">

    <!-- Header -->
    <div class="flex justify-content-between align-items-center mb-4">
      <div class="flex align-items-center gap-2">
        <i class="pi pi-building text-3xl text-primary"></i>
        <h2 class="text-2xl font-bold m-0">Departamentos</h2>
      </div>
      <Button label="Nuevo Departamento" icon="pi pi-plus" @click="openNewDepartment" />
    </div>

    <!-- DataTable -->
    <DataTable
      :value="departmentStore.departments"
      dataKey="id"
      :paginator="true"
      :rows="10"
      :filters="filters"
      :loading="departmentStore.loading"
      :globalFilterFields="['name', 'code', 'description']"
      paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
      :rowsPerPageOptions="[5, 10, 25]"
      currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} departamentos"
      responsiveLayout="scroll"
    >
      <template #header>
        <div class="flex justify-content-end">
          <IconField>
            <InputIcon>
              <i class="pi pi-search" />
            </InputIcon>
            <InputText v-model="filters['global'].value" placeholder="Búsqueda global" />
          </IconField>
        </div>
      </template>

      <template #empty>No se encontraron departamentos.</template>
      <template #loading>Cargando departamentos...</template>

      <Column field="name" header="Nombre" :sortable="true" style="min-width: 12rem" />
      <Column field="code" header="Código" :sortable="true" style="min-width: 8rem" />
      <Column field="description" header="Descripción" style="min-width: 16rem">
        <template #body="{ data }">{{ data.description || '-' }}</template>
      </Column>
      <Column field="active" header="Estado" :sortable="true" style="min-width: 8rem">
        <template #body="{ data }">
          <Tag :value="data.active ? 'Activo' : 'Inactivo'" :severity="data.active ? 'success' : 'danger'" />
        </template>
      </Column>
      <Column header="Acciones" :exportable="false" style="min-width: 8rem">
        <template #body="{ data }">
          <Button icon="pi pi-pencil" outlined rounded class="mr-2" @click="editDepartment(data)" v-tooltip.top="'Editar'" />
          <Button icon="pi pi-trash" outlined rounded severity="danger" @click="confirmDelete(data)" v-tooltip.top="'Eliminar'" />
        </template>
      </Column>
    </DataTable>

    <!-- Dialog -->
    <Dialog v-model:visible="departmentDialog" :style="{width: '500px'}" :header="editingDepartment ? 'Editar Departamento' : 'Nuevo Departamento'" :modal="true" class="p-fluid">
      <div class="formgrid grid mt-2">
        <div class="field col-12 md:col-6">
          <label for="deptName" class="font-bold block mb-2">Nombre *</label>
          <IconField class="w-full">
            <InputIcon><i class="pi pi-building" /></InputIcon>
            <InputText id="deptName" v-model.trim="formData.name" required :class="{'p-invalid': submitted && !formData.name}" placeholder="Ej. Tecnología" class="w-full" />
          </IconField>
          <small class="p-error" v-if="submitted && !formData.name">El nombre es requerido.</small>
        </div>

        <div class="field col-12 md:col-6">
          <label for="deptCode" class="font-bold block mb-2">Código *</label>
          <IconField class="w-full">
            <InputIcon><i class="pi pi-hashtag" /></InputIcon>
            <InputText id="deptCode" v-model.trim="formData.code" required :class="{'p-invalid': submitted && !formData.code}" placeholder="Ej. TEC-01" class="w-full" />
          </IconField>
          <small class="p-error" v-if="submitted && !formData.code">El código es requerido.</small>
        </div>

        <div class="field col-12">
          <label for="deptDesc" class="font-bold block mb-2">Descripción</label>
          <Textarea id="deptDesc" v-model="formData.description" rows="3" placeholder="Descripción del departamento (opcional)" class="w-full" />
        </div>

        <div class="col-12 mt-2">
          <div class="field-checkbox flex align-items-center p-3 border-round surface-50 border-1 surface-border">
            <Checkbox id="deptActive" v-model="formData.active" :binary="true" />
            <label for="deptActive" class="ml-2 font-bold cursor-pointer text-color-secondary mb-0">Departamento activo</label>
          </div>
        </div>
      </div>

      <template #footer>
        <Button label="Cancelar" icon="pi pi-times" text @click="hideDialog" />
        <Button label="Guardar" icon="pi pi-check" :loading="saving" @click="saveDepartment" />
      </template>
    </Dialog>

  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { FilterMatchMode } from '@primevue/core/api'
import { useConfirm } from 'primevue/useconfirm'
import { useToast } from 'primevue/usetoast'
import { useDepartmentStore } from '@/stores/department.store'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import InputText from 'primevue/inputtext'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import Dialog from 'primevue/dialog'
import Checkbox from 'primevue/checkbox'
import Textarea from 'primevue/textarea'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'

const departmentStore = useDepartmentStore()
const confirm = useConfirm()
const toast = useToast()

const departmentDialog = ref(false)
const editingDepartment = ref(null)
const submitted = ref(false)
const saving = ref(false)

const filters = ref({
  global: { value: null, matchMode: FilterMatchMode.CONTAINS }
})

const formData = reactive({
  name: '',
  code: '',
  description: '',
  active: true
})

const hideDialog = () => {
  departmentDialog.value = false
  submitted.value = false
}

const openNewDepartment = () => {
  formData.name = ''
  formData.code = ''
  formData.description = ''
  formData.active = true
  editingDepartment.value = null
  submitted.value = false
  departmentDialog.value = true
}

const editDepartment = (dept) => {
  editingDepartment.value = dept
  formData.name = dept.name
  formData.code = dept.code
  formData.description = dept.description || ''
  formData.active = dept.active
  submitted.value = false
  departmentDialog.value = true
}

const confirmDelete = (dept) => {
  confirm.require({
    message: `¿Eliminar "${dept.name}"?`,
    header: 'Confirmación',
    icon: 'pi pi-exclamation-triangle',
    acceptClass: 'p-button-danger',
    accept: async () => {
      try {
        await departmentStore.deleteDepartment(dept.id)
        toast.add({ severity: 'success', summary: 'Eliminado', detail: 'Departamento eliminado correctamente', life: 3000 })
      } catch (error) {
        toast.add({ severity: 'error', summary: 'Error', detail: 'Error al eliminar: ' + error.message, life: 3000 })
      }
    }
  })
}

const saveDepartment = async () => {
  submitted.value = true
  if (!formData.name || !formData.code) return

  saving.value = true
  try {
    if (editingDepartment.value) {
      await departmentStore.updateDepartment(editingDepartment.value.id, formData)
      toast.add({ severity: 'success', summary: 'Éxito', detail: 'Departamento actualizado correctamente', life: 3000 })
    } else {
      await departmentStore.createDepartment(formData)
      toast.add({ severity: 'success', summary: 'Éxito', detail: 'Departamento creado correctamente', life: 3000 })
    }
    departmentDialog.value = false
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: error.message, life: 3000 })
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  departmentStore.fetchDepartments()
})
</script>
