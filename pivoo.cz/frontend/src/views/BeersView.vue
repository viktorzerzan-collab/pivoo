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
    @add="openAddModal"
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
        @showDetail="openDetail"
      />
    </CatalogGrid>

    <template #modals>
      <AddBeerModal 
        :show="isAddModalOpen" 
        :isEditing="false" 
        :form="beerForm" 
        @close="isAddModalOpen = false" 
        @submit="submitBeer" 
      />

      <DetailModal 
        :show="isDetailOpen" 
        :item="selectedItem" 
        type="beer" 
        :reviews="beerReviews"
        @close="isDetailOpen = false" 
      />
    </template>
  </BaseCatalogLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { storeToRefs } from 'pinia'
import { useI18n } from 'vue-i18n'
import { apiFetch } from '../api'
import { useCatalogStore } from '../stores/catalog'
import { useAuthStore } from '../stores/auth'
import { useToastStore } from '../stores/toast'
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

const catalogStore = useCatalogStore()
const authStore = useAuthStore()
const toastStore = useToastStore()
const { t } = useI18n()

const { beers, beersPagination, styles, isLoading } = storeToRefs(catalogStore)

const initialFilters = {
  search: '', brewery: '', style: '', country: '',
  epm: { min: '', max: '' }, abv: { min: '', max: '' }, ibu: { min: '', max: '' }
}

const fetchAction = async (filterVals, baseParams, append) => {
  const params = {
    ...baseParams,
    search: filterVals.search,
    brewery: filterVals.brewery,
    style: filterVals.style,
    country: filterVals.country,
    epm_min: filterVals.epm.min,
    epm_max: filterVals.epm.max,
    abv_min: filterVals.abv.min,
    abv_max: filterVals.abv.max,
    ibu_min: filterVals.ibu.min,
    ibu_max: filterVals.ibu.max
  }
  await catalogStore.fetchBeers(params, append)
}

const {
  currentPage, sortBy, filtersOpen, isAppending, filters,
  totalPages, totalItems, removeFilter, resetFilters, loadNextPage, addMultiChips, loadData
} = useCatalogFilters(initialFilters, beersPagination, isLoading, fetchAction)

const activeFilters = computed(() => {
  const active = []
  addMultiChips(active, filters.value.search, 'search', t('catalog.search_prefix'))
  addMultiChips(active, filters.value.brewery, 'brewery', t('catalog.filter_brewery'))
  addMultiChips(active, filters.value.country, 'country', t('catalog.filter_country_short'))
  
  if (filters.value.style) {
    const s = styles.value.find(x => x.id == filters.value.style)
    if (s) active.push({ id: 'style', realKey: 'style', label: `Styl: ${s.name}` })
  }

  ['epm', 'abv', 'ibu'].forEach(key => {
    const min = filters.value[key].min
    const max = filters.value[key].max
    if (min !== '' || max !== '') {
      active.push({ 
        id: key, 
        realKey: 'range',
        rangeKey: key,
        label: `${key.toUpperCase()}: ${min !== '' ? min : '0'} - ${max !== '' ? max : '∞'}` 
      })
    }
  })
  return active
})

const sortOptions = computed(() => [
  { value: 'name_asc', label: t('catalog.sort.name_asc') },
  { value: 'name_desc', label: t('catalog.sort.name_desc') },
  { value: 'brewery_asc', label: t('catalog.sort.brewery_asc') },
  { value: 'brewery_desc', label: t('catalog.sort.brewery_desc') },
  { value: 'style_asc', label: t('catalog.sort.style_asc') },
  { value: 'style_desc', label: t('catalog.sort.style_desc') },
  { value: 'rating_desc', label: t('catalog.sort.rating_desc') },
  { value: 'rating_asc', label: t('catalog.sort.rating_asc') },
  { value: 'abv_desc', label: t('catalog.sort.abv_desc') },
  { value: 'abv_asc', label: t('catalog.sort.abv_asc') },
  { value: 'epm_desc', label: t('catalog.sort.epm_desc') },
  { value: 'epm_asc', label: t('catalog.sort.epm_asc') },
  { value: 'ibu_desc', label: t('catalog.sort.ibu_desc') },
  { value: 'ibu_asc', label: t('catalog.sort.ibu_asc') },
  { value: 'newest', label: t('catalog.sort.newest') },
  { value: 'oldest', label: t('catalog.sort.oldest') }
])

const isAddModalOpen = ref(false)
const isDetailOpen = ref(false)
const selectedItem = ref(null)
const beerReviews = ref([])
const beerForm = ref({ 
  name: '', brewery_id: '', style_id: '', epm: '', abv: '', ibu: '', 
  ebc: '', hops: '', malts: '', fermentation: '', tags: '', 
  is_unfiltered: false, is_unpasteurized: false 
})

const openAddModal = () => {
  beerForm.value = { 
    name: '', brewery_id: '', style_id: '', epm: '', abv: '', ibu: '', 
    ebc: '', hops: '', malts: '', fermentation: '', tags: '', 
    is_unfiltered: false, is_unpasteurized: false 
  }
  isAddModalOpen.value = true
}

const submitBeer = async () => {
  try {
    const res = await apiFetch('/add_beer.php', { method: 'POST', body: JSON.stringify(beerForm.value) })
    if (res.status === 'success') { 
      isAddModalOpen.value = false
      catalogStore.addBeerLocally({ id: res.id, ...beerForm.value })
      toastStore.showToast(t('toast.beer_added'))
    }
  } catch (e) { toastStore.showToast(t('toast.communication_error'), 'toast-error') }
}

const openDetail = async (beer) => {
  selectedItem.value = beer
  isDetailOpen.value = true
  beerReviews.value = [] 
  try {
    const res = await apiFetch(`/beer_reviews.php?beer_id=${beer.id}`)
    if (res.status === 'success') beerReviews.value = res.data
  } catch (error) { console.error("Chyba při načítání recenzí", error) }
}

onMounted(async () => {
  await catalogStore.fetchAllData()
  loadData(false)
})
</script>

<style scoped>
/* Všechny styly se přesunuly do CatalogGrid.vue, tento blok může zůstat prázdný nebo ho lze smazat. 
Ponecháváme čisté řešení bez tagu <style>. */
</style>