<template>
  <div class="p-4">
    <!-- Mensaje de Acceso Denegado -->
    <Message v-if="!authStore.isManagerOrAdmin" severity="error" :closable="false" class="mb-4">
      <template #icon>
        <i class="pi pi-lock"></i>
      </template>
      <div class="flex flex-column gap-2">
        <p class="m-0 font-bold">Acceso Restringido</p>
        <p class="m-0">Esta sección está reservada solo para usuarios con rol <strong>MANAGER</strong> o <strong>ADMIN</strong>.</p>
      </div>
    </Message>

    <!-- Header -->
    <div class="flex align-items-center gap-2 mb-4">
      <i class="pi pi-cog text-3xl text-primary"></i>
      <h2 class="text-2xl font-bold m-0">Configuración</h2>
    </div>

    <div class="surface-card border-round-xl shadow-2">
      <Tabs value="0">
        <TabList>
          <Tab value="0">
            <i class="pi pi-user mr-2"></i>
            <span>Perfil y Seguridad</span>
          </Tab>
          <Tab value="1">
            <i class="pi pi-shield mr-2"></i>
            <span>Configuración de Guardias</span>
          </Tab>
          <Tab value="2">
            <i class="pi pi-key mr-2"></i>
            <span>Roles</span>
          </Tab>
          <Tab value="3">
            <i class="pi pi-calendar mr-2"></i>
            <span>Turnos</span>
          </Tab>
        </TabList>

        <TabPanels>
          <!-- PANEL PERFIL -->
          <TabPanel value="0">
            <div class="grid p-2">
              <div class="col-12 md:col-6">
                <h3 class="text-lg font-bold mb-4">Datos del Perfil</h3>
                <div class="flex align-items-center gap-4 p-3 border-1 border-round surface-border">
                  <div class="flex align-items-center justify-content-center border-round-full bg-primary" style="width:64px; height:64px;">
                    <i class="pi pi-user text-3xl text-white"></i>
                  </div>
                  <div>
                    <p class="text-900 font-bold text-xl m-0">{{ authStore.userName }}</p>
                    <p class="text-500 mt-1 mb-2">{{ authStore.user?.email }}</p>
                    <Tag v-if="authStore.isAdmin" value="Administrador" severity="danger" icon="pi pi-shield" />
                    <Tag v-else value="Usuario" severity="info" icon="pi pi-user" />
                  </div>
                </div>
              </div>

              <div class="col-12 md:col-6">
                <h3 class="text-lg font-bold mb-4">Cambiar Contraseña</h3>
                <div class="flex flex-column gap-3">
                  <div class="field m-0">
                    <label class="font-semibold block mb-2">Contraseña Actual *</label>
                    <IconField class="w-full">
                      <InputIcon><i class="pi pi-lock" /></InputIcon>
                      <InputText v-model="passwordForm.current" type="password" class="w-full" :class="{'p-invalid': submitted && !passwordForm.current}" />
                    </IconField>
                    <small class="p-error" v-if="submitted && !passwordForm.current">La contraseña actual es requerida.</small>
                  </div>
                  <div class="field m-0">
                    <label class="font-semibold block mb-2">Nueva Contraseña *</label>
                    <IconField class="w-full">
                      <InputIcon><i class="pi pi-lock-open" /></InputIcon>
                      <InputText v-model="passwordForm.new" type="password" class="w-full" :class="{'p-invalid': submitted && !passwordForm.new}" />
                    </IconField>
                    <small class="p-error" v-if="submitted && !passwordForm.new">La nueva contraseña es requerida.</small>
                  </div>
                  <div class="field m-0">
                    <label class="font-semibold block mb-2">Confirmar Nueva Contraseña *</label>
                    <IconField class="w-full">
                      <InputIcon><i class="pi pi-lock-open" /></InputIcon>
                      <InputText v-model="passwordForm.confirm" type="password" class="w-full" :class="{'p-invalid': submitted && !passwordForm.confirm}" />
                    </IconField>
                    <small class="p-error" v-if="submitted && !passwordForm.confirm">Debes confirmar la contraseña.</small>
                  </div>
                  <Button label="Actualizar Contraseña" icon="pi pi-check" :loading="changingPassword" @click="changePassword" class="mt-2" />
                </div>
              </div>
            </div>
          </TabPanel>

          <!-- PANEL GUARDIAS -->
          <TabPanel value="1">
            <div class="p-2 flex flex-column align-items-center">
              <div class="w-full lg:w-8">
                <div class="flex flex-column sm:flex-row align-items-center justify-content-between mb-4 gap-3">
                  <div>
                    <h3 class="text-lg font-bold m-0">Niveles de Usuario</h3>
                    <p class="text-500 text-sm m-0">Define los niveles para los usuarios.</p>
                  </div>
                  <div class="flex gap-2 w-full sm:w-auto">
                    <InputText v-model="newLevelName" placeholder="Nombre del nivel" @keyup.enter="addLevel" class="flex-1" />
                    <Button label="Agregar" icon="pi pi-plus" @click="addLevel" :disabled="!newLevelName" />
                  </div>
                </div>

                <DataTable :value="userStore.levels" v-model:editingRows="editingRows" class="p-datatable-sm border-1 surface-border border-round overflow-hidden" :loading="userStore.loading" editMode="row" @row-edit-save="onRowEditSave">
                  <Column field="name" header="Nombre del Nivel">
                    <template #editor="{ data, field }">
                      <InputText v-model="data[field]" autofocus class="w-full" />
                    </template>
                  </Column>
                  <Column :rowEditor="true" style="width: 3rem" bodyStyle="text-align:center"></Column>
                  <Column header="Acciones" style="width: 3rem" class="text-center">
                    <template #body="slotProps">
                      <Button 
                        icon="pi pi-trash" 
                        severity="danger" 
                        text 
                        rounded 
                        @click="removeLevel(slotProps.data)" 
                        :title="slotProps.data.isUsed ? 'Este nivel está en uso y no se puede eliminar' : 'Eliminar nivel'"
                        :disabled="slotProps.data.isUsed"
                      />
                    </template>
                  </Column>
                </DataTable>

                <Message severity="info" :closable="false" class="mt-4">
                  Los niveles en uso (asignados a usuarios) no pueden ser eliminados.
                </Message>
              </div>
            </div>
          </TabPanel>

          <!-- PANEL ROLES -->
          <TabPanel value="2">
            <div class="p-2 flex flex-column align-items-center">
              <div class="w-full lg:w-8">
                <div class="flex flex-column sm:flex-row align-items-center justify-content-between mb-4 gap-3">
                  <div>
                    <h3 class="text-lg font-bold m-0">Roles del Sistema</h3>
                    <p class="text-500 text-sm m-0">Define los roles disponibles para los usuarios.</p>
                  </div>
                  <div class="flex gap-2 w-full sm:w-auto">
                    <InputText v-model="newRoleName" placeholder="Nombre del rol (ej: ROLE_SUPERVISOR)" @keyup.enter="addRole" class="flex-1" />
                    <InputText v-model="newRoleDescription" placeholder="Descripción" @keyup.enter="addRole" class="flex-1" />
                    <Button label="Agregar" icon="pi pi-plus" @click="addRole" :disabled="!newRoleName" />
                  </div>
                </div>

                <DataTable :value="userStore.roles" v-model:editingRows="editingRolesRows" class="p-datatable-sm border-1 surface-border border-round overflow-hidden" :loading="userStore.loading" editMode="row" @row-edit-save="onRoleEditSave">
                  <Column field="name" header="Nombre del Rol">
                    <template #body="{ data }">
                      <Tag :value="data.name" :severity="getRoleSeverity(data.name)" />
                    </template>
                    <template #editor="{ data, field }">
                      <InputText v-model="data[field]" autofocus class="w-full" placeholder="ROLE_XYZ" />
                    </template>
                  </Column>
                  <Column field="description" header="Descripción">
                    <template #editor="{ data, field }">
                      <InputText v-model="data[field]" autofocus class="w-full" />
                    </template>
                  </Column>
                  <Column field="isUsed" header="Estado" style="min-width: 8rem">
                    <template #body="{ data }">
                      <Tag :value="data.isUsed ? 'En uso' : 'Disponible'" :severity="data.isUsed ? 'success' : 'secondary'" />
                    </template>
                  </Column>
                  <Column :rowEditor="true" style="width: 3rem" bodyStyle="text-align:center"></Column>
                  <Column header="Acciones" style="width: 3rem" class="text-center">
                    <template #body="slotProps">
                      <Button
                        icon="pi pi-trash"
                        severity="danger"
                        text
                        rounded
                        @click="removeRole(slotProps.data)"
                        :title="slotProps.data.isUsed ? 'Este rol está en uso y no se puede eliminar' : 'Eliminar rol'"
                        :disabled="slotProps.data.isUsed"
                      />
                    </template>
                  </Column>
                </DataTable>

                <Message severity="info" :closable="false" class="mt-4">
                  <i class="pi pi-info-circle mr-2"></i>
                  Los roles en uso (asignados a usuarios) no pueden ser eliminados.
                </Message>
              </div>
            </div>
          </TabPanel>

          <!-- PANEL TURNOS -->
          <TabPanel value="3">
            <div class="p-4">
              <!-- Header con título y botón -->
              <div class="flex align-items-center justify-content-between mb-4">
                <div>
                  <h3 class="text-xl font-bold m-0">Turnos del Sistema</h3>
                  <p class="text-500 text-sm m-0 mt-1">Define los turnos disponibles (mañana, tarde, noche, etc.).</p>
                </div>
              </div>

              <!-- Formulario de agregado -->
              <div class="surface-50 border-round-xl p-4 mb-4">
                <div class="grid gap-3 align-items-end">
                  <div class="col-12 md:col-3">
                    <label class="block text-sm font-semibold mb-2">Nombre</label>
                    <InputText v-model="newShiftName" placeholder="Ej: Turno Mañana" class="w-full" />
                  </div>
                  <div class="col-12 md:col-2">
                    <label class="block text-sm font-semibold mb-2">Código</label>
                    <InputText v-model="newShiftCode" placeholder="MORNING" class="w-full" />
                  </div>
                  <div class="col-12 md:col-2">
                    <label class="block text-sm font-semibold mb-2">Hora Inicio</label>
                    <InputText v-model="newShiftStartTime" type="time" class="w-full" />
                  </div>
                  <div class="col-12 md:col-2">
                    <label class="block text-sm font-semibold mb-2">Hora Fin</label>
                    <InputText v-model="newShiftEndTime" type="time" class="w-full" />
                  </div>
                  <div class="col-12 md:col-2">
                    <label class="block text-sm font-semibold mb-2">Tipo</label>
                    <Select v-model="newShiftType" :options="shiftTypes" optionLabel="label" optionValue="value" placeholder="Tipo" class="w-full" />
                  </div>
                  <div class="col-12 md:col-12">
                    <label class="block text-sm font-semibold mb-2">Descripción (opcional)</label>
                    <InputText v-model="newShiftDescription" placeholder="Descripción del turno" class="w-full" />
                  </div>
                  <div class="col-12 text-right">
                    <Button
                      label="Agregar Turno"
                      icon="pi pi-plus"
                      @click="addShift"
                      :disabled="!newShiftName || !newShiftCode || !newShiftStartTime || !newShiftEndTime"
                      severity="primary"
                    />
                  </div>
                </div>
              </div>

              <!-- Tabla de turnos -->
              <div>
                <DataTable :value="shiftStore.shifts" v-model:editingRows="editingShiftsRows" class="p-datatable-sm border-1 surface-border border-round overflow-hidden" :loading="shiftStore.loading" editMode="row" @row-edit-save="onShiftEditSave">
                  <Column field="name" header="Nombre">
                    <template #editor="{ data, field }">
                      <InputText v-model="data[field]" autofocus class="w-full" />
                    </template>
                  </Column>
                  <Column field="code" header="Código">
                    <template #body="{ data }">
                      <Tag :value="data.code" severity="secondary" />
                    </template>
                    <template #editor="{ data, field }">
                      <InputText v-model="data[field]" autofocus class="w-full" placeholder="Ej: MORNING" />
                    </template>
                  </Column>
                  <Column field="startTime" header="Inicio">
                    <template #editor="{ data, field }">
                      <InputText v-model="data[field]" type="time" autofocus class="w-full" />
                    </template>
                  </Column>
                  <Column field="endTime" header="Fin">
                    <template #editor="{ data, field }">
                      <InputText v-model="data[field]" type="time" autofocus class="w-full" />
                    </template>
                  </Column>
                  <Column field="type" header="Tipo">
                    <template #body="{ data }">
                      <Tag :value="getShiftTypeLabel(data.type)" :severity="getShiftTypeSeverity(data.type)" />
                    </template>
                    <template #editor="{ data, field }">
                      <Select v-model="data[field]" :options="shiftTypes" optionLabel="label" optionValue="value" class="w-full" />
                    </template>
                  </Column>
                  <Column field="description" header="Descripción">
                    <template #editor="{ data, field }">
                      <InputText v-model="data[field]" autofocus class="w-full" />
                    </template>
                  </Column>
                  <Column field="isUsed" header="Estado" style="min-width: 8rem">
                    <template #body="{ data }">
                      <Tag :value="data.isUsed ? 'En uso' : 'Disponible'" :severity="data.isUsed ? 'success' : 'secondary'" />
                    </template>
                  </Column>
                  <Column :rowEditor="true" style="width: 3rem" bodyStyle="text-align:center"></Column>
                  <Column header="Acciones" style="width: 3rem" class="text-center">
                    <template #body="slotProps">
                      <Button
                        icon="pi pi-trash"
                        severity="danger"
                        text
                        rounded
                        @click="removeShift(slotProps.data)"
                        :title="slotProps.data.isUsed ? 'Este turno está en uso y no se puede eliminar' : 'Eliminar turno'"
                        :disabled="slotProps.data.isUsed"
                      />
                    </template>
                  </Column>
                </DataTable>

                <Message severity="info" :closable="false" class="mt-4">
                  <i class="pi pi-info-circle mr-2"></i>
                  Los turnos en uso no pueden ser eliminados.
                </Message>
              </div>
            </div>
          </TabPanel>
        </TabPanels>
      </Tabs>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth.store'
