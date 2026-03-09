import { defineStore } from 'pinia'
import { ref, onMounted } from 'vue'

export const useLayoutStore = defineStore('layout', () => {
  const isDarkTheme = ref(false)

  const initTheme = () => {
    const savedTheme = localStorage.getItem('theme')
    if (savedTheme) {
      isDarkTheme.value = savedTheme === 'dark'
    } else {
      isDarkTheme.value = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches
    }
    updateTheme()
  }

  const toggleTheme = () => {
    isDarkTheme.value = !isDarkTheme.value
    updateTheme()
  }

  const updateTheme = () => {
    if (isDarkTheme.value) {
      document.documentElement.classList.add('p-dark')
      localStorage.setItem('theme', 'dark')
    } else {
      document.documentElement.classList.remove('p-dark')
      localStorage.setItem('theme', 'light')
    }
  }

  return {
    isDarkTheme,
    initTheme,
    toggleTheme
  }
})
