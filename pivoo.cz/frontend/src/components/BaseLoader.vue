<template>
  <transition name="fade">
    <div v-if="isActuallyVisible" class="loader-overlay">
      <div class="loader-content">
        <div class="spinner"></div>
        <p class="loader-text">{{ message || $t('loader.loading') }}</p>
      </div>
    </div>
  </transition>
</template>

<script setup>
import { ref, watch, onUnmounted } from 'vue'

const props = defineProps({
  show: Boolean,
  message: { type: String, default: '' }
})

const isActuallyVisible = ref(false)
let timeoutId = null

watch(() => props.show, (newVal) => {
  if (newVal) {
    timeoutId = setTimeout(() => {
      isActuallyVisible.value = true
    }, 200)
  } else {
    if (timeoutId) clearTimeout(timeoutId)
    isActuallyVisible.value = false
  }
}, { immediate: true })

onUnmounted(() => {
  if (timeoutId) clearTimeout(timeoutId)
})
</script>

<style scoped>
.loader-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.6);
  backdrop-filter: blur(8px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
}

.loader-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 4px solid var(--border);
  border-top: 4px solid var(--primary);
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

.loader-text {
  font-weight: 600;
  color: #f1f5f9;
  text-shadow: 0 2px 4px rgba(0,0,0,0.5);
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.fade-enter-active, .fade-leave-active { transition: opacity 0.3s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>