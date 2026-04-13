<template>
  <div class="breweries-page">
    <transition name="toast-fade">
      <div v-if="toast.show" class="toast-notification" :class="toast.type">{{ toast.message }}</div>
    </transition>

    <div class="view-header">
      <div class="header-top">
        <div class="title-group">
          <h2 class="section-title">Pivovary</h2>
          <p class="auth-subtitle">Seznam pivovarů v našem katalogu</p>
        </div>
        <button v-if="isAdmin" class="btn-add" @click="isAddModalOpen = true">
          <PlusIcon /> Přidat pivovar
        </button>
      </div>

      <div class="header-filters-row">
        <FilterInput v-model="searchQuery" placeholder="Hledat pivovar..." class="flex-2" />
        <FilterSelect v-model="sortBy" :icon="ArrowDownUpIcon" class="flex-1">
          <option value="name">Abecedně (A-Z)</option>
          <option value="rating">Dle hodnocení</option>
        </FilterSelect>
      </div>
    </div>

    <div class="catalog-container">
      <BaseLoader :show="isLoading" message="Načítám pivovary..." />

      <div class="breweries-grid" v-if="filteredBreweries.length > 0">
        <BreweryCard 
          v-for="brewery in filteredBreweries" 
          :key="brewery.id" 
          :brewery="brewery" 
          @showDetail="openDetail" 
        />
      </div>
      
      <div v-else-if="!isLoading" class="empty-state">
        <FactoryIcon :size="48" color="#cbd5e1" />
        <h3>Žádné pivovary k zobrazení</h3>
      </div>
    </div>

    <DetailModal :show="isDetailOpen" :item="selectedItem" type="brewery" @close="isDetailOpen = false" />
    <AddBreweryModal :show="isAddModalOpen" :isEditing="false" :form="form" @close="isAddModalOpen = false" @submit="submitBrewery" />
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { storeToRefs } from 'pinia'
import { PlusIcon, FactoryIcon, ArrowDownUpIcon } from 'lucide-vue-next'

import { apiFetch } from '../api'
import { useAuthStore } from '../stores/auth'
import { useCatalogStore } from '../stores/catalog'

import BaseLoader from '../components/BaseLoader.vue'
import FilterInput from '../components/FilterInput.vue'
import FilterSelect from '../components/FilterSelect.vue'
import BreweryCard from '../components/BreweryCard.vue'
import DetailModal from '../components/modals/DetailModal.vue'
import AddBreweryModal from '../components/modals/AddBreweryModal.vue'

const authStore = useAuthStore()
const catalogStore = useCatalogStore()
const { user } = storeToRefs(authStore)
const { breweries, isLoading } = storeToRefs(catalogStore)
const isAdmin = computed(() => user.value?.role === 'admin')

const toast = ref({ show: false, message: '', type: 'toast-success' })
const searchQuery = ref('')
const sortBy = ref('name')
const isAddModalOpen = ref(false)
const isDetailOpen = ref(false)
const selectedItem = ref(null)

const form = ref({ 
  name: '', city: '', zip_code: '', country: 'Česká republika', 
  address: '', street_number: '', email: '', phone: '', website: '', logoFile: null 
})

const showToast = (message, type = 'toast-success') => { 
  toast.value = { show: true, message, type }
  setTimeout(() => { toast.value.show = false }, 3000) 
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

const openDetail = (item) => { 
  selectedItem.value = item
  isDetailOpen.value = true 
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
      form.value = { name: '', city: '', zip_code: '', country: 'Česká republika', address: '', street_number: '', email: '', phone: '', website: '', logoFile: null }
      await catalogStore.fetchAllData()
      showToast("Pivovar přidán") 
    } else {
      showToast(result.message || 'Nepodařilo se přidat pivovar.', 'toast-error')
    }
  } catch (e) { 
    showToast('Chyba serveru.', 'toast-error') 
  }
}

onMounted(() => { if (user.value) catalogStore.fetchAllData() })
</script>

<style scoped>
.catalog-container { position: relative; min-height: 400px; }
.view-header { display: flex; flex-direction: column; gap: 1rem; margin-bottom: 2rem; }
.header-top { display: flex; justify-content: space-between; align-items: center; }
.header-filters-row { display: flex; gap: 1rem; width: 60%; }
.breweries-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1.5rem; }
.empty-state { text-align: center; padding: 4rem; display: flex; flex-direction: column; align-items: center; gap: 1rem; background: var(--bg-panel); border-radius: 12px; border: 1px dashed var(--border); transition: background-color 0.5s ease, border-color 0.5s ease; }
.empty-state h3 { color: var(--text-main); transition: color 0.5s ease; }

@media (max-width: 800px) { 
  .header-top { flex-direction: column; align-items: flex-start; gap: 1rem; }
  .header-top .btn-add { width: 100%; padding: 1rem; font-size: 1.05rem; } /* VZDUŠNĚJŠÍ TLAČÍTKO */
  .header-filters-row { width: 100%; flex-direction: column; } 
}
</style>