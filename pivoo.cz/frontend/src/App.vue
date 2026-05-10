<template>
  <div class="layout-wrapper" :class="{ 'has-bottom-nav': !isAuthPage }">
    <AppNavigation v-if="!isAuthPage" @toggle-theme="toggleTheme" :is-dark="isDark" />
    
    <div v-if="isAuthPage" class="auth-top-bar">
      <LangToggleButton />
      <ThemeToggleButton :is-dark="isDark" @toggle="toggleTheme" />
    </div>

    <main class="main-content" :class="{ 'auth-layout': isAuthPage }">
      <div :class="isAuthPage ? 'full-width' : 'container'">
        <router-view />
      </div>
    </main>
    <AppFooter v-if="!isAuthPage" />

    <AppToast />
    <BackToTop />
  </div>
</template>

<script setup>
import { computed, ref, onMounted, onUnmounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import { storeToRefs } from 'pinia'
import { useI18n } from 'vue-i18n'
import AppNavigation from './components/AppNavigation.vue'
import AppFooter from './components/AppFooter.vue'
import AppToast from './components/AppToast.vue'
import BackToTop from './components/BackToTop.vue'
import LangToggleButton from './components/LangToggleButton.vue'
import ThemeToggleButton from './components/ThemeToggleButton.vue'
import { useAuthStore } from './stores/auth'
import { apiFetch } from './api' 

const route = useRoute()
const authStore = useAuthStore()
const { user, theme } = storeToRefs(authStore)

const { t } = useI18n()

const isAuthPage = computed(() => route.name === 'login' || route.name === 'register')

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

watch(() => user.value?.theme_mode, checkAutoTheme)
watch(theme, checkAutoTheme)

onMounted(() => {
  checkAutoTheme()
  autoInterval = setInterval(checkAutoTheme, 60000)
})

onUnmounted(() => {
  if (autoInterval) clearInterval(autoInterval)
})
</script>

<style>
/* --- 1. GLOBÁLNÍ RESET --- */
*, *::before, *::after {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

:root {
  --primary: #facc15;
  --primary-hover: #eab308;
  --blue: #3b82f6;
  --blue-hover: #2563eb;
  --orange: #f97316;
  --orange-hover: #ea580c;
  --danger: #ef4444;
  --danger-hover: #dc2626;

  /* SVĚTLÝ REŽIM */
  --bg-app: #f8fafc;
  --bg-panel: #ffffff;
  --text-main: #1e293b;
  --text-muted: #64748b;
  --border: #e2e8f0;
  
  --shadow: none;
  --shadow-floating: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
  
  --card-hover-bg: #f1f5f9;
  
  --scrollbar-thumb: #cbd5e1;
  --scrollbar-thumb-hover: #94a3b8;

  --radius-sm: 8px;
  --radius-md: 12px;
}

/* --- TMAVÝ REŽIM --- */
html.dark-mode {
  --bg-app: #0f172a;
  --bg-panel: #1e293b;
  --text-main: #f1f5f9;
  --text-muted: #94a3b8;
  --border: #334155;
  
  --shadow: none;
  --shadow-floating: 0 10px 25px -5px rgba(0, 0, 0, 0.5), 0 8px 10px -6px rgba(0, 0, 0, 0.5);
  
  --card-hover-bg: #242f42;
  
  --scrollbar-thumb: #475569;
  --scrollbar-thumb-hover: #64748b;
}

/* --- GRAFICKÉ VYHLAZENÍ --- */
::selection {
  background-color: var(--primary);
  color: #1e293b;
}
::-moz-selection {
  background-color: var(--primary);
  color: #1e293b;
}

input::-webkit-outer-spin-button,
input::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}
input[type=number] {
  -moz-appearance: textfield;
}

html {
  overflow-y: scroll;
  scrollbar-width: thin;
  scrollbar-color: var(--scrollbar-thumb) transparent;
}

::-webkit-scrollbar { width: 14px; height: 14px; }
::-webkit-scrollbar-track { background: transparent; }
::-webkit-scrollbar-thumb {
  background-color: var(--scrollbar-thumb);
  border-radius: var(--radius-sm);
  border: 4px solid transparent; 
  background-clip: content-box;
  transition: background-color 0.2s ease;
}
::-webkit-scrollbar-thumb:hover { background-color: var(--scrollbar-thumb-hover); }

html, body, #app {
  height: 100%;
  width: 100%;
  background-color: var(--bg-app);
  font-family: 'Inter', system-ui, sans-serif;
  color: var(--text-main);
  -webkit-font-smoothing: antialiased;
  transition: background-color 0.3s ease, color 0.3s ease;
}

