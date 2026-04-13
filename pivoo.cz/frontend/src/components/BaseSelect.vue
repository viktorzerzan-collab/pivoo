<template>
  <div class="base-select-group" ref="selectRef">
    <label v-if="label" class="base-label">{{ label }}</label>
    
    <div class="custom-select-container">
      <button 
        type="button" 
        class="select-trigger" 
        :class="{ 'is-open': isOpen, 'is-disabled': disabled }"
        @click="toggleDropdown"
        :disabled="disabled"
      >
        <span class="selected-text" :class="{ 'placeholder': !selectedLabel }">
          {{ selectedLabel || placeholder }}
        </span>
        <ChevronDownIcon :size="18" class="arrow-icon" :class="{ 'rotated': isOpen }" />
      </button>

      <transition name="dropdown-slide">
        <div v-if="isOpen" class="options-menu">
          <ul class="options-list">
            <li 
              v-for="option in parsedOptions" 
              :key="option.value" 
              class="option-item"
              :class="{ 
                'is-selected': modelValue == option.value, 
                'is-disabled': option.disabled 
              }"
              @click="selectOption(option)"
            >
              {{ option.label }}
              <CheckIcon v-if="modelValue == option.value" :size="16" class="check-icon" />
            </li>
            <li v-if="parsedOptions.length === 0" class="option-empty">
              Žádné možnosti k dispozici
            </li>
          </ul>
        </div>
      </transition>
    </div>

    <div v-show="false" ref="slotContainer">
      <slot></slot>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch, useSlots } from 'vue'
import { ChevronDownIcon, CheckIcon } from 'lucide-vue-next'

const props = defineProps({
  modelValue: [String, Number],
  label: String,
  placeholder: { type: String, default: '-- Vyberte --' },
  disabled: Boolean
})

const emit = defineEmits(['update:modelValue'])
const slots = useSlots()

const isOpen = ref(false)
const selectRef = ref(null)
const slotContainer = ref(null)
const parsedOptions = ref([])

// Funkce pro parsování <option> prvků ze slotu
const updateOptionsFromSlots = () => {
  if (!slotContainer.value) return
  
  // Najdeme všechny <option> prvky, které jsi vložil do slotu v rodiči
  const options = slotContainer.value.querySelectorAll('option')
  parsedOptions.value = Array.from(options).map(opt => ({
    label: opt.textContent.trim(),
    value: opt.value,
    disabled: opt.disabled
  }))
}

// Najdeme text aktuálně vybrané položky
const selectedLabel = computed(() => {
  const active = parsedOptions.value.find(opt => opt.value == props.modelValue)
  return active ? active.label : null
})

const toggleDropdown = () => {
  if (!props.disabled) isOpen.value = !isOpen.value
}

const selectOption = (option) => {
  if (option.disabled) return
  emit('update:modelValue', option.value)
  isOpen.value = false
}

// Zavření při kliknutí mimo komponent
const handleClickOutside = (event) => {
  if (selectRef.value && !selectRef.value.contains(event.target)) {
    isOpen.value = false
  }
}

// Sledujeme změny ve slotu (např. když se načtou pivovary z API)
const observer = new MutationObserver(updateOptionsFromSlots)

onMounted(() => {
  updateOptionsFromSlots()
  document.addEventListener('click', handleClickOutside)
  if (slotContainer.value) {
    observer.observe(slotContainer.value, { childList: true, subtree: true })
  }
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
  observer.disconnect()
})

// Reagujeme na změnu slotu manuálně pro jistotu
watch(() => slots.default?.(), updateOptionsFromSlots, { deep: true })
</script>

<style scoped>
.base-select-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  width: 100%;
  text-align: left;
  position: relative;
}

.base-label {
  font-size: 0.9rem;
  font-weight: 600;
  color: var(--text-main);
  transition: color 0.5s ease;
}

.custom-select-container {
  position: relative;
  width: 100%;
}

/* STYL SPOUŠTĚČE (Zavřený stav) */
.select-trigger {
  width: 100%;
  padding: 0.75rem 1rem;
  border: 1px solid var(--border);
  border-radius: 10px;
  background-color: var(--bg-panel);
  color: var(--text-main);
  font-size: 0.95rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: var(--shadow);
  outline: none;
}

.select-trigger:hover:not(.is-disabled) {
  border-color: var(--primary);
}

.select-trigger.is-open {
  border-color: var(--primary);
  box-shadow: 0 0 0 3px rgba(250, 204, 21, 0.15);
}

.select-trigger.is-disabled {
  background-color: var(--bg-app);
  color: var(--text-muted);
  cursor: not-allowed;
  opacity: 0.7;
}

.selected-text {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  margin-right: 0.5rem;
}

.selected-text.placeholder {
  color: var(--text-muted);
}

.arrow-icon {
  color: var(--text-muted);
  transition: transform 0.3s ease, color 0.3s ease;
  flex-shrink: 0;
}

.arrow-icon.rotated {
  transform: rotate(180deg);
  color: var(--primary);
}

/* STYL ROZBALENÉHO MENU */
.options-menu {
  position: absolute;
  top: calc(100% + 5px);
  left: 0;
  right: 0;
  background-color: var(--bg-panel);
  border: 1px solid var(--border);
  border-radius: 12px;
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.2);
  z-index: 1000;
  max-height: 250px;
  overflow-y: auto;
  overflow-x: hidden;
}

.options-list {
  list-style: none;
  padding: 0.5rem;
  margin: 0;
}

.option-item {
  padding: 0.75rem 1rem;
  border-radius: 8px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: space-between;
  color: var(--text-main);
  font-size: 0.95rem;
  font-weight: 500;
  transition: all 0.2s ease;
}

.option-item:hover {
  background-color: var(--card-hover-bg);
  color: var(--primary);
}

.option-item.is-selected {
  background-color: rgba(250, 204, 21, 0.1);
  color: var(--primary);
  font-weight: 700;
}

.option-item.is-disabled {
  opacity: 0.5;
  cursor: not-allowed;
  font-style: italic;
}

.check-icon {
  color: var(--primary);
}

.option-empty {
  padding: 1rem;
  color: var(--text-muted);
  text-align: center;
  font-size: 0.9rem;
}

/* Scrollbar menu */
.options-menu::-webkit-scrollbar {
  width: 6px;
}
.options-menu::-webkit-scrollbar-thumb {
  background-color: var(--border);
  border-radius: 10px;
}

/* ANIMACE */
.dropdown-slide-enter-active, .dropdown-slide-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}
.dropdown-slide-enter-from, .dropdown-slide-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}
</style>