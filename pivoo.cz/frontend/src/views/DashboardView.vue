<template>
  <div class="dashboard-page">
    <div class="section-actions">
      <BaseButton variant="add" @click="openCheckInModal">
        <template #icon><PlusCircleIcon :size="20" /></template>
        {{ $t('views.dashboard.record_beers') }}
      </BaseButton>
    </div>

    <div class="dashboard-layout">
      <BaseLoader :show="isLoading" />

      <div class="dashboard-content">
        <BasePanel :title="$t('views.dashboard.me_and_beer_in') + ' ' + currentMonthName" :icon="BeerIcon">
          <StatsBoard :stats="stats" />
        </BasePanel>

        <BasePanel :title="$t('views.dashboard.recent_records')" :icon="HistoryIcon">
          <HistoryList 
            v-if="history && history.length > 0"
            :history="history.slice(0, 6)" 
            @edit="openEditModal" 
            @delete="confirmDelete" 
          />
          
          <div class="history-actions" v-if="history && history.length > 0">
            <BaseButton variant="secondary" @click="goToHistory" class="full-history-btn">
              {{ $t('views.dashboard.show_full_history') }}
            </BaseButton>
          </div>
          
          <BaseEmptyState 
            v-if="!isLoading" 
            v-show="!history || history.length === 0"
            :text="$t('views.dashboard.no_records')" 
            :icon="BeerIcon" 
          />
        </BasePanel>
      </div>
    </div>

    <CheckInModal 
      :show="isModalOpen || isEditModalOpen" 
      :form="isEditModalOpen ? editForm : form" 
      :isEditing="isEditModalOpen"
      @close="isEditModalOpen ? (isEditModalOpen = false) : (isModalOpen = false)" 
      @submit="isEditModalOpen ? submitEdit($event) : submitCheckIn($event)"
      @open-add-location="openAddLocationFromCheckin" 
      @magic-add-brewery="handleMagicAddBrewery"
      @magic-add-beer="handleMagicAddBeer"
    />
    
    <DeleteConfirmModal 
      :show="isDeleteConfirmModalOpen" 
      @close="isDeleteConfirmModalOpen = false" 
      @confirm="executeDelete" 
    />

    <AddLocationModal 
      :show="isAddLocationModalOpen" 
      :isEditing="false" 
      :countries="countries" 
      :form="locationForm" 
      @close="isAddLocationModalOpen = false" 
      @submit="submitNewLocation" 
    />

    <AddBreweryModal 
      :show="isAddBreweryModalOpen" 
      :isEditing="false" 
      :countries="countries" 
      :form="breweryForm" 
      @close="isAddBreweryModalOpen = false" 
      @submit="submitNewBrewery" 
    />

    <AddBeerModal 
      :show="isAddBeerModalOpen" 
      :isEditing="false" 
      :form="beerForm" 
      @close="isAddBeerModalOpen = false" 
      @submit="submitNewBeer" 
    />
  </div>
</template>

<script setup>
import { onMounted, computed, watch } from 'vue'
import { useRouter } from 'vue-router'
import { storeToRefs } from 'pinia'
import { PlusCircleIcon, HistoryIcon, BeerIcon } from 'lucide-vue-next'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '../stores/auth'
import { useCatalogStore } from '../stores/catalog'

import BaseButton from '../components/BaseButton.vue'
import BaseLoader from '../components/BaseLoader.vue'
import BasePanel from '../components/BasePanel.vue'
import BaseEmptyState from '../components/BaseEmptyState.vue'
import StatsBoard from '../components/StatsBoard.vue'
import HistoryList from '../components/HistoryList.vue'
import CheckInModal from '../components/modals/CheckInModal.vue'
import DeleteConfirmModal from '../components/modals/DeleteConfirmModal.vue'
import AddLocationModal from '../components/modals/AddLocationModal.vue'
import AddBreweryModal from '../components/modals/AddBreweryModal.vue'
import AddBeerModal from '../components/modals/AddBeerModal.vue'

// Import nového composable
import { useDashboardModals } from '../composables/useDashboardModals'

const router = useRouter()
const authStore = useAuthStore()
const catalogStore = useCatalogStore()
const { tm } = useI18n()

// Data z catalogStore pro zobrazení
const { stats, history, countries, isLoading } = storeToRefs(catalogStore)

// Inicializace composable pro logiku modálních oken
const {
  isModalOpen,
  isEditModalOpen,
  isDeleteConfirmModalOpen,
  isAddLocationModalOpen,
  isAddBreweryModalOpen,
  isAddBeerModalOpen,
  form,
  editForm,
  locationForm,
  breweryForm,
  beerForm,
  openCheckInModal,
  handleMagicAddBrewery,
  handleMagicAddBeer,
  submitNewBrewery,
  submitNewBeer,
  openAddLocationFromCheckin,
  submitNewLocation,
  openEditModal,
  submitCheckIn,
  submitEdit,
  confirmDelete,
  executeDelete
} = useDashboardModals()

// Výpočet aktuálního měsíce
const currentMonthName = computed(() => {
  const months = tm('views.dashboard.months')
  return months[new Date().getMonth()]
})

// Načtení dat při namontování nebo změně uživatele
onMounted(() => { if (authStore.user) catalogStore.fetchAllData() })
watch(() => authStore.user, (newUser) => { if (newUser) catalogStore.fetchAllData() })

// Přechod do historie
const goToHistory = () => {
  router.push({ path: '/profile', query: { tab: 'history' } })
}
</script>

<style scoped>
.dashboard-layout { position: relative; min-height: 400px; }
.section-actions { display: flex; justify-content: flex-end; margin-bottom: 1.5rem; }
.dashboard-content { display: flex; flex-direction: column; gap: 2rem; }

.history-actions { display: flex; justify-content: center; margin-top: 1.5rem; }
.full-history-btn { justify-content: center; }

@media (max-width: 600px) {
  .section-actions :deep(.base-button) { width: 100%; padding: 1rem; justify-content: center; font-size: 1.1rem; }
  .full-history-btn { width: 100%; }
}
</style>
