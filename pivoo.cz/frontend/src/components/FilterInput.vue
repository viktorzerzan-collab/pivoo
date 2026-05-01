<template>
  <div class="filter-input-group">
    <label v-if="label" class="base-label">{{ label }}</label>
    <div class="input-wrapper">
      <SearchIcon :size="18" class="field-icon" />
      <input 
        type="text" 
        :value="modelValue" 
        @input="handleInput"
        :placeholder="placeholder"
        class="filter-input"
      />
    </div>
  </div>
</template>

<script setup>
import { SearchIcon } from 'lucide-vue-next'
const props = defineProps({
  modelValue: String,
  label: { type: String, default: '' },
  placeholder: { type: String, default: 'Hledat...' }
})
const emit = defineEmits(['update:modelValue'])

let timeout = null

const handleInput = (event) => {
  clearTimeout(timeout)
  timeout = setTimeout(() => {
    emit('update:modelValue', event.target.value)
  }, 300)
}
</script>

<style scoped>
.filter-input-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  width: 100%;
  text-align: left;
}

.base-label {
  font-size: 0.9rem;
  font-weight: 600;
  color: var(--text-main);
  transition: color 0.5s ease;
}

.input-wrapper { 
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

.filter-input {
  width: 100%;
  /* Dostatečný padding na levé straně, aby se text nepřekrýval s ikonou lupy */
  padding: 0.75rem 1rem 0.75rem 2.5rem;
  border: 1px solid var(--border);
  border-radius: 10px;
  background-color: var(--bg-app); /* Lehce odlišené od samotného panelu */
  color: var(--text-main);
  font-size: 0.95rem;
  font-family: inherit;
  transition: all 0.3s ease;
  box-shadow: inset 0 1px 2px rgba(0,0,0,0.02);
  outline: none;
}

.filter-input:focus { 
  border-color: var(--primary); 
  box-shadow: 0 0 0 3px rgba(250, 204, 21, 0.15); 
}

/* Zajištění správného vykreslení v Chrome/Webkit (zrušení defaultního bílého pozadí při autofillu) */
.filter-input:-webkit-autofill,
.filter-input:-webkit-autofill:hover, 
.filter-input:-webkit-autofill:focus, 
.filter-input:-webkit-autofill:active {
  -webkit-box-shadow: 0 0 0 30px var(--bg-app) inset !important;
  -webkit-text-fill-color: var(--text-main) !important;
  transition: background-color 5000s ease-in-out 0s;
}
</style>