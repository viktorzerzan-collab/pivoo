<template>
  <BaseTooltip 
    v-if="authStore.user" 
    :text="isFavorite ? 'Odebrat z oblíbených' : 'Přidat do oblíbených'" 
    position="top"
  >
    <button 
      class="fav-btn" 
      :class="{ 'active': isFavorite }" 
      @click.stop="$emit('toggle')" 
    >
      <StarIcon 
        :size="size" 
        :fill="isFavorite ? 'var(--primary)' : 'none'" 
        :color="isFavorite ? 'var(--primary)' : 'var(--text-muted)'"
      />
    </button>
  </BaseTooltip>
</template>

<script setup>
import { StarIcon } from 'lucide-vue-next'
import { useAuthStore } from '../stores/auth'
import BaseTooltip from './BaseTooltip.vue' 

defineProps({
  isFavorite: {
    type: [Boolean, Number],
    default: false
  },
  size: {
    type: Number,
    default: 20
  }
})

defineEmits(['toggle'])

const authStore = useAuthStore()
</script>

<style scoped>
.fav-btn {
  background: none;
  border: none;
  padding: 0.5rem;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  transition: all 0.2s ease;
  box-shadow: none;
}

.fav-btn:hover {
  background-color: rgba(250, 204, 21, 0.1);
  transform: scale(1.1);
}

.fav-btn.active:hover {
  background-color: rgba(250, 204, 21, 0.15);
}

/* Dokonalé vycentrování - potlačení globálního odsazení z App.vue */
.fav-btn :deep(svg) {
  margin: 0 !important;
}
</style>