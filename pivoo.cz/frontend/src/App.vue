<template>
  <div class="layout-wrapper">
    <AppNavigation v-if="!isAuthPage" />
    <main class="main-content" :class="{ 'auth-layout': isAuthPage }">
      <div :class="isAuthPage ? 'full-width' : 'container'">
        <router-view />
      </div>
    </main>
    <AppFooter v-if="!isAuthPage" />
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import AppNavigation from './components/AppNavigation.vue'
import AppFooter from './components/AppFooter.vue'
const route = useRoute()
const isAuthPage = computed(() => route.name === 'login' || route.name === 'register')
</script>

<style>
/* --- RESET A ZÁKLAD --- */
*, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

:root {
  --primary: #facc15;
  --primary-hover: #eab308;
  --blue: #3b82f6;
  --orange: #f97316;
  --danger: #ef4444;
  --text-main: #1e293b;
  --text-muted: #64748b;
  --border: #e2e8f0;
  --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

html, body, #app {
  height: 100%; width: 100%;
  background-color: #f1f5f9;
  font-family: 'Inter', system-ui, sans-serif;
  color: var(--text-main);
}

.layout-wrapper { display: flex; flex-direction: column; min-height: 100vh; }
.main-content { flex: 1 0 auto; display: flex; flex-direction: column; }
.container { max-width: 1200px; margin: 0 auto; padding: 2rem; width: 100%; }

/* --- UNIVERZÁLNÍ TLAČÍTKA --- */
button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0.6rem 1.2rem;
  border-radius: 8px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s;
  border: none;
  font-family: inherit;
  box-shadow: var(--shadow);
}

/* Barevné varianty */
button.btn-primary { background: var(--primary); color: #1e293b; }
button.btn-add { background: var(--blue); color: white; }
button.btn-edit { background: var(--orange); color: white; }
button.btn-danger { background: var(--danger); color: white; }

/* --- OPRAVA PRO ZAVÍRACÍ KŘÍŽKY --- */
button.btn-close {
  background: none !important;
  box-shadow: none !important;
  padding: 0.25rem !important;
  border-radius: 4px !important;
  color: var(--text-muted) !important;
  width: auto !important;
  height: auto !important;
}
button.btn-close:hover {
  color: var(--text-main) !important;
  background: rgba(0,0,0,0.05) !important;
  transform: scale(1.1);
}

/* Ikony v tlačítkách */
button svg { width: 1.2em; height: 1.2em; stroke-width: 2.5; }
button:not(.is-icon-only):not(.btn-close) svg { margin-right: 0.5rem; }
button.is-icon-only { padding: 0.6rem; width: 38px; height: 38px; }

/* Ostatní */
.badge { padding: 0.3rem 0.7rem; border-radius: 99px; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; }
.badge.admin { background: #fee2e2; color: #ef4444; }
.badge.user { background: #e0f2fe; color: #0284c7; }
</style>