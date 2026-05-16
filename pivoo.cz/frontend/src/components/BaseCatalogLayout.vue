<template>
  <div class="catalog-layout-wrapper">
    <BaseLoader :show="isLoading" />

    <div class="catalog-header-layout">
      <div class="header-top-row" v-if="$slots['header-top'] || showAddButton || showResultsBar">
        <div class="header-controls-left">
          <slot name="header-top"></slot>
        </div>
        
        <div class="header-controls-right">
          <span class="results-count" v-if="showResultsBar">{{ foundLabel }} <strong>{{ totalItems }}</strong></span>

          <div class="sort-control-wrapper" v-if="showResultsBar && showSort">
            <BaseSelect 
              v-model="internalSortBy" 
              :placeholder="$t('catalog.sort_by')" 
              :searchable="false"
            >
              <option v-for="opt in sortOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
            </BaseSelect>
          </div>

          <div class="desktop-action-bar" v-if="showAddButton">
            <BaseButton variant="add" @click="$emit('add')">
              <template #icon><PlusCircleIcon :size="20" /></template>
              {{ addLabel }}
            </BaseButton>
          </div>
        </div>

        <div class="mobile-action-bar" v-if="showAddButton">
          <BaseButton variant="add" @click="$emit('add')">
            <template #icon><PlusCircleIcon :size="20" /></template>
            {{ addLabel }}
          </BaseButton>
        </div>
      </div>

      <BasePanel 
        v-if="showFilters"
        :title="$t('catalog.filters_title')" 
        :icon="FilterIcon" 
        class="filters-section"
        @click="internalFiltersOpen = !internalFiltersOpen"
        style="cursor: pointer;"
      >
        <template #header-actions>
          <ChevronDownIcon :class="{ 'rotated': internalFiltersOpen }" :size="20" class="toggle-icon" />
        </template>

        <transition name="slide-fade">
          <div v-show="internalFiltersOpen" class="filters-body" @click.stop>
            <div class="filters-grid">
              <slot name="filters"></slot>
            </div>
            
            <div class="filters-footer">
              <BaseButton variant="edit" @click="$emit('reset-filters')">{{ $t('catalog.reset_filters') }}</BaseButton>
            </div>
          </div>
        </transition>
      </BasePanel>

      <ActiveFilterChips 
        v-if="showFilters && activeFilters.length > 0"
        :filters="activeFilters" 
        @remove="$emit('remove-filter', $event)" 
      />
    </div>

    <div class="catalog-container">
      <template v-if="hasItems">
        <slot></slot>
        
        <div class="desktop-pagination" v-if="totalPages > 1">
          <BasePagination 
            v-model:currentPage="internalCurrentPage" 
            :total-pages="totalPages"
          />
        </div>

        <div ref="loadMoreTrigger" class="load-more-trigger">
          <div v-if="isAppending" class="mobile-loader">
            {{ $t('catalog.loading_more') }}
          </div>
        </div>
      </template>
      
      <BaseEmptyState 
        v-else-if="!isLoading" 
        :text="emptyText" 
        :icon="emptyIcon"
      >
        <BaseButton v-if="showFilters" variant="edit" class="mt-2" @click="$emit('reset-filters')">
          {{ $t('catalog.cancel_filters') }}
        </BaseButton>
      </BaseEmptyState>
    </div>

    <slot name="modals"></slot>
  </div>
</template>

<script setup>
import { ref, watch, onMounted, onUnmounted, computed } from 'vue'
import { PlusCircleIcon, FilterIcon, ChevronDownIcon } from 'lucide-vue-next'
import BaseLoader from './BaseLoader.vue'
import BasePanel from './BasePanel.vue'
import BaseButton from './BaseButton.vue'
import BaseSelect from './BaseSelect.vue'
import BasePagination from './BasePagination.vue'
import BaseEmptyState from './BaseEmptyState.vue'
import ActiveFilterChips from './ActiveFilterChips.vue'

const props = defineProps({
  isLoading: Boolean,
  isAppending: Boolean,
  filtersOpen: Boolean,
  activeFilters: { type: Array, default: () => [] },
  sortBy: String,
  sortOptions: { type: Array, default: () => [] },
  showSort: { type: Boolean, default: true },
  showFilters: { type: Boolean, default: true },
  showResultsBar: { type: Boolean, default: true },
  totalItems: Number,
  foundLabel: String,
  showAddButton: Boolean,
  addLabel: String,
  hasItems: Boolean,
  emptyText: String,
  emptyIcon: [Object, Function, String],
  currentPage: Number,
  totalPages: Number
})

