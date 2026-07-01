<template>
  <Teleport to="body">
    <transition name="modal-fade">
      <div v-if="show" class="modal-overlay" @click.self="$emit('close')">
        <div class="modal-container" :style="customStyle">
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
  </Teleport>
</template>

<script setup>
import { watch, onUnmounted } from 'vue'
import { XIcon } from 'lucide-vue-next'

const props = defineProps({ 
  show: Boolean,
  customStyle: {
    type: [String, Object],
    default: ''
  }
})
defineEmits(['close'])

let scrollPosition = 0

watch(() => props.show, (isShown) => {
  if (isShown) {
    scrollPosition = window.scrollY
    const docHeight = document.documentElement.scrollHeight
    
    document.body.style.position = 'fixed'
    document.body.style.top = `-${scrollPosition}px`
    document.body.style.width = '100%'
    document.body.style.overflowY = 'scroll'
    document.body.style.height = `${docHeight}px`
    document.body.classList.add('modal-open')
  } else {
    document.body.style.position = ''
    document.body.style.top = ''
    document.body.style.width = ''
    document.body.style.overflowY = ''
    document.body.style.height = ''
    document.body.classList.remove('modal-open')
    window.scrollTo(0, scrollPosition)
  }
}, { immediate: true })

onUnmounted(() => {
  document.body.style.position = ''
  document.body.style.top = ''
  document.body.style.width = ''
  document.body.style.overflowY = ''
  document.body.style.height = ''
  document.body.classList.remove('modal-open')
  window.scrollTo(0, scrollPosition)
})
</script>

<style scoped>
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  box-sizing: border-box;
  background: rgba(0, 0, 0, 0.7);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 2000;
  /* Zmenšeno z 1.25rem pro okraje okolo modalu na mobilu */
  padding: 1rem; 
}

.modal-container {
  background: var(--bg-panel);
  color: var(--text-main);
  width: 100%;
  max-width: 500px;
  max-height: 100%;
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-floating);
  display: flex;
  flex-direction: column;
  overflow: hidden;
  position: relative;
  transition: background-color 0.3s ease, color 0.3s ease;
}

.modal-header {
  /* Zmenšeno z 1.5rem na 1.25rem */
  padding: 1.25rem; 
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid var(--border);
  position: relative;
  flex-shrink: 0;
  transition: border-color 0.3s ease;
}

.modal-header h2 {
  margin: 0;
  /* Zmenšeno z 1.5rem pro kompaktnost */
  font-size: 1.35rem; 
  color: var(--text-main);
  transition: color 0.3s ease;
}

.btn-close {
  background: transparent;
  border: none;
  cursor: pointer;
  color: inherit;
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  z-index: 10;
}

.modal-body {
  /* Zmenšeno z 1.5rem na 1.25rem */
  padding: 1.25rem; 
  overflow-y: auto;
  flex: 1; 
  min-height: 0; 
}

/* ODDĚLENÁ LOGIKA PRO MOBILNÍ TELEFONY */
@media (max-width: 600px) {
  .modal-header {
    padding: 1rem;
  }
  .modal-body {
    padding: 1rem;
  }
  .modal-header h2 {
    font-size: 1.25rem;
  }
}

.modal-fade-enter-active, .modal-fade-leave-active { transition: all 0.3s ease; }
.modal-fade-enter-from, .modal-fade-leave-to { opacity: 0; transform: scale(0.95); }
</style>