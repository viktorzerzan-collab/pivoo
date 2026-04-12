<template>
  <div v-if="show" class="modal-backdrop" @click.self="closeModal">
    <div class="modal-card">
      <button class="close-btn" @click="closeModal" title="Zavřít">×</button>
      <div class="modal-header">
        <slot name="header"></slot> 
      </div>
      <div class="modal-body">
        <slot name="body"></slot>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  show: Boolean
})

const emit = defineEmits(['close'])

const closeModal = () => {
  emit('close')
}
</script>

<style scoped>
.modal-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.75);
  backdrop-filter: blur(8px);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1000;
  padding: 1rem; /* Aby modál na mobilu nebyl nalepený úplně na kraj displeje */
  box-sizing: border-box;
}

.modal-card {
  background: white;
  width: 100%;
  max-width: 500px;
  border-radius: 12px;
  padding: 2.5rem 2rem;
  position: relative;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
  max-height: 95vh; /* Aby nepřetekl z obrazovky, když je dlouhý */
  overflow-y: auto; /* Pokud se nevejde, půjde scrollovat uvnitř okna */
}

.close-btn {
  position: absolute;
  top: 1rem;
  right: 1.5rem;
  background: transparent;
  border: none;
  font-size: 2.5rem;
  color: #9ca3af;
  cursor: pointer;
  transition: color 0.2s;
  line-height: 1;
}

.close-btn:hover {
  color: #ef4444;
}

.modal-header {
  margin-bottom: 1.5rem;
}

/* RESPONZIVNÍ DESIGN PRO MOBILY (Max šířka 600px) */
@media (max-width: 600px) {
  .modal-card {
    padding: 1.5rem 1rem; /* Zmenšíme vnitřní okraje na mobilu */
  }
  
  .close-btn {
    top: 0.5rem;
    right: 1rem;
  }
}
</style>