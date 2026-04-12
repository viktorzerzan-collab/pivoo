<template>
  <header class="app-header">
    <div class="header-content">
      <router-link to="/dashboard" class="logo">
        <BeerIcon :size="28" style="margin-right: 0.5rem; color: var(--primary);" />
        Pivoo.cz
      </router-link>
      
      <nav class="main-nav">
        <router-link to="/dashboard" class="nav-link"><LayoutDashboardIcon :size="18" /> Nástěnka</router-link>
        <router-link to="/beers" class="nav-link"><BeerIcon :size="18" /> Piva</router-link>
        <router-link to="/breweries" class="nav-link"><FactoryIcon :size="18" /> Pivovary</router-link>
        <router-link to="/locations" class="nav-link"><MapIcon :size="18" /> Podniky</router-link>
        <router-link v-if="isAdmin" to="/admin" class="nav-link admin-link"><ShieldAlertIcon :size="18" /> Administrace</router-link>
      </nav>

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
  </header>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { storeToRefs } from 'pinia'
import { 
  BeerIcon, LayoutDashboardIcon, FactoryIcon, MapIcon,
  ShieldAlertIcon, LogOutIcon, UserIcon, ChevronDownIcon 
} from 'lucide-vue-next'

import { useAuthStore } from '../stores/auth'

const router = useRouter()
const authStore = useAuthStore()
const { user } = storeToRefs(authStore)

const isAdmin = computed(() => user.value?.role === 'admin')
const isDropdownOpen = ref(false)
const dropdownContainer = ref(null)

const toggleDropdown = () => { isDropdownOpen.value = !isDropdownOpen.value }

const handleClickOutside = (event) => {
  if (dropdownContainer.value && !dropdownContainer.value.contains(event.target)) {
    isDropdownOpen.value = false
  }
}

onMounted(() => document.addEventListener('click', handleClickOutside))
onUnmounted(() => document.removeEventListener('click', handleClickOutside))

const handleLogout = () => {
  authStore.logout()
  router.push('/')
}
</script>

<style scoped>
.app-header { background-color: #1e293b; color: white; padding: 0.75rem 2rem; box-shadow: var(--shadow-md); position: sticky; top: 0; z-index: 50; }
.header-content { display: flex; justify-content: space-between; align-items: center; max-width: 1200px; margin: 0 auto; width: 100%; }
.logo { display: flex; align-items: center; font-size: 1.5rem; font-weight: 800; color: var(--white); text-decoration: none; }
.main-nav { display: flex; gap: 0.5rem; }
.nav-link { display: flex; align-items: center; gap: 0.4rem; color: #94a3b8; text-decoration: none; font-weight: 600; font-size: 0.95rem; padding: 0.5rem 1rem; border-radius: 8px; transition: all 0.2s ease; }
.nav-link:hover { color: var(--white); background-color: rgba(255, 255, 255, 0.05); }
.router-link-active { color: var(--primary); background-color: rgba(255, 255, 255, 0.1); }
.admin-link { color: #f87171; }
.admin-link.router-link-active { color: #ef4444; background-color: rgba(239, 68, 68, 0.1); }

/* Dropdown Menu CSS */
.user-panel-wrapper { position: relative; cursor: pointer; user-select: none; }
.user-trigger { display: flex; align-items: center; gap: 0.6rem; padding: 0.4rem 0.75rem; border-radius: 8px; transition: background-color 0.2s; border: 1px solid transparent; }
.user-trigger:hover { background-color: rgba(255, 255, 255, 0.05); border-color: rgba(255, 255, 255, 0.1); }
.avatar-small { width: 32px; height: 32px; border-radius: 50%; object-fit: cover; border: 2px solid var(--primary); }
.avatar-small-placeholder { width: 32px; height: 32px; border-radius: 50%; background: #475569; display: flex; align-items: center; justify-content: center; }
.greeting { font-size: 0.95rem; font-weight: 600; color: #f8fafc; }
.dropdown-arrow { color: #94a3b8; transition: transform 0.2s; }
.dropdown-arrow.rotated { transform: rotate(180deg); }

.dropdown-menu { position: absolute; top: 110%; right: 0; background: white; border-radius: 10px; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1); min-width: 200px; padding: 0.5rem 0; border: 1px solid var(--border); transform-origin: top right; }
.dropdown-item { display: flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1.25rem; color: #334155; text-decoration: none; font-weight: 500; font-size: 0.95rem; background: none; border: none; width: 100%; cursor: pointer; text-align: left; }
.dropdown-item:hover { background-color: #f1f5f9; color: var(--primary-hover); }
.dropdown-divider { height: 1px; background-color: var(--border); margin: 0.25rem 0; }
.logout-item { color: #ef4444; }
.logout-item:hover { background-color: #fef2f2; color: #dc2626; }

.dropdown-fade-enter-active, .dropdown-fade-leave-active { transition: all 0.2s ease; }
.dropdown-fade-enter-from, .dropdown-fade-leave-to { opacity: 0; transform: scale(0.95) translateY(-10px); }

@media (max-width: 900px) {
  .header-content { flex-direction: column; gap: 1rem; }
  .main-nav { flex-wrap: wrap; justify-content: center; }
  .user-panel-wrapper { width: 100%; display: flex; justify-content: center; }
  .dropdown-menu { left: 50%; transform: translateX(-50%); right: auto; transform-origin: top center; }
}
</style>