/* --- 2. LAYOUT A PŘECHODY --- */
.layout-wrapper { 
  display: flex; 
  flex-direction: column; 
  min-height: 100vh; 
  background-color: var(--bg-app);
  color: var(--text-main);
  transition: background-color 0.3s ease, color 0.3s ease;
}

/* PLOVOUCÍ LIŠTA NA AUTH STRÁNKÁCH */
.auth-top-bar {
  position: absolute;
  top: 1.5rem;
  right: 1.5rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  z-index: 100;
}

.main-content { flex: 1 0 auto; display: flex; flex-direction: column; }
.container { max-width: 1200px; margin: 0 auto; padding: 2rem; width: 100%; }
.auth-layout { justify-content: center; align-items: center; flex: 1; position: relative; }

.full-width { width: 100%; }

/* --- 3. UNIVERZÁLNÍ TLAČÍTKA --- */
button, .base-button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0.6rem 1.2rem;
  border-radius: var(--radius-sm);
  font-weight: 700;
  font-family: inherit;
  cursor: pointer;
  transition: background-color 0.2s ease, color 0.2s ease, border-color 0.2s ease;
  border: 1px solid transparent;
  font-size: 0.95rem;
  box-shadow: none;
  line-height: 1.2;
}

button.btn-primary { background-color: var(--primary); color: #1e293b; }
button.btn-primary:hover, button.btn-primary:active { background-color: var(--primary-hover); }

button.btn-add { background-color: var(--blue); color: white; }
button.btn-add:hover, button.btn-add:active { background-color: var(--blue-hover); }

button.btn-edit { background-color: var(--orange); color: white; }
button.btn-edit:hover, button.btn-edit:active { background-color: var(--orange-hover); }

button.btn-danger { background-color: var(--danger); color: white; }
button.btn-danger:hover, button.btn-danger:active { background-color: var(--danger-hover); }

button svg { width: 18px; height: 18px; stroke-width: 2.5; }
button:not(.is-icon-only):not(.btn-close) svg { margin-right: 0.5rem; }
button.is-icon-only { padding: 0.6rem; width: 38px; height: 38px; }

/* --- 4. ZAVÍRACÍ KŘÍŽKY --- */
button.btn-close {
  background: none !important;
  border: none !important;
  padding: 0.25rem !important;
  color: var(--text-muted) !important;
  width: auto !important;
  height: auto !important;
}
button.btn-close:hover {
  color: var(--text-main) !important;
  background: rgba(128,128,128,0.1) !important;
}

/* --- 5. ODZNAČKY --- */
.badge { padding: 0.3rem 0.7rem; border-radius: 99px; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; }
.badge.admin { background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); color: #ef4444; }
.badge.user { background: rgba(59, 130, 246, 0.1); border: 1px solid rgba(59, 130, 246, 0.2); color: #3b82f6; }

/* --- 6. TOAST NOTIFIKACE A ANIMACE --- */
.toast-notification {
  position: fixed;
  top: 20px;
  left: 50%;
  transform: translateX(-50%);
  padding: 1rem 2rem;
  border-radius: var(--radius-sm);
  color: white;
  font-weight: 600;
  z-index: 9999;
  box-shadow: var(--shadow-floating);
}
.toast-success { background-color: #10b981; }
.toast-error { background-color: #ef4444; }

.toast-fade-enter-active, .toast-fade-leave-active { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
.toast-fade-enter-from, .toast-fade-leave-to { opacity: 0; transform: translate(-50%, -20px); }

/* --- 7. RESPONSIVNÍ ÚPRAVY --- */
@media (max-width: 900px) {
  .layout-wrapper.has-bottom-nav {
    padding-bottom: calc(70px + env(safe-area-inset-bottom));
  }
}

@media (max-width: 600px) {
  .container { padding: 1rem; }
  .auth-top-bar { top: 1rem; right: 1rem; }
}
</style>