<template>
  <div class="breweries-page">
    <BaseLoader :show="isLoading" />

    <div class="catalog-header-layout">
      <div class="header-top-row">
        <BaseSwitch v-model="viewMode" :options="viewModeOptions" />

        <div class="mobile-action-bar">
          <BaseButton v-if="isAdmin" variant="add" @click="isAddModalOpen = true">
            <template #icon><PlusIcon :size="20" /></template>
            {{ $t('catalog.add_brewery') }}
          </BaseButton>
        </div>
      </div>

      <BasePanel 
        :title="$t('catalog.filters_title')" 
        :icon="FilterIcon" 
        class="filters-section"
        @click="filtersOpen = !filtersOpen"
        style="cursor: pointer;"
      >
        <template #header-actions>
          <ChevronDownIcon :class="{ 'rotated': filtersOpen }" :size="20" class="toggle-icon" />
        </template>
        
        <transition name="slide-fade">
          <div v-show="filtersOpen" class="filters-body" @click.stop>
            <div class="filters-grid">
              <FilterInput v-model="filters.search" :label="$t('catalog.filter_name_brewery')" :placeholder="$t('catalog.placeholder_brewery')" />
              <FilterInput v-model="filters.city" :label="$t('catalog.filter_city')" :placeholder="$t('catalog.placeholder_city')" />
              <FilterInput v-model="filters.country" :label="$t('catalog.filter_country_short')" :placeholder="$t('catalog.placeholder_country')" />
            </div>
            <div class="filters-footer">
              <BaseButton variant="edit" @click="resetFilters">{{ $t('catalog.reset_filters') }}</BaseButton>
            </div>
          </div>
        </transition>
      </BasePanel>

      <ActiveFilterChips 
        :filters="activeFilters" 
        @remove="removeFilter" 
      />

      <div class="results-bar">
        <div v-if="viewMode === 'list'" class="sort-control-wrapper">
          <BaseSelect v-model="sortBy" :placeholder="$t('catalog.sort_by')" :searchable="false">
            <option v-for="opt in sortOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
          </BaseSelect>
        </div>
        <div v-else class="sort-control-placeholder"></div>

        <span class="results-count">{{ $t('catalog.found_breweries') }} <strong>{{ totalItems }}</strong></span>
        
        <div class="desktop-action-bar">
          <BaseButton v-if="isAdmin" variant="add" @click="isAddModalOpen = true">
            <template #icon><PlusIcon :size="20" /></template>
            {{ $t('catalog.add_brewery') }}
          </BaseButton>
        </div>
      </div>
    </div>

    <div class="catalog-container">
      <template v-if="breweries.length > 0">
        <div v-if="viewMode === 'list'" class="list-wrapper">
          <div class="breweries-grid">
            <BreweryCard v-for="brewery in breweries" :key="brewery.id" :brewery="brewery" @showDetail="openDetail" />
          </div>
          <div class="desktop-pagination">
            <BasePagination v-if="totalPages > 1" v-model:currentPage="currentPage" :total-pages="totalPages" />
          </div>
          <div ref="loadMoreTrigger" class="load-more-trigger">
            <div v-if="isAppending" class="mobile-loader">{{ $t('catalog.loading_more') }}</div>
          </div>
        </div>

        <div v-else class="map-wrapper">
          <MapView :items="breweries" type="brewery" @showDetail="openDetail" />
          <p class="map-info">{{ $t('catalog.map_info', { count: breweries.length }) }}</p>
        </div>
      </template>
      
      <BaseEmptyState 
        v-else-if="!isLoading" 
        :text="$t('catalog.empty_breweries')" 
        :icon="FactoryIcon"
      >
        <BaseButton variant="edit" class="mt-2" @click="resetFilters">{{ $t('catalog.cancel_filters') }}</BaseButton>
      </BaseEmptyState>
    </div>

    <DetailModal :show="isDetailOpen" :item="selectedItem" type="brewery" @close="isDetailOpen = false" />
    <AddBreweryModal :show="isAddModalOpen" :isEditing="false" :countries="countries" :form="form" @close="isAddModalOpen = false" @submit="submitBrewery" />
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { useI18n } from 'vue-i18n'
import { PlusIcon, FactoryIcon, FilterIcon, ChevronDownIcon, LayoutGridIcon, MapIcon } from 'lucide-vue-next'
import { apiFetch } from '../api'
import { useAuthStore } from '../stores/auth'
import { useCatalogStore } from '../stores/catalog'
import { useToastStore } from '../stores/toast'

