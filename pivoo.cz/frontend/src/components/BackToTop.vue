<template>
  <transition name="fade-slide">
    <button
      v-if="isVisible"
      @click="scrollToTop"
      class="back-to-top-btn"
      aria-label="Zpět nahoru"
      title="Zpět nahoru"
    >
      <ArrowUpIcon class="arrow-icon" />
    </button>
  </transition>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { ArrowUpIcon } from 'lucide-vue-next'

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
.back-to-top-btn {
  position: fixed;
  bottom: 2rem;
  right: 2rem;
  z-index: 99;
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
  /* Přidáme padding 0, aby se tlačítko nedeformovalo */
  padding: 0;
}

/* TÍMTO PŘEBIJEME GLOBÁLNÍ STYLY Z APP.VUE:
  Musíme použít !important, abychom měli jistotu, že se aplikuje 
  správná velikost a zruší se případné vnější okraje (margin).
*/
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

/* Animace pro zobrazení a skrytí */
.fade-slide-enter-active,
.fade-slide-leave-active {
  transition: opacity 0.3s ease, transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.fade-slide-enter-from,
.fade-slide-leave-to {
  opacity: 0;
  transform: translateY(20px);
}

/* Úprava pro mobilní zobrazení */
@media (max-width: 900px) {
  .back-to-top-btn {
    bottom: calc(75px + env(safe-area-inset-bottom));
    right: 1rem;
    width: 50px;
    height: 50px;
  }
  
  .back-to-top-btn .arrow-icon {
    width: 24px !important;
    height: 24px !important;
  }
}
</style>