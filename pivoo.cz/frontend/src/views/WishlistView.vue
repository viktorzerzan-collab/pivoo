<template>
  <div class="wishlist-page">
    <div class="view-toggle-container">
      <BaseSwitch v-model="activeTab" :options="tabOptions" />
    </div>

    <BaseLoader :show="catalogStore.isLoading" />

    <div v-if="!catalogStore.isLoading" class="wishlist-content">
      
      <div v-if="activeTab === 'beers'" class="items-grid">
        <BeerCard 
          v-for="beer in wishlistBeers" 
          :key="beer.id" 
          :beer="beer" 
          @showDetail="openDetail(beer, 'beer')" 
        />
        <BaseEmptyState 
          v-if="wishlistBeers.length === 0" 
          :text="$t('views.wishlist.no_beers')" 
          :icon="BeerIcon"
        />
      </div>

      <div v-if="activeTab === 'breweries'" class="items-grid">
        <BreweryCard 
          v-for="brewery in wishlistBreweries" 
          :key="brewery.id" 
          :brewery="brewery" 
          @showDetail="openDetail(brewery, 'brewery')" 
        />
        <BaseEmptyState 
          v-if="wishlistBreweries.length === 0" 
          :text="$t('views.wishlist.no_breweries')" 
          :icon="FactoryIcon"
        />
      </div>

      <div v-if="activeTab === 'locations'" class="items-grid">
        <LocationCard 
          v-for="location in wishlistLocations" 
          :key="location.id" 
          :location="location" 
          @showDetail="openDetail(location, 'location')" 
        />
        <BaseEmptyState 
          v-if="wishlistLocations.length === 0" 
          :text="$t('views.wishlist.no_locations')" 
          :icon="MapPinIcon"
        />
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
import { useI18n } from 'vue-i18n'
import { useCatalogStore } from '../stores/catalog'
import { apiFetch } from '../api'

import BeerCard from '../components/BeerCard.vue'
import BreweryCard from '../components/BreweryCard.vue'
import LocationCard from '../components/LocationCard.vue'
import DetailModal from '../components/modals/DetailModal.vue'
import BaseLoader from '../components/BaseLoader.vue'
import BaseSwitch from '../components/BaseSwitch.vue'
import BaseEmptyState from '../components/BaseEmptyState.vue'

const catalogStore = useCatalogStore()
const { t } = useI18n()
const activeTab = ref('beers')

const tabOptions = computed(() => [
  { value: 'beers', label: t('nav.beers'), icon: BeerIcon },
  { value: 'breweries', label: t('nav.breweries'), icon: FactoryIcon },
  { value: 'locations', label: t('nav.locations'), icon: MapPinIcon }
])

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

.view-toggle-container {
  display: flex;
  margin-bottom: 2rem;
}

.wishlist-content {
  margin-top: 0.5rem;
}

.items-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 1.5rem;
}

@media (max-width: 768px) {
  .items-grid { grid-template-columns: 1fr; }
}
</style>