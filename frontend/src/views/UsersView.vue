<template>
  <div class="p-4">
    <!-- Header -->
    <div class="flex justify-content-between align-items-center mb-4">
      <div class="flex align-items-center gap-2">
        <i class="pi pi-users text-3xl text-primary"></i>
        <h2 class="text-2xl font-bold m-0">Usuarios</h2>
      </div>
      <Button label="Nuevo Usuario" icon="pi pi-plus" @click="openNewUser()" />
    </div>

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
              <ToggleSwitch v-model="data.active" @change="toggleUserActive(data)" />
              <span :class="data.active ? 'text-green-500' : 'text-red-500'">{{ data.active ? 'Activo' : 'Inactivo' }}</span>
            </div>
          </template>
        </Column>

        <Column header="Acciones" :exportable="false" style="min-width: 8rem">
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

const userStore = useUserStore()
const departmentStore = useDepartmentStore()
const confirm = useConfirm()
const toast = useToast()

const userDialog = ref(false)
const editingUser = ref(null)
const submitted = ref(false)
const saving = ref(false)
const departments = ref([])

const roleOptions = [
  { label: 'Usuario', value: 'ROLE_USER', icon: 'pi pi-user' },
  { label: 'Manager', value: 'ROLE_MANAGER', icon: 'pi pi-briefcase' },
  { label: 'Administrador', value: 'ROLE_ADMIN', icon: 'pi pi-shield' }
]

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    firstName: { value: null, matchMode: FilterMatchMode.STARTS_WITH },
    email: { value: null, matchMode: FilterMatchMode.CONTAINS },
    departmentName: { value: null, matchMode: FilterMatchMode.CONTAINS },
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

onMounted(async () => {
  userStore.fetchUsers()
  userStore.fetchLevels()
  await departmentStore.fetchDepartments()
  departments.value = departmentStore.departments || []
})
</script>