import BaseLoader from '../components/BaseLoader.vue'
import BasePanel from '../components/BasePanel.vue'
import BaseEmptyState from '../components/BaseEmptyState.vue'
import FilterInput from '../components/FilterInput.vue'
import BaseSelect from '../components/BaseSelect.vue'
import BaseButton from '../components/BaseButton.vue'
import BreweryCard from '../components/BreweryCard.vue'
import MapView from '../components/MapView.vue'
import DetailModal from '../components/modals/DetailModal.vue'
import AddBreweryModal from '../components/modals/AddBreweryModal.vue'
import BasePagination from '../components/BasePagination.vue'
import BaseSwitch from '../components/BaseSwitch.vue'
import ActiveFilterChips from '../components/ActiveFilterChips.vue'

const authStore = useAuthStore()
const catalogStore = useCatalogStore()
const toastStore = useToastStore()
const { t } = useI18n()

const { user } = storeToRefs(authStore)
const { breweries, breweriesPagination, countries, isLoading } = storeToRefs(catalogStore)

const isAdmin = computed(() => user.value?.role === 'admin')
const viewMode = ref('list')

const viewModeOptions = computed(() => [
  { value: 'list', label: t('catalog.view_cards'), icon: LayoutGridIcon },
  { value: 'map', label: t('catalog.view_map'), icon: MapIcon }
])

const isAddModalOpen = ref(false)
const filtersOpen = ref(false)
const isDetailOpen = ref(false)
const selectedItem = ref(null)

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
    parts = parts.filter(p => p !== chip.partValue)
    filters.value[chip.realKey] = parts.join(', ')
  } else { filters.value[chip.realKey] = '' }
}

const loadMoreTrigger = ref(null)
const isAppending = ref(false)
let observer = null

const loadBreweries = async (append = false) => {
  const params = { page: currentPage.value, limit: itemsPerPage, search: filters.value.search, city: filters.value.city, country: filters.value.country, sort: sortBy.value }
  await catalogStore.fetchBreweries(params, append)
}

const loadNextPage = async () => {
  if (currentPage.value < totalPages.value && !isLoading.value && !isAppending.value) {
    isAppending.value = true
    currentPage.value++
    await loadBreweries(true)
    isAppending.value = false
  }
}

watch(loadMoreTrigger, (el) => {
  if (observer) observer.disconnect()
  if (el) {
    observer = new IntersectionObserver((entries) => {
      if (entries[0].isIntersecting && window.innerWidth <= 800 && viewMode.value === 'list') {
        loadNextPage()
      }
    }, { rootMargin: '200px' })
    observer.observe(el)
  }
})

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
onUnmounted(() => { if (observer) observer.disconnect() })
</script>

<style scoped>
.catalog-header-layout { display: flex; flex-direction: column; gap: 0; }
.header-top-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; gap: 1rem; }

.filters-section { margin-bottom: 1.5rem; position: relative; z-index: 20; }
.filters-section :deep(.panel-header) { border-bottom: none; margin-bottom: 0; padding-bottom: 1rem; }
.filters-section :deep(.panel-header h3) { font-size: 1.1rem; }

.toggle-icon { color: var(--text-muted); transition: transform 0.3s ease; }
.toggle-icon.rotated { transform: rotate(180deg); }
.filters-body { padding-top: 1.5rem; border-top: 1px solid var(--border); }
.filters-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; }
.filters-footer { margin-top: 1.5rem; display: flex; justify-content: flex-end; }

.results-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; padding: 0 0 1rem 0; border-bottom: 1px solid var(--border); }
.results-count { color: var(--text-muted); font-size: 0.95rem; flex: 1; text-align: center; }
.sort-control-wrapper, .sort-control-placeholder { width: 260px; }
.desktop-action-bar { width: 260px; display: flex; justify-content: flex-end; }
.mobile-action-bar { display: none; }

.map-wrapper { margin-bottom: 2rem; }
.map-info { margin-top: 10px; font-size: 0.85rem; color: var(--text-muted); text-align: center; font-style: italic; }

.breweries-grid { 
  display: grid; 
  grid-template-columns: repeat(2, 1fr); 
  gap: 1.5rem; 
  margin-bottom: 2rem; 
}

.mt-2 { margin-top: 0.5rem; }

@media (max-width: 800px) {
  .breweries-grid { 
    grid-template-columns: 1fr; 
  }

  .header-top-row { flex-direction: column; align-items: stretch; }
  .mobile-action-bar { display: block; margin-bottom: 1.5rem; }
  .mobile-action-bar :deep(.base-button) { width: 100%; padding: 1rem; justify-content: center; font-size: 1.1rem; }
  .desktop-action-bar { display: none; }
  .results-bar { flex-direction: column; align-items: stretch; gap: 1rem; border-bottom: none; }
  .sort-control-wrapper { width: 100%; }
}
</style>