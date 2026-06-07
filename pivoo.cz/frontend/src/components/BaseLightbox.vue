<template>
  <teleport to="body">
    <div v-if="show" class="lightbox-overlay" @click="$emit('close')">
      <button class="lightbox-close" @click.stop="$emit('close')">
        <XIcon :size="32" />
      </button>
      <img :src="src" :alt="alt" class="lightbox-img" @click.stop />
    </div>
  </teleport>
</template>

<script setup>
import { XIcon } from 'lucide-vue-next'

defineProps({
  show: {
    type: Boolean,
    required: true
  },
  src: {
    type: String,
    required: true
  },
  alt: {
    type: String,
    default: 'Náhled obrázku'
  }
})

defineEmits(['close'])
</script>

<style>
.lightbox-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background-color: rgba(0, 0, 0, 0.85);
  z-index: 9999;
  display: flex;
  align-items: center;
  justify-content: center;
  backdrop-filter: blur(5px);
}

.lightbox-close {
  position: absolute;
  top: 20px;
  right: 20px;
  background: transparent;
  border: none;
  color: white;
  cursor: pointer;
  padding: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: transform 0.2s ease;
}

.lightbox-close:hover {
  transform: scale(1.1);
}

.lightbox-img {
  max-width: 95vw;
  max-height: 90vh;
  object-fit: contain;
  border-radius: var(--radius-md, 8px);
  box-shadow: 0 4px 25px rgba(0, 0, 0, 0.5);
}
</style>