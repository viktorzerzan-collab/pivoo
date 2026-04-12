<template>
  <div class="dashboard-page">
    <transition name="toast-fade">
      <div v-if="toast.show" class="toast-notification" :class="toast.type">{{ toast.message }}</div>
    </transition>

    <div class="section-header">
      <h2 class="section-title">Moje nástěnka</h2>
      <div class="action-buttons-top">
        <BaseButton variant="add" @click="isModalOpen = true">
          <template #icon><PlusCircleIcon :size="18" /></template>
          Zapsat úlovek
        </BaseButton>
      </div>
    </div>

    <div v-if="isLoading" class="loading-state">Načítám tvoje data... ⏳</div>
    
    <div v-else class="dashboard-content">
      
      <div class="panel-card">
        <div class="panel-header">
          <h3><TrendingUpIcon :size="20" class="panel-icon" /> Rychlý přehled</h3>
        </div>
        <StatsBoard :stats="stats" />
      </div>

      <div class="panel-card">
        <div class="panel-header">
          <h3><HistoryIcon :size="20" class="panel-icon" /> Poslední zápisy</h3>
        </div>
        <HistoryList :history="history" @edit="openEditModal" @delete="confirmDelete" />
        
        <div v-if="!history || history.length === 0" class="empty-dashboard">
          <BeerIcon :size="48" color="#cbd5e1" stroke-width="1" />
          <p>Zatím jsi si nezapsal žádné pivo.<br>Začni kliknutím na tlačítko "Zapsat úlovek"!</p>
        </div>
      </div>
    </div>

    <CheckInModal 
      :show="isModalOpen" 
      :beers="beers" 
      :locations="locations" 
      :form="form" 
      @close="isModalOpen = false" 
      @submit="submitCheckIn" 
    />
    
    <EditCheckInModal 
      :show="isEditModalOpen" 
      :beers="beers" 
      :locations="locations" 
      :form="editForm" 
      @close="isEditModalOpen = false" 
      @submit="submitEdit" 
    />
    
    <DeleteConfirmModal 
      :show="isDeleteConfirmModalOpen" 
      @close="isDeleteConfirmModalOpen = false" 
      @confirm="executeDelete" 
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { storeToRefs } from 'pinia'
import { PlusCircleIcon, TrendingUpIcon, HistoryIcon, BeerIcon } from 'lucide-vue-next'

import { useAuthStore } from '../stores/auth'
import { useCatalogStore } from '../stores/catalog'
import BaseButton from '../components/BaseButton.vue'
import StatsBoard from '../components/StatsBoard.vue'
import HistoryList from '../components/HistoryList.vue'
import CheckInModal from '../components/modals/CheckInModal.vue'
import EditCheckInModal from '../components/modals/EditCheckInModal.vue'
import DeleteConfirmModal from '../components/modals/DeleteConfirmModal.vue'

const authStore = useAuthStore()
const catalogStore = useCatalogStore()
const { user } = storeToRefs(authStore)
const { beers, locations, stats, history, isLoading } = storeToRefs(catalogStore)

const toast = ref({ show: false, message: '', type: 'toast-success' })
const showToast = (message, type = 'toast-success') => { 
  toast.value = { show: true, message, type }
  setTimeout(() => { toast.value.show = false }, 3000) 
}

const isModalOpen = ref(false)
const isEditModalOpen = ref(false)
const isDeleteConfirmModalOpen = ref(false)
const recordIdToDelete = ref(null)
const selectedEditRecordId = ref(null)

const form = ref({ 
  beer_id: '', location_id: '', packaging: 'točené', volume: '0.50', 
  quantity: 1, price: '', rating_beer: 0, rating_care: 0, note: '' 
})

const editForm = ref({ 
  beer_id: '', location_id: '', packaging: 'točené', volume: '0.50', 
  quantity: 1, price: '', rating_beer: 0, rating_care: 0, note: '' 
})

onMounted(() => { 
  if (user.value) { catalogStore.fetchAllData(user.value.id) }
})

