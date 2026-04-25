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
              
              <FilterInput v-model="filters.brewery" label="Pivovar" placeholder="Např. Prazdroj, Bernard..." />

              <FilterInput v-model="filters.country" label="Země původu" placeholder="Např. Česko, Německo..." />

              <BaseSelect v-model="filters.style" label="Pivní styl" placeholder="Všechny styly" searchable>
                <option value="">Všechny styly</option>
                <option v-for="s in styles" :key="s.id" :value="s.id">{{ s.name }}</option>
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

      <div v-if="activeFilters.length > 0" class="active-filters-chips">
        <span class="chips-label">Aktivní filtry:</span>
        <div class="chips-container">
          <button 
            v-for="chip in activeFilters" 
            :key="chip.id" 
            class="filter-chip"
            @click="removeFilter(chip)"
            title="Zrušit filtr"
          >
            {{ chip.label }}
            <XIcon :size="14" />
          </button>
        </div>
      </div>

      <div class="results-bar">
        <div class="sort-control-wrapper">
          <BaseSelect v-model="sortBy" placeholder="Řadit podle..." :searchable="false">
            <option v-for="opt in sortOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
          </BaseSelect>
        </div>

        <span class="results-count">Nalezeno piv: <strong>{{ totalItems }}</strong></span>
        
        <div class="desktop-action-bar">
          <button v-if="authStore.user" class="btn-add" @click="openAddModal">
            <PlusCircleIcon :size="20" /> Přidat pivo
          </button>
        </div>
      </div>
    </div>

    <div class="catalog-container">
      <template v-if="beers.length > 0">
        <div class="beers-grid">
          <BeerCard 
            v-for="beer in beers" 
            :key="beer.id" 
            :beer="beer" 
            @showDetail="openDetail"
          />
        </div>
        
        <div class="desktop-pagination">
          <BasePagination 
            v-if="totalPages > 1"
            v-model:currentPage="currentPage" 
            :total-pages="totalPages"
          />
        </div>

        <div ref="loadMoreTrigger" class="load-more-trigger">
          <div v-if="isAppending" class="mobile-loader">
            Načítám další piva...
          </div>
        </div>
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

    <DetailModal 
      :show="isDetailOpen" 
      :item="selectedItem" 
      type="beer" 
      :reviews="beerReviews"
      @close="isDetailOpen = false" 
    />

    <transition name="toast-fade">
      <div v-if="toast.show" class="toast-notification" :class="toast.type">{{ toast.message }}</div>
    </transition>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { apiFetch } from '../api'
import { useCatalogStore } from '../stores/catalog'
import { useAuthStore } from '../stores/auth'
import { PlusCircleIcon, FilterIcon, ChevronDownIcon, BeerIcon, XIcon } from 'lucide-vue-next'

import FilterInput from '../components/FilterInput.vue'
import FilterRange from '../components/FilterRange.vue'
import BaseSelect from '../components/BaseSelect.vue'
import BeerCard from '../components/BeerCard.vue'
import BaseLoader from '../components/BaseLoader.vue'
import BasePagination from '../components/BasePagination.vue'
import AddBeerModal from '../components/modals/AddBeerModal.vue'
import DetailModal from '../components/modals/DetailModal.vue'

const catalogStore = useCatalogStore()
const authStore = useAuthStore()

const { beers, beersPagination, breweries, styles, countries, isLoading } = storeToRefs(catalogStore)

const currentPage = ref(1)
const itemsPerPage = 30
const sortBy = ref('name_asc')

const initialFilters = {
  search: '', brewery: '', style: '', country: '',
  epm: { min: '', max: '' }, abv: { min: '', max: '' }, ibu: { min: '', max: '' }
}
const filters = ref(JSON.parse(JSON.stringify(initialFilters)))

const totalPages = computed(() => beersPagination.value?.total_pages || 1)
const totalItems = computed(() => beersPagination.value?.total || 0)

const activeFilters = computed(() => {
  const active = []
  
  const addMultiChips = (value, key, labelPrefix) => {
    if (value) {
       const parts = String(value).split(',').map(s => s.trim()).filter(s => s)
       parts.forEach(part => {
         active.push({ id: `${key}|${part}`, realKey: key, partValue: part, label: `${labelPrefix}: ${part}` })
       })
    }
  }

  addMultiChips(filters.value.search, 'search', 'Hledání')
  addMultiChips(filters.value.brewery, 'brewery', 'Pivovar')
  addMultiChips(filters.value.country, 'country', 'Země')
  
  if (filters.value.style) {
    const s = styles.value.find(x => x.id == filters.value.style)
    if (s) {
      active.push({ id: 'style', realKey: 'style', label: `Styl: ${s.name}` })
    }
  }

  const ranges = ['epm', 'abv', 'ibu'];
  ranges.forEach(key => {
    const min = filters.value[key].min
    const max = filters.value[key].max
    if (min !== '' || max !== '') {
      active.push({ 
        id: key, 
        realKey: 'range',
        rangeKey: key,
        label: `${key.toUpperCase()}: ${min !== '' ? min : '0'} - ${max !== '' ? max : '∞'}` 
      })
    }
  })
  
  return active
})

