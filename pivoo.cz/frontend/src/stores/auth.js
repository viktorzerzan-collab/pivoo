import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useAuthStore = defineStore('auth', () => {
  const user = ref(JSON.parse(localStorage.getItem('pivoo_user')) || null)
  const token = ref(localStorage.getItem('pivoo_token') || null)
  // Reaktivní stav pro aktuálně zvolené téma (light/dark)
  const theme = ref(localStorage.getItem('pivoo_theme') || 'light')
  // Reaktivní stav pro výchozí měnu
  const defaultCurrency = ref(localStorage.getItem('pivoo_currency') || 'CZK')

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

    if (userData.default_currency) {
      defaultCurrency.value = userData.default_currency
      localStorage.setItem('pivoo_currency', userData.default_currency)
    }
  }

  const updateUser = (newData) => {
    user.value = { ...user.value, ...newData }
    localStorage.setItem('pivoo_user', JSON.stringify(user.value))
    
    if (newData.default_currency) {
      defaultCurrency.value = newData.default_currency
      localStorage.setItem('pivoo_currency', newData.default_currency)
    }
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

  return { user, token, theme, defaultCurrency, login, updateUser, setTheme, logout }
})