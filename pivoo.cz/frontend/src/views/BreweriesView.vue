<template>
  <div class="breweries-page">
    <transition name="toast-fade">
      <div v-if="toast.show" class="toast-notification" :class="toast.type">{{ toast.message }}</div>
    </transition>

    <div class="page-header">
      <h2>Katalog pivovarů</h2>
      <button v-if="isAdmin" class="btn-add" @click="isAddModalOpen = true">
        <PlusIcon :size="20" /> Přidat pivovar
      </button>
    </div>

    <div class="filters-section panel-card">
      <div class="filters-header" @click="filtersOpen = !filtersOpen">
        <div class="filters-title">
          <FilterIcon :size="20" /> 
          <h3>Filtrování a vyhledávání</h3>
        </div>
        <ChevronDownIcon :class="{ 'rotated': filtersOpen }" :size="20" class="toggle-icon" />
      </div>
      
      <transition name="slide-fade">
        <div v-show="filtersOpen" class="filters-body">
          <div class="filters-grid">
            <FilterInput v-model="filters.search" label="Název pivovaru" placeholder="Hledat pivovar..." />
            
            <BaseSelect v-model="filters.city" label="Město" placeholder="Všechna města" searchable>
              <option value="">Všechna města</option>
              <option v-for="city in uniqueCities" :key="city" :value="city">{{ city }}</option>
            </BaseSelect>

            <BaseSelect v-model="filters.country" label="Země" placeholder="Všechny země" searchable>
              <option value="">Všechny země</option>
              <option v-for="c in countries" :key="c.id" :value="c.id">{{ c.name_cz }}</option>
            </BaseSelect>
          </div>
          
          <div class="filters-footer">
            <button class="btn-secondary" @click="resetFilters">Resetovat filtry</button>
          </div>
        </div>
      </transition>
    </div>

    <div class="results-bar">
      <span class="results-count">Nalezeno pivovarů: <strong>{{ filteredBreweries.length }}</strong></span>
      
      <div class="results-actions">
        <div class="view-toggle-group">
          <button 
            class="toggle-btn" 
            :class="{ active: !isMapView }" 
            @click="isMapView = false"
            title="Zobrazit seznam"
          >
            <LayoutGridIcon :size="20" />
          </button>
          <button 
            class="toggle-btn" 
            :class="{ active: isMapView }" 
            @click="isMapView = true"
            title="Zobrazit mapu"
          >
            <MapIcon :size="20" />
          </button>
        </div>

        <div class="sort-control-wrapper" v-show="!isMapView">
          <BaseSelect v-model="sortBy" placeholder="Řadit podle..." :searchable="false">
            <option v-for="opt in sortOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
          </BaseSelect>
        </div>
      </div>
    </div>

    <div class="catalog-container">
      <BaseLoader :show="isLoading" />

      <div v-if="isMapView" class="map-wrapper">
        <MapView 
          :items="filteredBreweries" 
          @showDetail="openDetail" 
        />
      </div>

      <div v-else-if="!isMapView && filteredBreweries.length > 0" class="list-wrapper">
        <div class="breweries-grid">
          <BreweryCard 
            v-for="brewery in paginatedBreweries" 
            :key="brewery.id" 
            :brewery="brewery" 
            @showDetail="openDetail" 
          />
        </div>
        
        <BasePagination 
          v-if="totalPages > 1"
          :current-page="currentPage" 
          :total-pages="totalPages"
          @page-changed="changePage" 
        />
      </div>
      
      <div v-else-if="!isLoading && filteredBreweries.length === 0" class="empty-state">
        <FactoryIcon :size="48" color="#cbd5e1" :stroke-width="1" />
        <p>Žádné pivovary neodpovídají zadaným filtrům.</p>
        <button class="btn-secondary mt-2" @click="resetFilters">Zrušit filtry</button>
      </div>
    </div>

    <DetailModal :show="isDetailOpen" :item="selectedItem" type="brewery" @close="isDetailOpen = false" />
    <AddBreweryModal :show="isAddModalOpen" :isEditing="false" :countries="countries" :form="form" @close="isAddModalOpen = false" @submit="submitBrewery" />
  </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { PlusIcon, FactoryIcon, FilterIcon, ChevronDownIcon, MapIcon, LayoutGridIcon } from 'lucide-vue-next'

import { apiFetch } from '../api'
import { useAuthStore } from '../stores/auth'
import { useCatalogStore } from '../stores/catalog'

