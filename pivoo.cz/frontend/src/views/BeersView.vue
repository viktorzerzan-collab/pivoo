<template>
  <div class="beers-page">
    <div class="view-header">
      <div class="header-top">
        <div class="title-group">
          <h2 class="section-title">Katalog piv</h2>
          <p class="auth-subtitle">Procházej a filtruj piva</p>
        </div>
        <button v-if="isAdmin" class="btn-add" @click="isAddBeerModalOpen = true">
          <PlusIcon /> Přidat pivo
        </button>
      </div>
      <div class="header-filters-row">
        <FilterInput v-model="searchQuery" placeholder="Hledat pivo..." class="flex-2" />
        <FilterSelect v-model="sortBy" :icon="ArrowDownUpIcon" class="flex-1">
          <option value="name">Abecedně (A-Z)</option>
          <option value="rating">Nejlépe hodnocená</option>
        </FilterSelect>
      </div>
    </div>

    <div class="catalog-container">
      <BaseLoader :show="isLoading" />
      <div class="beers-grid" v-if="filteredBeers.length > 0">
        <BeerCard v-for="beer in filteredBeers" :key="beer.id" :beer="beer" @showDetail="openBeerDetail" />
      </div>
      
      <div v-else-if="!isLoading" class="empty-state">
        <BeerIcon :size="48" color="#cbd5e1" />
        <h3>Žádná piva k zobrazení</h3>
      </div>
    </div>

    <AddBeerModal v-if="isAdmin" :show="isAddBeerModalOpen" :isEditing="false" :breweries="breweries" :styles="styles" :form="newBeerForm" @close="isAddBeerModalOpen = false" @submit="submitNewBeer" />
    <DetailModal :show="isDetailModalOpen" :item="selectedBeer" :reviews="beerReviews" type="beer" @close="isDetailModalOpen = false" />
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { storeToRefs } from 'pinia'
import { PlusIcon, ArrowDownUpIcon, BeerIcon } from 'lucide-vue-next'
import { apiFetch } from '../api'
import { useAuthStore } from '../stores/auth'
import { useCatalogStore } from '../stores/catalog'
import BaseLoader from '../components/BaseLoader.vue'
import FilterInput from '../components/FilterInput.vue'
import FilterSelect from '../components/FilterSelect.vue'
import BeerCard from '../components/BeerCard.vue'
import AddBeerModal from '../components/modals/AddBeerModal.vue'
import DetailModal from '../components/modals/DetailModal.vue'

const authStore = useAuthStore(); const catalogStore = useCatalogStore()
const { user } = storeToRefs(authStore); const { beers, breweries, styles, isLoading } = storeToRefs(catalogStore)
const isAdmin = computed(() => user.value?.role === 'admin')
const searchQuery = ref(''); const sortBy = ref('name'); const isAddBeerModalOpen = ref(false); const isDetailModalOpen = ref(false); const selectedBeer = ref(null); const beerReviews = ref([])

// Aktualizovaný model pro nové pivo se všemi komplexními parametry
const newBeerForm = ref({ 
  name: '', brewery_id: '', style: '', epm: '', abv: '', 
  ibu: '', ebc: '', hops: '', malts: '', fermentation: '', tags: '',
  is_unfiltered: false, is_unpasteurized: false 
})

const filteredBeers = computed(() => {
  let result = beers.value || []
  if (searchQuery.value) result = result.filter(b => b.name.toLowerCase().includes(searchQuery.value.toLowerCase()))
  return result.slice().sort((a, b) => sortBy.value === 'name' ? a.name.localeCompare(b.name) : (parseFloat(b.avg_rating) || 0) - (parseFloat(a.avg_rating) || 0))
})

const openBeerDetail = async (beer) => {
  selectedBeer.value = beer; isDetailModalOpen.value = true;
  const res = await apiFetch(`/beer_reviews.php?beer_id=${beer.id}`); if (res.status === 'success') beerReviews.value = res.data
}

const submitNewBeer = async () => {
  const res = await apiFetch('/add_beer.php', { method: 'POST', body: JSON.stringify(newBeerForm.value) })
  if (res.status === 'success') { 
    isAddBeerModalOpen.value = false; 
    // Reset formuláře po úspěšném odeslání
    newBeerForm.value = { 
      name: '', brewery_id: '', style: '', epm: '', abv: '', 
      ibu: '', ebc: '', hops: '', malts: '', fermentation: '', tags: '',
      is_unfiltered: false, is_unpasteurized: false 
    }
    await catalogStore.fetchAllData() 
  }
}
onMounted(() => { if (user.value) catalogStore.fetchAllData() })
</script>

<style scoped>
.catalog-container { position: relative; min-height: 400px; }
.view-header { display: flex; flex-direction: column; gap: 1rem; margin-bottom: 2rem; }
.header-top { display: flex; justify-content: space-between; align-items: center; }
.header-filters-row { display: flex; gap: 1rem; width: 60%; }
.beers-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem; }

.empty-state { text-align: center; padding: 4rem; display: flex; flex-direction: column; align-items: center; gap: 1rem; background: var(--bg-panel); border-radius: 12px; border: 1px dashed var(--border); transition: background-color 0.5s ease, border-color 0.5s ease; }
.empty-state h3 { color: var(--text-main); transition: color 0.5s ease; }

@media (max-width: 800px) {
  .header-top { flex-direction: column; align-items: flex-start; gap: 1rem; }
  .header-top .btn-add { width: 100%; padding: 1rem; font-size: 1.05rem; } /* VZDUŠNĚJŠÍ TLAČÍTKO */
  .header-filters-row { width: 100%; flex-direction: column; }
}
</style>