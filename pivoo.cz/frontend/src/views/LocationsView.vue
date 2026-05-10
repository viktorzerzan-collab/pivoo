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
    @load-more="loadNextPage"
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
import { ref, onMounted, computed, watch } from 'vue'
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

const authStore = useAuthStore()
const catalogStore = useCatalogStore()
const toastStore = useToastStore()
const { t } = useI18n()

const { user } = storeToRefs(authStore)
const { locations, locationsPagination, countries, isLoading } = storeToRefs(catalogStore)

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

const form = ref({ name: '', type: 'hospoda', city: '', zip_code: '', country_id: 1, address: '', email: '', phone: '', website: '', opening_hours: '', lat: null, lng: null })
const currentPage = ref(1)
const itemsPerPage = 30
const sortBy = ref('name_asc')

const initialFilters = { search: '', city: '', country: '' }
const filters = ref(JSON.parse(JSON.stringify(initialFilters)))

const totalPages = computed(() => locationsPagination.value?.total_pages || 1)
const totalItems = computed(() => locationsPagination.value?.total || 0)

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

const removeFilter = (chip) => {
  if (chip.partValue) {
    let parts = String(filters.value[chip.realKey]).split(',').map(s => s.trim()).filter(s => s)
    filters.value[chip.realKey] = parts.filter(p => p !== chip.partValue).join(', ')
  } else { filters.value[chip.realKey] = '' }
}

const loadLocations = async (append = false) => {
  const params = { page: currentPage.value, limit: itemsPerPage, search: filters.value.search, city: filters.value.city, country: filters.value.country, sort: sortBy.value }
  await catalogStore.fetchLocations(params, append)
}

const loadNextPage = async () => {
  if (currentPage.value < totalPages.value && !isLoading.value && !isAppending.value && viewMode.value === 'list') {
    isAppending.value = true
    currentPage.value++
    await loadLocations(true)
    isAppending.value = false
  }
}

const resetFilters = () => {
  filters.value = JSON.parse(JSON.stringify(initialFilters))
  sortBy.value = 'name_asc'
  currentPage.value = 1
  loadLocations(false)
}

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

watch([filters, sortBy], () => { currentPage.value = 1; loadLocations(false) }, { deep: true })
watch(currentPage, () => { if (!isAppending.value) loadLocations(false) })

onMounted(async () => { 
  await catalogStore.fetchAllData()
  loadLocations(false) 
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