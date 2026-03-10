<template>
  <div class="p-4">
    <!-- Header -->
    <div class="flex justify-content-between align-items-center mb-4">
      <div class="flex align-items-center gap-2">
        <i class="pi pi-users text-3xl text-primary"></i>
        <h2 class="text-2xl font-bold m-0">Usuarios</h2>
      </div>
      <div class="flex gap-2" v-if="authStore.isManagerOrAdmin">
        <Button
          label="Exportar"
          icon="pi pi-download"
          severity="secondary"
          outlined
          @click="exportUsers"
          v-tooltip.top="'Exportar usuarios a Excel'"
        />
        <Button
          label="Importar"
          icon="pi pi-upload"
          severity="secondary"
          outlined
          @click="showImportDialog"
          v-tooltip.top="'Importar usuarios desde Excel'"
        />
        <Button label="Nuevo Usuario" icon="pi pi-plus" @click="openNewUser()" />
      </div>
    </div>

    <!-- Dialog de Importación -->
    <Dialog 
      v-model:visible="importDialog" 
      :style="{width: '500px'}" 
      header="Importar Usuarios desde Excel" 
      :modal="true"
    >
      <div class="flex flex-column gap-4 py-4">
        <Message severity="info" :closable="false">
          <div class="flex flex-column gap-2">
            <p class="m-0 font-bold">Instrucciones:</p>
            <ul class="m-0 pl-4">
              <li>Descarga la plantilla oficial para conocer el formato requerido</li>
              <li>Completa los datos de los usuarios en el archivo Excel</li>
              <li>Los campos requeridos son: Email, Password, FirstName, LastName, Department Code</li>
              <li>Sube el archivo completado para importar los usuarios</li>
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
                {{ importResult.success }} de {{ importResult.total }} usuarios importados
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
          @click="importUsers" 
          :disabled="!selectedFile"
        />
      </template>
    </Dialog>

      <Message v-if="userStore.error" severity="error" :closable="false" class="mb-4">{{ userStore.error }}</Message>

      <DataTable 
        ref="dt"
        :value="userStore.users" 
        dataKey="id" 
        :paginator="true" 
        :rows="10" 
        :filters="filters"

        :loading="userStore.loading"
        :globalFilterFields="['firstName', 'lastName', 'email', 'departmentName']"
        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
        :rowsPerPageOptions="[5, 10, 25]"
        currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} usuarios"
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

        <template #empty>
          No se encontraron usuarios.
        </template>
        <template #loading>
          Cargando datos de usuarios. Por favor, espere.
        </template>

        <Column field="firstName" header="Nombre" :sortable="true" style="min-width: 14rem">
          <template #body="{ data }">
            {{ data.firstName }} {{ data.lastName }}
          </template>

        </Column>

        <Column field="email" header="Email" :sortable="true" style="min-width: 14rem">

        </Column>

        <Column field="departmentName" header="Departamento" :sortable="true" style="min-width: 12rem">
          <template #body="{ data }">
            {{ data.departmentName || '-' }}
          </template>

        </Column>

        <Column field="guardLevel" header="Nivel" :sortable="true" style="min-width: 10rem">
          <template #body="{ data }">
            <Tag v-if="data.guardLevel" :value="data.guardLevel" severity="info" />
            <span v-else>-</span>
          </template>
        </Column>

        <Column header="Teléfono" style="min-width: 10rem">
          <template #body="{ data }">
            {{ data.phone || '-' }}
          </template>
        </Column>

        <Column field="active" header="Estado" :sortable="true" style="min-width: 8rem">
          <template #body="{ data }">
            <div class="flex align-items-center gap-2">
              <ToggleSwitch v-if="authStore.isManagerOrAdmin" v-model="data.active" @change="toggleUserActive(data)" />
              <span v-else class="pi" :class="data.active ? 'pi-check-circle text-green-500' : 'pi-times-circle text-red-500'" style="font-size: 1.5rem"></span>
              <span :class="data.active ? 'text-green-500' : 'text-red-500'">{{ data.active ? 'Activo' : 'Inactivo' }}</span>
            </div>
          </template>
        </Column>

        <Column header="Acciones" :exportable="false" style="min-width: 8rem" v-if="authStore.isManagerOrAdmin">
          <template #body="{ data }">
            <Button icon="pi pi-pencil" outlined rounded class="mr-2" @click="editUser(data)" v-tooltip.top="'Editar'" />
            <Button icon="pi pi-trash" outlined rounded severity="danger" @click="confirmDelete(data)" v-tooltip.top="'Eliminar'" />
          </template>
        </Column>
      </DataTable>

    <Dialog v-model:visible="userDialog" :style="{width: '600px'}" :header="editingUser ? 'Detalles del Usuario' : 'Nuevo Usuario'" :modal="true" class="p-fluid">
      
      <div class="formgrid grid mb-4 mt-2">
        <!-- Fila 1: Nombre y Apellido -->
        <div class="field col-12 md:col-6">
          <label for="firstName" class="font-bold block mb-2">Nombre *</label>
          <IconField class="w-full">
            <InputIcon>
              <i class="pi pi-user" />
            </InputIcon>
            <InputText id="firstName" v-model.trim="formData.firstName" required="true" autofocus :class="{'p-invalid': submitted && !formData.firstName}" placeholder="Ej. Juan" class="w-full" />
          </IconField>
          <small class="p-error" v-if="submitted && !formData.firstName">El nombre es requerido.</small>
        </div>

        <div class="field col-12 md:col-6">
          <label for="lastName" class="font-bold block mb-2">Apellido *</label>
          <IconField class="w-full">
            <InputIcon>
              <i class="pi pi-id-card" />
            </InputIcon>
            <InputText id="lastName" v-model.trim="formData.lastName" required="true" :class="{'p-invalid': submitted && !formData.lastName}" placeholder="Ej. Pérez" class="w-full" />
          </IconField>
          <small class="p-error" v-if="submitted && !formData.lastName">El apellido es requerido.</small>
        </div>

        <!-- Fila 2: Email (Full width) -->
        <div class="field col-12 md:col-6">
          <label for="email" class="font-bold block mb-2">Email *</label>
          <IconField class="w-full">
            <InputIcon>
              <i class="pi pi-envelope" />
            </InputIcon>
            <InputText id="email" v-model.trim="formData.email" required="true" type="email" :class="{'p-invalid': submitted && !formData.email}" placeholder="correo@ejemplo.com" class="w-full" />
          </IconField>
          <small class="p-error" v-if="submitted && !formData.email">El email es requerido.</small>
        </div>

        <!-- Fila 3: Phone (Full width) -->
        <div class="field col-12 md:col-6">
          <label for="phone" class="font-bold block mb-2">Teléfono *</label>
          <IconField class="w-full">
            <InputIcon>
              <i class="pi pi-phone" />
            </InputIcon>
            <InputText id="phone" v-model.trim="formData.phone" required="true" type="tel" :class="{'p-invalid': submitted && (!formData.phone || !isPhoneValid)}" placeholder="04121234567" class="w-full" />
          </IconField>
          <small class="p-error" v-if="submitted && !formData.phone">El teléfono es requerido.</small>
          <small class="p-error" v-else-if="submitted && !isPhoneValid">El teléfono solo debe contener números.</small>
        </div>

        <!-- Fila 4: Contraseña (Full width) -->
        <div class="field col-12 md:col-6" v-if="!editingUser">
          <label for="password" class="font-bold block mb-2">Contraseña *</label>
          <IconField class="w-full">
            <InputIcon>
              <i class="pi pi-lock" />
            </InputIcon>
            <InputText id="password" v-model.trim="formData.password" required="true" autocomplete="off" type="password" :class="{'p-invalid': submitted && !formData.password}" placeholder="Mínimo 6 caracteres" class="w-full" />
          </IconField>
          <small class="p-error" v-if="submitted && !formData.password">La contraseña es requerida para usuarios nuevos.</small>
        </div>

        <!-- Fila 5: Departamento y Rol -->
        <div class="field col-12 md:col-6">
          <label for="department" class="font-bold block mb-2">Departamento *</label>
          <Dropdown id="department" v-model="formData.departmentId" :options="departments" optionLabel="name" optionValue="id" placeholder="Seleccione un departamento" :class="{'p-invalid': submitted && !formData.departmentId}" class="w-full">
            <template #value="slotProps">
              <div v-if="slotProps.value" class="flex align-items-center">
                <i class="pi pi-building mr-2" />
                <div>{{ departments.find(d => d.id === slotProps.value)?.name }}</div>
              </div>
              <span v-else>
                {{ slotProps.placeholder }}
              </span>
            </template>
            <template #option="slotProps">
              <div class="flex align-items-center">
                <i class="pi pi-building mr-2" />
                <div>{{ slotProps.option.name }}</div>
              </div>
            </template>
          </Dropdown>
          <small class="p-error" v-if="submitted && !formData.departmentId">El departamento es requerido.</small>
        </div>

        <div class="field col-12 md:col-6">
          <label for="role" class="font-bold block mb-2">Rol *</label>
          <Dropdown id="role" v-model="formData.role" :options="roleOptions" optionLabel="label" optionValue="value" placeholder="Seleccione un rol" :class="{'p-invalid': submitted && !formData.role}" class="w-full">
            <template #value="slotProps">
              <div v-if="slotProps.value" class="flex align-items-center">
                <i :class="roleOptions.find(r => r.value === slotProps.value)?.icon" class="mr-2" />
                <div>{{ roleOptions.find(r => r.value === slotProps.value)?.label }}</div>
              </div>
              <span v-else>
                {{ slotProps.placeholder }}
              </span>
            </template>
            <template #option="slotProps">
              <div class="flex align-items-center">
                <i :class="slotProps.option.icon" class="mr-2" />
                <div>{{ slotProps.option.label }}</div>
              </div>
            </template>
          </Dropdown>
          <small class="p-error" v-if="submitted && !formData.role">El rol es requerido.</small>
        </div>

        <!-- Fila 6: Nivel de Guardia -->
        <div class="field col-12 md:col-6">
          <label for="guardLevel" class="font-bold block mb-2">Nivel de Usuario *</label>
          <Dropdown id="guardLevel" v-model="formData.guardLevelId" :options="userStore.levels" optionLabel="name" optionValue="id" placeholder="Seleccione un nivel" showClear :class="{'p-invalid': submitted && !formData.guardLevelId}" class="w-full">
            <template #value="slotProps">
              <div v-if="slotProps.value" class="flex align-items-center">
                <i class="pi pi-shield mr-2" />
                <div>{{ userStore.levels.find(l => l.id === slotProps.value)?.name }}</div>
              </div>
              <span v-else>
                {{ slotProps.placeholder }}
              </span>
            </template>
            <template #option="slotProps">
              <div class="flex align-items-center">
                <i class="pi pi-shield mr-2" />
                <div>{{ slotProps.option.name }}</div>
              </div>
            </template>
          </Dropdown>
          <small class="p-error" v-if="submitted && !formData.guardLevelId">El nivel de usuario es requerido.</small>
        </div>

        <!-- Fila 6: Activo -->
        <div class="col-12 mt-2">
          <div class="field-checkbox flex align-items-center p-3 border-round surface-50 border-1 surface-border">
            <Checkbox id="active" v-model="formData.active" :binary="true" />
            <label for="active" class="ml-2 font-bold cursor-pointer text-color-secondary mb-0">Habilitar acceso de usuario a la plataforma</label>
          </div>
        </div>
      </div>

      <template #footer>
        <Button label="Cancelar" icon="pi pi-times" text @click="hideDialog"/>
        <Button label="Guardar" icon="pi pi-check" :loading="saving" @click="saveUser" />
      </template>
    </Dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import { FilterMatchMode } from '@primevue/core/api'
