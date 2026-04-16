<template>
  <div class="locations-page">
    <transition name="toast-fade">
      <div v-if="toast.show" class="toast-notification" :class="toast.type">{{ toast.message }}</div>
    </transition>

    <BaseLoader :show="isLoading" />

    <div class="catalog-header-layout">
      <div class="mobile-action-bar">
        <button v-if="isAdmin" class="btn-add" @click="openAddModal">
          <PlusIcon :size="20" /> Přidat podnik
        </button>
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
              <FilterInput v-model="filters.search" label="Název podniku" placeholder="Hledat podnik..." />
              
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
        <div class="sort-control-wrapper">
          <BaseSelect v-model="sortBy" placeholder="Řadit podle..." :searchable="false">
            <option v-for="opt in sortOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
          </BaseSelect>
        </div>

        <span class="results-count">Nalezeno podniků: <strong>{{ filteredLocations.length }}</strong></span>
        
        <div class="desktop-action-bar">
          <button v-if="isAdmin" class="btn-add" @click="openAddModal">
            <PlusIcon :size="20" /> Přidat podnik
          </button>
        </div>
      </div>
    </div>

    <div class="catalog-container">
      <template v-if="filteredLocations.length > 0">
        <div class="locations-grid">
          <LocationCard 
            v-for="loc in paginatedLocations" 
            :key="loc.id" 
            :location="loc" 
            @showDetail="openDetail" 
          />
        </div>
        
        <BasePagination 
          v-if="totalPages > 1"
          :current-page="currentPage" 
          :total-pages="totalPages"
          @page-changed="changePage" 
        />
      </template>
      
      <div v-else-if="!isLoading" class="empty-state">
        <MapIcon :size="48" color="#cbd5e1" :stroke-width="1" />
        <p>Žádné podniky neodpovídají zadaným filtrům.</p>
        <button class="btn-secondary mt-2" @click="resetFilters">Zrušit filtry</button>
      </div>
    </div>

    <DetailModal :show="isDetailOpen" :item="selectedItem" type="location" @close="isDetailOpen = false" />
    <AddLocationModal :show="isAddModalOpen" :countries="countries" :form="form" @close="isAddModalOpen = false" @submit="submitLocation" />
  </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { PlusIcon, MapIcon, FilterIcon, ChevronDownIcon } from 'lucide-vue-next'
import { apiFetch } from '../api'
import { useAuthStore } from '../stores/auth'
import { useCatalogStore } from '../stores/catalog'
import BaseLoader from '../components/BaseLoader.vue'
import FilterInput from '../components/FilterInput.vue'
import BaseSelect from '../components/BaseSelect.vue'
import LocationCard from '../components/LocationCard.vue'
import DetailModal from '../components/modals/DetailModal.vue'
import AddLocationModal from '../components/modals/AddLocationModal.vue'
import BasePagination from '../components/BasePagination.vue'

const authStore = useAuthStore()
const catalogStore = useCatalogStore()
const { user } = storeToRefs(authStore)
const { locations, countries, isLoading } = storeToRefs(catalogStore)
const isAdmin = computed(() => user.value?.role === 'admin')

const toast = ref({ show: false, message: '', type: 'toast-success' })
const isAddModalOpen = ref(false)
const filtersOpen = ref(false)
const isDetailOpen = ref(false)
const selectedItem = ref(null)

const form = ref({ name: '', type: 'hospoda', city: '', zip_code: '', country_id: 1, address: '', email: '', phone: '', website: '', opening_hours: '' })
const currentPage = ref(1)
const itemsPerPage = 30

// VÝCHOZÍ ŘAZENÍ NASTAVENO NA NÁZEV
const sortBy = ref('name_asc')

const initialFilters = { search: '', city: '', country: '' }
const filters = ref(JSON.parse(JSON.stringify(initialFilters)))

const resetFilters = () => {
  filters.value = JSON.parse(JSON.stringify(initialFilters))
  sortBy.value = 'name_asc'
  currentPage.value = 1
}

watch([filters, sortBy], () => { currentPage.value = 1 }, { deep: true })

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

const uniqueCities = computed(() => {
  const cities = new Set()
  if (locations.value) {
    locations.value.filter(loc => loc.type === 'hospoda').forEach(l => {
      if (l.city && l.city.trim() !== '') {
        cities.add(l.city.trim())
      }
    })
  }
  return Array.from(cities).sort((a, b) => a.localeCompare(b))
})

