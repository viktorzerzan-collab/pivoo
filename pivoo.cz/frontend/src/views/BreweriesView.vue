<template>
  <div class="breweries-page">
    <BaseLoader :show="isLoading" />

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
        <div class="sort-control-wrapper" v-show="!isMapView">
          <BaseSelect v-model="sortBy" placeholder="Řadit podle..." :searchable="false">
            <option v-for="opt in sortOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
          </BaseSelect>
        </div>
      </div>
    </div>

    <div class="catalog-container">
      <div v-if="filteredBreweries.length > 0" class="list-wrapper">
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
      
      <div v-else-if="!isLoading" class="empty-state">
        <FactoryIcon :size="48" color="#cbd5e1" :stroke-width="1" />
        <p>Žádné pivovary neodpovídají zadaným filtrům.</p>
        <button class="btn-secondary mt-2" @click="resetFilters">Zrušit filtry</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { PlusIcon, FactoryIcon, FilterIcon, ChevronDownIcon } from 'lucide-vue-next'
import { useAuthStore } from '../stores/auth'
import { useCatalogStore } from '../stores/catalog'

import BaseLoader from '../components/BaseLoader.vue'
import FilterInput from '../components/FilterInput.vue'
import BaseSelect from '../components/BaseSelect.vue'
import BreweryCard from '../components/BreweryCard.vue'
import BasePagination from '../components/BasePagination.vue'

const authStore = useAuthStore()
const catalogStore = useCatalogStore()
const { user } = storeToRefs(authStore)
const { breweries, countries, isLoading } = storeToRefs(catalogStore)
const isAdmin = computed(() => user.value?.role === 'admin')

const filtersOpen = ref(false)
const currentPage = ref(1)
const itemsPerPage = 30
const sortBy = ref('newest')

const initialFilters = { search: '', city: '', country: '' }
const filters = ref(JSON.parse(JSON.stringify(initialFilters)))

const resetFilters = () => {
  filters.value = JSON.parse(JSON.stringify(initialFilters))
  sortBy.value = 'newest'
}

watch([filters, sortBy], () => { currentPage.value = 1 }, { deep: true })

const sortOptions = [
  { value: 'newest', label: 'Datum přidání (Od nejnovějšího)' },
  { value: 'rating_desc', label: 'Hodnocení (Od nejlepšího)' },
  { value: 'name_asc', label: 'Název (A-Z)' }
]

const filteredBreweries = computed(() => {
  let result = [...(breweries.value || [])]

  if (filters.value.search) {
    const q = filters.value.search.toLowerCase()
    result = result.filter(b => b.name.toLowerCase().includes(q))
  }
  if (filters.value.city) result = result.filter(b => b.city === filters.value.city)
  if (filters.value.country) result = result.filter(b => b.country_id == filters.value.country)

  result.sort((a, b) => {
    // PRIMÁRNÍ ŘAZENÍ: Oblíbené jdou vždy nahoru
    if (a.is_favorite !== b.is_favorite) {
      return (b.is_favorite || 0) - (a.is_favorite || 0)
    }

    switch (sortBy.value) {
      case 'name_asc': return a.name.localeCompare(b.name)
      case 'rating_desc': return (parseFloat(b.avg_rating) || 0) - (parseFloat(a.avg_rating) || 0)
      case 'newest': return new Date(b.created_at) - new Date(a.created_at)
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

const changePage = (page) => {
  currentPage.value = page
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

const openDetail = (item) => { /* Emit detail */ }

onMounted(() => { catalogStore.fetchAllData() })
</script>

<style scoped>
/* Styly jsou identické jako v BeersView.vue */
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
.page-header h2 { margin: 0; font-size: 2rem; color: var(--text-main); }
.panel-card { background: var(--bg-panel); border: 1px solid var(--border); border-radius: 12px; margin-bottom: 2rem; }
.filters-header { display: flex; justify-content: space-between; align-items: center; padding: 1.25rem 1.5rem; cursor: pointer; }
.filters-title { display: flex; align-items: center; gap: 0.75rem; }
.filters-body { padding: 0 1.5rem 1.5rem; border-top: 1px solid var(--border); }
.filters-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-top: 1.5rem; }
.results-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid var(--border); }
.sort-control-wrapper { width: 260px; }
.breweries-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
.empty-state { text-align: center; padding: 4rem; color: var(--text-muted); }
</style>