<template>
  <div class="p-4">

    <!-- Header -->
    <div class="flex justify-content-between align-items-center mb-4">
      <div class="flex align-items-center gap-2">
        <i class="pi pi-building text-3xl text-primary"></i>
        <h2 class="text-2xl font-bold m-0">Departamentos</h2>
      </div>
      <div class="flex gap-2">
        <Button 
          label="Exportar" 
          icon="pi pi-download" 
          severity="secondary" 
          outlined 
          @click="exportDepartments" 
          v-tooltip.top="'Exportar departamentos a Excel'"
        />
        <Button 
          label="Importar" 
          icon="pi pi-upload" 
          severity="secondary" 
          outlined 
          @click="showImportDialog" 
          v-tooltip.top="'Importar departamentos desde Excel'"
        />
        <Button label="Nuevo Departamento" icon="pi pi-plus" @click="openNewDepartment" />
      </div>
    </div>

    <!-- Dialog de Importación -->
    <Dialog 
      v-model:visible="importDialog" 
      :style="{width: '500px'}" 
      header="Importar Departamentos desde Excel" 
      :modal="true"
    >
      <div class="flex flex-column gap-4 py-4">
        <Message severity="info" :closable="false">
          <div class="flex flex-column gap-2">
            <p class="m-0 font-bold">Instrucciones:</p>
            <ul class="m-0 pl-4">
              <li>Descarga la plantilla oficial para conocer el formato requerido</li>
              <li>Completa los datos de los departamentos en el archivo Excel</li>
              <li>Los campos requeridos son: Name, Code</li>
              <li>Sube el archivo completado para importar los departamentos</li>
            </ul>
          </div>
        </Message>

        <div class="flex gap-2 justify-content-center">
          <Button 
            label="Descargar Plantilla" 
            icon="pi pi-file-excel" 
            severity="success" 
            outlined 
            @click="downloadTemplate"
            class="flex-1"
          />
        </div>

        <div class="field">
          <label for="excelFile" class="font-bold block mb-2">Archivo Excel *</label>
          <input 
            type="file" 
            id="excelFile" 
            accept=".xlsx,.xls" 
            @change="onFileSelect" 
            class="w-full"
          />
          <small class="p-error block mt-1" v-if="selectedFile && !isValidFileType">
            El archivo debe ser un Excel (.xlsx o .xls)
          </small>
        </div>

        <div v-if="importResult" class="mt-3">
          <Message 
            :severity="importResult.success > 0 ? 'success' : 'error'" 
            :closable="false"
          >
            <div class="flex flex-column gap-2">
              <p class="m-0 font-bold">
                {{ importResult.success }} de {{ importResult.total }} departamentos importados
              </p>
              <div v-if="importResult.errors && importResult.errors.length > 0">
                <p class="m-0 text-sm">Errores encontrados:</p>
                <ul class="m-0 pl-4 text-sm">
                  <li v-for="(err, idx) in importResult.errors" :key="idx">
                    Fila {{ err.row }}: {{ err.error }}
                  </li>
                </ul>
              </div>
            </div>
          </Message>
        </div>
      </div>

      <template #footer>
        <Button label="Cancelar" icon="pi pi-times" text @click="hideImportDialog"/>
        <Button 
          label="Importar" 
          icon="pi pi-upload" 
          :loading="importing" 
          @click="importDepartments" 
          :disabled="!selectedFile"
        />
      </template>
    </Dialog>

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
import { ref, reactive, onMounted, computed } from 'vue'
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
import Message from 'primevue/message'

const departmentStore = useDepartmentStore()
const confirm = useConfirm()
const toast = useToast()

const departmentDialog = ref(false)
const editingDepartment = ref(null)
const submitted = ref(false)
const saving = ref(false)

// Variables para importación
const importDialog = ref(false)
const selectedFile = ref(null)
const importing = ref(false)
const importResult = ref(null)

const isValidFileType = computed(() => {
  if (!selectedFile.value) return true
  const validTypes = [
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'application/vnd.ms-excel'
  ]
  return validTypes.includes(selectedFile.value.type)
})

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

// Funciones de Importación/Exportación
const downloadTemplate = async () => {
  try {
    await departmentStore.downloadTemplate()
    toast.add({
      severity: 'success',
      summary: 'Éxito',
      detail: 'Plantilla descargada correctamente',
      life: 3000
    })
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.message,
      life: 3000
    })
  }
}

const exportDepartments = async () => {
  try {
    await departmentStore.exportDepartments()
    toast.add({
      severity: 'success',
      summary: 'Éxito',
      detail: 'Departamentos exportados correctamente',
      life: 3000
    })
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.message,
      life: 3000
    })
  }
}

const showImportDialog = () => {
  importDialog.value = true
  selectedFile.value = null
  importResult.value = null
}

const hideImportDialog = () => {
  importDialog.value = false
  selectedFile.value = null
  importResult.value = null
}

const onFileSelect = (event) => {
  const file = event.target.files[0]
  if (file) {
    selectedFile.value = file
  }
}

const importDepartments = async () => {
  if (!selectedFile.value) {
    console.error('No hay archivo seleccionado')
    return
  }

  console.log('Archivo seleccionado:', selectedFile.value.name, 'Tipo:', selectedFile.value.type, 'Tamaño:', selectedFile.value.size)

  importing.value = true
  importResult.value = null

  try {
    const data = await departmentStore.importDepartments(selectedFile.value)
    console.log('Respuesta:', data)
    importResult.value = data

    if (data.success > 0) {
      toast.add({
        severity: 'success',
        summary: 'Importación completada',
        detail: `${data.success} de ${data.total} departamentos importados`,
        life: 5000
      })

      // Recargar lista de departamentos
      await departmentStore.fetchDepartments()

      // Cerrar dialog después de un momento
      setTimeout(() => {
        hideImportDialog()
      }, 3000)
    } else {
      toast.add({
        severity: 'error',
        summary: 'Importación fallida',
        detail: data.details ? JSON.stringify(data.details) : 'No se pudo importar ningún departamento',
        life: 8000
      })
    }

  } catch (error) {
    console.error('Error importing departments:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.message,
      life: 5000
    })
  } finally {
    importing.value = false
  }
}

onMounted(() => {
  departmentStore.fetchDepartments()
})
</script>
