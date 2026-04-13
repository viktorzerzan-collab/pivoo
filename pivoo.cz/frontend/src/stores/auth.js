import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useAuthStore = defineStore('auth', () => {
  const user = ref(JSON.parse(localStorage.getItem('pivoo_user')) || null)
  const token = ref(localStorage.getItem('pivoo_token') || null)
  // Reaktivní stav pro aktuálně zvolené téma (light/dark)
  const theme = ref(localStorage.getItem('pivoo_theme') || 'light')

  const login = (userData, authToken) => {
    user.value = userData
    token.value = authToken
    localStorage.setItem('pivoo_user', JSON.stringify(userData))
    localStorage.setItem('pivoo_token', authToken)
    
    // Pokud má uživatel v databázi uloženou preferenci, aplikujeme ji
    if (userData.theme_preference) {
      theme.value = userData.theme_preference
      localStorage.setItem('pivoo_theme', userData.theme_preference)
    }
  }

  const updateUser = (newData) => {
    user.value = { ...user.value, ...newData }
    localStorage.setItem('pivoo_user', JSON.stringify(user.value))
  }

  const setTheme = (newTheme) => {
    theme.value = newTheme
    localStorage.setItem('pivoo_theme', newTheme)
  }

  const logout = () => {
    user.value = null
    token.value = null
    localStorage.removeItem('pivoo_user')
    localStorage.removeItem('pivoo_token')
  }

  return { user, token, theme, login, updateUser, setTheme, logout }
})