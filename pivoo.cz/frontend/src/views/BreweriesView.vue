<template>
  <div class="breweries-page">
    <transition name="toast-fade">
      <div v-if="toast.show" class="toast-notification" :class="toast.type">{{ toast.message }}</div>
    </transition>

    <div class="view-header">
      <div class="header-actions">
        <div class="header-filters-row">
          <FilterInput v-model="searchQuery" placeholder="Hledat pivovar..." class="flex-2" />
          
          <div class="view-toggle-group">
            <button 
              class="toggle-btn" 
              :class="{ active: !isMapView }" 
              @click="isMapView = false"
              title="Zobrazit seznam"
            >
              <LayoutGridIcon :size="20" />
            </button>
            <button 
              class="toggle-btn" 
              :class="{ active: isMapView }" 
              @click="isMapView = true"
              title="Zobrazit mapu"
            >
              <MapIcon :size="20" />
            </button>
          </div>

          <FilterSelect v-model="sortBy" :icon="ArrowDownUpIcon" class="flex-1" v-show="!isMapView">
            <option value="name">Abecedně (A-Z)</option>
            <option value="rating">Dle hodnocení</option>
          </FilterSelect>
        </div>
        <button v-if="isAdmin" class="btn-add" @click="isAddModalOpen = true">
          <PlusIcon /> Přidat pivovar
        </button>
      </div>
    </div>

    <div class="catalog-container">
      <BaseLoader :show="isLoading" />

      <div v-if="isMapView" class="map-wrapper">
        <MapView 
          :items="filteredBreweries" 
          @showDetail="openDetail" 
        />
      </div>

      <div v-else-if="!isMapView && filteredBreweries.length > 0" class="list-wrapper">
        <div class="breweries-grid">
          <BreweryCard 
            v-for="brewery in paginatedBreweries" 
            :key="brewery.id" 
            :brewery="brewery" 
            @showDetail="openDetail" 
          />
        </div>
        <BasePagination v-model:currentPage="currentPage" :totalPages="totalPages" />
      </div>
      
      <div v-else-if="!isLoading && filteredBreweries.length === 0" class="empty-state">
        <FactoryIcon :size="48" color="#cbd5e1" />
        <h3>Žádné pivovary k zobrazení</h3>
      </div>
    </div>

    <DetailModal :show="isDetailOpen" :item="selectedItem" type="brewery" @close="isDetailOpen = false" />
    <AddBreweryModal :show="isAddModalOpen" :isEditing="false" :countries="countries" :form="form" @close="isAddModalOpen = false" @submit="submitBrewery" />
  </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { PlusIcon, FactoryIcon, ArrowDownUpIcon, MapIcon, LayoutGridIcon } from 'lucide-vue-next'

import { apiFetch } from '../api'
import { useAuthStore } from '../stores/auth'
import { useCatalogStore } from '../stores/catalog'

import BaseLoader from '../components/BaseLoader.vue'
import FilterInput from '../components/FilterInput.vue'
import FilterSelect from '../components/FilterSelect.vue'
import BreweryCard from '../components/BreweryCard.vue'
import DetailModal from '../components/modals/DetailModal.vue'
import AddBreweryModal from '../components/modals/AddBreweryModal.vue'
import MapView from '../components/MapView.vue'
import BasePagination from '../components/BasePagination.vue'

const authStore = useAuthStore()
const catalogStore = useCatalogStore()
const { user } = storeToRefs(authStore)
const { breweries, countries, isLoading } = storeToRefs(catalogStore)
const isAdmin = computed(() => user.value?.role === 'admin')

const isMapView = ref(false) // Stav přepínače
const toast = ref({ show: false, message: '', type: 'toast-success' })
const searchQuery = ref('')
const sortBy = ref('name')
const isAddModalOpen = ref(false)
const isDetailOpen = ref(false)
const selectedItem = ref(null)

