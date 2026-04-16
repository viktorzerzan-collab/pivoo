<template>
  <div class="beers-page">
    <BaseLoader :show="isLoading" />

    <div class="catalog-header-layout">
      <div class="mobile-action-bar">
        <button v-if="authStore.user" class="btn-add" @click="openAddModal">
          <PlusCircleIcon :size="20" /> Přidat pivo
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
              <FilterInput v-model="filters.search" label="Název piva" placeholder="Např. Pilsner..." />
              
              <BaseSelect v-model="filters.brewery" label="Pivovar" placeholder="Všechny pivovary" searchable>
                <option value="">Všechny pivovary</option>
                <option v-for="b in breweries" :key="b.id" :value="b.id">
                  {{ b.is_favorite ? '⭐ ' : '🏭 ' }}{{ b.name }}
                </option>
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
        <div class="sort-control-wrapper">
          <BaseSelect v-model="sortBy" placeholder="Řadit podle..." :searchable="false">
            <option v-for="opt in sortOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
          </BaseSelect>
        </div>

        <span class="results-count">Nalezeno piv: <strong>{{ filteredAndSortedBeers.length }}</strong></span>
        
        <div class="desktop-action-bar">
          <button v-if="authStore.user" class="btn-add" @click="openAddModal">
            <PlusCircleIcon :size="20" /> Přidat pivo
          </button>
        </div>
      </div>
    </div>

    <div class="catalog-container">
      <template v-if="filteredAndSortedBeers.length > 0">
        <div class="beers-grid">
          <BeerCard 
            v-for="beer in paginatedBeers" 
            :key="beer.id" 
            :beer="beer" 
            @showDetail="openDetail"
          />
        </div>
        
        <BasePagination 
          v-if="totalPages > 1"
          v-model:currentPage="currentPage" 
          :total-pages="totalPages"
        />
      </template>
      
      <div v-else-if="!isLoading" class="empty-state">
        <BeerIcon :size="48" color="#cbd5e1" :stroke-width="1" />
        <p>Žádná piva neodpovídají zadaným filtrům.</p>
        <button class="btn-secondary mt-2" @click="resetFilters">Zrušit filtry</button>
      </div>
    </div>

    <AddBeerModal 
      :show="isAddModalOpen" 
      :isEditing="false" 
      :breweries="breweries" 
      :styles="styles" 
      :form="beerForm" 
      @close="isAddModalOpen = false" 
      @submit="submitBeer" 
    />

    <transition name="toast-fade">
      <div v-if="toast.show" class="toast-notification" :class="toast.type">{{ toast.message }}</div>
    </transition>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { apiFetch } from '../api'
import { useCatalogStore } from '../stores/catalog'
import { useAuthStore } from '../stores/auth'
import { PlusCircleIcon, FilterIcon, ChevronDownIcon, BeerIcon } from 'lucide-vue-next'

import FilterInput from '../components/FilterInput.vue'
import FilterRange from '../components/FilterRange.vue'
import BaseSelect from '../components/BaseSelect.vue'
import BeerCard from '../components/BeerCard.vue'
import BaseLoader from '../components/BaseLoader.vue'
import BasePagination from '../components/BasePagination.vue'
import AddBeerModal from '../components/modals/AddBeerModal.vue'

const catalogStore = useCatalogStore()
const authStore = useAuthStore()

const { beers, breweries, styles, countries, isLoading } = storeToRefs(catalogStore)

onMounted(() => {
  catalogStore.fetchAllData()
})

const isAddModalOpen = ref(false)
const filtersOpen = ref(false)
const toast = ref({ show: false, message: '', type: 'toast-success' })

const beerForm = ref({ 
  name: '', brewery_id: '', style_id: '', epm: '', abv: '', ibu: '', 
  ebc: '', hops: '', malts: '', fermentation: '', tags: '', 
  is_unfiltered: false, is_unpasteurized: false 
})

