<template>
  <div class="beers-page">
    <transition name="toast-fade">
      <div v-if="toast.show" class="toast-notification" :class="toast.type">{{ toast.message }}</div>
    </transition>

    <div class="view-header">
      <div class="header-top">
        <div class="title-group">
          <h2 class="section-title">Katalog piv</h2>
          <p class="auth-subtitle">Procházej a filtruj piva podle stylu, síly nebo stupňovitosti</p>
        </div>
        <BaseButton v-if="isAdmin" variant="add" @click="isAddBeerModalOpen = true">
          <template #icon><PlusIcon :size="18" /></template>
          Přidat pivo
        </BaseButton>
      </div>

      <div class="header-filters-row">
        <FilterInput v-model="searchQuery" placeholder="Hledat pivo nebo pivovar..." class="flex-2" />
        
        <FilterSelect v-model="sortBy" :icon="ArrowDownUpIcon" class="flex-1">
          <option value="name">Abecedně (A-Z)</option>
          <option value="rating">Nejlépe hodnocená</option>
          <option value="epm">Dle stupňovitosti</option>
          <option value="abv">Dle obsahu alkoholu</option>
        </FilterSelect>

        <FilterSelect v-model="filterEPM" :icon="ActivityIcon" class="flex-1">
          <option value="all">Všechny stupně</option>
          <option value="10">Desítky (10°)</option>
          <option value="11">Jedenáctky (11°)</option>
          <option value="12">Dvanáctky (12°)</option>
          <option value="13">Silná (13°+)</option>
        </FilterSelect>

        <FilterSelect v-model="filterABV" :icon="PercentIcon" class="flex-1">
          <option value="all">Veškerý alkohol</option>
          <option value="light">Lehká (do 4%)</option>
          <option value="normal">Standard (4-6%)</option>
          <option value="strong">Silná (nad 6%)</option>
        </FilterSelect>
      </div>
    </div>

    <div v-if="isLoading" class="loading-state">Točíme piva... ⏳</div>
    
    <div v-else>
      <div class="beers-grid" v-if="filteredBeers && filteredBeers.length > 0">
        <BeerCard 
          v-for="beer in filteredBeers" 
          :key="beer.id" 
          :beer="beer" 
          @showDetail="openBeerDetail" 
        />
      </div>
      
      <div v-else class="empty-state">
        <BeerIcon :size="48" color="#cbd5e1" stroke-width="1" />
        <h3>Podmínkám neodpovídá žádné pivo</h3>
        <p>Zkus zvolit mírnější filtry nebo upravit vyhledávání.</p>
      </div>
    </div>

    <AddBeerModal 
      v-if="isAdmin" 
      :show="isAddBeerModalOpen" 
      :breweries="breweries" 
      :styles="styles" 
      :form="newBeerForm" 
      @close="isAddBeerModalOpen = false" 
      @submit="submitNewBeer" 
    />
    
    <DetailModal 
      :show="isDetailModalOpen" 
      :item="selectedBeer" 
      :reviews="beerReviews" 
      type="beer" 
      @close="isDetailModalOpen = false" 
    />
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { storeToRefs } from 'pinia'
import { PlusIcon, BeerIcon, ActivityIcon, PercentIcon, ArrowDownUpIcon } from 'lucide-vue-next'

import { useAuthStore } from '../stores/auth'
import { useCatalogStore } from '../stores/catalog'
import BaseButton from '../components/BaseButton.vue'
import FilterInput from '../components/FilterInput.vue'
import FilterSelect from '../components/FilterSelect.vue'
import BeerCard from '../components/BeerCard.vue'
import AddBeerModal from '../components/modals/AddBeerModal.vue'
import DetailModal from '../components/modals/DetailModal.vue'

const authStore = useAuthStore()
const catalogStore = useCatalogStore()
const { user } = storeToRefs(authStore)
const { beers, breweries, styles, isLoading } = storeToRefs(catalogStore)
const isAdmin = computed(() => user.value?.role === 'admin')

