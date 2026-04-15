<template>
  <div class="filter-select-wrapper" ref="selectRef">
    <component :is="icon" :size="18" class="field-icon" />
    
    <button 
      type="button" 
      class="custom-select-trigger" 
      :class="{ 'is-open': isOpen }"
      @click="toggleDropdown"
    >
      <span class="selected-text">{{ selectedLabel || 'Vyberte...' }}</span>
    </button>
    <ChevronDownIcon :size="18" class="select-arrow" :class="{ 'rotated': isOpen }" />

    <transition name="dropdown-slide">
      <div v-if="isOpen" class="options-menu">
        <ul class="options-list">
          <li 
            v-for="option in parsedOptions" 
            :key="option.value" 
            class="option-item"
            :class="{ 'is-selected': modelValue == option.value }"
            @click="selectOption(option)"
          >
            {{ option.label }}
            <CheckIcon v-if="modelValue == option.value" :size="16" class="check-icon" />
          </li>
        </ul>
      </div>
    </transition>

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
  icon: [Object, Function] 
})

const emit = defineEmits(['update:modelValue'])
const slots = useSlots()

const isOpen = ref(false)
const selectRef = ref(null)
const slotContainer = ref(null)
const parsedOptions = ref([])

const updateOptionsFromSlots = () => {
  if (!slotContainer.value) return
  const options = slotContainer.value.querySelectorAll('option')
  parsedOptions.value = Array.from(options).map(opt => ({
    label: opt.textContent.trim(),
    value: opt.value
  }))
}

const selectedLabel = computed(() => {
  const active = parsedOptions.value.find(opt => opt.value == props.modelValue)
  return active ? active.label : null
})

const toggleDropdown = () => {
  isOpen.value = !isOpen.value
}

const selectOption = (option) => {
  emit('update:modelValue', option.value)
  isOpen.value = false
}

const handleClickOutside = (event) => {
  if (selectRef.value && !selectRef.value.contains(event.target)) {
    isOpen.value = false
  }
}

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

watch(() => slots.default?.(), updateOptionsFromSlots, { deep: true })
</script>

<style scoped>
.filter-select-wrapper { 
  position: relative; 
  display: flex; 
  align-items: center; 
  width: 100%; 
}

.field-icon { 
  position: absolute; 
  left: 12px; 
  color: var(--text-muted); 
  pointer-events: none; 
  z-index: 1; 
  transition: color 0.5s ease; 
}

.select-arrow { 
  position: absolute; 
  right: 12px; 
  color: var(--text-muted); 
  pointer-events: none; 
  z-index: 1; 
  transition: transform 0.3s ease, color 0.5s ease; 
}

.select-arrow.rotated {
  transform: rotate(180deg);
  color: var(--primary);
}

.custom-select-trigger {
  width: 100%;
  height: 42px;
  padding: 0 2.5rem;
  border: 1px solid var(--border);
  border-radius: 10px;
  background: var(--bg-panel);
  color: var(--text-main);
  font-size: 0.95rem;
  display: flex;
  align-items: center;
  cursor: pointer;
  transition: all 0.3s ease;
  outline: none;
}

.custom-select-trigger:focus, .custom-select-trigger.is-open { 
  border-color: var(--primary); 
  box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.1); 
}

.selected-text {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  text-align: left;
  flex: 1;
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
  text-align: left;
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

.check-icon {
  color: var(--primary);
}

/* Scrollbar menu */
.options-menu::-webkit-scrollbar { width: 6px; }
.options-menu::-webkit-scrollbar-thumb { background-color: var(--border); border-radius: 10px; }

/* ANIMACE */
.dropdown-slide-enter-active, .dropdown-slide-leave-active { transition: opacity 0.2s ease, transform 0.2s ease; }
.dropdown-slide-enter-from, .dropdown-slide-leave-to { opacity: 0; transform: translateY(-10px); }
</style>