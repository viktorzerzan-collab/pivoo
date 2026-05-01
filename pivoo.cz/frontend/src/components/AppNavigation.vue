<template>
  <header class="app-header">
    <div class="header-content">
      
      <div class="header-left">
        <router-link language="cz" to="/dashboard" class="logo">
          <BeerIcon :size="28" style="margin-right: 0.5rem; color: var(--primary);" />
          Pivoo.cz
        </router-link>

        <nav class="main-nav desktop-only">
          <router-link to="/dashboard" class="nav-link"><LayoutDashboardIcon :size="18" /> Nástěnka</router-link>
          <router-link to="/statistics" class="nav-link"><BarChart3Icon :size="18" /> Statistiky</router-link>
          <router-link to="/beers" class="nav-link"><BeerIcon :size="18" /> Piva</router-link>
          <router-link to="/breweries" class="nav-link"><FactoryIcon :size="18" /> Pivovary</router-link>
          <router-link to="/locations" class="nav-link"><MapPinIcon :size="18" /> Podniky</router-link>
        </nav>
      </div>

      <div class="header-right">
        <ThemeToggleButton 
          :is-dark="isDark" 
          @toggle="$emit('toggle-theme')" 
        />

        <div class="user-panel-wrapper" @click="toggleDropdown" ref="dropdownContainer">
          <div class="user-trigger">
            <img v-if="user?.avatar" :src="'https://www.pivoo.cz/backend/uploads/avatars/' + user.avatar" class="avatar-small" alt="Avatar" />
            <div v-else class="avatar-small-placeholder"><UserIcon :size="18" color="#fff" /></div>
            
            <span class="greeting desktop-only">{{ user?.username }}</span>
            <ChevronDownIcon :size="16" class="dropdown-arrow desktop-only" :class="{ 'rotated': isDropdownOpen }" />
          </div>

          <transition name="dropdown-fade">
            <div v-if="isDropdownOpen" class="dropdown-menu">
              <router-link to="/profile" class="dropdown-item">
                <UserIcon :size="16" /> Můj profil
              </router-link>
              <router-link to="/wishlist" class="dropdown-item">
                <BookmarkIcon :size="16" /> Můj wishlist
              </router-link>
              <router-link v-if="isAdmin" to="/admin" class="dropdown-item">
                <ShieldAlertIcon :size="16" /> Administrace
              </router-link>
              <div class="dropdown-divider"></div>
              <button @click="handleLogout" class="dropdown-item logout-item">
                <LogOutIcon :size="16" /> Odhlásit
              </button>
            </div>
          </transition>
        </div>
      </div>

    </div>
  </header>

  <nav class="bottom-nav mobile-only">
    <div class="bottom-nav-inner">
      <router-link to="/dashboard" class="bottom-link">
        <LayoutDashboardIcon :size="24" />
        <span>Nástěnka</span>
      </router-link>
      <router-link to="/statistics" class="bottom-link">
        <BarChart3Icon :size="24" />
        <span>Statistiky</span>
      </router-link>
      <router-link to="/beers" class="bottom-link">
        <BeerIcon :size="24" />
        <span>Piva</span>
      </router-link>
      <router-link to="/breweries" class="bottom-link">
        <FactoryIcon :size="24" />
        <span>Pivovary</span>
      </router-link>
      <router-link to="/locations" class="bottom-link">
        <MapPinIcon :size="24" />
        <span>Podniky</span>
      </router-link>
    </div>
  </nav>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { storeToRefs } from 'pinia'
import { 
  BeerIcon, LayoutDashboardIcon, FactoryIcon, MapPinIcon,
  ShieldAlertIcon, LogOutIcon, UserIcon, ChevronDownIcon,
  BarChart3Icon, BookmarkIcon
} from 'lucide-vue-next'

import { useAuthStore } from '../stores/auth'
import ThemeToggleButton from './ThemeToggleButton.vue'

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
const dropdownContainer = ref(null)

const toggleDropdown = () => { isDropdownOpen.value = !isDropdownOpen.value }

const handleClickOutside = (event) => {
  if (dropdownContainer.value && !dropdownContainer.value.contains(event.target)) {
    isDropdownOpen.value = false
  }
}

onMounted(() => document.addEventListener('click', handleClickOutside))
onUnmounted(() => document.removeEventListener('click', handleClickOutside))