import { useUserStore } from '@/stores/user.store'
import { useShiftStore } from '@/stores/shift.store'
import { useConfirm } from 'primevue/useconfirm'
import { useToast } from 'primevue/usetoast'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Tag from 'primevue/tag'
import Message from 'primevue/message'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import Tabs from 'primevue/tabs'
import TabList from 'primevue/tablist'
import Tab from 'primevue/tab'
import TabPanels from 'primevue/tabpanels'
import TabPanel from 'primevue/tabpanel'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Select from 'primevue/select'

const authStore = useAuthStore()
const userStore = useUserStore()
const shiftStore = useShiftStore()
const confirm = useConfirm()
const toast = useToast()
const changingPassword = ref(false)
const submitted = ref(false)
const newLevelName = ref('')
const newRoleName = ref('')
const newRoleDescription = ref('')
const editingRows = ref([])
const editingRolesRows = ref([])
const editingShiftsRows = ref([])

// Variables para Turnos
const newShiftName = ref('')
const newShiftCode = ref('')
const newShiftStartTime = ref('')
const newShiftEndTime = ref('')
const newShiftType = ref('custom')
const newShiftDescription = ref('')

const shiftTypes = [
  { label: 'Mañana', value: 'morning' },
  { label: 'Tarde', value: 'afternoon' },
  { label: 'Noche', value: 'night' },
  { label: 'Todo el día', value: 'full_day' },
  { label: 'Personalizado', value: 'custom' }
]