import { useConfirm } from 'primevue/useconfirm'
import { useToast } from 'primevue/usetoast'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import InputText from 'primevue/inputtext'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import Dialog from 'primevue/dialog'
import Dropdown from 'primevue/dropdown'
import Checkbox from 'primevue/checkbox'
import Message from 'primevue/message'
import ToggleSwitch from 'primevue/toggleswitch'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'

import { useUserStore } from '@/stores/user.store'
import { useDepartmentStore } from '@/stores/department.store'
import { useAuthStore } from '@/stores/auth.store'

const userStore = useUserStore()
const departmentStore = useDepartmentStore()
const authStore = useAuthStore()
const confirm = useConfirm()
const toast = useToast()

const userDialog = ref(false)
const editingUser = ref(null)
const submitted = ref(false)
const saving = ref(false)
const departments = ref([])

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    firstName: { value: null, matchMode: FilterMatchMode.STARTS_WITH },
    email: { value: null, matchMode: FilterMatchMode.CONTAINS },
    departmentName: { value: null, matchMode: FilterMatchMode.CONTAINS },
})

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

const formData = reactive({
  firstName: '',
  lastName: '',
  email: '',
  phone: '',
  password: '',
  departmentId: null,
  guardLevelId: null,
  role: 'ROLE_USER',
  active: true
})