const form = ref({ 
  name: '', city: '', zip_code: '', country_id: 1, 
  address: '', email: '', phone: '', website: '', logoFile: null 
})

const currentPage = ref(1)
const itemsPerPage = 30

const openDetail = (item) => { 
  selectedItem.value = item
  isDetailOpen.value = true 
}

const filteredBreweries = computed(() => {
  let result = breweries.value || []
  if (searchQuery.value) {
    result = result.filter(b => b.name.toLowerCase().includes(searchQuery.value.toLowerCase()))
  }
  return result.slice().sort((a, b) => 
    sortBy.value === 'name' 
      ? a.name.localeCompare(b.name) 
      : (parseFloat(b.avg_rating) || 0) - (parseFloat(a.avg_rating) || 0)
  )
})

const totalPages = computed(() => Math.ceil(filteredBreweries.value.length / itemsPerPage))

const paginatedBreweries = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage
  return filteredBreweries.value.slice(start, start + itemsPerPage)
})

const showToast = (message, type = 'toast-success') => { 
  toast.value = { show: true, message, type }
  setTimeout(() => { toast.value.show = false }, 3000) 
}

const submitBrewery = async () => {
  try {
    const formData = new FormData()
    Object.keys(form.value).forEach(key => {
      if (form.value[key] !== null && form.value[key] !== '') {
        formData.append(key, form.value[key])
      }
    })

    const result = await apiFetch('/add_brewery.php', { method: 'POST', body: formData })
    if (result.status === 'success') { 
      isAddModalOpen.value = false
      form.value = { name: '', city: '', zip_code: '', country_id: 1, address: '', email: '', phone: '', website: '', logoFile: null }
      await catalogStore.fetchAllData()
      showToast("Pivovar přidán") 
    }
  } catch (e) { showToast('Chyba serveru.', 'toast-error') }
}

onMounted(() => { if (user.value) catalogStore.fetchAllData() })
</script>

<style scoped>
/* PŮVODNÍ STYLY PRO ROZLOŽENÍ */
.catalog-container { position: relative; min-height: 400px; display: flex; flex-direction: column; width: 100%; }
.view-header { margin-bottom: 2rem; }
.header-actions { display: flex; justify-content: space-between; align-items: center; gap: 1.5rem; }
.header-filters-row { display: flex; gap: 1rem; flex: 1; max-width: 800px; align-items: center; }

/* OPRAVENÁ MŘÍŽKA - Pevné wrappery */
.list-wrapper { width: 100%; display: flex; flex-direction: column; }
.map-wrapper { width: 100%; }
.breweries-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1.5rem; width: 100%; }

.empty-state { text-align: center; padding: 4rem; display: flex; flex-direction: column; align-items: center; gap: 1rem; background: var(--bg-panel); border-radius: 12px; border: 1px dashed var(--border); transition: background-color 0.5s ease, border-color 0.5s ease; }
.empty-state h3 { color: var(--text-main); transition: color 0.5s ease; }

/* STYLY PRO PŘEPÍNAČ ZOBRAZENÍ */
.view-toggle-group {
  display: flex;
  background: var(--bg-app);
  border: 1px solid var(--border);
  padding: 4px;
  border-radius: 10px;
}

.toggle-btn {
  background: transparent;
  color: var(--text-muted);
  border: none;
  padding: 8px 12px;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s;
  box-shadow: none;
}

.toggle-btn.active {
  background: var(--bg-panel);
  color: var(--primary);
  box-shadow: var(--shadow-sm);
}

/* RESPONSIVNÍ DESIGN PRO MOBILY */
@media (max-width: 800px) { 
  .header-actions { flex-direction: column-reverse; align-items: stretch; }
  .header-actions .btn-add { width: 100%; padding: 1rem; font-size: 1.05rem; }
  .header-filters-row { width: 100%; flex-direction: column; max-width: none; }
  .view-toggle-group { width: 100%; justify-content: center; }
}
</style>