const API_URL = 'http://localhost:10000'

onMounted(() => {
  userStore.fetchLevels()
  userStore.fetchRoles()
  shiftStore.fetchShifts()
})

const getRoleSeverity = (roleName) => {
  switch (roleName) {
    case 'ROLE_ADMIN': return 'danger'
    case 'ROLE_MANAGER': return 'warning'
    case 'ROLE_USER': return 'info'
    default: return 'secondary'
  }
}

const addLevel = async () => {
  if (!newLevelName.value.trim()) return
  try {
    await userStore.createLevel(newLevelName.value.trim())
    newLevelName.value = ''
    toast.add({ severity: 'success', summary: 'Éxito', detail: 'Nivel creado correctamente', life: 3000 })
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: error.message, life: 3000 })
  }
}

const onRowEditSave = async (event) => {
  let { data, newData } = event
  if (newData.name.trim().length > 0) {
    try {
      await userStore.updateLevel(newData.id, newData.name.trim())
      toast.add({ severity: 'success', summary: 'Actualizado', detail: 'Nivel actualizado correctamente', life: 3000 })
    } catch (error) {
      toast.add({ severity: 'error', summary: 'Error', detail: error.message, life: 3000 })
    }
  } else {
    toast.add({ severity: 'error', summary: 'Error', detail: 'El nombre no puede estar vacío', life: 3000 })
  }
}

