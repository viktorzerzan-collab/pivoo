import { ref, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { useAuthStore } from '../stores/auth'
import { apiFetch } from '../api'

export function useTheme() {
  const authStore = useAuthStore()
  const { user, theme } = storeToRefs(authStore)

  const isDark = ref(false)
  let autoInterval = null

  const updateHtmlClass = (isDarkMode) => {
    if (isDarkMode) {
      document.documentElement.classList.add('dark-mode')
    } else {
      document.documentElement.classList.remove('dark-mode')
    }
  }

  const checkAutoTheme = () => {
    if (user.value?.theme_mode === 'auto') {
      const hour = new Date().getHours()
      isDark.value = (hour >= 18 || hour < 6)
    } else {
      isDark.value = theme.value === 'dark'
    }
    updateHtmlClass(isDark.value)
  }

  const toggleTheme = async () => {
    const newTheme = isDark.value ? 'light' : 'dark'
    isDark.value = !isDark.value 
    authStore.setTheme(newTheme)
    updateHtmlClass(isDark.value)

    if (user.value?.theme_mode === 'auto') {
      authStore.updateUser({ theme_mode: 'manual' })
    }

    if (user.value) {
      try {
        await apiFetch('/update_profile.php', {
          method: 'POST',
          body: JSON.stringify({
            action: 'update_theme',
            theme_mode: 'manual', 
            theme_preference: newTheme 
          })
        })
      } catch (error) {
        console.error('Chyba při ukládání tématu do databáze:', error)
      }
    }
  }

  const initTheme = () => {
    checkAutoTheme()
    autoInterval = setInterval(checkAutoTheme, 60000)
  }

  const cleanupTheme = () => {
    if (autoInterval) clearInterval(autoInterval)
  }

  // Sledování změn v nastavení uživatele
  watch(() => user.value?.theme_mode, checkAutoTheme)
  watch(theme, checkAutoTheme)

  return {
    isDark,
    toggleTheme,
    initTheme,
    cleanupTheme
  }
}
