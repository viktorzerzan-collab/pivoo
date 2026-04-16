<template>
  <transition name="fade">
    <div v-if="show" class="loader-overlay">
      <div class="loader-content">
        <div class="spinner"></div>
        <p class="loader-text">{{ message }}</p>
      </div>
    </div>
  </transition>
</template>

<script setup>
defineProps({
  show: Boolean,
  // Sjednocený výchozí text pro celou aplikaci
  message: { type: String, default: 'Probíhá načítání...' }
})
</script>

<style scoped>
/* OPRAVA: Změněno z absolute na fixed, aby překrylo celou obrazovku */
.loader-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.6);
  backdrop-filter: blur(8px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999; /* OPRAVA: Zvýšeno na 9999, aby překrylo i horní lištu (ta má z-index 50) */
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