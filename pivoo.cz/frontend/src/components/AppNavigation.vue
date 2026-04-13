<template>
  <header class="app-header">
    <div class="header-content">
      
      <div class="header-top-mobile">
        <router-link to="/dashboard" class="logo">
          <BeerIcon :size="28" style="margin-right: 0.5rem; color: var(--primary);" />
          Pivoo.cz
        </router-link>

        <div class="mobile-actions">
          <button class="theme-toggle-btn mobile-only" @click="$emit('toggle-theme')" :title="isDark ? 'Přepnout na světlý režim' : 'Přepnout na tmavý režim'">
            <SunIcon v-if="isDark" :size="22" />
            <MoonIcon v-else :size="22" />
          </button>
          
          <button class="mobile-menu-toggle" @click="toggleMobileMenu">
            <MenuIcon v-if="!isMobileMenuOpen" :size="24" />
            <XIcon v-else :size="24" />
          </button>
        </div>
      </div>

      <div class="nav-container" :class="{ 'is-open': isMobileMenuOpen }">
        <nav class="main-nav">
          <router-link to="/dashboard" class="nav-link"><LayoutDashboardIcon :size="18" /> Nástěnka</router-link>
          <router-link to="/beers" class="nav-link"><BeerIcon :size="18" /> Piva</router-link>
          <router-link to="/breweries" class="nav-link"><FactoryIcon :size="18" /> Pivovary</router-link>
          <router-link to="/locations" class="nav-link"><MapIcon :size="18" /> Podniky</router-link>
          <router-link v-if="isAdmin" to="/admin" class="nav-link admin-link"><ShieldAlertIcon :size="18" /> Administrace</router-link>
        </nav>

        <div class="nav-right-actions">
          <button class="theme-toggle-btn desktop-only" @click="$emit('toggle-theme')" :title="isDark ? 'Přepnout na světlý režim' : 'Přepnout na tmavý režim'">
            <SunIcon v-if="isDark" :size="20" />
            <MoonIcon v-else :size="20" />
          </button>

          <div class="user-panel-wrapper" @click="toggleDropdown" ref="dropdownContainer">
            <div class="user-trigger">
              <img v-if="user?.avatar" :src="'https://www.pivoo.cz/backend/uploads/avatars/' + user.avatar" class="avatar-small" alt="Avatar" />
              <div v-else class="avatar-small-placeholder"><UserIcon :size="18" color="#fff" /></div>
              
              <span class="greeting">{{ user?.username }}</span>
              <ChevronDownIcon :size="16" class="dropdown-arrow" :class="{ 'rotated': isDropdownOpen }" />
            </div>

          <transition name="dropdown-fade">
            <div v-if="isDropdownOpen" class="dropdown-menu">
              <router-link to="/profile" class="dropdown-item"><UserIcon :size="16" /> Můj profil</router-link>
              <div class="dropdown-divider"></div>
              <button @click="handleLogout" class="dropdown-item logout-item"><LogOutIcon :size="16" /> Odhlásit</button>
            </div>
          </transition>
          </div>
        </div>
      </div>

    </div>
  </header>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { storeToRefs } from 'pinia'
import { 
  BeerIcon, LayoutDashboardIcon, FactoryIcon, MapIcon,
  ShieldAlertIcon, LogOutIcon, UserIcon, ChevronDownIcon,
  MenuIcon, XIcon, SunIcon, MoonIcon
} from 'lucide-vue-next'

import { useAuthStore } from '../stores/auth'

defineProps({
  isDark: Boolean
})

defineEmits(['toggle-theme'])

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()
const { user } = storeToRefs(authStore)

const isAdmin = computed(() => user.value?.role === 'admin')
const isDropdownOpen = ref(false)
const isMobileMenuOpen = ref(false)
const dropdownContainer = ref(null)

const toggleDropdown = () => { isDropdownOpen.value = !isDropdownOpen.value }
const toggleMobileMenu = () => { isMobileMenuOpen.value = !isMobileMenuOpen.value }

// Zavření dropdownu při kliknutí jinam
const handleClickOutside = (event) => {
  if (dropdownContainer.value && !dropdownContainer.value.contains(event.target)) {
    isDropdownOpen.value = false
  }
}

onMounted(() => document.addEventListener('click', handleClickOutside))
onUnmounted(() => document.removeEventListener('click', handleClickOutside))

// Zavřít mobilní menu automaticky při změně podstránky
watch(route, () => {
  isMobileMenuOpen.value = false
  isDropdownOpen.value = false
})

const handleLogout = () => {
  authStore.logout()
  router.push('/')
}
</script>

