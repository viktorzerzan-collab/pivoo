<template>
  <div class="locations-page">
    <BaseLoader :show="isLoading" />

    <div class="catalog-header-layout">
      <div class="header-top-row">
        <div class="view-mode-toggle">
          <button :class="{ active: viewMode === 'list' }" @click="viewMode = 'list'">
            <LayoutGridIcon :size="18" /> Karty
          </button>
          <button :class="{ active: viewMode === 'map' }" @click="viewMode = 'map'">
            <MapIcon :size="18" /> Mapa
          </button>
        </div>

        <div class="mobile-action-bar">
          <button v-if="isAdmin" class="btn-add" @click="openAddModal">
            <PlusIcon :size="20" /> Přidat podnik
          </button>
        </div>
      </div>

      <div class="filters-section panel-card">
        <div class="filters-header" @click="filtersOpen = !filtersOpen">
          <div class="filters-title">
            <FilterIcon :size="20" class="panel-icon" /> 
            <h3>Filtrování a vyhledávání</h3>
          </div>
          <ChevronDownIcon :class="{ 'rotated': filtersOpen }" :size="20" class="toggle-icon" />
        </div>
        
        <transition name="slide-fade">
          <div v-show="filtersOpen" class="filters-body">
            <div class="filters-grid">
              <FilterInput v-model="filters.search" label="Název podniku" placeholder="Např. U Tygra..." />
              <FilterInput v-model="filters.city" label="Město" placeholder="Např. Praha, Plzeň..." />
              <FilterInput v-model="filters.country" label="Země" placeholder="Např. Česko, Německo..." />
            </div>
            <div class="filters-footer">
              <button class="btn-secondary" @click="resetFilters">Resetovat filtry</button>
            </div>
          </div>
        </transition>
      </div>

      <div v-if="activeFilters.length > 0" class="active-filters-chips">
        <span class="chips-label">Aktivní filtry:</span>
        <div class="chips-container">
          <button v-for="chip in activeFilters" :key="chip.id" class="filter-chip" @click="removeFilter(chip)">
            {{ chip.label }} <XIcon :size="14" />
          </button>
        </div>
      </div>

      <div class="results-bar">
        <div v-if="viewMode === 'list'" class="sort-control-wrapper">
          <BaseSelect v-model="sortBy" placeholder="Řadit podle..." :searchable="false">
            <option v-for="opt in sortOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
          </BaseSelect>
        </div>
        <div v-else class="sort-control-placeholder"></div>

        <span class="results-count">Nalezeno podniků: <strong>{{ totalItems }}</strong></span>
        
        <div class="desktop-action-bar">
          <button v-if="isAdmin" class="btn-add" @click="openAddModal">
            <PlusIcon :size="20" /> Přidat podnik
          </button>
        </div>
      </div>
    </div>

    <div class="catalog-container">
      <template v-if="locations.length > 0">
        <div v-if="viewMode === 'list'" class="list-wrapper">
          <div class="locations-grid">
            <LocationCard v-for="loc in locations" :key="loc.id" :location="loc" @showDetail="openDetail" />
          </div>
          <div class="desktop-pagination">
            <BasePagination v-if="totalPages > 1" v-model:currentPage="currentPage" :total-pages="totalPages" />
          </div>
          <div ref="loadMoreTrigger" class="load-more-trigger">
            <div v-if="isAppending" class="mobile-loader">Načítám další...</div>
          </div>
        </div>

        <div v-else class="map-wrapper">
          <MapView :items="locations" type="location" @showDetail="openDetail" />
          <p class="map-info">Zobrazuji výsledky z aktuálního výběru ({{ locations.length }} špendlíků).</p>
        </div>
      </template>
      
      <div v-else-if="!isLoading" class="empty-state">
        <MapIcon :size="48" color="#cbd5e1" />
        <p>Žádné podniky neodpovídají zadaným filtrům.</p>
        <button class="btn-secondary mt-2" @click="resetFilters">Zrušit filtry</button>
      </div>
    </div>

    <DetailModal :show="isDetailOpen" :item="selectedItem" type="location" @close="isDetailOpen = false" />
    <AddLocationModal :show="isAddModalOpen" :countries="countries" :form="form" @close="isAddModalOpen = false" @submit="submitLocation" />
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { PlusIcon, MapIcon, FilterIcon, ChevronDownIcon, XIcon, LayoutGridIcon } from 'lucide-vue-next'
import { apiFetch } from '../api'
import { useAuthStore } from '../stores/auth'
import { useCatalogStore } from '../stores/catalog'
import BaseLoader from '../components/BaseLoader.vue'
import FilterInput from '../components/FilterInput.vue'
import BaseSelect from '../components/BaseSelect.vue'
import LocationCard from '../components/LocationCard.vue'
import MapView from '../components/MapView.vue'
import DetailModal from '../components/modals/DetailModal.vue'
import AddLocationModal from '../components/modals/AddLocationModal.vue'
import BasePagination from '../components/BasePagination.vue'

