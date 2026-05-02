<template>
  <div class="base-switch" :class="{ 'is-full-width': fullWidth }">
    <button
      v-for="option in options"
      :key="option.value"
      type="button"
      class="switch-btn"
      :class="{ active: modelValue === option.value }"
      @click="$emit('update:modelValue', option.value)"
    >
      <component v-if="option.icon" :is="option.icon" :size="18" class="switch-icon" />
      <span class="switch-label">{{ option.label }}</span>
    </button>
  </div>
</template>

<script setup>
defineProps({
  modelValue: {
    type: [String, Number, Boolean],
    required: true
  },
  options: {
    type: Array,
    required: true
  },
  fullWidth: {
    type: Boolean,
    default: false
  }
})

defineEmits(['update:modelValue'])
</script>

<style scoped>
.base-switch {
  display: inline-flex;
  background-color: var(--border);
  padding: 0.375rem;
  border-radius: var(--radius-md);
  gap: 0.25rem;
  flex-wrap: wrap;
  transition: background-color 0.3s ease;
}

.base-switch.is-full-width {
  display: flex;
  width: 100%;
}

.base-switch.is-full-width .switch-btn {
  flex: 1;
}

.switch-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 0.6rem 1.25rem;
  border: none;
  background: transparent;
  cursor: pointer;
  border-radius: var(--radius-sm);
  font-weight: 600;
  font-size: 0.95rem;
  color: var(--text-muted);
  transition: all 0.2s ease;
  white-space: nowrap;
}

.switch-btn:hover:not(.active) {
  color: var(--text-main);
  background: rgba(128, 128, 128, 0.1);
}

.switch-btn.active {
  background-color: var(--primary);
  color: #1e293b;
}

.switch-icon {
  flex-shrink: 0;
}

@media (max-width: 600px) {
  .base-switch {
    width: 100%;
    display: flex;
  }
  .switch-btn {
    flex: 1;
    padding: 0.6rem 0.5rem;
    font-size: 0.85rem;
  }
}
</style>