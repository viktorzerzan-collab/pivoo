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
    :found-label="$t('catalog.found_breweries')"
    :show-add-button="isAdmin"
    :add-label="$t('catalog.add_brewery')"
    :has-items="breweries.length > 0"
    :empty-text="$t('catalog.empty_breweries')"
    :empty-icon="FactoryIcon"
    :total-pages="totalPages"
    @reset-filters="resetFilters"
    @remove-filter="removeFilter"
    @add="openAddModal"
    @load-more="() => loadNextPage(viewMode === 'list')"
  >
    <template #header-top>
      <BaseSwitch v-model="viewMode" :options="viewModeOptions" />
    </template>

    <template #filters>
      <FilterInput v-model="filters.search" :label="$t('catalog.filter_name_brewery')" :placeholder="$t('catalog.placeholder_brewery')" />
      <FilterInput v-model="filters.city" :label="$t('catalog.filter_city')" :placeholder="$t('catalog.placeholder_city')" />
      <FilterInput v-model="filters.country" :label="$t('catalog.filter_country_short')" :placeholder="$t('catalog.placeholder_country')" />
    </template>

    <div v-if="viewMode === 'list'" class="list-wrapper">
      <CatalogGrid>
        <CatalogCard 
          v-for="brewery in breweries" 
          :key="brewery.id" 
          :item="brewery" 
          type="brewery"
          @showDetail="openDetail" 
        />
      </CatalogGrid>
    </div>

    <div v-else class="map-wrapper">
      <MapView :items="breweries" type="brewery" @showDetail="openDetail" />
      <p class="map-info">{{ $t('catalog.map_info', { count: breweries.length }) }}</p>
    </div>

    <template #modals>
      <DetailModal :show="isDetailOpen" :item="selectedItem" type="brewery" @close="closeDetail" />
      <AddBreweryModal :show="isAddModalOpen" :isEditing="false" :countries="countries" :form="form" @close="closeAddModal" @submit="submitBrewery" />
    </template>
  </BaseCatalogLayout>
</template>

<script setup>
import { computed, onMounted } from 'vue'
import { storeToRefs } from 'pinia'
import { FactoryIcon } from 'lucide-vue-next'
import { useAuthStore } from '../stores/auth'
import { useCatalogStore } from '../stores/catalog'

import BaseCatalogLayout from '../components/BaseCatalogLayout.vue'
import CatalogGrid from '../components/CatalogGrid.vue'
import CatalogCard from '../components/CatalogCard.vue'
import FilterInput from '../components/FilterInput.vue'
import MapView from '../components/MapView.vue'
import DetailModal from '../components/modals/DetailModal.vue'
import AddBreweryModal from '../components/modals/AddBreweryModal.vue'
import BaseSwitch from '../components/BaseSwitch.vue'

import { useCatalogFilters } from '../composables/useCatalogFilters'
import { useViewMode } from '../composables/useViewMode'
import { useCatalogModals } from '../composables/useCatalogModals'
import { useBreweries } from '../composables/useBreweries'

const authStore = useAuthStore()
const catalogStore = useCatalogStore()

const { user } = storeToRefs(authStore)
const { breweries, breweriesPagination, countries, isLoading } = storeToRefs(catalogStore)

const isAdmin = computed(() => user.value?.role === 'admin')

const { viewMode, viewModeOptions } = useViewMode('list')
const { isAddModalOpen, isDetailOpen, selectedItem, openDetail, closeDetail, openAddModal, closeAddModal } = useCatalogModals()

// Načtení logiky specifické pro pivovary z našeho nového composable
const {
  form, initialFilters, fetchAction, getActiveFilters, sortOptions, submitBrewery
} = useBreweries(closeAddModal)

const {
  currentPage, sortBy, filtersOpen, isAppending, filters,
  totalPages, totalItems, removeFilter, resetFilters, loadNextPage, addMultiChips, loadData
} = useCatalogFilters(initialFilters, breweriesPagination, isLoading, fetchAction)

// Sestavení computed vlastnosti pro aktivní filtry
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
