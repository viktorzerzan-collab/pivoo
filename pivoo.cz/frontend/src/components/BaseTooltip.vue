<template>
  <div 
    class="base-tooltip-wrapper" 
    @mouseenter="show = true" 
    @mouseleave="show = false"
    @focusin="show = true"
    @focusout="show = false"
  >
    <slot></slot>

    <transition name="tooltip-fade">
      <div v-show="show && text" class="tooltip-box" :class="position">
        {{ text }}
        <div class="tooltip-arrow"></div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref } from 'vue'

defineProps({
  text: {
    type: String,
    required: true
  },
  // Možnosti: 'top', 'bottom', 'left', 'right'
  position: {
    type: String,
    default: 'top'
  }
})

const show = ref(false)
</script>

<style scoped>
.base-tooltip-wrapper {
  position: relative;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.tooltip-box {
  position: absolute;
  background-color: #1e293b; /* Tmavá barva pro dobrý kontrast v obou režimech */
  color: #f8fafc;
  font-size: 0.75rem;
  font-weight: 600;
  padding: 0.4rem 0.75rem;
  border-radius: 6px;
  white-space: nowrap;
  z-index: 9999;
  pointer-events: none;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.2), 0 2px 4px -1px rgba(0, 0, 0, 0.1);
  letter-spacing: 0.02em;
}

.tooltip-arrow {
  position: absolute;
  width: 0;
  height: 0;
  border-style: solid;
}

/* POZICE: TOP (Výchozí) */
.tooltip-box.top {
  bottom: calc(100% + 8px);
  left: 50%;
  transform: translateX(-50%);
}
.tooltip-box.top .tooltip-arrow {
  bottom: -4px;
  left: 50%;
  transform: translateX(-50%);
  border-width: 5px 5px 0 5px;
  border-color: #1e293b transparent transparent transparent;
}

/* POZICE: BOTTOM */
.tooltip-box.bottom {
  top: calc(100% + 8px);
  left: 50%;
  transform: translateX(-50%);
}
.tooltip-box.bottom .tooltip-arrow {
  top: -4px;
  left: 50%;
  transform: translateX(-50%);
  border-width: 0 5px 5px 5px;
  border-color: transparent transparent #1e293b transparent;
}

/* POZICE: LEFT */
.tooltip-box.left {
  right: calc(100% + 8px);
  top: 50%;
  transform: translateY(-50%);
}
.tooltip-box.left .tooltip-arrow {
  right: -4px;
  top: 50%;
  transform: translateY(-50%);
  border-width: 5px 0 5px 5px;
  border-color: transparent transparent transparent #1e293b;
}

/* POZICE: RIGHT */
.tooltip-box.right {
  left: calc(100% + 8px);
  top: 50%;
  transform: translateY(-50%);
}
.tooltip-box.right .tooltip-arrow {
  left: -4px;
  top: 50%;
  transform: translateY(-50%);
  border-width: 5px 5px 5px 0;
  border-color: transparent #1e293b transparent transparent;
}

/* ANIMACE */
.tooltip-fade-enter-active,
.tooltip-fade-leave-active {
  transition: opacity 0.2s ease, transform 0.2s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.tooltip-fade-enter-from,
.tooltip-fade-leave-to {
  opacity: 0;
}

/* Jemný pohyb podle pozice při animaci */
.tooltip-fade-enter-from.top { transform: translate(-50%, 5px); }
.tooltip-fade-enter-from.bottom { transform: translate(-50%, -5px); }
.tooltip-fade-enter-from.left { transform: translate(5px, -50%); }
.tooltip-fade-enter-from.right { transform: translate(-5px, -50%); }
</style>