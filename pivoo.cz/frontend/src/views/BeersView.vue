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
import DetailModal from '../components/modals/DetailModal.vue'

const catalogStore = useCatalogStore()
const authStore = useAuthStore()

// Vytáhneme potřebná data včetně beersPagination pro stránkovač
const { beers, beersPagination, breweries, styles, countries, isLoading } = storeToRefs(catalogStore)

const currentPage = ref(1)
const itemsPerPage = 12
const sortBy = ref('name_asc')

const initialFilters = {
  search: '', brewery: '', style: '', country: '',
  epm: { min: '', max: '' }, abv: { min: '', max: '' }, ibu: { min: '', max: '' }
}
const filters = ref(JSON.parse(JSON.stringify(initialFilters)))

// Dynamicky získané hodnoty z backendového stránkovače
const totalPages = computed(() => beersPagination.value?.total_pages || 1)
const totalItems = computed(() => beersPagination.value?.total || 0)

// Funkce, která pošle parametry z filtrů do API
const loadBeers = async () => {
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
  await catalogStore.fetchBeers(params)
}

// Sledování změn (pokud změníme filtr, jdeme na stranu 1)
watch([filters, sortBy], () => {
  if (currentPage.value !== 1) {
    currentPage.value = 1 // Spustí watcher pro currentPage
  } else {
    loadBeers()
  }
}, { deep: true })

watch(currentPage, () => {
  loadBeers()
})

onMounted(async () => {
  // Stáhneme číselníky (pivovary, styly) pro formulář a filtry
  await catalogStore.fetchAllData()
  // Stáhneme paginovaná piva
  loadBeers()
})

const resetFilters = () => {
  filters.value = JSON.parse(JSON.stringify(initialFilters))
  sortBy.value = 'name_asc'
  if (currentPage.value !== 1) {
    currentPage.value = 1
  } else {
    loadBeers()
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

// --- Logika Modálů ---
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
      await loadBeers() // Refresh gridu po přidání piva
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
/* Původní styly zůstávají zachovány, zaručuje, že design nedozná škod */
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
    flex-direction: column; 
    align-items: stretch; 
    gap: 1rem; 
    border-bottom: none;
  }
  .sort-control-wrapper { width: 100%; }
  .results-count { text-align: center; padding-top: 0.5rem; border-top: 1px solid var(--border); }
}
</style>