const removeLevel = (level) => {
  if (level.isUsed) {
    toast.add({ severity: 'warn', summary: 'No permitido', detail: 'Nivel en uso por usuarios', life: 3000 })
    return
  }

  confirm.require({
    message: `¿Estás seguro de eliminar el nivel "${level.name}"?`,
    header: 'Confirmación',
    icon: 'pi pi-exclamation-triangle',
    acceptClass: 'p-button-danger',
    accept: async () => {
      try {
        await userStore.deleteLevel(level.id)
        toast.add({ severity: 'success', summary: 'Eliminado', detail: 'Nivel eliminado correctamente', life: 3000 })
      } catch (error) {
        toast.add({ severity: 'error', summary: 'Error', detail: error.message, life: 3000 })
      }
    }
  })
}

// Funciones para Roles
const addRole = async () => {
  if (!newRoleName.value.trim()) return
  try {
    await userStore.createRole(newRoleName.value.trim().toUpperCase().replace(/\s/g, '_'), newRoleDescription.value.trim())
    newRoleName.value = ''
    newRoleDescription.value = ''
    toast.add({ severity: 'success', summary: 'Éxito', detail: 'Rol creado correctamente', life: 3000 })
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: error.message, life: 3000 })
  }
}

const onRoleEditSave = async (event) => {
  let { data, newData } = event
  if (newData.name.trim().length > 0) {
    try {
      const roleName = newData.name.trim().toUpperCase().replace(/\s/g, '_')
      await userStore.updateRole(newData.id, roleName, newData.description?.trim())
      toast.add({ severity: 'success', summary: 'Actualizado', detail: 'Rol actualizado correctamente', life: 3000 })
    } catch (error) {
      toast.add({ severity: 'error', summary: 'Error', detail: error.message, life: 3000 })
    }
  } else {
    toast.add({ severity: 'error', summary: 'Error', detail: 'El nombre no puede estar vacío', life: 3000 })
  }
}