const toast = ref({ show: false, message: '', type: 'toast-success' })
const showToast = (message, type = 'toast-success') => { 
  toast.value = { show: true, message, type }; setTimeout(() => { toast.value.show = false }, 3000) 
}

const searchQuery = ref(''); const sortBy = ref('name'); const filterEPM = ref('all'); const filterABV = ref('all')
const isAddBeerModalOpen = ref(false); const isDetailModalOpen = ref(false); const selectedBeer = ref(null); const beerReviews = ref([])
const newBeerForm = ref({ name: '', brewery_id: '', style: '', epm: '', abv: '' })

const filteredBeers = computed(() => {
  let result = beers.value || []
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    result = result.filter(beer => beer.name.toLowerCase().includes(query) || beer.brewery_name.toLowerCase().includes(query))
  }
  if (filterEPM.value !== 'all') {
    const target = parseInt(filterEPM.value)
    if (target === 13) result = result.filter(b => parseFloat(b.epm) >= 13)
    else result = result.filter(b => Math.floor(parseFloat(b.epm)) === target)
  }
  if (filterABV.value !== 'all') {
    if (filterABV.value === 'light') result = result.filter(b => parseFloat(b.abv) < 4)
    if (filterABV.value === 'normal') result = result.filter(b => parseFloat(b.abv) >= 4 && parseFloat(b.abv) <= 6)
    if (filterABV.value === 'strong') result = result.filter(b => parseFloat(b.abv) > 6)
  }
  return result.slice().sort((a, b) => {
    if (sortBy.value === 'name') return a.name.localeCompare(b.name)
    if (sortBy.value === 'rating') return (parseFloat(b.avg_rating) || 0) - (parseFloat(a.avg_rating) || 0)
    if (sortBy.value === 'epm') return (parseFloat(b.epm) || 0) - (parseFloat(a.epm) || 0)
    if (sortBy.value === 'abv') return (parseFloat(b.abv) || 0) - (parseFloat(a.abv) || 0)
    return 0
  })
})

const openBeerDetail = async (beer) => {
  selectedBeer.value = beer; isDetailModalOpen.value = true; beerReviews.value = []
  try {
    const res = await fetch(`https://www.pivoo.cz/backend/api/beer_reviews.php?beer_id=${beer.id}`)
    const data = await res.json()
    if (data.status === 'success') beerReviews.value = data.data
  } catch (error) { showToast("Chyba při stahování recenzí.", "toast-error") }
}

const submitNewBeer = async () => {
  if (!isAdmin.value) return
  try {
    const res = await fetch('https://www.pivoo.cz/backend/api/add_beer.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(newBeerForm.value) })
    if (res.ok) { isAddBeerModalOpen.value = false; newBeerForm.value = { name: '', brewery_id: '', style: '', epm: '', abv: '' }; await catalogStore.fetchAllData(user.value.id); showToast("Pivo přidáno do katalogu") }
  } catch (error) { showToast('Chyba připojení.', "toast-error") }
}

onMounted(() => { if (user.value) catalogStore.fetchAllData(user.value.id) })
</script>

<style scoped>
.view-header { display: flex; flex-direction: column; gap: 0.75rem; margin-bottom: 2rem; }
.header-top { display: flex; justify-content: space-between; align-items: center; gap: 1rem; }
.section-title { margin: 0; }
.auth-subtitle { margin: 0.15rem 0 0; color: #64748b; }
.header-filters-row { display: flex; flex-wrap: wrap; gap: 1rem; margin-top: 0.25rem; align-items: center; }
.flex-2 { flex: 2; min-width: 280px; }
.flex-1 { flex: 1; min-width: 160px; }
.beers-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.5rem; }
.empty-state { text-align: center; padding: 4rem 2rem; background: var(--bg-panel); border-radius: 12px; border: 1px dashed var(--border); display: flex; flex-direction: column; align-items: center; gap: 1rem; }
@media (max-width: 800px) { .header-top { flex-direction: column; align-items: stretch; gap: 1.25rem; } .header-filters-row { flex-direction: column; } .flex-2, .flex-1 { width: 100%; } }
</style>