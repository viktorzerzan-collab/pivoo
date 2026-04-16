<template>
  <div class="pagination-wrapper" v-if="totalPages > 1">
    <button
      class="page-btn"
      :disabled="currentPage === 1"
      @click="changePage(1)"
      title="První strana"
    >
      <ChevronsLeftIcon :size="20" />
    </button>

    <button
      class="page-btn"
      :disabled="currentPage === 1"
      @click="changePage(currentPage - 1)"
      title="Předchozí strana"
    >
      <ChevronLeftIcon :size="20" />
    </button>

    <div class="page-numbers">
      <span class="page-info">Strana <strong>{{ currentPage }}</strong> z <strong>{{ totalPages }}</strong></span>
    </div>

    <button
      class="page-btn"
      :disabled="currentPage === totalPages"
      @click="changePage(currentPage + 1)"
      title="Další strana"
    >
      <ChevronRightIcon :size="20" />
    </button>

    <button
      class="page-btn"
      :disabled="currentPage === totalPages"
      @click="changePage(totalPages)"
      title="Poslední strana"
    >
      <ChevronsRightIcon :size="20" />
    </button>
  </div>
</template>

<script setup>
import { 
  ChevronLeftIcon, 
  ChevronRightIcon, 
  ChevronsLeftIcon, 
  ChevronsRightIcon 
} from 'lucide-vue-next'

defineProps({
  currentPage: {
    type: Number,
    required: true
  },
  totalPages: {
    type: Number,
    required: true
  }
})

const emit = defineEmits(['update:currentPage'])

const changePage = (newPage) => {
  emit('update:currentPage', newPage)
  
  // Hladké odskrolování na samotný začátek stránky
  window.scrollTo({
    top: 0,
    left: 0,
    behavior: 'smooth'
  })
}
</script>

<style scoped>
.pagination-wrapper {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 0.75rem;
  margin-top: 2.5rem;
  padding: 1rem 0;
}

.page-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
  padding: 0;
  border-radius: 8px;
  border: 1px solid var(--border);
  background-color: var(--bg-panel);
  color: var(--text-main);
  cursor: pointer;
  transition: all 0.2s ease;
  box-shadow: var(--shadow-sm);
}

.page-btn svg {
  margin: 0 !important;
  display: block;
}

.page-btn:hover:not(:disabled) {
  background-color: var(--primary);
  border-color: var(--primary);
  color: #1e293b;
}

.page-btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
  background-color: var(--bg-app);
}

.page-numbers {
  display: flex;
  align-items: center;
  padding: 0 0.5rem;
}

.page-info {
  font-size: 0.95rem;
  color: var(--text-muted);
  white-space: nowrap;
}

.page-info strong {
  color: var(--text-main);
}

@media (max-width: 480px) {
  .pagination-wrapper {
    gap: 0.4rem;
  }
  .page-info {
    font-size: 0.85rem;
  }
}
</style>