import BaseLoader from '../components/BaseLoader.vue'
import FilterInput from '../components/FilterInput.vue'
import BaseSelect from '../components/BaseSelect.vue'
import BreweryCard from '../components/BreweryCard.vue'
import DetailModal from '../components/modals/DetailModal.vue'
import AddBreweryModal from '../components/modals/AddBreweryModal.vue'
import MapView from '../components/MapView.vue'
import BasePagination from '../components/BasePagination.vue'

const authStore = useAuthStore()
const catalogStore = useCatalogStore()
const { user } = storeToRefs(authStore)
const { breweries, countries, isLoading } = storeToRefs(catalogStore)
const isAdmin = computed(() => user.value?.role === 'admin')

const isMapView = ref(false)
const toast = ref({ show: false, message: '', type: 'toast-success' })

const isAddModalOpen = ref(false)
const filtersOpen = ref(false)
const isDetailOpen = ref(false)
const selectedItem = ref(null)

const form = ref({ 
  name: '', city: '', zip_code: '', country_id: 1, 
  address: '', email: '', phone: '', website: '', logoFile: null 
})

const currentPage = ref(1)
const itemsPerPage = 30

const changePage = (page) => {
  currentPage.value = page
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

const initialFilters = {
  search: '',
  city: '',
  country: ''
}

const filters = ref(JSON.parse(JSON.stringify(initialFilters)))
const sortBy = ref('newest')

const resetFilters = () => {
  filters.value = JSON.parse(JSON.stringify(initialFilters))
  sortBy.value = 'newest'
  currentPage.value = 1
}

watch([filters, sortBy], () => { currentPage.value = 1 }, { deep: true })

// Sjednocené názvosloví a přidáno řazení podle města
const sortOptions = [
  { value: 'newest', label: 'Datum přidání (Od nejnovějšího)' },
  { value: 'oldest', label: 'Datum přidání (Od nejstaršího)' },
  { value: 'rating_desc', label: 'Hodnocení (Od nejlepšího)' },
  { value: 'rating_asc', label: 'Hodnocení (Od nejhoršího)' },
  { value: 'name_asc', label: 'Název (A-Z)' },
  { value: 'name_desc', label: 'Název (Z-A)' },
  { value: 'city_asc', label: 'Město (A-Z)' },
  { value: 'city_desc', label: 'Město (Z-A)' }
]

const uniqueCities = computed(() => {
  const cities = new Set()
  if (breweries.value) {
    breweries.value.forEach(b => {
      if (b.city && b.city.trim() !== '') {
        cities.add(b.city.trim())
      }
    })
  }
  return Array.from(cities).sort((a, b) => a.localeCompare(b))
})

const openDetail = (item) => { 
  selectedItem.value = item
  isDetailOpen.value = true 
}

const filteredBreweries = computed(() => {
  let result = breweries.value || []

  if (filters.value.search) {
    const q = filters.value.search.toLowerCase()
    result = result.filter(b => b.name.toLowerCase().includes(q))
  }

  if (filters.value.city) {
    result = result.filter(b => b.city === filters.value.city)
  }

  if (filters.value.country) {
    result = result.filter(b => b.country_id == filters.value.country)
  }

  result.sort((a, b) => {
    const getRating = (val) => (val != null && val !== '') ? parseFloat(val) : -1
    const valA = getRating(a.avg_rating)
    const valB = getRating(b.avg_rating)

    const compareStr = (strA, strB, asc) => {
      const sA = strA || ''; const sB = strB || '';
      return asc ? sA.localeCompare(sB) : sB.localeCompare(sA);
    }
    
    switch (sortBy.value) {
      case 'name_asc': return compareStr(a.name, b.name, true)
      case 'name_desc': return compareStr(a.name, b.name, false)
      case 'city_asc': return compareStr(a.city, b.city, true)
      case 'city_desc': return compareStr(a.city, b.city, false)
      case 'rating_desc': return valB - valA
      case 'rating_asc': return valA - valB
      case 'newest': return new Date(b.created_at || 0) - new Date(a.created_at || 0)
      case 'oldest': return new Date(a.created_at || 0) - new Date(b.created_at || 0)
      default: return 0
    }
  })

  return result
})

const totalPages = computed(() => Math.ceil(filteredBreweries.value.length / itemsPerPage))

const paginatedBreweries = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage
  return filteredBreweries.value.slice(start, start + itemsPerPage)
})

