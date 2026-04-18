<template>
  <div class="layout-wrapper" :class="{ 'has-bottom-nav': !isAuthPage }">
    <AppNavigation v-if="!isAuthPage" @toggle-theme="toggleTheme" :is-dark="isDark" />
    <main class="main-content" :class="{ 'auth-layout': isAuthPage }">
      <div :class="isAuthPage ? 'full-width' : 'container'">
        <router-view />
      </div>
    </main>
    <AppFooter v-if="!isAuthPage" />

    <BackToTop />
  </div>
</template>

<script setup>
import { computed, ref, onMounted, onUnmounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import { storeToRefs } from 'pinia'
import AppNavigation from './components/AppNavigation.vue'
import AppFooter from './components/AppFooter.vue'
// NOVÉ: Import komponenty
import BackToTop from './components/BackToTop.vue'
import { useAuthStore } from './stores/auth'
import { apiFetch } from './api' 

const route = useRoute()
const authStore = useAuthStore()
const { user, theme } = storeToRefs(authStore)

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

  /* SVĚTLÝ REŽIM (Výchozí) */
  --bg-app: #f1f5f9;
  --bg-panel: #ffffff;
  --text-main: #1e293b;
  --text-muted: #64748b;
  --border: #e2e8f0;
  --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  --card-hover-bg: #f8fafc;
  
  --scrollbar-thumb: #cbd5e1;
  --scrollbar-thumb-hover: #94a3b8;
}

/* --- TMAVÝ REŽIM --- */
html.dark-mode {
  --bg-app: #0f172a;
  --bg-panel: #1e293b;
  --text-main: #f1f5f9;
  --text-muted: #94a3b8;
  --border: #334155;
  --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3);
  --card-hover-bg: #242f42;
  
  --scrollbar-thumb: #475569;
  --scrollbar-thumb-hover: #64748b;
}

/* --- GRAFICKÉ VYHLAZENÍ: OZNAČENÍ TEXTU --- */
::selection {
  background-color: var(--primary);
  color: #1e293b;
}
::-moz-selection {
  background-color: var(--primary);
  color: #1e293b;
}

/* --- GRAFICKÉ VYHLAZENÍ: ODSTRANĚNÍ ŠIPEK U ČÍSELNÝCH VSTUPŮ --- */
input::-webkit-outer-spin-button,
input::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}
input[type=number] {
  -moz-appearance: textfield;
}

/* --- USKAKOVÁNÍ STRÁNKY A POSUVNÍKY --- */
html {
  overflow-y: scroll;
  scrollbar-width: thin;
  scrollbar-color: var(--scrollbar-thumb) transparent;
}

::-webkit-scrollbar {
  width: 14px;  
  height: 14px; 
}

::-webkit-scrollbar-track {
  background: transparent; 
}

::-webkit-scrollbar-thumb {
  background-color: var(--scrollbar-thumb);
  border-radius: 10px;
  border: 4px solid transparent; 
  background-clip: content-box;
  transition: background-color 0.3s ease;
}

::-webkit-scrollbar-thumb:hover {
  background-color: var(--scrollbar-thumb-hover);
}

/* PŘIDÁNO: Logika pro zmrazení pozadí při otevřeném modálu */
body.modal-open {
  overflow: hidden;
}

/* --- ZÁKLAD DOKUMENTU --- */
html, body, #app {
  height: 100%;
  width: 100%;
  background-color: var(--bg-app);
  font-family: 'Inter', system-ui, sans-serif;
  color: var(--text-main);
  -webkit-font-smoothing: antialiased;
  transition: background-color 0.5s ease;
}

/* --- 2. LAYOUT A PŘECHODY --- */
.layout-wrapper { 
  display: flex; 
  flex-direction: column; 
  min-height: 100vh; 
  background-color: var(--bg-app);
  color: var(--text-main);
  transition: background-color 0.5s ease, color 0.5s ease;
}

.main-content { flex: 1 0 auto; display: flex; flex-direction: column; }
.container { max-width: 1200px; margin: 0 auto; padding: 2rem; width: 100%; }
.auth-layout { justify-content: center; align-items: center; flex: 1; }

.full-width { width: 100%; }

/* --- 3. UNIVERZÁLNÍ TLAČÍTKA --- */
button, .base-button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0.6rem 1.2rem;
  border-radius: 8px;
  font-weight: 700;
  font-family: inherit;
  cursor: pointer;
  transition: all 0.3s ease;
  border: none;
  font-size: 0.95rem;
  box-shadow: var(--shadow);
  line-height: 1.2;
}

button:active { transform: scale(0.98); }

button.btn-primary { background-color: var(--primary); color: #1e293b; }
button.btn-primary:hover { background-color: var(--primary-hover); }

button.btn-add { background-color: var(--blue); color: white; }
button.btn-add:hover { background-color: var(--blue-hover); }

button.btn-edit { background-color: var(--orange); color: white; }
button.btn-edit:hover { background-color: var(--orange-hover); }

button.btn-danger { background-color: var(--danger); color: white; }
button.btn-danger:hover { background-color: var(--danger-hover); }

button svg { width: 18px; height: 18px; stroke-width: 2.5; }
button:not(.is-icon-only):not(.btn-close) svg { margin-right: 0.5rem; }
button.is-icon-only { padding: 0.6rem; width: 38px; height: 38px; }

/* --- 4. ZAVÍRACÍ KŘÍŽKY --- */
button.btn-close {
  background: none !important;
  box-shadow: none !important;
  padding: 0.25rem !important;
  color: var(--text-muted) !important;
  width: auto !important;
  height: auto !important;
}
button.btn-close:hover {
  color: var(--text-main) !important;
  background: rgba(255,255,255,0.05) !important;
}

/* --- 5. OSTATNÍ KOMPONENTY A ODZNAČKY --- */
.badge { padding: 0.3rem 0.7rem; border-radius: 99px; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; }
.badge.admin { background: rgba(239, 68, 68, 0.2); color: #f87171; }
.badge.user { background: rgba(59, 130, 246, 0.2); color: #60a5fa; }

/* --- 6. TOAST NOTIFIKACE A ANIMACE --- */
.toast-notification {
  position: fixed;
  top: 20px;
  left: 50%;
  transform: translateX(-50%);
  padding: 1rem 2rem;
  border-radius: 8px;
  color: white;
  z-index: 9999;
  box-shadow: var(--shadow);
}
.toast-success { background-color: #10b981; }
.toast-error { background-color: #ef4444; }

.toast-fade-enter-active, .toast-fade-leave-active { 
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); 
}
.toast-fade-enter-from, .toast-fade-leave-to { 
  opacity: 0; 
  transform: translate(-50%, -20px);
}

/* --- 7. RESPONSIVNÍ ÚPRAVY --- */
@media (max-width: 900px) {
  .layout-wrapper.has-bottom-nav {
    padding-bottom: calc(70px + env(safe-area-inset-bottom));
  }
}

@media (max-width: 600px) {
  .container { padding: 1rem; }
}
</style>