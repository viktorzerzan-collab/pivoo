<template>
  <button 
    :class="['base-button', variant, { 'icon-only': isIconOnly }]" 
    :type="type"
    :disabled="disabled"
  >
    <slot name="icon"></slot>
    <span v-if="$slots.default && !isIconOnly"><slot></slot></span>
  </button>
</template>

<script setup>
defineProps({
  type: { type: String, default: 'button' },
  variant: { type: String, default: 'primary' }, // primary, secondary, add, edit, danger, logout
  disabled: { type: Boolean, default: false },
  isIconOnly: { type: Boolean, default: false }
})
</script>

<style scoped>
.base-button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 0.6rem 1.25rem;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
  border: none;
  font-size: 0.9rem;
  line-height: 1.25rem;
}

/* Dokonalý čtverec pro samostatné ikonky */
.icon-only {
  padding: 0.5rem;
  width: 36px;
  height: 36px;
  border-radius: 8px;
}

/* Barevné varianty */
.primary { background: var(--primary); color: white; }
.primary:hover { background: var(--primary-hover); }

.secondary { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }
.secondary:hover { background: #e2e8f0; color: #0f172a; }

.add { background: var(--color-add); color: white; }
.add:hover { filter: brightness(1.1); }

.edit { background: var(--color-edit); color: white; }
.edit:hover { filter: brightness(1.1); }

.danger { background: var(--color-delete); color: white; }
.danger:hover { filter: brightness(1.1); }

.logout { background: transparent; border: 1px solid rgba(255,255,255,0.2); color: white; }
.logout:hover { background: rgba(255,255,255,0.1); }

button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Pokud je uvnitř svg ikona, ať se nekrčí */
:deep(svg) {
  flex-shrink: 0;
}
</style>