const openEditModal = (record) => {
  selectedEditRecordId.value = record.id
  editForm.value = { 
    beer_id: Number(record.beer_id), location_id: Number(record.location_id), 
    packaging: record.packaging || 'točené', volume: record.volume, 
    quantity: Number(record.quantity), price: record.price ? Number(record.price) : '', 
    rating_beer: record.rating_beer ? Number(record.rating_beer) : 0, 
    rating_care: record.rating_care ? Number(record.rating_care) : 0, 
    note: record.note || '' 
  }
  isEditModalOpen.value = true
}

// ZMĚNA: Do hlavičky přibyla Authorization
const submitCheckIn = async () => {
  try {
    const res = await fetch('https://www.pivoo.cz/backend/api/checkin.php', { 
      method: 'POST', 
      headers: { 
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${authStore.token}` 
      }, 
      body: JSON.stringify({ user_id: user.value.id, ...form.value }) 
    })
    
    if (res.ok) { 
      isModalOpen.value = false
      form.value = { beer_id: '', location_id: '', packaging: 'točené', volume: '0.50', quantity: 1, price: '', rating_beer: 0, rating_care: 0, note: '' }
      await catalogStore.fetchAllData(user.value.id)
      showToast("Pivo zapsáno!") 
    } else { showToast('Nepodařilo se uložit.', 'toast-error') }
  } catch (e) { showToast('Chyba serveru.', 'toast-error') }
}

// ZMĚNA: Do hlavičky přibyla Authorization
const submitEdit = async () => {
  try {
    const res = await fetch('https://www.pivoo.cz/backend/api/update_checkin.php', { 
      method: 'POST', 
      headers: { 
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${authStore.token}` 
      }, 
      body: JSON.stringify({ id: selectedEditRecordId.value, user_id: user.value.id, ...editForm.value }) 
    })
    
    if (res.ok) { 
      isEditModalOpen.value = false
      await catalogStore.fetchAllData(user.value.id)
      showToast("Záznam upraven") 
    } else { showToast('Nepodařilo se upravit.', 'toast-error') }
  } catch (e) { showToast('Chyba serveru.', 'toast-error') }
}

const confirmDelete = (id) => { recordIdToDelete.value = id; isDeleteConfirmModalOpen.value = true }

// ZMĚNA: Do hlavičky přibyla Authorization
const executeDelete = async () => {
  try {
    const res = await fetch('https://www.pivoo.cz/backend/api/delete_checkin.php', { 
      method: 'POST', 
      headers: { 
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${authStore.token}` 
      }, 
      body: JSON.stringify({ id: recordIdToDelete.value, user_id: user.value.id }) 
    })
    
    if (res.ok) {
      isDeleteConfirmModalOpen.value = false
      await catalogStore.fetchAllData(user.value.id)
      showToast("Smazáno")
    } else { showToast('Nepodařilo se smazat.', 'toast-error') }
  } catch (e) { showToast('Chyba serveru.', 'toast-error') }
}
</script>

<style scoped>
.dashboard-content { display: flex; flex-direction: column; gap: 2rem; }
.panel-card { background: var(--bg-panel); border-radius: 12px; border: 1px solid var(--border); box-shadow: var(--shadow-sm); padding: 1.5rem; }
.panel-header { border-bottom: 1px solid var(--border); padding-bottom: 1rem; margin-bottom: 1.5rem; }
.panel-header h3 { margin: 0; display: flex; align-items: center; gap: 0.5rem; font-size: 1.25rem; color: #334155; }
.panel-icon { color: var(--primary); }
.empty-dashboard { text-align: center; padding: 4rem 2rem; color: var(--text-muted); display: flex; flex-direction: column; align-items: center; gap: 1rem; }
.empty-dashboard p { margin: 0; line-height: 1.6; }
@media (max-width: 600px) { .action-buttons-top { width: 100%; display: flex; flex-direction: column; } .panel-card { padding: 1rem; } }
</style>