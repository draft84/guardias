<template>
  <div class="login-view">
    <div class="login-container">
      <div class="login-card surface-card p-6 shadow-5 border-round-xl">

        <div class="text-center mb-5">
          <i class="pi pi-shield text-6xl text-primary mb-3" style="display:block"></i>
          <h1 class="text-3xl font-bold text-900 m-0">Sistema de Guardias</h1>
          <p class="text-500 mt-2 mb-0">Inicia sesión para continuar</p>
        </div>

        <div class="field mb-4">
          <label for="email" class="font-semibold block mb-2 text-700">Email</label>
          <IconField class="w-full">
            <InputIcon><i class="pi pi-envelope" /></InputIcon>
            <InputText
              id="email"
              v-model="email"
              type="email"
              placeholder="admin@example.com"
              class="w-full"
              :class="{'p-invalid': submitted && !email}"
              @keyup.enter="handleLogin"
            />
          </IconField>
          <small class="p-error" v-if="submitted && !email">El email es requerido.</small>
        </div>

        <div class="field mb-5">
          <label for="password" class="font-semibold block mb-2 text-700">Contraseña</label>
          <IconField class="w-full">
            <InputIcon><i class="pi pi-lock" /></InputIcon>
            <InputText
              id="password"
              v-model="password"
              type="password"
              placeholder="••••••••"
              class="w-full"
              :class="{'p-invalid': submitted && !password}"
              @keyup.enter="handleLogin"
            />
          </IconField>
          <small class="p-error" v-if="submitted && !password">La contraseña es requerida.</small>
        </div>

        <Button
          label="Iniciar Sesión"
          icon="pi pi-sign-in"
          class="w-full"
          :loading="loading"
          @click="handleLogin"
          size="large"
        />

        <Message v-if="error" severity="error" class="mt-4 w-full" :closable="false">
          {{ error }}
        </Message>

      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth.store'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Message from 'primevue/message'

const router = useRouter()
const authStore = useAuthStore()

const email = ref('admin@example.com')
const password = ref('admin123')
const loading = ref(false)
const submitted = ref(false)
const error = ref('')

const handleLogin = async () => {
  submitted.value = true
  if (!email.value || !password.value) return

  loading.value = true
  error.value = ''
  try {
    await authStore.login(email.value, password.value)
    router.push('/')
  } catch (err) {
    console.error('Login error in component:', err)
    error.value = err.response?.data?.message || 'Las credenciales proporcionadas no coinciden.'
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.login-view {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, var(--primary-300) 0%, var(--primary-600) 100%);
}
.login-container {
  width: 100%;
  max-width: 440px;
  padding: 20px;
}
.login-card {
  animation: fadeInUp 0.4s ease;
}
@keyframes fadeInUp {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>
