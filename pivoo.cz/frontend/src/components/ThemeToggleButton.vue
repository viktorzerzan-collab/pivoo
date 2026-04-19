<template>
  <button 
    class="theme-toggle-btn" 
    @click="$emit('toggle')" 
    :title="isDark ? 'Přepnout na světlý režim' : 'Přepnout na tmavý režim'"
  >
    <SunIcon v-if="isDark" :size="22" />
    <MoonIcon v-else :size="22" />
  </button>
</template>

<script setup>
import { SunIcon, MoonIcon } from 'lucide-vue-next'

defineProps({
  isDark: Boolean
})

defineEmits(['toggle'])
</script>

<style scoped>
.theme-toggle-btn {
  background: rgba(255, 255, 255, 0.1);
  border: none;
  cursor: pointer;
  width: 38px;
  height: 38px;
  padding: 0;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
  color: var(--primary);
}

.theme-toggle-btn:hover { 
  background: rgba(255, 255, 255, 0.2); 
  transform: rotate(15deg); 
}

/* Dokonalé vycentrování potlačením globálního stylu */
.theme-toggle-btn :deep(svg) {
  margin: 0 !important;
}
</style>