const removeFilter = (chip) => {
  if (chip.realKey === 'range') {
    filters.value[chip.rangeKey] = { min: '', max: '' }
  } else if (chip.partValue) {
    let parts = String(filters.value[chip.realKey]).split(',').map(s => s.trim()).filter(s => s)
    parts = parts.filter(p => p !== chip.partValue)
    filters.value[chip.realKey] = parts.join(', ')
  } else {
    filters.value[chip.realKey] = ''
  }
}

const loadMoreTrigger = ref(null)
const isAppending = ref(false)
let observer = null

const loadBeers = async (append = false) => {
  const params = {
    page: currentPage.value,
    limit: itemsPerPage,
    search: filters.value.search,
    brewery: filters.value.brewery,
    style: filters.value.style,
    country: filters.value.country,
    epm_min: filters.value.epm.min,
    epm_max: filters.value.epm.max,
    abv_min: filters.value.abv.min,
    abv_max: filters.value.abv.max,
    ibu_min: filters.value.ibu.min,
    ibu_max: filters.value.ibu.max,
    sort: sortBy.value
  }
  await catalogStore.fetchBeers(params, append)
}

const loadNextPage = async () => {
  if (currentPage.value < totalPages.value && !isLoading.value && !isAppending.value) {
    isAppending.value = true
    currentPage.value++
    await loadBeers(true)
    isAppending.value = false
  }
}

watch(loadMoreTrigger, (el) => {
  if (observer) observer.disconnect()
  if (el) {
    observer = new IntersectionObserver((entries) => {
      if (entries[0].isIntersecting && window.innerWidth <= 768) {
        loadNextPage()
      }
    }, { rootMargin: '200px' })
    observer.observe(el)
  }
})

onUnmounted(() => {
  if (observer) observer.disconnect()
})

watch([filters, sortBy], () => {
  if (currentPage.value !== 1) {
    isAppending.value = false 
    currentPage.value = 1
  } else {
    loadBeers(false)
  }
}, { deep: true })

watch(currentPage, () => {
  if (!isAppending.value) {
    loadBeers(false)
  }
})

onMounted(async () => {
  await catalogStore.fetchAllData()
  loadBeers(false)
})

const resetFilters = () => {
  filters.value = JSON.parse(JSON.stringify(initialFilters))
  sortBy.value = 'name_asc'
  isAppending.value = false
  if (currentPage.value !== 1) {
    currentPage.value = 1
  } else {
    loadBeers(false)
  }
}

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

const isAddModalOpen = ref(false)
const filtersOpen = ref(false)
const toast = ref({ show: false, message: '', type: 'toast-success' })

const isDetailOpen = ref(false)
const selectedItem = ref(null)
const beerReviews = ref([])

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
      isAppending.value = false
      currentPage.value = 1
      await loadBeers(false) 
      showToast("Pivo bylo úspěšně přidáno") 
    } else {
      showToast(res.message || "Chyba při ukládání", "toast-error")
    }
  } catch (e) { 
    showToast('Chyba serveru.', 'toast-error') 
  }
}

const openDetail = async (beer) => {
  selectedItem.value = beer
  isDetailOpen.value = true
  beerReviews.value = [] 
  
  try {
    const res = await apiFetch(`/beer_reviews.php?beer_id=${beer.id}`)
    if (res.status === 'success') {
      beerReviews.value = res.data
    }
  } catch (error) {
    console.error("Chyba při načítání recenzí piva", error)
  }
}
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

.active-filters-chips {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.75rem;
  margin-bottom: 1.5rem;
  padding: 0 0.5rem;
}
.chips-label {
  font-size: 0.85rem;
  font-weight: 700;
  color: var(--text-muted);
  text-transform: uppercase;
}
.chips-container {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}
.filter-chip {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  background-color: var(--primary);
  color: #1e293b;
  border: none;
  padding: 0.3rem 0.8rem;
  border-radius: 99px;
  font-size: 0.8rem;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s ease;
}
.filter-chip:hover {
  background-color: var(--primary-hover);
  transform: scale(1.05);
}

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
/* OPRAVA: Sjednocení minmax na 320px pro konzistentní počet sloupců */
.beers-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }

.empty-state { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 4rem 1rem; text-align: center; color: var(--text-muted); }
.mt-2 { margin-top: 0.5rem; }

.slide-fade-enter-active { transition: all 0.3s ease-out; }
.slide-fade-leave-active { transition: all 0.2s cubic-bezier(1, 0.5, 0.8, 1); }
.slide-fade-enter-from, .slide-fade-leave-to { transform: translateY(-10px); opacity: 0; }

.desktop-pagination { display: block; }
.load-more-trigger { height: 20px; width: 100%; }
.mobile-loader { display: none; text-align: center; padding: 1rem; color: var(--text-muted); font-weight: 600; font-size: 0.9rem; }

@media (max-width: 768px) {
  .mobile-action-bar { display: block; margin-bottom: 1.5rem; }
  .mobile-action-bar .btn-add { width: 100%; padding: 1rem; justify-content: center; font-size: 1.1rem; }
  .desktop-action-bar { display: none; }
  
  .results-bar { 
    flex-direction: column; 
    align-items: stretch; 
    gap: 1rem; 
    border-bottom: none;
  }
  .sort-control-wrapper { width: 100%; }
  .results-count { text-align: center; padding-top: 0.5rem; border-top: 1px solid var(--border); }

  .desktop-pagination { display: none; }
  .mobile-loader { display: block; }
}
</style>