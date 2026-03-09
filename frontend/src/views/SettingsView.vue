<template>
  <div class="p-4">
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
        </TabPanels>
      </Tabs>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth.store'
import { useUserStore } from '@/stores/user.store'
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

const authStore = useAuthStore()
const userStore = useUserStore()
const confirm = useConfirm()
const toast = useToast()
const changingPassword = ref(false)
const submitted = ref(false)
const newLevelName = ref('')
const editingRows = ref([])

onMounted(() => {
  userStore.fetchLevels()
})

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
