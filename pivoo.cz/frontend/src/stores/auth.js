import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useAuthStore = defineStore('auth', () => {
  const user = ref(JSON.parse(localStorage.getItem('pivoo_user')) || null)
  const token = ref(localStorage.getItem('pivoo_token') || null)

  const login = (userData, authToken) => {
    user.value = userData
    token.value = authToken
    localStorage.setItem('pivoo_user', JSON.stringify(userData))
    localStorage.setItem('pivoo_token', authToken)
  }

  const updateUser = (newData) => {
    user.value = { ...user.value, ...newData }
    localStorage.setItem('pivoo_user', JSON.stringify(user.value))
  }

  const logout = () => {
    user.value = null
    token.value = null
    localStorage.removeItem('pivoo_user')
    localStorage.removeItem('pivoo_token')
  }

  return { user, token, login, updateUser, logout }
})