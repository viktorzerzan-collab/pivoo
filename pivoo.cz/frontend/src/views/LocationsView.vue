<template>
  <div class="locations-page">
    <transition name="toast-fade">
      <div v-if="toast.show" class="toast-notification" :class="toast.type">{{ toast.message }}</div>
    </transition>

    <div class="view-header">
      <div class="header-top">
        <div class="title-group">
          <h2 class="section-title">Podniky</h2>
          <p class="auth-subtitle">Kam na dobré pivo</p>
        </div>
        <button v-if="isAdmin" class="btn-add" @click="isAddModalOpen = true">
          <PlusIcon /> Přidat podnik
        </button>
      </div>
      <div class="header-filters-row">
        <FilterInput v-model="searchQuery" placeholder="Hledat podnik..." class="flex-2" />
        <FilterSelect v-model="sortBy" :icon="ArrowDownUpIcon" class="flex-1">
          <option value="name">Abecedně (A-Z)</option>
          <option value="rating">Dle hodnocení</option>
        </FilterSelect>
      </div>
    </div>

    <div class="catalog-container">
      <BaseLoader :show="isLoading" />

      <template v-if="filteredLocations.length > 0">
        <div class="locations-grid">
          <LocationCard 
            v-for="loc in paginatedLocations" 
            :key="loc.id" 
            :location="loc" 
            @showDetail="openDetail" 
          />
        </div>
        
        <BasePagination 
          v-model:currentPage="currentPage" 
          :totalPages="totalPages" 
        />
      </template>
      
      <div v-else-if="!isLoading" class="empty-state">
        <MapIcon :size="48" color="#cbd5e1" />
        <h3>Žádné podniky k zobrazení</h3>
      </div>
    </div>

    <DetailModal :show="isDetailOpen" :item="selectedItem" type="location" @close="isDetailOpen = false" />
    <AddLocationModal :show="isAddModalOpen" :form="form" @close="isAddModalOpen = false" @submit="submitLocation" />
  </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { PlusIcon, MapIcon, ArrowDownUpIcon } from 'lucide-vue-next'

import { apiFetch } from '../api'
import { useAuthStore } from '../stores/auth'
import { useCatalogStore } from '../stores/catalog'

import BaseButton from '../components/BaseButton.vue'
import BaseLoader from '../components/BaseLoader.vue'
import FilterInput from '../components/FilterInput.vue'
import FilterSelect from '../components/FilterSelect.vue'
import LocationCard from '../components/LocationCard.vue'
import DetailModal from '../components/modals/DetailModal.vue'
import AddLocationModal from '../components/modals/AddLocationModal.vue'
import BasePagination from '../components/BasePagination.vue'

const authStore = useAuthStore()
const catalogStore = useCatalogStore()
const { user } = storeToRefs(authStore)
const { locations, isLoading } = storeToRefs(catalogStore)
const isAdmin = computed(() => user.value?.role === 'admin')

const toast = ref({ show: false, message: '', type: 'toast-success' })
const searchQuery = ref('')
const sortBy = ref('name')
const isAddModalOpen = ref(false)
const isDetailOpen = ref(false)
const selectedItem = ref(null)

const form = ref({ 
  name: '', type: 'hospoda', city: '', zip_code: '', country: 'Česká republika', 
  address: '', street_number: '', email: '', phone: '', website: '', opening_hours: '' 
})

// STRÁNKOVÁNÍ
const currentPage = ref(1)
const itemsPerPage = 30

watch([searchQuery, sortBy], () => {
  currentPage.value = 1
})

const showToast = (message, type = 'toast-success') => { 
  toast.value = { show: true, message, type }
  setTimeout(() => { toast.value.show = false }, 3000) 
}

const filteredLocations = computed(() => {
  let result = (locations.value || []).filter(loc => loc.type === 'hospoda')
  if (searchQuery.value) {
    result = result.filter(loc => loc.name.toLowerCase().includes(searchQuery.value.toLowerCase()))
  }
  return result.slice().sort((a, b) => 
    sortBy.value === 'name' 
      ? a.name.localeCompare(b.name) 
      : (parseFloat(b.avg_rating) || 0) - (parseFloat(a.avg_rating) || 0)
  )
})

const totalPages = computed(() => Math.ceil(filteredLocations.value.length / itemsPerPage))

const paginatedLocations = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage
  return filteredLocations.value.slice(start, start + itemsPerPage)
})

const openDetail = (loc) => { 
  selectedItem.value = loc
  isDetailOpen.value = true 
}

const submitLocation = async () => {
  try {
    const result = await apiFetch('/add_location.php', { method: 'POST', body: JSON.stringify(form.value) })
    if (result.status === 'success') { 
      isAddModalOpen.value = false
      form.value = { name: '', type: 'hospoda', city: '', zip_code: '', country: 'Česká republika', address: '', street_number: '', email: '', phone: '', website: '', opening_hours: '' }
      await catalogStore.fetchAllData()
      showToast("Podnik uložen") 
    }
  } catch (e) { showToast('Chyba serveru.', 'toast-error') }
}

onMounted(() => { if (user.value) catalogStore.fetchAllData() })
</script>

<style scoped>
.catalog-container { position: relative; min-height: 400px; display: flex; flex-direction: column; }
.view-header { display: flex; flex-direction: column; gap: 1rem; margin-bottom: 2rem; }
.header-top { display: flex; justify-content: space-between; align-items: center; }
.header-filters-row { display: flex; gap: 1rem; width: 60%; }
.locations-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1.5rem; }
.empty-state { text-align: center; padding: 4rem; display: flex; flex-direction: column; align-items: center; gap: 1rem; background: var(--bg-panel); border-radius: 12px; border: 1px dashed var(--border); transition: background-color 0.5s ease, border-color 0.5s ease; }
.empty-state h3 { color: var(--text-main); transition: color 0.5s ease; }

@media (max-width: 800px) { 
  .header-top { flex-direction: column; align-items: flex-start; gap: 1rem; }
  .header-top .btn-add { width: 100%; padding: 1rem; font-size: 1.05rem; }
  .header-filters-row { width: 100%; flex-direction: column; } 
}
</style>