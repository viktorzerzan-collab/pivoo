<template>
  <div class="filter-input-wrapper">
    <SearchIcon :size="18" class="field-icon" />
    <input 
      type="text" 
      :value="modelValue" 
      @input="$emit('update:modelValue', $event.target.value)"
      :placeholder="placeholder"
      class="custom-input"
    />
  </div>
</template>

<script setup>
import { SearchIcon } from 'lucide-vue-next'
defineProps({
  modelValue: String,
  placeholder: { type: String, default: 'Hledat...' }
})
defineEmits(['update:modelValue'])
</script>

<style scoped>
.filter-input-wrapper { position: relative; display: flex; align-items: center; width: 100%; }
.field-icon { position: absolute; left: 12px; color: #94a3b8; pointer-events: none; z-index: 1; }
.custom-input {
  width: 100%;
  height: 42px;
  padding: 0 12px 0 2.5rem;
  border: 1px solid var(--border);
  border-radius: 10px;
  background: white;
  font-size: 0.95rem;
  color: #1e293b;
  transition: all 0.2s ease;
}
.custom-input:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.1); }
</style>