// Roles dinámicos desde el store
const roleOptions = computed(() => {
  return userStore.roles.map(role => ({
    label: role.name.replace('ROLE_', '').replace('_', ' '),
    value: role.name,
    icon: getRoleIcon(role.name)
  }))
})

const getRoleIcon = (roleName) => {
  switch (roleName) {
    case 'ROLE_ADMIN': return 'pi pi-shield'
    case 'ROLE_MANAGER': return 'pi pi-briefcase'
    default: return 'pi pi-user'
  }
}

const isPhoneValid = computed(() => {
  if (!formData.phone) return true
  // Permite opcionalmente un '+' al inicio, y luego solo números y espacios
  return /^\+?[0-9\s]+$/.test(formData.phone)
})

const hideDialog = () => {
  userDialog.value = false
  submitted.value = false
}

const formatRole = (role) => {
  return role.replace('ROLE_', '').toLowerCase().charAt(0).toUpperCase() + role.replace('ROLE_', '').toLowerCase().slice(1)
}

const getRoleSeverity = (role) => {
  switch (role) {
    case 'ROLE_ADMIN': return 'danger'
    case 'ROLE_MANAGER': return 'warning'
    default: return 'info'
  }
}

const openNewUser = () => {
  formData.firstName = ''
  formData.lastName = ''
  formData.email = ''
  formData.phone = ''
  formData.password = ''
  formData.departmentId = null
  formData.guardLevelId = null
  formData.role = 'ROLE_USER'
  formData.active = true
  
  editingUser.value = null
  submitted.value = false
  userDialog.value = true
}

