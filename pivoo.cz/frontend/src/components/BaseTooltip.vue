<template>
  <div 
    class="base-tooltip-wrapper" 
    ref="wrapper"
    @mouseenter="onEnter" 
    @mouseleave="onLeave"
    @focusin="onEnter"
    @focusout="onLeave"
  >
    <slot></slot>

    <Teleport to="body">
      <transition name="tooltip-fade">
        <div 
          v-show="show && text" 
          class="tooltip-box" 
          :class="position"
          :style="tooltipStyle"
          ref="tooltip"
        >
          {{ text }}
          <div class="tooltip-arrow"></div>
        </div>
      </transition>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, nextTick, onBeforeUnmount } from 'vue'

const props = defineProps({
  text: {
    type: String,
    required: true
  },
  position: {
    type: String,
    default: 'top'
  }
})

const show = ref(false)
const wrapper = ref(null)
const tooltip = ref(null)
const tooltipStyle = ref({})

// Dynamický výpočet pozice vůči obrazovce
const updatePosition = async () => {
  if (!show.value || !wrapper.value) return
  
  // Počkáme na bezpečné vykreslení DOMu, abychom mohli změřit šířku a výšku
  await nextTick()
  
  if (!tooltip.value) return

  const rect = wrapper.value.getBoundingClientRect()
  const tipRect = tooltip.value.getBoundingClientRect()
  
  const scrollY = window.scrollY || window.pageYOffset
  const scrollX = window.scrollX || window.pageXOffset

  let top = 0
  let left = 0

  // Výpočet souřadnic
  if (props.position === 'top') {
    top = rect.top + scrollY - tipRect.height - 8
    left = rect.left + scrollX + (rect.width / 2) - (tipRect.width / 2)
  } else if (props.position === 'bottom') {
    top = rect.bottom + scrollY + 8
    left = rect.left + scrollX + (rect.width / 2) - (tipRect.width / 2)
  } else if (props.position === 'left') {
    top = rect.top + scrollY + (rect.height / 2) - (tipRect.height / 2)
    left = rect.left + scrollX - tipRect.width - 8
  } else if (props.position === 'right') {
    top = rect.top + scrollY + (rect.height / 2) - (tipRect.height / 2)
    left = rect.right + scrollX + 8
  } else if (props.position === 'top-end') {
    top = rect.top + scrollY - tipRect.height - 8
    left = rect.right + scrollX - tipRect.width
  }

  tooltipStyle.value = {
    top: `${top}px`,
    left: `${left}px`,
    position: 'absolute',
    zIndex: 99999 // Vždy navrchu i nad jakýmkoliv modálem
  }
}

const onEnter = () => {
  show.value = true
  updatePosition()
  // Přídání eventListenerů, aby tooltip sledoval tlačítko, pokud uživatel scrolluje
  window.addEventListener('scroll', updatePosition, true)
  window.addEventListener('resize', updatePosition)
}

const onLeave = () => {
  show.value = false
  window.removeEventListener('scroll', updatePosition, true)
  window.removeEventListener('resize', updatePosition)
}

onBeforeUnmount(() => {
  window.removeEventListener('scroll', updatePosition, true)
  window.removeEventListener('resize', updatePosition)
})
</script>

<style scoped>
.base-tooltip-wrapper {
  position: relative;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.tooltip-box {
  background-color: #1e293b; 
  color: #f8fafc;
  font-size: 0.75rem;
  font-weight: 600;
  padding: 0.4rem 0.75rem;
  border-radius: 6px;
  white-space: nowrap;
  pointer-events: none;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

.tooltip-arrow {
  position: absolute;
  width: 0;
  height: 0;
  border-style: solid;
}

/* Úprava chování šipek - absolutní pozicování tooltipů obstaráváme inline styly */
.tooltip-box.top .tooltip-arrow {
  bottom: -4px;
  left: 50%;
  transform: translateX(-50%);
  border-width: 5px 5px 0 5px;
  border-color: #1e293b transparent transparent transparent;
}

.tooltip-box.bottom .tooltip-arrow {
  top: -4px;
  left: 50%;
  transform: translateX(-50%);
  border-width: 0 5px 5px 5px;
  border-color: transparent transparent #1e293b transparent;
}

.tooltip-box.left .tooltip-arrow {
  right: -4px;
  top: 50%;
  transform: translateY(-50%);
  border-width: 5px 0 5px 5px;
  border-color: transparent transparent transparent #1e293b;
}

.tooltip-box.right .tooltip-arrow {
  left: -4px;
  top: 50%;
  transform: translateY(-50%);
  border-width: 5px 5px 5px 0;
  border-color: transparent #1e293b transparent transparent;
}

.tooltip-box.top-end .tooltip-arrow {
  bottom: -4px;
  right: 12px;
  border-width: 5px 5px 0 5px;
  border-color: #1e293b transparent transparent transparent;
}

/* Animace (vyhlazena pro čistější scale) */
.tooltip-fade-enter-active,
.tooltip-fade-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
  transform-origin: center;
}

.tooltip-fade-enter-from,
.tooltip-fade-leave-to {
  opacity: 0;
  transform: scale(0.95);
}
</style>