const showToast = (message, type = 'toast-success') => { 
  toast.value = { show: true, message, type }
  setTimeout(() => { toast.value.show = false }, 3000) 
}

const submitBrewery = async () => {
  try {
    const formData = new FormData()
    Object.keys(form.value).forEach(key => {
      if (form.value[key] !== null && form.value[key] !== '') {
        formData.append(key, form.value[key])
      }
    })

    const result = await apiFetch('/add_brewery.php', { method: 'POST', body: formData })
    if (result.status === 'success') { 
      isAddModalOpen.value = false
      form.value = { name: '', city: '', zip_code: '', country_id: 1, address: '', email: '', phone: '', website: '', logoFile: null }
      await catalogStore.fetchAllData()
      showToast("Pivovar přidán") 
    }
  } catch (e) { showToast('Chyba serveru.', 'toast-error') }
}

onMounted(() => { if (user.value) catalogStore.fetchAllData() })
</script>

<style scoped>
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
.page-header h2 { margin: 0; font-size: 2rem; color: var(--text-main); transition: color 0.5s ease; }

.panel-card { 
  background: var(--bg-panel); 
  border: 1px solid var(--border); 
  border-radius: 12px; 
  margin-bottom: 2rem; 
  position: relative;
  z-index: 20; 
  transition: background-color 0.5s ease, border-color 0.5s ease; 
}

.filters-header { 
  display: flex; justify-content: space-between; align-items: center; 
  padding: 1.25rem 1.5rem; cursor: pointer; user-select: none; 
  background: transparent; 
  transition: background-color 0.5s ease; 
}
.filters-title { display: flex; align-items: center; gap: 0.75rem; }
.filters-title h3 { margin: 0; font-size: 1.1rem; color: var(--text-main); transition: color 0.5s ease; }
.toggle-icon { color: var(--text-muted); transition: transform 0.3s ease, color 0.5s ease; }
.toggle-icon.rotated { transform: rotate(180deg); }
.filters-body { padding: 0 1.5rem 1.5rem 1.5rem; border-top: 1px solid var(--border); transition: border-color 0.5s ease; }
.filters-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-top: 1.5rem; }
.filters-footer { margin-top: 1.5rem; display: flex; justify-content: flex-end; }

.results-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid var(--border); transition: border-color 0.5s ease; }
.results-count { color: var(--text-muted); font-size: 0.95rem; transition: color 0.5s ease; }
.results-count strong { color: var(--text-main); transition: color 0.5s ease; }

.results-actions { display: flex; align-items: center; gap: 1rem; }
.sort-control-wrapper { width: 260px; } /* Rozšířeno na 260px stejně jako u piv pro delší texty */

.view-toggle-group {
  display: flex;
  background: var(--bg-app);
  border: 1px solid var(--border);
  padding: 4px;
  border-radius: 10px;
  transition: background-color 0.5s ease, border-color 0.5s ease;
}

.toggle-btn {
  background: transparent;
  color: var(--text-muted);
  border: none;
  padding: 8px 12px;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s;
  box-shadow: none;
}

.toggle-btn.active {
  background: var(--bg-panel);
  color: var(--primary);
  box-shadow: var(--shadow-sm);
}

.catalog-container { position: relative; min-height: 400px; display: flex; flex-direction: column; width: 100%; }
.list-wrapper { width: 100%; display: flex; flex-direction: column; }
.map-wrapper { width: 100%; }
.breweries-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1.5rem; width: 100%; margin-bottom: 2rem; }

.empty-state { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 4rem 1rem; text-align: center; color: var(--text-muted); transition: color 0.5s ease; }
.empty-state p { margin: 1rem 0; font-size: 1.1rem; }
.mt-2 { margin-top: 0.5rem; }

.slide-fade-enter-active { transition: all 0.3s ease-out; }
.slide-fade-leave-active { transition: all 0.2s cubic-bezier(1, 0.5, 0.8, 1); }
.slide-fade-enter-from, .slide-fade-leave-to { transform: translateY(-10px); opacity: 0; }

@media (max-width: 800px) { 
  .page-header { flex-direction: column; align-items: flex-start; gap: 1rem; }
  .page-header .btn-add { width: 100%; justify-content: center; }
  .results-bar { flex-direction: column; align-items: flex-start; gap: 1rem; }
  .results-actions { width: 100%; flex-direction: row-reverse; justify-content: space-between; }
  .sort-control-wrapper { flex: 1; width: auto; }
}
</style>