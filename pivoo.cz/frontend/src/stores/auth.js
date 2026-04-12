import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useAuthStore = defineStore('auth', () => {
  // Při startu aplikace se podíváme, jestli už je někdo uložený v paměti prohlížeče
  const user = ref(JSON.parse(localStorage.getItem('pivoo_user')) || null)

  const login = (userData) => {
    user.value = userData
    localStorage.setItem('pivoo_user', JSON.stringify(userData))
  }

  const logout = () => {
    user.value = null
    localStorage.removeItem('pivoo_user')
  }

  return { user, login, logout }
})