<template>
  <button 
    class="base-button" 
    :class="[variant, { 'is-icon-only': isIconOnly }]"
    v-bind="$attrs"
  >
    <span v-if="$slots.icon" class="button-icon">
      <slot name="icon"></slot>
    </span>
    <slot></slot>
  </button>
</template>

<script setup>
defineProps({
  variant: { type: String, default: 'primary' }, // primary, add, danger, edit
  isIconOnly: { type: Boolean, default: false }
})
</script>

<style scoped>
.base-button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0.7rem 1.4rem;
  border-radius: 10px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s ease;
  border: none;
  font-family: inherit;
  font-size: 0.95rem;
  line-height: 1;
}

.base-button:active { transform: scale(0.98); }

/* VARIANTY */
.primary { background-color: var(--primary); color: #1e293b; }
.primary:hover { background-color: var(--primary-hover); }

.add { background-color: #3b82f6; color: white; }
.add:hover { background-color: #2563eb; }

.danger { background-color: #ef4444; color: white; }
.danger:hover { background-color: #dc2626; }

.edit { background-color: #64748b; color: white; }
.edit:hover { background-color: #475569; }

/* IKONKY */
.button-icon {
  display: flex;
  align-items: center;
}
.base-button:not(.is-icon-only) .button-icon {
  margin-right: 0.5rem;
}

.is-icon-only {
  padding: 0.7rem;
  border-radius: 8px;
}
</style>