const openAddModal = () => {
  beerForm.value = { 
    name: '', brewery_id: '', style_id: '', epm: '', abv: '', ibu: '', 
    ebc: '', hops: '', malts: '', fermentation: '', tags: '', 
    is_unfiltered: false, is_unpasteurized: false 
  }
  isAddModalOpen.value = true
}

const showToast = (message, type = 'toast-success') => { 
  toast.value = { show: true, message, type }
  setTimeout(() => { toast.value.show = false }, 3000) 
}

const submitBeer = async () => {
  try {
    const res = await apiFetch('/add_beer.php', { 
      method: 'POST', 
      body: JSON.stringify(beerForm.value) 
    })
    if (res.status === 'success') { 
      isAddModalOpen.value = false
      await catalogStore.fetchAllData()
      showToast("Pivo bylo úspěšně přidáno") 
    } else {
      showToast(res.message || "Chyba při ukládání", "toast-error")
    }
  } catch (e) { 
    showToast('Chyba serveru.', 'toast-error') 
  }
}

const currentPage = ref(1)
const itemsPerPage = 12

const initialFilters = {
  search: '', brewery: '', style: '', country: '',
  epm: { min: '', max: '' }, abv: { min: '', max: '' }, ibu: { min: '', max: '' }
}

const filters = ref(JSON.parse(JSON.stringify(initialFilters)))
const sortBy = ref('name_asc')

const resetFilters = () => {
  filters.value = JSON.parse(JSON.stringify(initialFilters))
  sortBy.value = 'name_asc'
  currentPage.value = 1
}

watch([filters, sortBy], () => { currentPage.value = 1 }, { deep: true })

const sortOptions = [
  { value: 'name_asc', label: 'Název (A-Z)' },
  { value: 'name_desc', label: 'Název (Z-A)' },
  { value: 'brewery_asc', label: 'Pivovar (A-Z)' },
  { value: 'brewery_desc', label: 'Pivovar (Z-A)' },
  { value: 'style_asc', label: 'Styl (A-Z)' },
  { value: 'style_desc', label: 'Styl (Z-A)' },
  { value: 'rating_desc', label: 'Hodnocení (Nejlepší)' },
  { value: 'rating_asc', label: 'Hodnocení (Nejhorší)' },
  { value: 'abv_desc', label: 'Alkohol (Nejsilnější)' },
  { value: 'abv_asc', label: 'Alkohol (Nejslabší)' },
  { value: 'epm_desc', label: 'Stupňovitost (Nejvyšší)' },
  { value: 'epm_asc', label: 'Stupňovitost (Nejnižší)' },
  { value: 'ibu_desc', label: 'Hořkost (Nejvyšší)' },
  { value: 'ibu_asc', label: 'Hořkost (Nejnižší)' },
  { value: 'newest', label: 'Datum přidání (Od nejnovějšího)' },
  { value: 'oldest', label: 'Datum přidání (Od nejstaršího)' }
]

