<template>
  <div class="dashboard-page">
    <transition name="toast-fade">
      <div v-if="toast.show" class="toast-notification" :class="toast.type">{{ toast.message }}</div>
    </transition>

    <div class="section-actions">
      <button class="btn-add" @click="openCheckInModal">
        <PlusCircleIcon /> Zaznamenat vypitá piva
      </button>
    </div>

    <div class="dashboard-layout">
      <BaseLoader :show="isLoading" />

      <div class="dashboard-content">
        <div class="panel-card">
          <div class="panel-header">
            <h3><BeerIcon class="panel-icon" /> Já a pivo v {{ currentMonthName }}</h3>
          </div>
          <StatsBoard :stats="stats" />
        </div>

        <div class="panel-card">
          <div class="panel-header">
            <h3><HistoryIcon class="panel-icon" /> Poslední záznamy</h3>
          </div>
          <HistoryList :history="history" @edit="openEditModal" @delete="confirmDelete" />
          
          <div v-if="!isLoading && (!history || history.length === 0)" class="empty-dashboard">
            <BeerIcon :size="48" color="#cbd5e1" stroke-width="1" />
            <p>Zatím žádné záznamy.</p>
          </div>
        </div>
      </div>
    </div>

    <CheckInModal :show="isModalOpen" :breweries="breweries" :beers="beers" :locations="locations" :form="form" @close="isModalOpen = false" @submit="submitCheckIn" />
    <EditCheckInModal :show="isEditModalOpen" :breweries="breweries" :beers="beers" :locations="locations" :form="editForm" @close="isEditModalOpen = false" @submit="submitEdit" />
    <DeleteConfirmModal :show="isDeleteConfirmModalOpen" @close="isDeleteConfirmModalOpen = false" @confirm="executeDelete" />
  </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { PlusCircleIcon, HistoryIcon, BeerIcon } from 'lucide-vue-next'
import { apiFetch } from '../api'
import { useAuthStore } from '../stores/auth'
import { useCatalogStore } from '../stores/catalog'
import BaseLoader from '../components/BaseLoader.vue'
import StatsBoard from '../components/StatsBoard.vue'
import HistoryList from '../components/HistoryList.vue'
import CheckInModal from '../components/modals/CheckInModal.vue'
import EditCheckInModal from '../components/modals/EditCheckInModal.vue'
import DeleteConfirmModal from '../components/modals/DeleteConfirmModal.vue'

const authStore = useAuthStore()
const catalogStore = useCatalogStore()
const { beers, breweries, locations, stats, history, isLoading } = storeToRefs(catalogStore)

const toast = ref({ show: false, message: '', type: 'toast-success' })
const showToast = (message, type = 'toast-success') => { 
  toast.value = { show: true, message, type }
  setTimeout(() => { toast.value.show = false }, 3000) 
}

const currentMonthName = computed(() => {
  const months = [
    'lednu', 'únoru', 'březnu', 'dubnu', 'květnu', 'červnu', 
    'červenci', 'srpnu', 'září', 'říjnu', 'listopadu', 'prosinci'
  ]
  return months[new Date().getMonth()]
})

const isModalOpen = ref(false)
const isEditModalOpen = ref(false)
const isDeleteConfirmModalOpen = ref(false)
const recordIdToDelete = ref(null)
const selectedEditRecordId = ref(null)

// ZMĚNA: Přidána pole currency a price (v CheckInModalu slouží 'price' jako vstup pro original_price)
const form = ref({ brewery_id: '', beer_id: '', location_id: '', consumed_at: '', packaging: 'točené', volume: '0.50', quantity: 1, price: '', currency: 'CZK', is_free: false, rating_beer: 0, rating_care: 0, note: '' })
// ZMĚNA: Přidána pole currency a original_price
const editForm = ref({ brewery_id: '', beer_id: '', location_id: '', consumed_at: '', packaging: 'točené', volume: '0.50', quantity: 1, price: '', currency: 'CZK', original_price: '', is_free: false, rating_beer: 0, rating_care: 0, note: '' })

onMounted(() => { 
  if (authStore.user) {
    catalogStore.fetchAllData() 
  }
})

watch(() => authStore.user, (newUser) => {
  if (newUser) {
    catalogStore.fetchAllData()
  }
})

const openCheckInModal = async () => {
  await catalogStore.fetchAllData(true)
  isModalOpen.value = true
}

