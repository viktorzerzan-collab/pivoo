<template>
  <button 
    v-if="authStore.user"
    class="fav-btn" 
    :class="{ 'active': isFavorite }" 
    @click.stop="$emit('toggle')" 
    :title="isFavorite ? 'Odebrat z oblíbených' : 'Přidat do oblíbených'"
  >
    <StarIcon 
      :size="size" 
      :fill="isFavorite ? 'var(--primary)' : 'none'" 
      :color="isFavorite ? 'var(--primary)' : 'var(--text-muted)'"
    />
  </button>
</template>

<script setup>
import { StarIcon } from 'lucide-vue-next'
import { useAuthStore } from '../stores/auth'

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
  cursor: pointer;
  color: var(--text-muted);
  transition: all 0.2s ease;
  
  /* Jednoduché Flexbox centrování podle vzoru theme-toggle-btn */
  width: 40px;
  height: 40px;
  padding: 0;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* Klíčové: potlačení globálního margin-right: 0.5rem ze styles.css */
.fav-btn :deep(svg) {
  margin: 0 !important;
}

.fav-btn:hover {
  transform: scale(1.15);
  color: var(--primary);
  background-color: var(--card-hover-bg); /* Jemné podbarvení při najetí pro lepší vizuální odezvu */
}

.fav-btn.active {
  color: var(--primary);
}
</style>