const authStore = useAuthStore()
const catalogStore = useCatalogStore()
const { user } = storeToRefs(authStore)
const { locations, locationsPagination, countries, isLoading } = storeToRefs(catalogStore)

const isAdmin = computed(() => user.value?.role === 'admin')
const viewMode = ref('list')

const isAddModalOpen = ref(false)
const filtersOpen = ref(false)
const isDetailOpen = ref(false)
const selectedItem = ref(null)
const toast = ref({ show: false, message: '', type: 'toast-success' })

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
  addMultiChips(filters.value.search, 'search', 'Hledání')
  addMultiChips(filters.value.city, 'city', 'Město')
  addMultiChips(filters.value.country, 'country', 'Země')
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

const loadLocations = async (append = false) => {
  const params = { page: currentPage.value, limit: itemsPerPage, search: filters.value.search, city: filters.value.city, country: filters.value.country, sort: sortBy.value }
  await catalogStore.fetchLocations(params, append)
}

const loadNextPage = async () => {
  if (currentPage.value < totalPages.value && !isLoading.value && !isAppending.value) {
    isAppending.value = true
    currentPage.value++
    await loadLocations(true)
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

onUnmounted(() => { if (observer) observer.disconnect() })

const resetFilters = () => {
  filters.value = JSON.parse(JSON.stringify(initialFilters))
  sortBy.value = 'name_asc'
  currentPage.value = 1
  loadLocations(false)
}

watch([filters, sortBy], () => { currentPage.value = 1; loadLocations(false) }, { deep: true })
watch(currentPage, () => { if (!isAppending.value) loadLocations(false) })

const sortOptions = [
  { value: 'name_asc', label: 'Název (A-Z)' },
  { value: 'name_desc', label: 'Název (Z-A)' },
  { value: 'city_asc', label: 'Město (A-Z)' },
  { value: 'city_desc', label: 'Město (Z-A)' },
  { value: 'rating_desc', label: 'Hodnocení (Nejlepší)' },
  { value: 'rating_asc', label: 'Hodnocení (Nejhorší)' },
  { value: 'newest', label: 'Datum přidání (Od nejnovějšího)' },
  { value: 'oldest', label: 'Datum přidání (Od nejstaršího)' }
]

const openAddModal = () => {
  form.value = { name: '', type: 'hospoda', city: '', zip_code: '', country_id: 1, address: '', email: '', phone: '', website: '', opening_hours: '', lat: null, lng: null }
  isAddModalOpen.value = true
}

const openDetail = (loc) => { selectedItem.value = loc; isDetailOpen.value = true }
const showToast = (message, type = 'toast-success') => { toast.value = { show: true, message, type }; setTimeout(() => { toast.value.show = false }, 3000) }

const submitLocation = async () => {
  try {
    const result = await apiFetch('/add_location.php', { method: 'POST', body: JSON.stringify(form.value) })
    if (result.status === 'success') { isAddModalOpen.value = false; currentPage.value = 1; await loadLocations(false); showToast("Podnik uložen") }
  } catch (e) { showToast('Chyba serveru.', 'toast-error') }
}

onMounted(async () => { await catalogStore.fetchAllData(); loadLocations(false) })
</script>

<style scoped>
.catalog-header-layout { display: flex; flex-direction: column; gap: 0; }
.header-top-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; gap: 1rem; }

/* Sjednocení velikosti tlačítka s Dashboardem */
.btn-add { 
  display: flex; align-items: center; gap: 0.5rem; 
  padding: 0.75rem 1.5rem; font-weight: 700; 
}

.view-mode-toggle { display: flex; background: var(--bg-panel); padding: 4px; border-radius: 10px; border: 1px solid var(--border); }
.view-mode-toggle button { padding: 0.5rem 1.25rem; border-radius: 7px; border: none; background: none; color: var(--text-muted); font-weight: 700; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; gap: 0.5rem; }
.view-mode-toggle button.active { background: var(--primary); color: #1e293b; box-shadow: var(--shadow-sm); }

.panel-card { background: var(--bg-panel); border: 1px solid var(--border); border-radius: 12px; margin-bottom: 1.5rem; position: relative; z-index: 20; }
.filters-header { display: flex; justify-content: space-between; align-items: center; padding: 1.25rem 1.5rem; cursor: pointer; }
.filters-title { display: flex; align-items: center; gap: 0.75rem; }
.filters-title h3 { margin: 0; font-size: 1.1rem; color: var(--text-main); }
.filters-body { padding: 0 1.5rem 1.5rem 1.5rem; border-top: 1px solid var(--border); }
.filters-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-top: 1.5rem; }
.filters-footer { margin-top: 1.5rem; display: flex; justify-content: flex-end; }

.active-filters-chips { display: flex; align-items: center; flex-wrap: wrap; gap: 0.75rem; margin-bottom: 1.5rem; padding: 0 0.5rem; }
.chips-label { font-size: 0.85rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; }
.chips-container { display: flex; flex-wrap: wrap; gap: 0.5rem; }
.filter-chip { display: inline-flex; align-items: center; gap: 0.4rem; background-color: var(--primary); color: #1e293b; border: none; padding: 0.3rem 0.8rem; border-radius: 99px; font-size: 0.8rem; font-weight: 700; cursor: pointer; transition: all 0.2s ease; }

.results-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; padding: 0 0 1rem 0; border-bottom: 1px solid var(--border); }
.results-count { color: var(--text-muted); font-size: 0.95rem; flex: 1; text-align: center; }
.sort-control-wrapper, .sort-control-placeholder { width: 260px; }
.desktop-action-bar { width: 260px; display: flex; justify-content: flex-end; }
.mobile-action-bar { display: none; }

.map-wrapper { margin-bottom: 2rem; }
.map-info { margin-top: 10px; font-size: 0.85rem; color: var(--text-muted); text-align: center; font-style: italic; }

.locations-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
.empty-state { text-align: center; padding: 4rem; color: var(--text-muted); }

.desktop-pagination { display: block; }
.load-more-trigger { height: 20px; width: 100%; }
.mobile-loader { display: none; text-align: center; padding: 1rem; color: var(--text-muted); font-weight: 600; font-size: 0.9rem; }

@media (max-width: 800px) {
  .header-top-row { flex-direction: column; align-items: stretch; }
  .view-mode-toggle button { flex: 1; justify-content: center; }
  .mobile-action-bar { display: block; margin-bottom: 1.5rem; }
  .mobile-action-bar .btn-add { width: 100%; padding: 1rem; justify-content: center; font-size: 1.1rem; }
  .desktop-action-bar { display: none; }
  .results-bar { flex-direction: column; align-items: stretch; gap: 1rem; border-bottom: none; }
  .sort-control-wrapper { width: 100%; }
  .results-count { text-align: center; padding-top: 0.5rem; border-top: 1px solid var(--border); }
  .desktop-pagination { display: none; }
  .mobile-loader { display: block; }
}
</style>