const editUser = (user) => {
  editingUser.value = user
  formData.firstName = user.firstName
  formData.lastName = user.lastName
  formData.email = user.email
  formData.phone = user.phone || ''
  formData.password = ''
  formData.departmentId = user.department || null
  formData.guardLevelId = user.guardLevelId || null
  formData.role = user.roles?.[0] || 'ROLE_USER'
  formData.active = user.active !== undefined ? user.active : true
  
  submitted.value = false
  userDialog.value = true
}

const toggleUserActive = async (user) => {
  try {
    await userStore.updateUser(user.id, { active: user.active })
    toast.add({ severity: 'success', summary: user.active ? 'Activado' : 'Desactivado', detail: `${user.firstName} ${user.lastName} ha sido ${user.active ? 'activado' : 'desactivado'}`, life: 3000 })
  } catch (error) {
    user.active = !user.active
    toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo cambiar el estado del usuario', life: 3000 })
  }
}

const confirmDelete = (user) => {
  confirm.require({
    message: `¿Estás seguro de eliminar a "${user.firstName} ${user.lastName}"?`,
    header: 'Confirmación',
    icon: 'pi pi-exclamation-triangle',
    acceptClass: 'p-button-danger',
    accept: async () => {
      try {
        await userStore.deleteUser(user.id)
        toast.add({ severity: 'success', summary: 'Éxito', detail: 'Usuario eliminado correctamente', life: 3000 })
      } catch (error) {
        toast.add({ severity: 'error', summary: 'Error', detail: 'Error al eliminar: ' + error.message, life: 3000 })
      }
    }
  })
}

const saveUser = async () => {
  submitted.value = true

  const isNewUser = !editingUser.value
  const missingFields = !formData.firstName || !formData.lastName || !formData.email ||
    !formData.phone || !isPhoneValid.value || !formData.role || !formData.departmentId || !formData.guardLevelId ||
    (isNewUser && !formData.password)

  if (missingFields) {
    return
  }

  saving.value = true
  try {
    const payload = {
      firstName: formData.firstName,
      lastName: formData.lastName,
      email: formData.email,
      phone: formData.phone || null,
      active: formData.active,
      roles: [formData.role]
    }
    
    if (formData.password) {
      payload.password = formData.password
    }
    
    if (formData.departmentId) {
      payload.departmentId = formData.departmentId
    } else {
      payload.departmentId = null
    }

    if (formData.guardLevelId) {
      payload.guardLevelId = formData.guardLevelId
    } else {
      payload.guardLevelId = null
    }
    
    if (editingUser.value) {
      await userStore.updateUser(editingUser.value.id, payload)
      toast.add({ severity: 'success', summary: 'Actualizado', detail: 'Usuario actualizado correctamente', life: 3000 })
    } else {
      await userStore.createUser(payload)
      toast.add({ severity: 'success', summary: 'Creado', detail: 'Usuario creado correctamente', life: 3000 })
    }
    
    userDialog.value = false
    await userStore.fetchUsers()
  } catch (error) {
    console.error('Error saving user:', error)
    toast.add({ severity: 'error', summary: 'Error', detail: error.message, life: 3000 })
  } finally {
    saving.value = false
  }
}