watch(route, () => {
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

.header-left { display: flex; align-items: center; gap: 2rem; }
.header-right { display: flex; align-items: center; gap: 1rem; }

.logo { display: flex; align-items: center; font-size: 1.5rem; font-weight: 800; color: #fff; text-decoration: none; }
.logo svg { filter: drop-shadow(0 0 2px rgba(250, 204, 21, 0.3)); }

.main-nav { display: flex; gap: 0.5rem; }
.nav-link { display: flex; align-items: center; gap: 0.4rem; color: #94a3b8; text-decoration: none; font-weight: 600; font-size: 0.95rem; padding: 0.5rem 1rem; border-radius: 8px; transition: all 0.2s ease; }
.nav-link:hover { color: #fff; background-color: rgba(255, 255, 255, 0.05); }
.nav-link.router-link-active { color: var(--primary); background-color: rgba(255, 255, 255, 0.1); }

.user-panel-wrapper { position: relative; cursor: pointer; user-select: none; }
.user-trigger { display: flex; align-items: center; gap: 0.6rem; padding: 0.4rem 0.75rem; border-radius: 99px; transition: background-color 0.2s; border: 1px solid transparent; }
.user-trigger:hover { background-color: rgba(255, 255, 255, 0.05); border-color: rgba(255, 255, 255, 0.1); }
.avatar-small { width: 36px; height: 36px; border-radius: 50%; object-fit: cover; border: 2px solid var(--primary); }
.avatar-small-placeholder { width: 36px; height: 36px; border-radius: 50%; background: #475569; display: flex; align-items: center; justify-content: center; }
.greeting { font-size: 0.95rem; font-weight: 600; color: #f8fafc; }
.dropdown-arrow { color: #94a3b8; transition: transform 0.2s; }
.dropdown-arrow.rotated { transform: rotate(180deg); }

.dropdown-menu { position: absolute; top: calc(100% + 5px); right: 0; background: var(--bg-panel); border-radius: 12px; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.2); min-width: 160px; padding: 0.5rem 0; border: 1px solid var(--border); transform-origin: top right; transition: background-color 0.5s ease, border-color 0.5s ease; }

.dropdown-item { 
  display: flex; 
  align-items: center; 
  justify-content: flex-start; 
  gap: 0.75rem; 
  padding: 0.75rem 1.25rem; 
  color: var(--text-main); 
  text-decoration: none; 
  font-weight: 500; 
  font-size: 0.95rem; 
  background: none; 
  border: none; 
  width: 100%; 
  cursor: pointer; 
  text-align: left; 
  transition: all 0.2s ease; 
}

.dropdown-item:hover { 
  background-color: var(--bg-app); 
  color: var(--primary-hover); 
}

.logout-item { color: var(--danger); }
.logout-item:hover { background-color: rgba(239, 68, 68, 0.05); color: var(--danger-hover); }

.dropdown-divider { height: 1px; background-color: var(--border); margin: 0.25rem 0; transition: background-color 0.5s ease; }

.dropdown-fade-enter-active, .dropdown-fade-leave-active { transition: all 0.2s ease; }
.dropdown-fade-enter-from, .dropdown-fade-leave-to { opacity: 0; transform: scale(0.95) translateY(-10px); }

.bottom-nav { display: none; }
.mobile-only { display: none; }

@media (max-width: 900px) {
  .desktop-only { display: none !important; }
  .mobile-only { display: block; }
  
  .app-header { padding: 0.75rem 1rem; }
  .user-trigger { padding: 0; border: none; }
  .user-trigger:hover { background-color: transparent; border-color: transparent; }

  .bottom-nav {
    display: block;
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background-color: #1e293b;
    border-top: 1px solid #334155;
    z-index: 100;
    padding-bottom: env(safe-area-inset-bottom);
  }

  .bottom-nav-inner {
    display: flex;
    justify-content: space-around;
    align-items: center;
    padding: 0.5rem 0;
  }

  .bottom-link {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 0.2rem;
    color: #94a3b8;
    text-decoration: none;
    font-size: 0.75rem;
    font-weight: 600;
    flex: 1;
    transition: color 0.2s ease;
  }

  .bottom-link:hover { color: #cbd5e1; }
  .bottom-link.router-link-active { color: var(--primary); }
  .bottom-link svg { transition: transform 0.2s ease; }
  .bottom-link.router-link-active svg { transform: translateY(-2px); }
}
</style>