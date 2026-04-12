<template>
  <div class="base-select-group">
    <label v-if="label" class="base-label">{{ label }}</label>
    <div class="select-wrapper">
      <select 
        :value="modelValue" 
        @change="$emit('update:modelValue', $event.target.value)"
        class="base-select"
        v-bind="$attrs"
      >
        <slot></slot>
      </select>
    </div>
  </div>
</template>

<script setup>
defineProps({
  modelValue: [String, Number],
  label: String
})
defineEmits(['update:modelValue'])
</script>

<style scoped>
.base-select-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  width: 100%;
  text-align: left;
}

.base-label {
  font-size: 0.9rem;
  font-weight: 600;
  color: #475569;
}

.select-wrapper {
  position: relative;
  width: 100%;
}

.base-select {
  width: 100%;
  padding: 0.75rem 2.5rem 0.75rem 1rem;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  background: white;
  font-size: 0.95rem;
  font-family: inherit;
  appearance: none;
  -webkit-appearance: none;
  cursor: pointer;
  outline: none;
  transition: all 0.2s ease;
  
  /* Vlastní šipka */
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2364748b' stroke-width='2'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 1rem center;
  background-size: 1.1rem;
}

.base-select:focus {
  border-color: var(--primary);
  box-shadow: 0 0 0 3px rgba(250, 204, 21, 0.2);
}
</style>