<template>
  <header class="app-header">
    <div class="header-content">
      <router-link to="/dashboard" class="logo">
        <BeerIcon :size="28" style="margin-right: 0.5rem; color: var(--primary);" />
        Pivoo.cz
      </router-link>
      
      <nav class="main-nav">
        <router-link to="/dashboard" class="nav-link">
          <LayoutDashboardIcon :size="18" /> Nástěnka
        </router-link>
        <router-link to="/beers" class="nav-link">
          <BeerIcon :size="18" /> Piva
        </router-link>
        <router-link to="/breweries" class="nav-link">
          <FactoryIcon :size="18" /> Pivovary
        </router-link>
        <router-link to="/locations" class="nav-link">
          <MapIcon :size="18" /> Podniky
        </router-link>
        <router-link v-if="isAdmin" to="/admin" class="nav-link admin-link">
          <ShieldAlertIcon :size="18" /> Administrace
        </router-link>
      </nav>

      <div class="user-panel">
        <span class="greeting">Ahoj, <strong>{{ user?.username }}</strong></span>
        <BaseButton variant="logout" @click="handleLogout">
          <template #icon><LogOutIcon :size="16" /></template>
          Odhlásit
        </BaseButton>
      </div>
    </div>
  </header>
</template>

<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { storeToRefs } from 'pinia'
import { 
  BeerIcon, LayoutDashboardIcon, FactoryIcon, MapIcon,
  ShieldAlertIcon, LogOutIcon 
} from 'lucide-vue-next'

import { useAuthStore } from '../stores/auth'
import BaseButton from './BaseButton.vue'

const router = useRouter()
const authStore = useAuthStore()
const { user } = storeToRefs(authStore)

const isAdmin = computed(() => user.value?.role === 'admin')

const handleLogout = () => {
  authStore.logout()
  router.push('/')
}
</script>

<style scoped>
.app-header {
  background-color: #1e293b;
  color: white;
  padding: 0.75rem 2rem;
  box-shadow: var(--shadow-md);
  position: sticky;
  top: 0;
  z-index: 50;
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
  max-width: 1200px;
  margin: 0 auto;
  width: 100%;
}

.logo {
  display: flex;
  align-items: center;
  font-size: 1.5rem;
  font-weight: 800;
  color: var(--white);
  letter-spacing: -0.025em;
  text-decoration: none;
}

.main-nav {
  display: flex;
  gap: 0.5rem;
}

.nav-link {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  color: #94a3b8;
  text-decoration: none;
  font-weight: 600;
  font-size: 0.95rem;
  padding: 0.5rem 1rem;
  border-radius: 8px;
  transition: all 0.2s ease;
}

.nav-link:hover {
  color: var(--white);
  background-color: rgba(255, 255, 255, 0.05);
}

.router-link-active {
  color: var(--primary);
  background-color: rgba(255, 255, 255, 0.1);
}

.admin-link {
  color: #f87171;
}

.admin-link.router-link-active {
  color: #ef4444;
  background-color: rgba(239, 68, 68, 0.1);
}

.user-panel {
  display: flex;
  align-items: center;
  gap: 1.5rem;
}

.greeting {
  font-size: 0.95rem;
  color: #cbd5e1;
}

@media (max-width: 900px) {
  .header-content { flex-direction: column; gap: 1rem; }
  .main-nav { flex-wrap: wrap; justify-content: center; }
  .user-panel { width: 100%; justify-content: space-between; }
}
</style>