const removeRole = (role) => {
  if (role.isUsed) {
    toast.add({ severity: 'warn', summary: 'No permitido', detail: 'Rol en uso por usuarios', life: 3000 })
    return
  }

  confirm.require({
    message: `¿Estás seguro de eliminar el rol "${role.name}"?`,
    header: 'Confirmación',
    icon: 'pi pi-exclamation-triangle',
    acceptClass: 'p-button-danger',
    accept: async () => {
      try {
        await userStore.deleteRole(role.id)
        toast.add({ severity: 'success', summary: 'Eliminado', detail: 'Rol eliminado correctamente', life: 3000 })
      } catch (error) {
        toast.add({ severity: 'error', summary: 'Error', detail: error.message, life: 3000 })
      }
    }
  })
}

// Funciones para Turnos
const getShiftTypeLabel = (type) => {
  const found = shiftTypes.find(t => t.value === type)
  return found ? found.label : type
}

const getShiftTypeSeverity = (type) => {
  switch (type) {
    case 'morning': return 'success'
    case 'afternoon': return 'warning'
    case 'night': return 'contrast'
    case 'full_day': return 'info'
    default: return 'secondary'
  }
}

const addShift = async () => {
  if (!newShiftName.value.trim() || !newShiftCode.value.trim()) return
  try {
    await shiftStore.createShift(
      newShiftName.value.trim(),
      newShiftCode.value.trim().toUpperCase().replace(/\s/g, '_'),
      newShiftStartTime.value,
      newShiftEndTime.value,
      newShiftType.value,
      '#3498db',
      newShiftDescription.value.trim()
    )
    newShiftName.value = ''
    newShiftCode.value = ''
    newShiftStartTime.value = ''
    newShiftEndTime.value = ''
    newShiftType.value = 'custom'
    newShiftDescription.value = ''
    toast.add({ severity: 'success', summary: 'Éxito', detail: 'Turno creado correctamente', life: 3000 })
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: error.message, life: 3000 })
  }
}

