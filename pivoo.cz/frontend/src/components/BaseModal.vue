<template>
  <transition name="modal-fade">
    <div v-if="show" class="modal-overlay" @click.self="$emit('close')">
      <div class="modal-container">
        <header class="modal-header">
          <slot name="header"></slot>
          <button class="btn-close" @click="$emit('close')" aria-label="Zavřít">
            <XIcon :size="24" />
          </button>
        </header>
        
        <div class="modal-body">
          <slot name="body"></slot>
        </div>
      </div>
    </div>
  </transition>
</template>

<script setup>
import { XIcon } from 'lucide-vue-next'
defineProps({ show: Boolean })
defineEmits(['close'])
</script>

<style scoped>
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.7);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 2000;
  padding: 1.5rem;
}

.modal-container {
  background: var(--bg-panel);
  color: var(--text-main);
  width: 100%;
  max-width: 500px;
  border-radius: 16px;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5);
  display: flex;
  flex-direction: column;
  overflow: hidden;
  transition: background-color 0.5s ease, color 0.5s ease;
}

.modal-header {
  padding: 1.5rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid var(--border);
  transition: border-color 0.5s ease;
}

.modal-header h2 {
  margin: 0;
  font-size: 1.5rem;
  color: var(--text-main);
  transition: color 0.5s ease;
}

.modal-body {
  padding: 1.5rem;
  max-height: 80vh;
  overflow-y: auto;
}

.modal-fade-enter-active, .modal-fade-leave-active { transition: all 0.3s ease; }
.modal-fade-enter-from, .modal-fade-leave-to { opacity: 0; transform: scale(0.95); }
</style>