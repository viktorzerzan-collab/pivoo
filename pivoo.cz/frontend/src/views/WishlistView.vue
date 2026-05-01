<template>
  <div class="wishlist-page">
    <div class="view-toggle-container">
      <div class="view-toggle">
        <button 
          @click="activeTab = 'beers'" 
          :class="{ active: activeTab === 'beers' }"
        >
          <BeerIcon :size="18" /> Piva
        </button>
        <button 
          @click="activeTab = 'breweries'" 
          :class="{ active: activeTab === 'breweries' }"
        >
          <FactoryIcon :size="18" /> Pivovary
        </button>
        <button 
          @click="activeTab = 'locations'" 
          :class="{ active: activeTab === 'locations' }"
        >
          <MapPinIcon :size="18" /> Podniky
        </button>
      </div>
    </div>

    <BaseLoader :show="catalogStore.isLoading" />

    <div v-if="!catalogStore.isLoading" class="wishlist-content">
      
      <!-- PIVA -->
      <div v-if="activeTab === 'beers'" class="items-grid">
        <BeerCard 
          v-for="beer in wishlistBeers" 
          :key="beer.id" 
          :beer="beer" 
          @showDetail="openDetail(beer, 'beer')" 
        />
        <div v-if="wishlistBeers.length === 0" class="empty-state">
          Zatím zde nemáš žádná piva.
        </div>
      </div>

      <!-- PIVOVARY -->
      <div v-if="activeTab === 'breweries'" class="items-grid">
        <BreweryCard 
          v-for="brewery in wishlistBreweries" 
          :key="brewery.id" 
          :brewery="brewery" 
          @showDetail="openDetail(brewery, 'brewery')" 
        />
        <div v-if="wishlistBreweries.length === 0" class="empty-state">
          Zatím zde nemáš žádné pivovary.
        </div>
      </div>

      <!-- PODNIKY -->
      <div v-if="activeTab === 'locations'" class="items-grid">
        <LocationCard 
          v-for="location in wishlistLocations" 
          :key="location.id" 
          :location="location" 
          @showDetail="openDetail(location, 'location')" 
        />
        <div v-if="wishlistLocations.length === 0" class="empty-state">
          Zatím zde nemáš žádné podniky.
        </div>
      </div>

    </div>

    <DetailModal 
      :show="detailModal.show" 
      :item="detailModal.item" 
      :type="detailModal.type" 
      :reviews="detailModal.reviews"
      @close="detailModal.show = false" 
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { BeerIcon, FactoryIcon, MapPinIcon } from 'lucide-vue-next'
import { useCatalogStore } from '../stores/catalog'
import { apiFetch } from '../api'

import BeerCard from '../components/BeerCard.vue'
import BreweryCard from '../components/BreweryCard.vue'
import LocationCard from '../components/LocationCard.vue'
import DetailModal from '../components/modals/DetailModal.vue'
import BaseLoader from '../components/BaseLoader.vue'

const catalogStore = useCatalogStore()
const activeTab = ref('beers')

const wishlistBeers = computed(() => catalogStore.beers.filter(b => Number(b.is_wishlist) === 1))
const wishlistBreweries = computed(() => catalogStore.breweries.filter(b => Number(b.is_wishlist) === 1))
const wishlistLocations = computed(() => catalogStore.locations.filter(l => Number(l.is_wishlist) === 1))

const detailModal = ref({ show: false, item: null, type: '', reviews: [] })

const openDetail = async (item, type) => {
  detailModal.value.item = item
  detailModal.value.type = type
  detailModal.value.reviews = []
  
  if (type === 'beer') {
    try {
      const res = await apiFetch(`/beer_reviews.php?beer_id=${item.id}`)
      if (res.status === 'success') detailModal.value.reviews = res.data
    } catch (e) {
      console.error(e)
    }
  }
  
  detailModal.value.show = true
}

onMounted(async () => {
  if (catalogStore.beers.length === 0 || catalogStore.breweries.length === 0 || catalogStore.locations.length === 0) {
    await catalogStore.fetchAllData()
  }
})
</script>

<style scoped>
.wishlist-page { 
  display: flex; 
  flex-direction: column; 
}

/* KONZISTENTNÍ PŘEPÍNAČ DLE OBRÁZKU A STANDARDŮ APLIKACE */
.view-toggle-container {
  display: flex;
  margin-bottom: 2rem;
}

.view-toggle { 
  display: inline-flex; 
  background-color: var(--border); /* Reaguje na dark/light mode */
  padding: 0.375rem; 
  border-radius: 14px; 
  gap: 0.25rem; 
  transition: background-color 0.5s ease;
}

.view-toggle button { 
  display: flex; 
  align-items: center; 
  gap: 0.5rem; 
  padding: 0.6rem 1.25rem; 
  border: none; 
  background: transparent; 
  cursor: pointer; 
  border-radius: 10px; 
  font-weight: 700; 
  font-size: 0.9rem; 
  color: var(--text-muted); /* Standardní utlumený text[cite: 1] */
  transition: all 0.3s ease; 
  box-shadow: none;
}

.view-toggle button:hover:not(.active) { 
  color: var(--text-main); 
  background-color: rgba(255, 255, 255, 0.05);
}

.view-toggle button.active { 
  background-color: var(--primary); /* Pivní žlutá z obrázku[cite: 1] */
  color: #1e293b; /* Tmavý text na žlutém pozadí */
  box-shadow: var(--shadow-sm);
}

.wishlist-content {
  margin-top: 0.5rem;
}

.items-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 1.5rem;
}

.empty-state {
  grid-column: 1 / -1;
  text-align: center;
  padding: 4rem 2rem;
  background: var(--bg-panel);
  border: 1px dashed var(--border);
  border-radius: 12px;
  color: var(--text-muted);
  font-size: 1.1rem;
  font-style: italic;
}

@media (max-width: 768px) {
  .items-grid { grid-template-columns: 1fr; }
  .view-toggle { width: 100%; display: flex; }
  .view-toggle button { flex: 1; justify-content: center; padding: 0.7rem 0.5rem; font-size: 0.8rem; }
}
</style>