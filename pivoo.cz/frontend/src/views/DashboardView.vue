<template>
  <div class="dashboard-page">
    <div class="section-header">
      <h2 class="section-title">Moje nástěnka</h2>
      <button class="btn-add" @click="isModalOpen = true">
        <PlusCircleIcon /> Zapsat úlovek
      </button>
    </div>

    <div class="dashboard-layout">
      <BaseLoader :show="isLoading" message="Načítám tvůj deníček..." />

      <div class="dashboard-content">
        <div class="panel-card">
          <div class="panel-header">
            <h3><TrendingUpIcon /> Rychlý přehled</h3>
          </div>
          <StatsBoard :stats="stats" />
        </div>

        <div class="panel-card">
          <div class="panel-header">
            <h3><HistoryIcon /> Poslední zápisy</h3>
          </div>
          <HistoryList :history="history" @edit="openEditModal" @delete="confirmDelete" />
          
          <div v-if="!isLoading && (!history || history.length === 0)" class="empty-dashboard">
            <BeerIcon :size="48" color="#cbd5e1" stroke-width="1" />
            <p>Zatím žádné zápisy.</p>
          </div>
        </div>
      </div>
    </div>

    <CheckInModal :show="isModalOpen" :beers="beers" :locations="locations" :form="form" @close="isModalOpen = false" @submit="submitCheckIn" />
    <EditCheckInModal :show="isEditModalOpen" :beers="beers" :locations="locations" :form="editForm" @close="isEditModalOpen = false" @submit="submitEdit" />
    <DeleteConfirmModal :show="isDeleteConfirmModalOpen" @close="isDeleteConfirmModalOpen = false" @confirm="executeDelete" />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { storeToRefs } from 'pinia'
import { PlusCircleIcon, TrendingUpIcon, HistoryIcon, BeerIcon } from 'lucide-vue-next'
import { apiFetch } from '../api'
import { useAuthStore } from '../stores/auth'
import { useCatalogStore } from '../stores/catalog'
import BaseLoader from '../components/BaseLoader.vue'
import StatsBoard from '../components/StatsBoard.vue'
import HistoryList from '../components/HistoryList.vue'
import CheckInModal from '../components/modals/CheckInModal.vue'
import EditCheckInModal from '../components/modals/EditCheckInModal.vue'
import DeleteConfirmModal from '../components/modals/DeleteConfirmModal.vue'

const authStore = useAuthStore(); const catalogStore = useCatalogStore()
const { beers, locations, stats, history, isLoading } = storeToRefs(catalogStore)

const isModalOpen = ref(false); const isEditModalOpen = ref(false); const isDeleteConfirmModalOpen = ref(false)
const recordIdToDelete = ref(null); const selectedEditRecordId = ref(null)

const form = ref({ beer_id: '', location_id: '', packaging: 'točené', volume: '0.50', quantity: 1, price: '', rating_beer: 0, rating_care: 0, note: '' })
const editForm = ref({ beer_id: '', location_id: '', packaging: 'točené', volume: '0.50', quantity: 1, price: '', rating_beer: 0, rating_care: 0, note: '' })

onMounted(() => { if (authStore.user) catalogStore.fetchAllData() })

const openEditModal = (record) => {
  selectedEditRecordId.value = record.id
  editForm.value = { ...record, beer_id: Number(record.beer_id), location_id: Number(record.location_id), quantity: Number(record.quantity) }
  isEditModalOpen.value = true
}

const submitCheckIn = async () => {
  const res = await apiFetch('/checkin.php', { method: 'POST', body: form.value })
  if (res.status === 'success') { isModalOpen.value = false; await catalogStore.fetchAllData() }
}

const submitEdit = async () => {
  const res = await apiFetch('/update_checkin.php', { method: 'POST', body: { id: selectedEditRecordId.value, ...editForm.value } })
  if (res.status === 'success') { isEditModalOpen.value = false; await catalogStore.fetchAllData() }
}

const confirmDelete = (id) => { recordIdToDelete.value = id; isDeleteConfirmModalOpen.value = true }
const executeDelete = async () => {
  const res = await apiFetch('/delete_checkin.php', { method: 'POST', body: { id: recordIdToDelete.value } })
  if (res.status === 'success') { isDeleteConfirmModalOpen.value = false; await catalogStore.fetchAllData() }
}
</script>

<style scoped>
.dashboard-layout { position: relative; min-height: 400px; }
.section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
.dashboard-content { display: flex; flex-direction: column; gap: 2rem; }
.panel-card { background: var(--bg-panel); border-radius: 12px; border: 1px solid var(--border); padding: 1.5rem; }
.panel-header { border-bottom: 1px solid var(--border); padding-bottom: 1rem; margin-bottom: 1.5rem; }
.panel-header h3 { margin: 0; display: flex; align-items: center; gap: 0.5rem; font-size: 1.25rem; }
</style>