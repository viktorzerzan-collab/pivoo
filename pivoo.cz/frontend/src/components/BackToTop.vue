<template>
  <transition name="fade-slide">
    <div v-if="isVisible" class="back-to-top-wrapper">
      <BaseTooltip text="Zpět nahoru" position="top">
        <button
          @click="scrollToTop"
          class="back-to-top-btn"
          aria-label="Zpět nahoru"
        >
          <ArrowUpIcon class="arrow-icon" />
        </button>
      </BaseTooltip>
    </div>
  </transition>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { ArrowUpIcon } from 'lucide-vue-next'
import BaseTooltip from './BaseTooltip.vue' // Přidán import Tooltipu

const isVisible = ref(false)

const checkScroll = () => {
  isVisible.value = window.scrollY > 300
}

const scrollToTop = () => {
  window.scrollTo({
    top: 0,
    behavior: 'smooth'
  })
}

onMounted(() => {
  window.addEventListener('scroll', checkScroll)
})

onUnmounted(() => {
  window.removeEventListener('scroll', checkScroll)
})
</script>

<style scoped>
/* Nový obal, který drží tlačítko i tooltip ukotvené v pravém rohu */
.back-to-top-wrapper {
  position: fixed;
  bottom: 2rem;
  right: 2rem;
  z-index: 99;
}

.back-to-top-btn {
  /* position, bottom, right a z-index jsme přesunuli o třídu výš */
  background-color: var(--primary); 
  color: #1e293b;
  border: none;
  border-radius: 50%;
  width: 56px; 
  height: 56px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  box-shadow: var(--shadow-md);
  transition: all 0.3s ease;
  padding: 0;
}

.back-to-top-btn .arrow-icon {
  width: 28px !important;
  height: 28px !important;
  stroke-width: 2.5 !important;
  margin: 0 !important;
  color: #1e293b !important;
}

.back-to-top-btn:hover {
  background-color: var(--primary-hover);
  transform: translateY(-4px);
  box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2);
}

.back-to-top-btn:active {
  transform: translateY(0);
}

/* Animace zůstává stejná */
.fade-slide-enter-active,
.fade-slide-leave-active {
  transition: opacity 0.3s ease, transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.fade-slide-enter-from,
.fade-slide-leave-to {
  opacity: 0;
  transform: translateY(20px);
}
</style>