<style scoped>
.app-header { background-color: #1e293b; color: white; padding: 0.75rem 2rem; box-shadow: var(--shadow-md); position: sticky; top: 0; z-index: 50; }
.header-content { display: flex; justify-content: space-between; align-items: center; max-width: 1200px; margin: 0 auto; width: 100%; }

.header-top-mobile { display: flex; justify-content: space-between; align-items: center; width: auto; }
.mobile-actions { display: flex; align-items: center; gap: 0.5rem; }
.logo { display: flex; align-items: center; font-size: 1.5rem; font-weight: 800; color: #fff; text-decoration: none; }
.logo svg { filter: drop-shadow(0 0 2px rgba(250, 204, 21, 0.3)); }

.nav-container { display: flex; align-items: center; gap: 1rem; flex-grow: 1; justify-content: flex-end; }
.nav-right-actions { display: flex; align-items: center; gap: 0.5rem; }

/* Sjednocený styl pro kulatá tlačítka v liště (přepínač i menu) */
.theme-toggle-btn, .mobile-menu-toggle {
  background: rgba(255, 255, 255, 0.1);
  border: none;
  cursor: pointer;
  width: 36px;
  height: 36px;
  padding: 0;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
  box-shadow: none;
}

.theme-toggle-btn { color: var(--primary); }
.mobile-menu-toggle { color: white; display: none; } /* Skryto na desktopu */

.theme-toggle-btn:hover, .mobile-menu-toggle:hover { background: rgba(255, 255, 255, 0.2); }
.theme-toggle-btn:hover { transform: rotate(15deg); }

/* OPRAVA: Vynulování marginu u SVG, aby ikonky byly přesně uprostřed */
.theme-toggle-btn svg, .mobile-menu-toggle svg {
  margin: 0 !important;
}

.mobile-only { display: none; }

.main-nav { display: flex; gap: 0.5rem; }
.nav-link { display: flex; align-items: center; gap: 0.4rem; color: #94a3b8; text-decoration: none; font-weight: 600; font-size: 0.95rem; padding: 0.5rem 1rem; border-radius: 8px; transition: all 0.2s ease; }
.nav-link:hover { color: #fff; background-color: rgba(255, 255, 255, 0.05); }
.router-link-active { color: var(--primary); background-color: rgba(255, 255, 255, 0.1); }
.admin-link { color: #f87171; }
.admin-link.router-link-active { color: #ef4444; background-color: rgba(239, 68, 68, 0.1); }

/* Dropdown Menu CSS */
.user-panel-wrapper { position: relative; cursor: pointer; user-select: none; margin-left: 0.5rem; }
.user-trigger { display: flex; align-items: center; gap: 0.6rem; padding: 0.4rem 0.75rem; border-radius: 8px; transition: background-color 0.2s; border: 1px solid transparent; }
.user-trigger:hover { background-color: rgba(255, 255, 255, 0.05); border-color: rgba(255, 255, 255, 0.1); }
.avatar-small { width: 32px; height: 32px; border-radius: 50%; object-fit: cover; border: 2px solid var(--primary); }
.avatar-small-placeholder { width: 32px; height: 32px; border-radius: 50%; background: #475569; display: flex; align-items: center; justify-content: center; }
.greeting { font-size: 0.95rem; font-weight: 600; color: #f8fafc; }
.dropdown-arrow { color: #94a3b8; transition: transform 0.2s; }
.dropdown-arrow.rotated { transform: rotate(180deg); }

.dropdown-menu { position: absolute; top: 110%; right: 0; background: var(--bg-panel); border-radius: 10px; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1); min-width: 200px; padding: 0.5rem 0; border: 1px solid var(--border); transform-origin: top right; transition: background-color 0.5s ease, border-color 0.5s ease; }
.dropdown-item { display: flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1.25rem; color: var(--text-main); text-decoration: none; font-weight: 500; font-size: 0.95rem; background: none; border: none; width: 100%; cursor: pointer; text-align: left; transition: color 0.5s ease, background-color 0.5s ease; }
.dropdown-item:hover { background-color: var(--bg-app); color: var(--primary-hover); }
.dropdown-divider { height: 1px; background-color: var(--border); margin: 0.25rem 0; transition: background-color 0.5s ease; }
.logout-item { color: #ef4444; }
.logout-item:hover { background-color: rgba(239, 68, 68, 0.05); color: #dc2626; }

.dropdown-fade-enter-active, .dropdown-fade-leave-active { transition: all 0.2s ease; }
.dropdown-fade-enter-from, .dropdown-fade-leave-to { opacity: 0; transform: scale(0.95) translateY(-10px); }

/* --- RESPONSIVNÍ DESIGN PRO MOBILY --- */
@media (max-width: 900px) {
  .app-header { padding: 1rem; }
  .header-content { flex-direction: column; align-items: stretch; }
  
  .header-top-mobile { width: 100%; }
  .mobile-menu-toggle { display: flex; } /* Aktivováno pro mobil */
  .mobile-only { display: flex; }
  .desktop-only { display: none; }
  
  .nav-container { display: none; flex-direction: column; width: 100%; padding-top: 1rem; gap: 0.5rem; justify-content: flex-start; }
  .nav-container.is-open { display: flex; }
  .nav-right-actions { flex-direction: column; align-items: stretch; gap: 0; }
  
  .main-nav { flex-direction: column; width: 100%; }
  .nav-link { justify-content: flex-start; padding: 0.75rem 1rem; }
  
  .user-panel-wrapper { width: 100%; margin-left: 0; margin-top: 0.5rem; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 0.5rem; }
  .user-trigger { justify-content: space-between; }
  
  .dropdown-menu { position: static; box-shadow: none; border: none; background: transparent; padding-left: 1rem; margin-top: 0.5rem; }
  .dropdown-item { color: #cbd5e1; }
  .dropdown-item:hover { background-color: rgba(255,255,255,0.05); color: white; }
  .logout-item { color: #f87171; }
  .dropdown-divider { background-color: rgba(255,255,255,0.1); }
}
</style>