<template>
  <BaseTooltip text="Najít můj podnik podle GPS" position="top-end">
    <button 
      type="button" 
      class="btn-locate is-icon-only" 
      @click="$emit('locate')" 
      :disabled="isLocating"
      aria-label="Lokalizovat"
    >
      <MapPinIcon :class="{ 'spinning': isLocating }" />
    </button>
  </BaseTooltip>
</template>

<script setup>
import { MapPinIcon } from 'lucide-vue-next'
import BaseTooltip from './BaseTooltip.vue'

defineProps({
  isLocating: {
    type: Boolean,
    default: false
  }
})

defineEmits(['locate'])
</script>

<style scoped>
.btn-locate { 
  height: 44px !important; 
  width: 44px !important; 
  flex-shrink: 0; 
  display: flex; 
  align-items: center; 
  justify-content: center; 
  background-color: var(--bg-panel); 
  color: var(--text-muted); 
  border: 1px solid var(--border); 
  border-radius: var(--radius-sm); 
  cursor: pointer; 
  transition: all 0.2s ease;
  padding: 0 !important; 
}

.btn-locate:hover:not(:disabled) { 
  background-color: rgba(250, 204, 21, 0.1); 
  border-color: var(--primary); 
  color: var(--primary); 
}

.btn-locate:disabled { 
  opacity: 0.7; 
  cursor: not-allowed; 
}

.spinning { 
  animation: spin 1s linear infinite; 
}

@keyframes spin { 
  100% { transform: rotate(360deg); } 
}

.btn-locate :deep(svg) {
  width: 20px !important;
  height: 20px !important;
  flex-shrink: 0;
  margin: 0 !important;
}
</style>