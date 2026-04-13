<template>
  <div class="filter-select-wrapper">
    <component :is="icon" :size="18" class="field-icon" />
    <select 
      :value="modelValue" 
      @change="$emit('update:modelValue', $event.target.value)"
      class="custom-select"
    >
      <slot></slot>
    </select>
    <ChevronDownIcon :size="18" class="select-arrow" />
  </div>
</template>

<script setup>
import { ChevronDownIcon } from 'lucide-vue-next'
defineProps({
  modelValue: [String, Number],
  icon: [Object, Function] 
})
defineEmits(['update:modelValue'])
</script>

<style scoped>
.filter-select-wrapper { position: relative; display: flex; align-items: center; width: 100%; }
.field-icon { position: absolute; left: 12px; color: var(--text-muted); pointer-events: none; z-index: 1; transition: color 0.5s ease; }
.select-arrow { position: absolute; right: 12px; color: var(--text-muted); pointer-events: none; z-index: 1; transition: color 0.5s ease; }
.custom-select {
  width: 100%;
  height: 42px;
  padding: 0 2.5rem;
  border: 1px solid var(--border);
  border-radius: 10px;
  background: var(--bg-panel);
  color: var(--text-main);
  font-size: 0.95rem;
  appearance: none;
  cursor: pointer;
  transition: all 0.3s ease;
}
.custom-select:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.1); }
</style>