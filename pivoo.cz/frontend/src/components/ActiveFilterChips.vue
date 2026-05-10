<template>
  <div v-if="filters && filters.length > 0" class="active-filters-chips">
    <span class="chips-label">{{ $t('catalog.active_filters') }}</span>
    <div class="chips-container">
      <button 
        v-for="chip in filters" 
        :key="chip.id" 
        class="filter-chip"
        @click="$emit('remove', chip)"
        :title="$t('catalog.cancel_filter')"
      >
        {{ chip.label }}
        <XIcon :size="14" />
      </button>
    </div>
  </div>
</template>

<script setup>
import { XIcon } from 'lucide-vue-next'

defineProps({
  filters: {
    type: Array,
    required: true,
    default: () => []
  }
})

defineEmits(['remove'])
</script>

<style scoped>
.active-filters-chips {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.75rem;
  margin-bottom: 1.5rem;
  padding: 0 0.5rem;
}

.chips-label {
  font-size: 0.85rem;
  font-weight: 700;
  color: var(--text-muted);
  text-transform: uppercase;
}

.chips-container {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.filter-chip {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  background-color: var(--primary);
  color: #1e293b;
  border: none;
  padding: 0.3rem 0.8rem;
  border-radius: 99px;
  font-size: 0.8rem;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s ease;
}

.filter-chip:hover {
  background-color: var(--primary-hover);
}

@media (max-width: 768px) {
  .active-filters-chips {
    gap: 0.5rem;
  }
  
  .chips-label {
    width: 100%;
    margin-bottom: 0.25rem;
  }
}
</style>