<template>
  <div class="breweries-page">
    <transition name="toast-fade">
      <div v-if="toast.show" class="toast-notification" :class="toast.type">{{ toast.message }}</div>
    </transition>

    <div class="view-header">
      <div class="header-top">
        <div class="title-group">
          <h2 class="section-title">Pivovary</h2>
          <p class="auth-subtitle">Seznam pivovarů, jejichž piva máme v katalogu</p>
        </div>
        <BaseButton v-if="isAdmin" variant="add" @click="isAddModalOpen = true">
          <template #icon><PlusIcon :size="18" /></template>
          Přidat pivovar
        </BaseButton>
      </div>

      <div class="header-filters-row">
        <FilterInput v-model="searchQuery" placeholder="Hledat pivovar nebo město..." class="flex-2" />
        <FilterSelect v-model="sortBy" :icon="ArrowDownUpIcon" class="flex-1">
          <option value="name">Abecedně (A-Z)</option>
          <option value="rating">Dle hodnocení piv</option>
        </FilterSelect>
      </div>
    </div>

    <div v-if="isLoading" class="loading-state">Hledáme pivovary... ⏳</div>
    
    <div v-else>
      <div class="breweries-grid" v-if="filteredBreweries && filteredBreweries.length > 0">
        <BreweryCard 
          v-for="brewery in filteredBreweries" 
          :key="brewery.id" 
          :brewery="brewery" 
          @showDetail="openDetail"
        />
      </div>
      <div v-else class="empty-state">
        <FactoryIcon :size="48" color="#cbd5e1" stroke-width="1" />
        <h3>Zatím tu žádné pivovary nejsou</h3>
      </div>
    </div>

    <DetailModal :show="isDetailOpen" :item="selectedItem" type="brewery" @close="isDetailOpen = false" />

    <AddBreweryModal 
      :show="isAddModalOpen" 
      :form="form" 
      @close="isAddModalOpen = false" 
      @submit="submitBrewery" 
    />
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { storeToRefs } from 'pinia'
import { PlusIcon, FactoryIcon, ArrowDownUpIcon } from 'lucide-vue-next'

import { useAuthStore } from '../stores/auth'
import { useCatalogStore } from '../stores/catalog'
import BaseButton from '../components/BaseButton.vue'
import FilterInput from '../components/FilterInput.vue'
import FilterSelect from '../components/FilterSelect.vue'
import BreweryCard from '../components/BreweryCard.vue'
import DetailModal from '../components/modals/DetailModal.vue'
import AddBreweryModal from '../components/modals/AddBreweryModal.vue'

const authStore = useAuthStore(); const catalogStore = useCatalogStore()
const { user } = storeToRefs(authStore); const { breweries, isLoading } = storeToRefs(catalogStore)
const isAdmin = computed(() => user.value?.role === 'admin')

const toast = ref({ show: false, message: '', type: 'toast-success' }); const searchQuery = ref(''); const sortBy = ref('name')
const isAddModalOpen = ref(false); const isDetailOpen = ref(false); const selectedItem = ref(null)
const form = ref({ name: '', city: '', zip_code: '', country: 'Česká republika', address: '', street_number: '', email: '', phone: '', website: '' })

const showToast = (message, type = 'toast-success') => { toast.value = { show: true, message, type }; setTimeout(() => { toast.value.show = false }, 3000) }
const openDetail = (item) => { selectedItem.value = item; isDetailOpen.value = true }

const filteredBreweries = computed(() => {
  let result = breweries.value || []
  if (searchQuery.value) result = result.filter(b => b.name.toLowerCase().includes(searchQuery.value.toLowerCase()) || (b.city && b.city.toLowerCase().includes(searchQuery.value.toLowerCase())))
  return result.slice().sort((a, b) => sortBy.value === 'name' ? a.name.localeCompare(b.name) : (parseFloat(b.avg_rating) || 0) - (parseFloat(a.avg_rating) || 0))
})

onMounted(() => { if (user.value) catalogStore.fetchAllData(user.value.id) })

const submitBrewery = async () => {
  try {
    const res = await fetch('https://www.pivoo.cz/backend/api/add_brewery.php', { 
      method: 'POST', 
      headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${authStore.token}` }, 
      body: JSON.stringify(form.value) 
    })
    if (res.ok) { 
      isAddModalOpen.value = false; 
      form.value = { name: '', city: '', zip_code: '', country: 'Česká republika', address: '', street_number: '', email: '', phone: '', website: '' };
      await catalogStore.fetchAllData(user.value.id); 
      showToast("Pivovar přidán.") 
    }
  } catch (error) { showToast('Chyba serveru.', 'toast-error') }
}
</script>

<style scoped>
.view-header { display: flex; flex-direction: column; gap: 0.75rem; margin-bottom: 2rem; }
.header-top { display: flex; justify-content: space-between; align-items: center; gap: 1rem; }
.section-title { margin: 0; }
.auth-subtitle { margin: 0.15rem 0 0; color: #64748b; }
.header-filters-row { display: flex; gap: 1rem; margin-top: 0.25rem; width: 60%; }
.flex-2 { flex: 2; }
.flex-1 { flex: 1; }
.breweries-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1.5rem; }
.empty-state { text-align: center; padding: 4rem; display: flex; flex-direction: column; align-items: center; gap: 1rem; background: var(--bg-panel); border-radius: 12px; border: 1px dashed var(--border); }
@media (max-width: 800px) { .header-top { flex-direction: column; align-items: stretch; gap: 1.25rem; } .header-filters-row { width: 100%; flex-direction: column; } }
</style>