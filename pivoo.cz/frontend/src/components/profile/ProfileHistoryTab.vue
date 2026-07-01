<template>
  <div>
    <div class="history-tab-content">
      <BasePanel :title="$t('views.profile.tab_history')" :icon="HistoryIcon">
        
        <div v-if="isHistoryLoading" style="position: relative; min-height: 200px;">
          <BaseLoader :show="true" />
        </div>
        
        <div v-else>
          <HistoryList v-if="historyRecords.length > 0" :history="historyRecords" @edit="openEditModal" @delete="openDeleteConfirm" />
          
          <BaseEmptyState 
            v-else 
            :text="$t('views.profile.no_history')" 
            :icon="HistoryIcon" 
          />

          <BasePagination 
            v-if="historyTotalPages > 1"
            v-model:currentPage="historyPage"
            :totalPages="historyTotalPages"
          />
        </div>
      </BasePanel>
    </div>

    <CheckInModal 
      :show="showEditHistoryModal" 
      :form="editForm" 
      :isEditing="true"
      @close="showEditHistoryModal = false" 
      @submit="submitEdit" 
    />

    <DeleteConfirmModal
      :show="showDeleteRecordModal"
      @close="showDeleteRecordModal = false"
      @confirm="deleteHistoryRecord"
    />
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { useI18n } from 'vue-i18n'
import { HistoryIcon } from 'lucide-vue-next'

import BasePanel from '../BasePanel.vue'
import BaseLoader from '../BaseLoader.vue'
import BaseEmptyState from '../BaseEmptyState.vue'
import BasePagination from '../BasePagination.vue'
import HistoryList from '../HistoryList.vue'
import CheckInModal from '../modals/CheckInModal.vue'
import DeleteConfirmModal from '../modals/DeleteConfirmModal.vue'

import { useToastStore } from '../../stores/toast'
import { useCatalogStore } from '../../stores/catalog'
import { apiFetch } from '../../api'

const toastStore = useToastStore()
const catalogStore = useCatalogStore()
const { allBeers, allBreweries, allLocations } = storeToRefs(catalogStore)
const { t } = useI18n()

// Historie - Stav
const isHistoryLoading = ref(false)
const historyRecords = ref([])
const historyPage = ref(1)
const historyTotalPages = ref(1)

const showEditHistoryModal = ref(false)
const selectedEditRecordId = ref(null)
const editForm = ref({ brewery_id: '', beer_id: '', location_id: '', consumed_at: '', packaging: 'točené', volume: '0.50', quantity: 1, price: '', currency: 'CZK', original_price: '', is_free: false, rating_beer: 0, rating_care: 0, note: '' })

const showDeleteRecordModal = ref(false)
const recordIdToDelete = ref(null)

const fetchHistory = async () => {
  isHistoryLoading.value = true
  try {
    const res = await apiFetch(`/history.php?page=${historyPage.value}&limit=12`)
    if (res.status === 'success') {
      historyRecords.value = res.data
      historyTotalPages.value = res.pagination ? res.pagination.total_pages : 1
    }
  } catch (err) {
    toastStore.showToast(t('toast.communication_error'), 'toast-error')
  } finally {
    isHistoryLoading.value = false
  }
}

watch(historyPage, () => {
  fetchHistory()
})

onMounted(() => {
  fetchHistory()
})

const openEditModal = async (record) => {
  if (!catalogStore.isFormDataLoaded) {
    await catalogStore.fetchFormData()
  }

  selectedEditRecordId.value = record.id
  const currentBeer = allBeers.value.find(b => b.id == record.beer_id)
  const prefillBreweryId = currentBeer ? currentBeer.brewery_id : ''
  editForm.value = { 
    ...record, 
    consumed_at: record.consumed_at || '', 
    brewery_id: Number(prefillBreweryId), 
    beer_id: Number(record.beer_id), 
    location_id: Number(record.location_id), 
    quantity: Number(record.quantity), 
    is_free: !!Number(record.is_free), 
    currency: record.currency || 'CZK', 
    original_price: record.original_price || record.price 
  }
  showEditHistoryModal.value = true
}

const submitEdit = async () => {
  try {
    const res = await apiFetch('/update_checkin.php', { method: 'POST', body: JSON.stringify({ id: selectedEditRecordId.value, ...editForm.value }) })
    if (res.status === 'success') { 
       showEditHistoryModal.value = false
       
       const beer = allBeers.value.find(b => b.id == editForm.value.beer_id)
       const brewery = allBreweries.value.find(b => b.id == editForm.value.brewery_id)
       const loc = allLocations.value.find(l => l.id == editForm.value.location_id)
       
       catalogStore.updateCheckinLocally({
           id: selectedEditRecordId.value,
           ...editForm.value,
           price: res.price,           
           currency: res.currency,     
           original_price: res.original_price, 
           beer_name: beer ? beer.name : 'Neznámé pivo',
           brewery_name: brewery ? brewery.name : 'Neznámý pivovar',
           location_name: loc ? loc.name : 'Neznámý podnik'
       })
       toastStore.showToast(t('toast.record_edited'), 'toast-success') 
       fetchHistory()
    } else {
      toastStore.showToast(res.message || 'Error', 'toast-error')
    }
  } catch (e) { 
    toastStore.showToast(e.message || t('toast.communication_error'), 'toast-error') 
  }
}

const openDeleteConfirm = (id) => {
  recordIdToDelete.value = id
  showDeleteRecordModal.value = true
}

const deleteHistoryRecord = async () => {
  try {
    const res = await apiFetch('/delete_checkin.php', {
      method: 'POST',
      body: JSON.stringify({ id: recordIdToDelete.value })
    })
    
    if (res.status === 'success') {
      showDeleteRecordModal.value = false
      catalogStore.removeCheckinLocally(recordIdToDelete.value)
      toastStore.showToast(t('toast.record_deleted'), 'toast-success')
      fetchHistory()
    } else {
      toastStore.showToast(res.message || t('toast.delete_error'), 'toast-error')
    }
  } catch (e) {
    toastStore.showToast(t('toast.communication_error'), 'toast-error')
  }
}
</script>
