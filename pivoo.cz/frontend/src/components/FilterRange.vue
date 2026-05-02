<template>
  <div class="filter-range-group">
    <label v-if="label" class="base-label">{{ label }}</label>
    <div class="range-inputs">
      <div class="input-wrapper">
        <input 
          type="number" 
          :min="min" 
          :max="max" 
          :step="step"
          placeholder="Od"
          :value="modelValue.min"
          @input="updateMin($event.target.value)"
          class="range-input"
        />
        <span v-if="unit" class="range-unit">{{ unit }}</span>
      </div>
      <span class="range-separator">-</span>
      <div class="input-wrapper">
        <input 
          type="number" 
          :min="min" 
          :max="max" 
          :step="step"
          placeholder="Do"
          :value="modelValue.max"
          @input="updateMax($event.target.value)"
          class="range-input"
        />
        <span v-if="unit" class="range-unit">{{ unit }}</span>
      </div>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  modelValue: { type: Object, required: true, default: () => ({ min: '', max: '' }) },
  label: { type: String, default: '' },
  min: { type: Number, default: 0 },
  max: { type: Number, default: 100 },
  step: { type: Number, default: 1 },
  unit: { type: String, default: '' }
})

const emit = defineEmits(['update:modelValue'])

const updateMin = (val) => emit('update:modelValue', { ...props.modelValue, min: val })
const updateMax = (val) => emit('update:modelValue', { ...props.modelValue, max: val })
</script>

<style scoped>
.filter-range-group {
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
  transition: color 0.3s ease;
}

.range-inputs {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.input-wrapper {
  position: relative;
  flex: 1;
  display: flex;
  align-items: center;
}

.range-input {
  width: 100%;
  padding: 0.75rem 1rem;
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  background-color: var(--bg-panel);
  color: var(--text-main);
  font-size: 0.95rem;
  transition: all 0.3s ease;
  box-shadow: none;
  outline: none;
}

.range-input:focus {
  border-color: var(--primary);
  box-shadow: 0 0 0 3px rgba(250, 204, 21, 0.15);
}

.input-wrapper:has(.range-unit) .range-input {
  padding-right: 2.2rem;
}

.range-unit {
  position: absolute;
  right: 1rem;
  color: var(--text-muted);
  font-size: 0.85rem;
  pointer-events: none;
}

.range-separator {
  color: var(--text-muted);
  font-weight: 600;
}
</style>