const openEditModal = (record) => {
  selectedEditRecordId.value = record.id
  const fullDateTime = record.consumed_at || ''
  
  const currentBeer = beers.value.find(b => b.id == record.beer_id)
  const prefillBreweryId = currentBeer ? currentBeer.brewery_id : ''

  editForm.value = { 
    ...record, 
    consumed_at: fullDateTime, 
    brewery_id: Number(prefillBreweryId),
    beer_id: Number(record.beer_id), 
    location_id: Number(record.location_id), 
    quantity: Number(record.quantity),
    is_free: !!Number(record.is_free),
    // ZMĚNA: Mapování nových polí s fallbackem na CZK/původní cenu
    currency: record.currency || 'CZK',
    original_price: record.original_price || record.price
  }
  isEditModalOpen.value = true
}

const submitCheckIn = async () => {
  try {
    const res = await apiFetch('/checkin.php', { 
      method: 'POST', 
      body: JSON.stringify(form.value) 
    })
    
    if (res.status === 'success') { 
      isModalOpen.value = false
      await catalogStore.fetchAllData()
      showToast('Záznam úspěšně zapsán!')
      // ZMĚNA: Reset formuláře včetně měny
      form.value = { brewery_id: '', beer_id: '', location_id: '', consumed_at: '', packaging: 'točené', volume: '0.50', quantity: 1, price: '', currency: 'CZK', is_free: false, rating_beer: 0, rating_care: 0, note: '' }
    } else {
      showToast(res.message || 'Nepodařilo se vytvořit záznam.', 'toast-error')
    }
  } catch (e) {
    showToast(e.message || 'Chyba serveru (kód 500).', 'toast-error')
  }
}

const submitEdit = async () => {
  try {
    const res = await apiFetch('/update_checkin.php', { 
      method: 'POST', 
      body: JSON.stringify({ id: selectedEditRecordId.value, ...editForm.value }) 
    })
    
    if (res.status === 'success') { 
      isEditModalOpen.value = false
      await catalogStore.fetchAllData()
      showToast('Záznam upraven!')
    } else {
      showToast(res.message || 'Chyba při úpravě.', 'toast-error')
    }
  } catch (e) {
    showToast(e.message || 'Chyba komunikace se serverem.', 'toast-error')
  }
}

const confirmDelete = (id) => { recordIdToDelete.value = id; isDeleteConfirmModalOpen.value = true }

const executeDelete = async () => {
  try {
    const res = await apiFetch('/delete_checkin.php', { 
      method: 'POST', 
      body: JSON.stringify({ id: recordIdToDelete.value }) 
    })
    
    if (res.status === 'success') { 
      isDeleteConfirmModalOpen.value = false
      await catalogStore.fetchAllData()
      showToast('Záznam smazán.')
    } else {
      showToast(res.message || 'Chyba při mazání.', 'toast-error')
    }
  } catch (e) {
    showToast('Chyba komunikace se serverem.', 'toast-error')
  }
}
</script>

<style scoped>
.dashboard-layout { position: relative; min-height: 400px; }
.section-actions { display: flex; justify-content: flex-end; margin-bottom: 1.5rem; }

.btn-add { 
  display: flex; 
  align-items: center; 
  gap: 0.5rem; 
  padding: 0.75rem 1.5rem; 
  font-weight: 600; 
}

.dashboard-content { display: flex; flex-direction: column; gap: 2rem; }
.panel-card { background: var(--bg-panel); border-radius: 12px; border: 1px solid var(--border); padding: 1.5rem; transition: background-color 0.5s ease, border-color 0.5s ease; }
.panel-header { border-bottom: 1px solid var(--border); padding-bottom: 1rem; margin-bottom: 1.5rem; transition: border-color 0.5s ease; }
.panel-header h3 { margin: 0; display: flex; align-items: center; gap: 0.5rem; font-size: 1.25rem; color: var(--text-main); transition: color 0.5s ease; }

.panel-icon { color: var(--primary); }

.empty-dashboard { text-align: center; color: var(--text-muted); padding: 2rem 0; display: flex; flex-direction: column; align-items: center; gap: 0.5rem; transition: color 0.5s ease; }

@media (max-width: 600px) {
  .section-actions .btn-add { 
    width: 100%; 
    padding: 1rem; 
    justify-content: center; 
    font-size: 1.1rem; 
  }
  .panel-card { padding: 1rem; }
}
</style>