<template>
  <BaseTooltip 
    v-if="authStore.user" 
    :text="isWishlist ? 'Odebrat z wishlistu' : 'Přidat do wishlistu'" 
    position="top"
  >
    <button 
      class="wishlist-btn" 
      :class="{ 'active': isWishlist }" 
      @click.stop="$emit('toggle')" 
    >
      <BookmarkIcon 
        :size="size" 
        :fill="isWishlist ? 'var(--primary)' : 'none'" 
        :color="isWishlist ? 'var(--primary)' : 'var(--text-muted)'"
      />
    </button>
  </BaseTooltip>
</template>

<script setup>
import { BookmarkIcon } from 'lucide-vue-next'
import { useAuthStore } from '../stores/auth'
import BaseTooltip from './BaseTooltip.vue' 

defineProps({
  isWishlist: {
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
.wishlist-btn {
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

.wishlist-btn:hover {
  background-color: rgba(250, 204, 21, 0.1);
  transform: scale(1.1);
}

.wishlist-btn.active:hover {
  background-color: rgba(250, 204, 21, 0.15);
}

.wishlist-btn :deep(svg) {
  margin: 0 !important;
}
</style>