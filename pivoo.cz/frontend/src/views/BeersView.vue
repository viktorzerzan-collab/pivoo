<template>
  <div class="beers-page">
    <BaseLoader :show="isLoading" />

    <div class="page-header">
      <h2>Katalog piv</h2>
      <button v-if="authStore.user" class="btn-add" @click="isAddModalOpen = true">
        <PlusCircleIcon :size="20" /> Přidat pivo
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
            <FilterInput v-model="filters.search" label="Název piva" placeholder="Např. Pilsner..." />
            
            <BaseSelect v-model="filters.brewery" label="Pivovar" placeholder="Všechny pivovary" searchable>
              <option value="">Všechny pivovary</option>
              <option v-for="b in breweries" :key="b.id" :value="b.id">{{ b.name }}</option>
            </BaseSelect>

            <BaseSelect v-model="filters.style" label="Pivní styl" placeholder="Všechny styly" searchable>
              <option value="">Všechny styly</option>
              <option v-for="s in styles" :key="s.id" :value="s.id">{{ s.name }}</option>
            </BaseSelect>

            <BaseSelect v-model="filters.country" label="Země původu" placeholder="Všechny země" searchable>
              <option value="">Všechny země</option>
              <option v-for="c in countries" :key="c.id" :value="c.id">{{ c.name_cz }}</option>
            </BaseSelect>

            <FilterRange v-model="filters.epm" label="Stupňovitost (EPM)" :step="0.1" unit="°" />
            <FilterRange v-model="filters.abv" label="Obsah alkoholu (ABV)" :step="0.1" unit="%" />
            <FilterRange v-model="filters.ibu" label="Hořkost (IBU)" :step="1" unit="IBU" />
          </div>
          
          <div class="filters-footer">
            <button class="btn-secondary" @click="resetFilters">Resetovat filtry</button>
          </div>
        </div>
      </transition>
    </div>

    <div class="results-bar">
      <span class="results-count">Nalezeno piv: <strong>{{ filteredAndSortedBeers.length }}</strong></span>
      
      <div class="sort-control-wrapper">
        <BaseSelect v-model="sortBy" placeholder="Řadit podle..." :searchable="false">
          <option v-for="opt in sortOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
        </BaseSelect>
      </div>
    </div>

    <div class="catalog-container">
      <template v-if="filteredAndSortedBeers.length > 0">
        <div class="beers-grid">
          <BeerCard 
            v-for="beer in paginatedBeers" 
            :key="beer.id" 
            :beer="beer" 
            :brewery="getBrewery(beer.brewery_id)"
            :style-name="getStyleName(beer.style_id)"
            @edit="openEditModal"
            @delete="confirmDelete"
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
        <BeerIcon :size="48" color="#cbd5e1" :stroke-width="1" />
        <p>Žádná piva neodpovídají zadaným filtrům.</p>
        <button class="btn-secondary mt-2" @click="resetFilters">Zrušit filtry</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { useCatalogStore } from '../stores/catalog'
import { useAuthStore } from '../stores/auth'
import { PlusCircleIcon, FilterIcon, ChevronDownIcon, BeerIcon } from 'lucide-vue-next'

import FilterInput from '../components/FilterInput.vue'
import FilterRange from '../components/FilterRange.vue'
import BaseSelect from '../components/BaseSelect.vue'
import BeerCard from '../components/BeerCard.vue'
import BaseLoader from '../components/BaseLoader.vue'
import BasePagination from '../components/BasePagination.vue'

const catalogStore = useCatalogStore()
const authStore = useAuthStore()

const { beers, breweries, styles, countries, isLoading } = storeToRefs(catalogStore)

// OPRAVA: Vždy načítáme aktuální data, aby se zobrazil loader
onMounted(() => {
  catalogStore.fetchAllData()
})

const isAddModalOpen = ref(false)
const filtersOpen = ref(false)

const currentPage = ref(1)
const itemsPerPage = 12

