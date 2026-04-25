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
import BaseTooltip from './BaseTooltip.vue' // Přidán import Tooltipu

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
/* PONECHEJ SI ZDE SVŮJ PŮVODNÍ CSS KÓD, přidej jen toto nakonec: */

/* Dokonalé vycentrování - potlačení globálního odsazení z App.vue */
.fav-btn :deep(svg) {
  margin: 0 !important;
}
</style>