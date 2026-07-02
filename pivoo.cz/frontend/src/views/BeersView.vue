<template>
  <BaseCatalogLayout
    v-model:filters-open="filtersOpen"
    v-model:sort-by="sortBy"
    v-model:current-page="currentPage"
    :is-loading="isLoading"
    :is-appending="isAppending"
    :active-filters="activeFilters"
    :sort-options="sortOptions"
    :total-items="totalItems"
    :found-label="$t('catalog.found_beers')"
    :show-add-button="!!authStore.user"
    :add-label="$t('catalog.add_beer')"
    :has-items="beers.length > 0"
    :empty-text="$t('catalog.empty_beers')"
    :empty-icon="BeerIcon"
    :total-pages="totalPages"
    @reset-filters="resetFilters"
    @remove-filter="removeFilter"
    @add="handleOpenAddModal"
    @load-more="() => loadNextPage()"
  >
    <template #filters>
      <FilterInput v-model="filters.search" :label="$t('catalog.filter_name_beer')" :placeholder="$t('catalog.placeholder_beer')" />
      <FilterInput v-model="filters.brewery" :label="$t('catalog.filter_brewery')" :placeholder="$t('catalog.placeholder_brewery')" />
      <FilterInput v-model="filters.country" :label="$t('catalog.filter_country')" :placeholder="$t('catalog.placeholder_country')" />

      <BaseSelect v-model="filters.style" :label="$t('catalog.filter_style')" :placeholder="$t('catalog.all_styles')" searchable>
        <option value="">{{ $t('catalog.all_styles') }}</option>
        <option v-for="s in styles" :key="s.id" :value="s.id">{{ s.name }}</option>
      </BaseSelect>

      <FilterRange v-model="filters.epm" :label="$t('modals.add_beer.epm')" :step="0.1" unit="°" />
      <FilterRange v-model="filters.abv" :label="$t('modals.add_beer.abv')" :step="0.1" unit="%" />
      <FilterRange v-model="filters.ibu" :label="$t('modals.add_beer.ibu')" :step="1" unit="IBU" />
    </template>

    <CatalogGrid>
      <CatalogCard 
        v-for="beer in beers" 
        :key="beer.id" 
        :item="beer" 
        type="beer"
        @showDetail="handleOpenDetail"
      />
    </CatalogGrid>

    <template #modals>
      <AddBeerModal 
        :show="isAddModalOpen" 
        :isEditing="false" 
        :form="beerForm" 
        @close="closeAddModal" 
        @submit="submitBeer" 
      />

      <DetailModal 
        :show="isDetailOpen" 
        :item="selectedItem" 
        type="beer" 
        :reviews="beerReviews"
        @close="closeDetail" 
      />
    </template>
  </BaseCatalogLayout>
</template>

<script setup>
import { onMounted } from 'vue'
import { storeToRefs } from 'pinia'
import { useCatalogStore } from '../stores/catalog'
import { useAuthStore } from '../stores/auth'
import { BeerIcon } from 'lucide-vue-next'

import BaseCatalogLayout from '../components/BaseCatalogLayout.vue'
import CatalogCard from '../components/CatalogCard.vue'
import CatalogGrid from '../components/CatalogGrid.vue'
import FilterInput from '../components/FilterInput.vue'
import FilterRange from '../components/FilterRange.vue'
import BaseSelect from '../components/BaseSelect.vue'
import AddBeerModal from '../components/modals/AddBeerModal.vue'
import DetailModal from '../components/modals/DetailModal.vue'

import { useCatalogFilters } from '../composables/useCatalogFilters'
import { useCatalogModals } from '../composables/useCatalogModals'
import { useBeers } from '../composables/useBeers'

const catalogStore = useCatalogStore()
const authStore = useAuthStore()

const { beers, beersPagination, styles, isLoading } = storeToRefs(catalogStore)

const { 
  isAddModalOpen, isDetailOpen, selectedItem, 
  openDetail, closeDetail, openAddModal, closeAddModal 
} = useCatalogModals()

// Načtení logiky specifické pro piva z našeho nového composable
const {
  initialFilters, fetchAction, getActiveFilters, sortOptions,
  beerReviews, beerForm, handleOpenAddModal, submitBeer, handleOpenDetail
} = useBeers(openDetail, openAddModal, closeAddModal)

const {
  currentPage, sortBy, filtersOpen, isAppending, filters,
  totalPages, totalItems, removeFilter, resetFilters, loadNextPage, addMultiChips, loadData
} = useCatalogFilters(initialFilters, beersPagination, isLoading, fetchAction)

// Sestavení computed vlastnosti pro aktivní filtry (předáváme reaktivní data do composable)
const activeFilters = getActiveFilters(filters, styles, addMultiChips)

onMounted(async () => {
  await catalogStore.fetchAllData()
  loadData(false)
})
</script>

<style scoped>
/* Všechny styly se přesunuly do CatalogGrid.vue, tento blok může zůstat prázdný nebo ho lze smazat. 
Ponecháváme čisté řešení bez tagu <style>. */
</style>
