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
    </div>

    <AddBeerModal v-if="isAdmin" :show="isAddBeerModalOpen" :breweries="breweries" :styles="styles" :form="newBeerForm" @close="isAddBeerModalOpen = false" @submit="submitNewBeer" />
    <DetailModal :show="isDetailModalOpen" :item="selectedBeer" :reviews="beerReviews" type="beer" @close="isDetailModalOpen = false" />
  </div>
</template>

<script setup>
// ... (Zbytek scriptu zůstává stejný jako v předchozí verzi)
import { ref, onMounted, computed } from 'vue'
import { storeToRefs } from 'pinia'
import { PlusIcon, ArrowDownUpIcon } from 'lucide-vue-next'
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
const newBeerForm = ref({ name: '', brewery_id: '', style: '', epm: '', abv: '' })

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
  const res = await apiFetch('/add_beer.php', { method: 'POST', body: newBeerForm.value })
  if (res.status === 'success') { isAddBeerModalOpen.value = false; await catalogStore.fetchAllData() }
}
onMounted(() => { if (user.value) catalogStore.fetchAllData() })
</script>

<style scoped>
.catalog-container { position: relative; min-height: 400px; }
.view-header { display: flex; flex-direction: column; gap: 1rem; margin-bottom: 2rem; }
.header-top { display: flex; justify-content: space-between; align-items: center; }
.header-filters-row { display: flex; gap: 1rem; width: 60%; }
.beers-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem; }
</style>