const filteredAndSortedBeers = computed(() => {
  let result = [...beers.value]

  if (filters.value.search) {
    const q = filters.value.search.toLowerCase()
    result = result.filter(b => b.name.toLowerCase().includes(q))
  }
  if (filters.value.brewery) result = result.filter(b => b.brewery_id == filters.value.brewery)
  if (filters.value.style) result = result.filter(b => b.style_id == filters.value.style)
  if (filters.value.country) {
    result = result.filter(b => {
      const br = breweries.value.find(brew => brew.id == b.brewery_id)
      return br && br.country_id == filters.value.country
    })
  }

  const applyRange = (val, range) => {
    if (val == null || val === '') return true
    const n = Number(val)
    if (range.min !== '' && n < Number(range.min)) return false
    if (range.max !== '' && n > Number(range.max)) return false
    return true
  }
  result = result.filter(b => applyRange(b.epm, filters.value.epm))
  result = result.filter(b => applyRange(b.abv, filters.value.abv))
  result = result.filter(b => applyRange(b.ibu, filters.value.ibu))

  result.sort((a, b) => {
    if (a.is_favorite !== b.is_favorite) {
      return (b.is_favorite || 0) - (a.is_favorite || 0)
    }
    const getNum = (obj, prop) => (obj[prop] != null && obj[prop] !== '') ? Number(obj[prop]) : null
    switch (sortBy.value) {
      case 'name_asc': return a.name.localeCompare(b.name)
      case 'name_desc': return b.name.localeCompare(a.name)
      case 'brewery_asc': return (a.brewery_name || '').localeCompare(b.brewery_name || '')
      case 'brewery_desc': return (b.brewery_name || '').localeCompare(a.brewery_name || '')
      case 'style_asc': return (a.style || '').localeCompare(b.style || '')
      case 'style_desc': return (b.style || '').localeCompare(a.style || '')
      case 'rating_desc': return (getNum(b, 'avg_rating') || 0) - (getNum(a, 'avg_rating') || 0)
      case 'rating_asc': return (getNum(a, 'avg_rating') || 0) - (getNum(b, 'avg_rating') || 0)
      case 'abv_desc': return (getNum(b, 'abv') || 0) - (getNum(a, 'abv') || 0)
      case 'abv_asc': return (getNum(a, 'abv') || 0) - (getNum(b, 'abv') || 0)
      case 'epm_desc': return (getNum(b, 'epm') || 0) - (getNum(a, 'epm') || 0)
      case 'epm_asc': return (getNum(a, 'epm') || 0) - (getNum(b, 'epm') || 0)
      case 'ibu_desc': return (getNum(b, 'ibu') || 0) - (getNum(a, 'ibu') || 0)
      case 'ibu_asc': return (getNum(a, 'ibu') || 0) - (getNum(b, 'ibu') || 0)
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
  return filteredAndSortedBeers.value.slice(start, start + itemsPerPage)
})

const openDetail = (beer) => { /* Detail emit */ }
</script>

<style scoped>
.catalog-header-layout { display: flex; flex-direction: column; gap: 0; }

.panel-card { background: var(--bg-panel); border: 1px solid var(--border); border-radius: 12px; margin-bottom: 1.5rem; position: relative; z-index: 20; }
.filters-header { display: flex; justify-content: space-between; align-items: center; padding: 1.25rem 1.5rem; cursor: pointer; user-select: none; }
.filters-title { display: flex; align-items: center; gap: 0.75rem; }
.filters-title h3 { margin: 0; font-size: 1.1rem; color: var(--text-main); }

.panel-icon { color: var(--primary); }

.toggle-icon { color: var(--text-muted); transition: transform 0.3s ease; }
.toggle-icon.rotated { transform: rotate(180deg); }
.filters-body { padding: 0 1.5rem 1.5rem 1.5rem; border-top: 1px solid var(--border); }
.filters-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-top: 1.5rem; }
.filters-footer { margin-top: 1.5rem; display: flex; justify-content: flex-end; }

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

.catalog-container { position: relative; min-height: 400px; display: flex; flex-direction: column; width: 100%; }
.beers-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }

.empty-state { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 4rem 1rem; text-align: center; color: var(--text-muted); }
.mt-2 { margin-top: 0.5rem; }

.slide-fade-enter-active { transition: all 0.3s ease-out; }
.slide-fade-leave-active { transition: all 0.2s cubic-bezier(1, 0.5, 0.8, 1); }
.slide-fade-enter-from, .slide-fade-leave-to { transform: translateY(-10px); opacity: 0; }

@media (max-width: 768px) {
  .mobile-action-bar { display: block; margin-bottom: 1.5rem; }
  .mobile-action-bar .btn-add { width: 100%; padding: 1rem; justify-content: center; font-size: 1.1rem; }
  .desktop-action-bar { display: none; }
  
  .results-bar { 
    flex-direction: column; /* Změněno z column-reverse na column */
    align-items: stretch; 
    gap: 1rem; 
    border-bottom: none;
  }
  .sort-control-wrapper { width: 100%; }
  .results-count { text-align: center; padding-top: 0.5rem; border-top: 1px solid var(--border); }
}
</style>