const onShiftEditSave = async (event) => {
  let { data, newData } = event
  if (newData.name.trim().length > 0) {
    try {
      await shiftStore.updateShift(
        newData.id,
        newData.name.trim(),
        newData.code.trim().toUpperCase().replace(/\s/g, '_'),
        newData.startTime,
        newData.endTime,
        newData.type,
        '#3498db',
        newData.description
      )
      toast.add({ severity: 'success', summary: 'Actualizado', detail: 'Turno actualizado correctamente', life: 3000 })
    } catch (error) {
      toast.add({ severity: 'error', summary: 'Error', detail: error.message, life: 3000 })
    }
  } else {
    toast.add({ severity: 'error', summary: 'Error', detail: 'El nombre no puede estar vacío', life: 3000 })
  }
}

const removeShift = (shift) => {
  if (shift.isUsed) {
    toast.add({ severity: 'warn', summary: 'No permitido', detail: 'Turno en uso', life: 3000 })
    return
  }

  confirm.require({
    message: `¿Estás seguro de eliminar el turno "${shift.name}"?`,
    header: 'Confirmación',
    icon: 'pi pi-exclamation-triangle',
    acceptClass: 'p-button-danger',
    accept: async () => {
      try {
        await shiftStore.deleteShift(shift.id)
        toast.add({ severity: 'success', summary: 'Eliminado', detail: 'Turno eliminado correctamente', life: 3000 })
      } catch (error) {
        toast.add({ severity: 'error', summary: 'Error', detail: error.message, life: 3000 })
      }
    }
  })
}

const passwordForm = reactive({
  current: '',
  new: '',
  confirm: ''
})

const changePassword = async () => {
  submitted.value = true

  // Validar campos vacíos en el front primero
  if (!passwordForm.current || !passwordForm.new || !passwordForm.confirm) {
    return
  }

  // Validar longitud mínima front
  if (passwordForm.new.length < 6) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'La nueva contraseña debe tener al menos 6 caracteres', life: 3000 })
    return
  }

  if (passwordForm.new !== passwordForm.confirm) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Las contraseñas no coinciden', life: 3000 })
    return
  }
  
  changingPassword.value = true
  try {
    await authStore.changePassword(passwordForm.current, passwordForm.new, passwordForm.confirm)
    
    toast.add({ severity: 'success', summary: 'Éxito', detail: 'Contraseña cambiada correctamente', life: 3000 })
    
    // Limpiar form
    passwordForm.current = ''
    passwordForm.new = ''
    passwordForm.confirm = ''
    submitted.value = false
  } catch (error) {
    const errorMsg = error.response?.data?.error || 'No se pudo cambiar la contraseña'
    toast.add({ severity: 'error', summary: 'Error en el servidor', detail: errorMsg, life: 4000 })
  } finally {
    changingPassword.value = false
  }
}
</script>
