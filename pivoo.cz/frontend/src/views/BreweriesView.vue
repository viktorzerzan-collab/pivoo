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
    @add="isAddModalOpen = true"
    @load-more="loadNextPage"
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
      <div class="breweries-grid">
        <CatalogCard 
          v-for="brewery in breweries" 
          :key="brewery.id" 
          :item="brewery" 
          type="brewery"
          @showDetail="openDetail" 
        />
      </div>
    </div>

    <div v-else class="map-wrapper">
      <MapView :items="breweries" type="brewery" @showDetail="openDetail" />
      <p class="map-info">{{ $t('catalog.map_info', { count: breweries.length }) }}</p>
    </div>

    <template #modals>
      <DetailModal :show="isDetailOpen" :item="selectedItem" type="brewery" @close="isDetailOpen = false" />
      <AddBreweryModal :show="isAddModalOpen" :isEditing="false" :countries="countries" :form="form" @close="isAddModalOpen = false" @submit="submitBrewery" />
    </template>
  </BaseCatalogLayout>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { useI18n } from 'vue-i18n'
import { FactoryIcon, LayoutGridIcon, MapIcon } from 'lucide-vue-next'
import { apiFetch } from '../api'
import { useAuthStore } from '../stores/auth'
import { useCatalogStore } from '../stores/catalog'
import { useToastStore } from '../stores/toast'

import BaseCatalogLayout from '../components/BaseCatalogLayout.vue'
import CatalogCard from '../components/CatalogCard.vue'
import FilterInput from '../components/FilterInput.vue'
import MapView from '../components/MapView.vue'
import DetailModal from '../components/modals/DetailModal.vue'
import AddBreweryModal from '../components/modals/AddBreweryModal.vue'
import BaseSwitch from '../components/BaseSwitch.vue'

const authStore = useAuthStore()
const catalogStore = useCatalogStore()
const toastStore = useToastStore()
const { t } = useI18n()

const { user } = storeToRefs(authStore)
const { breweries, breweriesPagination, countries, isLoading } = storeToRefs(catalogStore)

const isAdmin = computed(() => user.value?.role === 'admin')
const viewMode = ref('list')
const isAddModalOpen = ref(false)
const filtersOpen = ref(false)
const isDetailOpen = ref(false)
const selectedItem = ref(null)
const isAppending = ref(false)

const viewModeOptions = computed(() => [
  { value: 'list', label: t('catalog.view_cards'), icon: LayoutGridIcon },
  { value: 'map', label: t('catalog.view_map'), icon: MapIcon }
])

const form = ref({ name: '', city: '', zip_code: '', country_id: 1, address: '', email: '', phone: '', website: '', logoFile: null })
const currentPage = ref(1)
const itemsPerPage = 30
const sortBy = ref('name_asc')

const initialFilters = { search: '', city: '', country: '' }
const filters = ref(JSON.parse(JSON.stringify(initialFilters)))

const totalPages = computed(() => breweriesPagination.value?.total_pages || 1)
const totalItems = computed(() => breweriesPagination.value?.total || 0)

const activeFilters = computed(() => {
  const active = []
  const addMultiChips = (value, key, labelPrefix) => {
    if (value) {
       const parts = String(value).split(',').map(s => s.trim()).filter(s => s)
       parts.forEach(part => active.push({ id: `${key}|${part}`, realKey: key, partValue: part, label: `${labelPrefix}: ${part}` }))
    }
  }
  addMultiChips(filters.value.search, 'search', t('catalog.search_prefix'))
  addMultiChips(filters.value.city, 'city', t('catalog.filter_city'))
  addMultiChips(filters.value.country, 'country', t('catalog.filter_country_short'))
  return active
})

const removeFilter = (chip) => {
  if (chip.partValue) {
    let parts = String(filters.value[chip.realKey]).split(',').map(s => s.trim()).filter(s => s)
    filters.value[chip.realKey] = parts.filter(p => p !== chip.partValue).join(', ')
  } else { filters.value[chip.realKey] = '' }
}

const loadBreweries = async (append = false) => {
  const params = { page: currentPage.value, limit: itemsPerPage, search: filters.value.search, city: filters.value.city, country: filters.value.country, sort: sortBy.value }
  await catalogStore.fetchBreweries(params, append)
}

const loadNextPage = async () => {
  if (currentPage.value < totalPages.value && !isLoading.value && !isAppending.value && viewMode.value === 'list') {
    isAppending.value = true
    currentPage.value++
    await loadBreweries(true)
    isAppending.value = false
  }
}

const resetFilters = () => {
  filters.value = JSON.parse(JSON.stringify(initialFilters))
  sortBy.value = 'name_asc'
  currentPage.value = 1
  loadBreweries(false)
}

watch([filters, sortBy], () => { currentPage.value = 1; loadBreweries(false) }, { deep: true })
watch(currentPage, () => { if (!isAppending.value) loadBreweries(false) })

const sortOptions = computed(() => [
  { value: 'name_asc', label: t('catalog.sort.name_asc') },
  { value: 'name_desc', label: t('catalog.sort.name_desc') },
  { value: 'city_asc', label: t('catalog.sort.city_asc') },
  { value: 'city_desc', label: t('catalog.sort.city_desc') },
  { value: 'rating_desc', label: t('catalog.sort.rating_desc') },
  { value: 'rating_asc', label: t('catalog.sort.rating_asc') },
  { value: 'newest', label: t('catalog.sort.newest') },
  { value: 'oldest', label: t('catalog.sort.oldest') }
])

const openDetail = (item) => { selectedItem.value = item; isDetailOpen.value = true }

const submitBrewery = async () => {
  try {
    const formData = new FormData()
    Object.keys(form.value).forEach(key => { if (form.value[key] !== null && form.value[key] !== '') formData.append(key, form.value[key]) })
    const result = await apiFetch('/add_brewery.php', { method: 'POST', body: formData })
    if (result.status === 'success') { 
      isAddModalOpen.value = false
      const country = catalogStore.countries.find(c => c.id == form.value.country_id)
      catalogStore.addBreweryLocally({
         id: result.id,
         name: form.value.name,
         city: form.value.city,
         country_id: form.value.country_id,
         country: country ? country.name_cz : '',
         country_code: country ? country.code : '',
         is_favorite: 0,
         avg_rating: null,
         total_beers_in_catalog: 0
      })
      toastStore.showToast(t('toast.brewery_added'))
    }
  } catch (e) { toastStore.showToast(t('toast.communication_error'), 'toast-error') }
}

onMounted(async () => { await catalogStore.fetchAllData(); loadBreweries(false) })
</script>

<style scoped>
.breweries-grid { 
  display: grid; 
  /* Zastropováno na max 2 sloupce, plynule přejde na 1 při nedostatku místa */
  grid-template-columns: repeat(auto-fit, minmax(max(300px, calc((100% - 1.5rem) / 2)), 1fr));
  gap: 1.5rem; 
  margin-bottom: 2rem; 
}

.map-wrapper { margin-bottom: 2rem; }
.map-info { margin-top: 10px; font-size: 0.85rem; color: var(--text-muted); text-align: center; font-style: italic; }
</style>