<template>
  <BaseCatalogLayout
    v-model:filters-open="filtersOpen"
    v-model:sort-by="sortBy"
    v-model:current-page="currentPage"
    :is-loading="isLoading"
    :is-appending="isAppending"
    :active-filters="activeFilters"
    :sort-options="sortOptions"
    :show-sort="viewMode === 'list'"
    :total-items="totalItems"
    :found-label="$t('catalog.found_locations')"
    :show-add-button="isAdmin"
    :add-label="$t('catalog.add_location')"
    :has-items="locations.length > 0"
    :empty-text="$t('catalog.empty_locations')"
    :empty-icon="MapIcon"
    :total-pages="totalPages"
    @reset-filters="resetFilters"
    @remove-filter="removeFilter"
    @add="handleOpenAddModal"
    @load-more="() => loadNextPage(viewMode === 'list')"
  >
    <template #header-top>
      <BaseSwitch v-model="viewMode" :options="viewModeOptions" />
    </template>

    <template #filters>
      <FilterInput v-model="filters.search" :label="$t('catalog.filter_name_location')" :placeholder="$t('catalog.placeholder_location')" />
      <FilterInput v-model="filters.city" :label="$t('catalog.filter_city')" :placeholder="$t('catalog.placeholder_city')" />
      <FilterInput v-model="filters.country" :label="$t('catalog.filter_country_short')" :placeholder="$t('catalog.placeholder_country')" />
    </template>

    <div v-if="viewMode === 'list'" class="list-wrapper">
      <CatalogGrid>
        <CatalogCard 
          v-for="loc in locations" 
          :key="loc.id" 
          :item="loc" 
          type="location"
          @showDetail="openDetail" 
        />
      </CatalogGrid>
    </div>

    <div v-else class="map-wrapper">
      <MapView :items="locations" type="location" @showDetail="openDetail" />
      <p class="map-info">{{ $t('catalog.map_info', { count: locations.length }) }}</p>
    </div>

    <template #modals>
      <DetailModal :show="isDetailOpen" :item="selectedItem" type="location" @close="closeDetail" />
      <AddLocationModal :show="isAddModalOpen" :countries="countries" :form="form" @close="closeAddModal" @submit="submitLocation" />
    </template>
  </BaseCatalogLayout>
</template>

<script setup>
import { computed, onMounted } from 'vue'
import { storeToRefs } from 'pinia'
import { useAuthStore } from '../stores/auth'
import { useCatalogStore } from '../stores/catalog'
import { MapIcon } from 'lucide-vue-next'

import BaseCatalogLayout from '../components/BaseCatalogLayout.vue'
import CatalogGrid from '../components/CatalogGrid.vue'
import CatalogCard from '../components/CatalogCard.vue'
import FilterInput from '../components/FilterInput.vue'
import MapView from '../components/MapView.vue'
import DetailModal from '../components/modals/DetailModal.vue'
import AddLocationModal from '../components/modals/AddLocationModal.vue'
import BaseSwitch from '../components/BaseSwitch.vue'

import { useCatalogFilters } from '../composables/useCatalogFilters'
import { useViewMode } from '../composables/useViewMode'
import { useCatalogModals } from '../composables/useCatalogModals'
import { useLocations } from '../composables/useLocations'

const authStore = useAuthStore()
const catalogStore = useCatalogStore()

const { user } = storeToRefs(authStore)
const { locations, locationsPagination, countries, isLoading } = storeToRefs(catalogStore)

const isAdmin = computed(() => user.value?.role === 'admin')

const { viewMode, viewModeOptions } = useViewMode('list')
const { isAddModalOpen, isDetailOpen, selectedItem, openDetail, closeDetail, openAddModal, closeAddModal } = useCatalogModals()

// Vytvoříme proxy objekt pro předání currentPage do composable dříve, než ho reálně získáme z useCatalogFilters
const pageProxy = {}

const {
  form, initialFilters, fetchAction, getActiveFilters, sortOptions, handleOpenAddModal, submitLocation
} = useLocations(openAddModal, closeAddModal, pageProxy)

const {
  currentPage, sortBy, filtersOpen, isAppending, filters,
  totalPages, totalItems, removeFilter, resetFilters, loadNextPage, addMultiChips, loadData
} = useCatalogFilters(initialFilters, locationsPagination, isLoading, fetchAction)

// Propojíme náš proxy s reálným currentPage z useCatalogFilters (aby se stránka resetovala při uložení)
Object.defineProperty(pageProxy, 'value', {
  get: () => currentPage.value,
  set: (val) => { currentPage.value = val }
})

const activeFilters = getActiveFilters(filters, addMultiChips)

onMounted(async () => { 
  await catalogStore.fetchAllData()
  loadData(false) 
})
</script>

<style scoped>
.map-wrapper { margin-bottom: 2rem; }
.map-info { margin-top: 10px; font-size: 0.85rem; color: var(--text-muted); text-align: center; font-style: italic; }
</style>
