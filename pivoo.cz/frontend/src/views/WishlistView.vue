<template>
  <BaseCatalogLayout
    :is-loading="isLoading"
    :show-filters="false"
    :show-results-bar="false"
    :has-items="currentTabCount > 0"
    :empty-text="dynamicEmptyText"
    :empty-icon="dynamicEmptyIcon"
  >
    <template #header-top>
      <BaseSwitch v-model="viewMode" :options="viewModeOptions" />
    </template>

    <div class="items-grid">
      <template v-if="viewMode === 'beers'">
        <CatalogCard 
          v-for="beer in wishlistBeers" 
          :key="'beer-' + beer.id" 
          :item="beer" 
          type="beer"
          @showDetail="openDetail('beer', $event)"
        />
      </template>

      <template v-else-if="viewMode === 'breweries'">
        <CatalogCard 
          v-for="brewery in wishlistBreweries" 
          :key="'brewery-' + brewery.id" 
          :item="brewery" 
          type="brewery"
          @showDetail="openDetail('brewery', $event)"
        />
      </template>

      <template v-else-if="viewMode === 'locations'">
        <CatalogCard 
          v-for="loc in wishlistLocations" 
          :key="'location-' + loc.id" 
          :item="loc" 
          type="location"
          @showDetail="openDetail('location', $event)"
        />
      </template>
    </div>

    <template #modals>
      <DetailModal 
        :show="isDetailOpen" 
        :item="selectedItem" 
        :type="detailType" 
        :reviews="itemReviews"
        @close="isDetailOpen = false" 
      />
    </template>
  </BaseCatalogLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { storeToRefs } from 'pinia'
import { useI18n } from 'vue-i18n'
import { BookmarkIcon, BeerIcon, FactoryIcon, MapIcon } from 'lucide-vue-next'
import { apiFetch } from '../api'
import { useCatalogStore } from '../stores/catalog'

import BaseCatalogLayout from '../components/BaseCatalogLayout.vue'
import CatalogCard from '../components/CatalogCard.vue'
import DetailModal from '../components/modals/DetailModal.vue'
import BaseSwitch from '../components/BaseSwitch.vue'

const catalogStore = useCatalogStore()
const { t } = useI18n()

const { beers, breweries, locations, isLoading } = storeToRefs(catalogStore)

const wishlistBeers = computed(() => beers.value.filter(b => b.is_wishlist))
const wishlistBreweries = computed(() => breweries.value.filter(b => b.is_wishlist))
const wishlistLocations = computed(() => locations.value.filter(l => l.is_wishlist))

// Logika pro přepínač
const viewMode = ref('beers')
const viewModeOptions = computed(() => [
  { value: 'beers', label: 'Piva', icon: BeerIcon },
  { value: 'breweries', label: 'Pivovary', icon: FactoryIcon },
  { value: 'locations', label: 'Místa', icon: MapIcon }
])

const currentTabCount = computed(() => {
  if (viewMode.value === 'beers') return wishlistBeers.value.length
  if (viewMode.value === 'breweries') return wishlistBreweries.value.length
  return wishlistLocations.value.length
})

// Dynamická data pro prázdný stav podle tabu
const dynamicEmptyText = computed(() => {
  if (viewMode.value === 'beers') return t('catalog.empty_beers')
  if (viewMode.value === 'breweries') return t('catalog.empty_breweries')
  return t('catalog.empty_locations')
})

const dynamicEmptyIcon = computed(() => {
  if (viewMode.value === 'beers') return BeerIcon
  if (viewMode.value === 'breweries') return FactoryIcon
  return MapIcon
})

const isDetailOpen = ref(false)
const selectedItem = ref(null)
const detailType = ref('beer')
const itemReviews = ref([])

const openDetail = async (type, item) => {
  selectedItem.value = item
  detailType.value = type
  isDetailOpen.value = true
  itemReviews.value = []

  if (type === 'beer') {
    try {
      const res = await apiFetch(`/beer_reviews.php?beer_id=${item.id}`)
      if (res.status === 'success') itemReviews.value = res.data
    } catch (error) {
      console.error("Chyba při načítání recenzí", error)
    }
  }
}

onMounted(async () => {
  await catalogStore.fetchAllData()
})
</script>

<style scoped>
.items-grid {
  display: grid;
  /* ZMĚNĚNO NA AUTO-FILL: Zabrání roztažení jedné karty přes celou šířku */
  grid-template-columns: repeat(auto-fill, minmax(max(300px, calc((100% - 1.5rem) / 2)), 1fr));
  gap: 1.5rem;
  margin-bottom: 3rem;
}
</style>