const emit = defineEmits([
  'update:filtersOpen', 
  'update:sortBy', 
  'update:currentPage', 
  'reset-filters', 
  'remove-filter', 
  'add', 
  'load-more'
])

const internalFiltersOpen = computed({
  get: () => props.filtersOpen,
  set: (val) => emit('update:filtersOpen', val)
})

const internalSortBy = computed({
  get: () => props.sortBy,
  set: (val) => emit('update:sortBy', val)
})

const internalCurrentPage = computed({
  get: () => props.currentPage,
  set: (val) => emit('update:currentPage', val)
})

const loadMoreTrigger = ref(null)
let observer = null

onMounted(() => {
  if (loadMoreTrigger.value) {
    observer = new IntersectionObserver((entries) => {
      if (entries[0].isIntersecting && window.innerWidth <= 800) {
        emit('load-more')
      }
    }, { rootMargin: '200px' })
    observer.observe(loadMoreTrigger.value)
  }
})

onUnmounted(() => {
  if (observer) observer.disconnect()
})
</script>

<style scoped>
.catalog-header-layout { display: flex; flex-direction: column; gap: 0; }
.header-top-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; gap: 1rem; flex-wrap: wrap; }
.header-controls-left { display: flex; align-items: center; gap: 1.5rem; flex-wrap: wrap; }
.header-controls-right { display: flex; align-items: center; gap: 1rem; flex-wrap: wrap; }

.filters-section { margin-bottom: 1.5rem; position: relative; z-index: 20; }
.filters-section :deep(.panel-header) { border-bottom: none; margin-bottom: 0; padding-bottom: 1rem; }
.filters-section :deep(.panel-header h3) { font-size: 1.1rem; }

.toggle-icon { color: var(--text-muted); transition: transform 0.3s ease; }
.toggle-icon.rotated { transform: rotate(180deg); }
.filters-body { padding-top: 1.5rem; border-top: 1px solid var(--border); }
.filters-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; }
.filters-footer { margin-top: 1.5rem; display: flex; justify-content: flex-end; }

.sort-control-wrapper { width: 220px; }
.results-count { color: var(--text-muted); font-size: 0.95rem; white-space: nowrap; }
.desktop-action-bar { display: flex; align-items: center; }
.mobile-action-bar { display: none; }

.catalog-container { position: relative; min-height: 400px; display: flex; flex-direction: column; width: 100%; }
.desktop-pagination { display: block; margin-top: 2rem; }
.load-more-trigger { height: 20px; width: 100%; }
.mobile-loader { display: none; text-align: center; padding: 1rem; color: var(--text-muted); font-weight: 600; font-size: 0.9rem; }

@media (max-width: 800px) {
  .header-top-row { margin-bottom: 1rem; flex-direction: column; align-items: stretch; gap: 0.75rem; }
  .header-controls-left { width: 100%; justify-content: space-between; gap: 0.75rem; }
  .header-controls-right { width: 100%; flex-direction: column; align-items: stretch; gap: 0.75rem; }
  .sort-control-wrapper { width: 100%; max-width: none; }
  
  .filters-section { margin-bottom: 0.75rem; }
  
  .mobile-action-bar { display: block; margin-bottom: 0.5rem; width: 100%; }
  .mobile-action-bar :deep(.base-button) { width: 100%; padding: 1rem; justify-content: center; font-size: 1.1rem; }
  .desktop-action-bar { display: none; }
  
  .desktop-pagination { display: none; }
  .mobile-loader { display: block; }
  
  /* Zarovnáme na mobilu počet výsledků na střed, když je to vše pod sebou */
  .results-count { text-align: center; }
}

.slide-fade-enter-active { transition: all 0.3s ease-out; }
.slide-fade-leave-active { transition: all 0.2s cubic-bezier(1, 0.5, 0.8, 1); }
.slide-fade-enter-from, .slide-fade-leave-to { transform: translateY(-10px); opacity: 0; }
.mt-2 { margin-top: 0.5rem; }
</style>