<template>
  <label class="base-checkbox-wrapper">
    <div class="checkbox-container">
      <input 
        type="checkbox" 
        :checked="modelValue" 
        @change="$emit('update:modelValue', $event.target.checked)"
        class="hidden-input"
      />
      <div class="checkbox-box"></div>
    </div>
    <span v-if="label" class="checkbox-label">{{ label }}</span>
  </label>
</template>

<script setup>
defineProps({
  modelValue: {
    type: Boolean,
    default: false
  },
  label: String
})
defineEmits(['update:modelValue'])
</script>

<style scoped>
.base-checkbox-wrapper {
  display: inline-flex;
  align-items: center;
  gap: 0.75rem;
  cursor: pointer;
  user-select: none;
  padding: 0.5rem 0;
}

.checkbox-container {
  position: relative;
  width: 22px;
  height: 22px;
  flex-shrink: 0;
}

.hidden-input {
  position: absolute;
  opacity: 0;
  cursor: pointer;
  height: 0;
  width: 0;
}

.checkbox-box {
  position: absolute;
  top: 0;
  left: 0;
  height: 22px;
  width: 22px;
  background-color: var(--bg-panel);
  border: 2px solid var(--border);
  border-radius: 6px;
  transition: all 0.2s ease;
}

.base-checkbox-wrapper:hover .checkbox-box {
  border-color: var(--primary);
}

.hidden-input:checked ~ .checkbox-box {
  background-color: var(--primary);
  border-color: var(--primary);
}

.checkbox-box:after {
  content: "";
  position: absolute;
  display: none;
  left: 6px;
  top: 2px;
  width: 6px;
  height: 11px;
  border: solid #1e293b;
  border-width: 0 2.5px 2.5px 0;
  transform: rotate(45deg);
}

.hidden-input:checked ~ .checkbox-box:after {
  display: block;
}

.checkbox-label {
  font-size: 0.95rem;
  font-weight: 600;
  color: var(--text-main);
  transition: color 0.5s ease;
}

/* Podpora pro tmavý režim je zajištěna skrze CSS proměnné */
</style>