// Funciones de Importación/Exportación
const API_URL = 'http://localhost:8000'

const downloadTemplate = async () => {
  try {
    const token = localStorage.getItem('token')
    console.log('Downloading template with token:', token ? 'Token presente' : 'Token NO presente')
    
    const response = await fetch(`${API_URL}/api/users/export-template`, {
      method: 'GET',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
      }
    })

    console.log('Response status:', response.status)

    if (!response.ok) {
      const errorData = await response.json().catch(() => ({}))
      console.error('Error response:', errorData)
      throw new Error(errorData.message || 'Error al descargar la plantilla')
    }

    const blob = await response.blob()
    console.log('Blob received:', blob.size, 'bytes')
    
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = 'plantilla_usuarios.xlsx'
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    window.URL.revokeObjectURL(url)
    
    toast.add({
      severity: 'success',
      summary: 'Éxito',
      detail: 'Plantilla descargada correctamente',
      life: 3000
    })
  } catch (error) {
    console.error('Error downloading template:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.message || 'No se pudo descargar la plantilla',
      life: 5000
    })
  }
}

const exportUsers = async () => {
  try {
    const token = localStorage.getItem('token')
    console.log('Exporting users with token:', token ? 'Token presente' : 'Token NO presente')
    
    const response = await fetch(`${API_URL}/api/users/export`, {
      method: 'GET',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
      }
    })

    console.log('Response status:', response.status)

    if (!response.ok) {
      const errorData = await response.json().catch(() => ({}))
      console.error('Error response:', errorData)
      throw new Error(errorData.message || 'Error al exportar usuarios')
    }

    const blob = await response.blob()
    console.log('Blob received:', blob.size, 'bytes')
    
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = `usuarios_export_${new Date().toISOString().split('T')[0]}.xlsx`
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    window.URL.revokeObjectURL(url)
    
    toast.add({
      severity: 'success',
      summary: 'Éxito',
      detail: 'Usuarios exportados correctamente',
      life: 3000
    })
  } catch (error) {
    console.error('Error exporting users:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.message || 'No se pudieron exportar los usuarios',
      life: 5000
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

const importUsers = async () => {
  if (!selectedFile.value) {
    console.error('No hay archivo seleccionado')
    return
  }

  console.log('Archivo seleccionado:', selectedFile.value.name, 'Tipo:', selectedFile.value.type, 'Tamaño:', selectedFile.value.size)

  importing.value = true
  importResult.value = null

  try {
    const token = localStorage.getItem('token')
    const formDataObj = new FormData()
    formDataObj.append('file', selectedFile.value)

    console.log('Enviando petición a:', `${API_URL}/api/users/import`)

    const response = await fetch(`${API_URL}/api/users/import`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`
      },
      body: formDataObj
    })

    console.log('Respuesta status:', response.status)
    const data = await response.json()
    console.log('Respuesta data:', data)

    if (!response.ok) {
      throw new Error(data.error || 'Error al importar usuarios')
    }

    importResult.value = data

    if (data.success > 0) {
      toast.add({
        severity: 'success',
        summary: 'Importación completada',
        detail: `${data.success} de ${data.total} usuarios importados`,
        life: 5000
      })

      // Recargar lista de usuarios
      await userStore.fetchUsers()

      // Cerrar dialog después de un momento
      setTimeout(() => {
        hideImportDialog()
      }, 3000)
    } else {
      toast.add({
        severity: 'error',
        summary: 'Importación fallida',
        detail: data.details ? JSON.stringify(data.details) : 'No se pudo importar ningún usuario',
        life: 8000
      })
    }

  } catch (error) {
    console.error('Error importing users:', error)
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

onMounted(async () => {
  userStore.fetchUsers()
  userStore.fetchLevels()
  userStore.fetchRoles()
  await departmentStore.fetchDepartments()
  departments.value = departmentStore.departments || []
})
</script>
