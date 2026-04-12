<template>
  <div class="app-container">
    <AppNavigation v-if="authStore.user" />
    
    <main :class="{ 'main-content': authStore.user }">
      <router-view v-slot="{ Component }">
        <transition name="fade" mode="out-in">
          <component :is="Component" />
        </transition>
      </router-view>
    </main>
  </div>
</template>

<script setup>
import { useAuthStore } from './stores/auth'
import AppNavigation from './components/AppNavigation.vue'

const authStore = useAuthStore()
</script>

<style>
/* Globální přechody pro hladší přepínání stráek */
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.2s ease;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}
</style>