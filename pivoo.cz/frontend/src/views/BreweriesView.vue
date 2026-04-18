<template>
  <div class="breweries-page">
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
          <button v-if="isAdmin" class="btn-add" @click="isAddModalOpen = true">
            <PlusIcon :size="20" /> Přidat pivovar
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
              <FilterInput v-model="filters.search" label="Název pivovaru" placeholder="Např. Prazdroj..." />
              <FilterInput v-model="filters.city" label="Město" placeholder="Např. Praha, Plzeň..." />
              <FilterInput v-model="filters.country" label="Země" placeholder="Např. Česko, Rakousko..." />
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

        <span class="results-count">Nalezeno pivovarů: <strong>{{ totalItems }}</strong></span>
        
        <div class="desktop-action-bar">
          <button v-if="isAdmin" class="btn-add" @click="isAddModalOpen = true">
            <PlusIcon :size="20" /> Přidat pivovar
          </button>
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
            <div v-if="isAppending" class="mobile-loader">Načítám další...</div>
          </div>
        </div>

        <div v-else class="map-wrapper">
          <MapView :items="breweries" type="brewery" @showDetail="openDetail" />
          <p class="map-info">Zobrazuji výsledky z aktuálního výběru ({{ breweries.length }} špendlíků).</p>
        </div>
      </template>
      
      <div v-else-if="!isLoading" class="empty-state">
        <FactoryIcon :size="48" color="#cbd5e1" />
        <p>Žádné pivovary neodpovídají zadaným filtrům.</p>
        <button class="btn-secondary mt-2" @click="resetFilters">Zrušit filtry</button>
      </div>
    </div>

    <DetailModal :show="isDetailOpen" :item="selectedItem" type="brewery" @close="isDetailOpen = false" />
    <AddBreweryModal :show="isAddModalOpen" :isEditing="false" :countries="countries" :form="form" @close="isAddModalOpen = false" @submit="submitBrewery" />
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { PlusIcon, FactoryIcon, FilterIcon, ChevronDownIcon, XIcon, LayoutGridIcon, MapIcon } from 'lucide-vue-next'
import { apiFetch } from '../api'
import { useAuthStore } from '../stores/auth'
import { useCatalogStore } from '../stores/catalog'
import BaseLoader from '../components/BaseLoader.vue'
import FilterInput from '../components/FilterInput.vue'
import BaseSelect from '../components/BaseSelect.vue'
import BreweryCard from '../components/BreweryCard.vue'
import MapView from '../components/MapView.vue'
import DetailModal from '../components/modals/DetailModal.vue'
import AddBreweryModal from '../components/modals/AddBreweryModal.vue'
import BasePagination from '../components/BasePagination.vue'

const authStore = useAuthStore()
const catalogStore = useCatalogStore()
const { user } = storeToRefs(authStore)
const { breweries, breweriesPagination, countries, isLoading } = storeToRefs(catalogStore)

const isAdmin = computed(() => user.value?.role === 'admin')
const viewMode = ref('list')

const isAddModalOpen = ref(false)
const filtersOpen = ref(false)
const isDetailOpen = ref(false)
const selectedItem = ref(null)
const toast = ref({ show: false, message: '', type: 'toast-success' })

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

const openDetail = (item) => { selectedItem.value = item; isDetailOpen.value = true }
const showToast = (message, type = 'toast-success') => { toast.value = { show: true, message, type }; setTimeout(() => { toast.value.show = false }, 3000) }

const submitBrewery = async () => {
  try {
    const formData = new FormData()
    Object.keys(form.value).forEach(key => { if (form.value[key] !== null && form.value[key] !== '') formData.append(key, form.value[key]) })
    const result = await apiFetch('/add_brewery.php', { method: 'POST', body: formData })
    if (result.status === 'success') { 
      isAddModalOpen.value = false; currentPage.value = 1; 
      await loadBreweries(false); showToast("Pivovar přidán") 
    }
  } catch (e) { showToast('Chyba serveru.', 'toast-error') }
}

onMounted(async () => { await catalogStore.fetchAllData(); loadBreweries(false) })
onUnmounted(() => { if (observer) observer.disconnect() })
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
.filter-chip { display: inline-flex; align-items: center; gap: 0.4rem; background-color: var(--primary); color: #1e293b; padding: 0.3rem 0.8rem; border-radius: 99px; font-size: 0.8rem; font-weight: 700; cursor: pointer; border: none; }

.results-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; padding: 0 0 1rem 0; border-bottom: 1px solid var(--border); }
.results-count { color: var(--text-muted); font-size: 0.95rem; flex: 1; text-align: center; }
.sort-control-wrapper, .sort-control-placeholder { width: 260px; }
.desktop-action-bar { width: 260px; display: flex; justify-content: flex-end; }
.mobile-action-bar { display: none; }

.map-wrapper { margin-bottom: 2rem; }
.map-info { margin-top: 10px; font-size: 0.85rem; color: var(--text-muted); text-align: center; font-style: italic; }

.breweries-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
.empty-state { text-align: center; padding: 4rem; color: var(--text-muted); }

@media (max-width: 800px) {
  .header-top-row { flex-direction: column; align-items: stretch; }
  .view-mode-toggle button { flex: 1; justify-content: center; }
  .mobile-action-bar { display: block; margin-bottom: 1.5rem; }
  .mobile-action-bar .btn-add { width: 100%; padding: 1rem; justify-content: center; font-size: 1.1rem; }
  .desktop-action-bar { display: none; }
  .results-bar { flex-direction: column; align-items: stretch; gap: 1rem; border-bottom: none; }
  .sort-control-wrapper { width: 100%; }
}
</style>