<template>
  <router-view />
  <ConfirmDialog />
  <Toast />
</template>

<script setup>
import { onMounted } from 'vue'
import { useLayoutStore } from '@/stores/layout.store'

const layoutStore = useLayoutStore()

onMounted(() => {
  layoutStore.initTheme()
})
</script>

<style>
/* Estilos globales */
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body {
  font-family: var(--font-family);
  background: var(--surface-ground);
  color: var(--text-color);
}
</style>
