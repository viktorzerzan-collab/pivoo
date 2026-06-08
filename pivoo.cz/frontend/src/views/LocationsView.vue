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
    @add="openAddModal"
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
      <div class="locations-grid">
        <CatalogCard 
          v-for="loc in locations" 
          :key="loc.id" 
          :item="loc" 
          type="location"
          @showDetail="openDetail" 
        />
      </div>
    </div>

    <div v-else class="map-wrapper">
      <MapView :items="locations" type="location" @showDetail="openDetail" />
      <p class="map-info">{{ $t('catalog.map_info', { count: locations.length }) }}</p>
    </div>

    <template #modals>
      <DetailModal :show="isDetailOpen" :item="selectedItem" type="location" @close="isDetailOpen = false" />
      <AddLocationModal :show="isAddModalOpen" :countries="countries" :form="form" @close="isAddModalOpen = false" @submit="submitLocation" />
    </template>
  </BaseCatalogLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { storeToRefs } from 'pinia'
import { useI18n } from 'vue-i18n'
import { apiFetch } from '../api'
import { useAuthStore } from '../stores/auth'
import { useCatalogStore } from '../stores/catalog'
import { useToastStore } from '../stores/toast'
import { MapIcon, LayoutGridIcon } from 'lucide-vue-next'

import BaseCatalogLayout from '../components/BaseCatalogLayout.vue'
import CatalogCard from '../components/CatalogCard.vue'
import FilterInput from '../components/FilterInput.vue'
import MapView from '../components/MapView.vue'
import DetailModal from '../components/modals/DetailModal.vue'
import AddLocationModal from '../components/modals/AddLocationModal.vue'
import BaseSwitch from '../components/BaseSwitch.vue'
import { useCatalogFilters } from '../composables/useCatalogFilters'

const authStore = useAuthStore()
const catalogStore = useCatalogStore()
const toastStore = useToastStore()
const { t } = useI18n()

const { user } = storeToRefs(authStore)
const { locations, locationsPagination, countries, isLoading } = storeToRefs(catalogStore)

const isAdmin = computed(() => user.value?.role === 'admin')
const viewMode = ref('list')
const isAddModalOpen = ref(false)
const isDetailOpen = ref(false)
const selectedItem = ref(null)

const viewModeOptions = computed(() => [
  { value: 'list', label: t('catalog.view_cards'), icon: LayoutGridIcon },
  { value: 'map', label: t('catalog.view_map'), icon: MapIcon }
])

const form = ref({ name: '', type: 'hospoda', city: '', zip_code: '', country_id: 1, address: '', email: '', phone: '', website: '', opening_hours: '', lat: null, lng: null })

const initialFilters = { search: '', city: '', country: '' }

const fetchAction = async (filterVals, baseParams, append) => {
  const params = {
    ...baseParams,
    search: filterVals.search,
    city: filterVals.city,
    country: filterVals.country
  }
  await catalogStore.fetchLocations(params, append)
}

const {
  currentPage, sortBy, filtersOpen, isAppending, filters,
  totalPages, totalItems, removeFilter, resetFilters, loadNextPage, addMultiChips, loadData
} = useCatalogFilters(initialFilters, locationsPagination, isLoading, fetchAction)

const activeFilters = computed(() => {
  const active = []
  addMultiChips(active, filters.value.search, 'search', t('catalog.search_prefix'))
  addMultiChips(active, filters.value.city, 'city', t('catalog.filter_city'))
  addMultiChips(active, filters.value.country, 'country', t('catalog.filter_country_short'))
  return active
})

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

const openAddModal = () => {
  form.value = { name: '', type: 'hospoda', city: '', zip_code: '', country_id: 1, address: '', email: '', phone: '', website: '', opening_hours: '', lat: null, lng: null }
  isAddModalOpen.value = true
}

const openDetail = (loc) => { selectedItem.value = loc; isDetailOpen.value = true }

const submitLocation = async () => {
  try {
    const result = await apiFetch('/add_location.php', { method: 'POST', body: JSON.stringify(form.value) })
    if (result.status === 'success') { 
      isAddModalOpen.value = false
      const country = countries.value.find(c => c.id == form.value.country_id)
      catalogStore.addLocationLocally({
         id: result.id,
         name: form.value.name,
         type: form.value.type,
         city: form.value.city,
         country_id: form.value.country_id,
         country: country ? country.name_cz : '',
         country_code: country ? country.code : '',
         is_favorite: 0,
         avg_rating: null,
         total_visits: 0,
         address: form.value.address,
         zip_code: form.value.zip_code,
         email: form.value.email,
         phone: form.value.phone,
         website: form.value.website,
         opening_hours: form.value.opening_hours,
         lat: form.value.lat,
         lng: form.value.lng
      })
      currentPage.value = 1
      toastStore.showToast(t('toast.location_added')) 
    } else {
      toastStore.showToast(result.message || t('toast.location_add_error'), 'toast-error')
    }
  } catch (e) { toastStore.showToast(t('toast.communication_error'), 'toast-error') }
}

onMounted(async () => { 
  await catalogStore.fetchAllData()
  loadData(false) 
})
</script>

<style scoped>
.locations-grid { 
  display: grid; 
  /* Zastropováno na max 2 sloupce, plynule přejde na 1 při nedostatku místa */
  grid-template-columns: repeat(auto-fit, minmax(max(300px, calc((100% - 1.5rem) / 2)), 1fr));
  gap: 1.5rem; 
  margin-bottom: 2rem; 
}

.map-wrapper { margin-bottom: 2rem; }
.map-info { margin-top: 10px; font-size: 0.85rem; color: var(--text-muted); text-align: center; font-style: italic; }
</style>