const filteredLocations = computed(() => {
  let result = [...(locations.value || [])].filter(loc => loc.type === 'hospoda')

  if (filters.value.search) {
    const q = filters.value.search.toLowerCase()
    result = result.filter(l => l.name.toLowerCase().includes(q))
  }
  if (filters.value.city) result = result.filter(l => l.city === filters.value.city)
  if (filters.value.country) result = result.filter(l => l.country_id == filters.value.country)

  result.sort((a, b) => {
    // Priorita: Oblíbené hvězdičky
    if (a.is_favorite !== b.is_favorite) {
      return (b.is_favorite || 0) - (a.is_favorite || 0)
    }

    const getRating = (obj) => parseFloat(obj.avg_rating) || 0

    switch (sortBy.value) {
      case 'name_asc': return a.name.localeCompare(b.name)
      case 'name_desc': return b.name.localeCompare(a.name)
      case 'city_asc': return (a.city || '').localeCompare(b.city || '')
      case 'city_desc': return (b.city || '').localeCompare(a.city || '')
      case 'rating_desc': return getRating(b) - getRating(a)
      case 'rating_asc': return getRating(a) - getRating(b)
      case 'newest': return new Date(b.created_at) - new Date(a.created_at)
      case 'oldest': return new Date(a.created_at) - new Date(b.created_at)
      default: return 0
    }
  })

  return result
})

const totalPages = computed(() => Math.ceil(filteredLocations.value.length / itemsPerPage))
const paginatedLocations = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage
  return filteredLocations.value.slice(start, start + itemsPerPage)
})

const openAddModal = () => {
  form.value = { name: '', type: 'hospoda', city: '', zip_code: '', country_id: 1, address: '', email: '', phone: '', website: '', opening_hours: '' }
  isAddModalOpen.value = true
}

const openDetail = (loc) => { 
  selectedItem.value = loc
  isDetailOpen.value = true 
}

const showToast = (message, type = 'toast-success') => { 
  toast.value = { show: true, message, type }
  setTimeout(() => { toast.value.show = false }, 3000) 
}

const submitLocation = async () => {
  try {
    const result = await apiFetch('/add_location.php', { method: 'POST', body: JSON.stringify(form.value) })
    if (result.status === 'success') { 
      isAddModalOpen.value = false
      await catalogStore.fetchAllData()
      showToast("Podnik uložen") 
    } else {
      showToast(result.message || "Chyba při ukládání", "toast-error")
    }
  } catch (e) { 
    showToast('Chyba serveru.', 'toast-error') 
  }
}

onMounted(() => { catalogStore.fetchAllData() })
</script>

<style scoped>
.catalog-header-layout { display: flex; flex-direction: column; gap: 0; }

.panel-card { background: var(--bg-panel); border: 1px solid var(--border); border-radius: 12px; margin-bottom: 1.5rem; position: relative; z-index: 20; }
.filters-header { display: flex; justify-content: space-between; align-items: center; padding: 1.25rem 1.5rem; cursor: pointer; }
.filters-title { display: flex; align-items: center; gap: 0.75rem; }
.filters-title h3 { margin: 0; font-size: 1.1rem; color: var(--text-main); }

/* Barva ikonky ve filtru */
.panel-icon { color: var(--primary); }

.toggle-icon { color: var(--text-muted); transition: transform 0.3s ease; }
.toggle-icon.rotated { transform: rotate(180deg); }
.filters-body { padding: 0 1.5rem 1.5rem 1.5rem; border-top: 1px solid var(--border); }
.filters-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-top: 1.5rem; }
.filters-footer { margin-top: 1.5rem; display: flex; justify-content: flex-end; }

/* Zarovnání k pravítku a rozvržení */
.results-bar { 
  display: flex; 
  justify-content: space-between; 
  align-items: center; 
  margin-bottom: 2rem; 
  padding: 0 0 1rem 0;
  border-bottom: 1px solid var(--border); 
}
.results-count { color: var(--text-muted); font-size: 0.95rem; flex: 1; text-align: center; }
.results-count strong { color: var(--text-main); }
.sort-control-wrapper { width: 260px; }
.desktop-action-bar { width: 260px; display: flex; justify-content: flex-end; }
.mobile-action-bar { display: none; }

.locations-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
.empty-state { text-align: center; padding: 4rem; color: var(--text-muted); }

@media (max-width: 800px) {
  .mobile-action-bar { display: block; margin-bottom: 1.5rem; }
  .mobile-action-bar .btn-add { width: 100%; padding: 1rem; justify-content: center; font-size: 1.1rem; }
  .desktop-action-bar { display: none; }
  
  .results-bar { 
    flex-direction: column-reverse; 
    align-items: stretch; 
    gap: 1rem; 
    border-bottom: none;
  }
  .sort-control-wrapper { width: 100%; }
  .results-count { text-align: center; padding-top: 0.5rem; border-top: 1px solid var(--border); }
}
</style>