const changePage = (page) => {
  currentPage.value = page
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

const initialFilters = {
  search: '', brewery: '', style: '', country: '',
  epm: { min: '', max: '' }, abv: { min: '', max: '' }, ibu: { min: '', max: '' }
}

const filters = ref(JSON.parse(JSON.stringify(initialFilters)))
const sortBy = ref('newest')

const resetFilters = () => {
  filters.value = JSON.parse(JSON.stringify(initialFilters))
  sortBy.value = 'newest'
  currentPage.value = 1
}

watch([filters, sortBy], () => { currentPage.value = 1 }, { deep: true })

const sortOptions = [
  { value: 'newest', label: 'Datum přidání (Od nejnovějšího)' },
  { value: 'oldest', label: 'Datum přidání (Od nejstaršího)' },
  { value: 'rating_desc', label: 'Hodnocení (Od nejlepšího)' },
  { value: 'rating_asc', label: 'Hodnocení (Od nejhoršího)' },
  { value: 'name_asc', label: 'Název piva (A-Z)' },
  { value: 'name_desc', label: 'Název piva (Z-A)' },
  { value: 'brewery_asc', label: 'Pivovar (A-Z)' },
  { value: 'brewery_desc', label: 'Pivovar (Z-A)' },
  { value: 'style_asc', label: 'Styl (A-Z)' },
  { value: 'style_desc', label: 'Styl (Z-A)' },
  { value: 'abv_desc', label: 'Alkohol (Od nejsilnějšího)' },
  { value: 'abv_asc', label: 'Alkohol (Od nejslabšího)' },
  { value: 'epm_desc', label: 'Stupňovitost (Od nejvyšší)' },
  { value: 'epm_asc', label: 'Stupňovitost (Od nejnižší)' },
  { value: 'ibu_desc', label: 'Hořkost (Od nejvyšší)' },
  { value: 'ibu_asc', label: 'Hořkost (Od nejnižší)' }
]

const getBrewery = (id) => breweries.value.find(b => b.id == id)
const getStyleName = (id) => styles.value.find(s => s.id == id)?.name || 'Neznámý styl'

const filteredAndSortedBeers = computed(() => {
  let result = beers.value

  if (filters.value.search) {
    const q = filters.value.search.toLowerCase()
    result = result.filter(b => b.name.toLowerCase().includes(q))
  }

  if (filters.value.brewery) result = result.filter(b => b.brewery_id == filters.value.brewery)
  if (filters.value.style) result = result.filter(b => b.style_id == filters.value.style)
  if (filters.value.country) {
    result = result.filter(b => {
      const br = getBrewery(b.brewery_id)
      return br && br.country_id == filters.value.country
    })
  }

  const checkRange = (val, range) => {
    if (val == null || val === '') return false
    const num = Number(val)
    const min = range.min !== '' ? Number(range.min) : -Infinity
    const max = range.max !== '' ? Number(range.max) : Infinity
    return num >= min && num <= max
  }

  if (filters.value.epm.min !== '' || filters.value.epm.max !== '') result = result.filter(b => checkRange(b.epm, filters.value.epm))
  if (filters.value.abv.min !== '' || filters.value.abv.max !== '') result = result.filter(b => checkRange(b.abv, filters.value.abv))
  if (filters.value.ibu.min !== '' || filters.value.ibu.max !== '') result = result.filter(b => checkRange(b.ibu, filters.value.ibu))

  result.sort((a, b) => {
    const getNum = (obj, prop) => (obj[prop] != null && obj[prop] !== '') ? Number(obj[prop]) : null
    
    const compareNum = (prop, asc) => {
      const vA = getNum(a, prop); const vB = getNum(b, prop);
      if (vA === null && vB === null) return 0;
      if (vA === null) return 1; 
      if (vB === null) return -1;
      return asc ? vA - vB : vB - vA;
    }

    const compareStr = (strA, strB, asc) => {
      const sA = strA || ''; const sB = strB || '';
      return asc ? sA.localeCompare(sB) : sB.localeCompare(sA);
    }

    switch (sortBy.value) {
      case 'name_asc': return compareStr(a.name, b.name, true)
      case 'name_desc': return compareStr(a.name, b.name, false)
      case 'brewery_asc': return compareStr(getBrewery(a.brewery_id)?.name, getBrewery(b.brewery_id)?.name, true)
      case 'brewery_desc': return compareStr(getBrewery(a.brewery_id)?.name, getBrewery(b.brewery_id)?.name, false)
      case 'style_asc': return compareStr(getStyleName(a.style_id), getStyleName(b.style_id), true)
      case 'style_desc': return compareStr(getStyleName(a.style_id), getStyleName(b.style_id), false)
      case 'epm_asc': return compareNum('epm', true)
      case 'epm_desc': return compareNum('epm', false)
      case 'abv_asc': return compareNum('abv', true)
      case 'abv_desc': return compareNum('abv', false)
      case 'ibu_asc': return compareNum('ibu', true)
      case 'ibu_desc': return compareNum('ibu', false)
      case 'rating_asc': return compareNum('avg_rating', true)
      case 'rating_desc': return compareNum('avg_rating', false)
      case 'newest': return new Date(b.created_at) - new Date(a.created_at)
      case 'oldest': return new Date(a.created_at) - new Date(b.created_at)
      default: return 0
    }
  })

  return result
})

const totalPages = computed(() => Math.ceil(filteredAndSortedBeers.value.length / itemsPerPage))

const paginatedBeers = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage
  const end = start + itemsPerPage
  return filteredAndSortedBeers.value.slice(start, end)
})

const openEditModal = (beer) => { /* tvá původní logika */ }
const confirmDelete = (id) => { /* tvá původní logika */ }
</script>

<style scoped>
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
.page-header h2 { margin: 0; font-size: 2rem; color: var(--text-main); }

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
}

.filters-title { display: flex; align-items: center; gap: 0.75rem; }
.filters-title h3 { margin: 0; font-size: 1.1rem; color: var(--text-main); }
.toggle-icon { color: var(--text-muted); transition: transform 0.3s ease; }
.toggle-icon.rotated { transform: rotate(180deg); }
.filters-body { padding: 0 1.5rem 1.5rem 1.5rem; border-top: 1px solid var(--border); }
.filters-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-top: 1.5rem; }
.filters-footer { margin-top: 1.5rem; display: flex; justify-content: flex-end; }

.results-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid var(--border); }
.results-count { color: var(--text-muted); font-size: 0.95rem; }
.results-count strong { color: var(--text-main); }

.sort-control-wrapper { width: 260px; }

.catalog-container { position: relative; min-height: 400px; display: flex; flex-direction: column; width: 100%; }
.beers-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
.empty-state { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 4rem 1rem; text-align: center; color: var(--text-muted); }
.empty-state p { margin: 1rem 0; font-size: 1.1rem; }
.mt-2 { margin-top: 0.5rem; }

.slide-fade-enter-active { transition: all 0.3s ease-out; }
.slide-fade-leave-active { transition: all 0.2s cubic-bezier(1, 0.5, 0.8, 1); }
.slide-fade-enter-from, .slide-fade-leave-to { transform: translateY(-10px); opacity: 0; }

@media (max-width: 768px) {
  .page-header { flex-direction: column; align-items: flex-start; gap: 1rem; }
  .results-bar { flex-direction: column; align-items: flex-start; gap: 1rem; }
  .sort-control-wrapper { width: 100%; }
}
</style>