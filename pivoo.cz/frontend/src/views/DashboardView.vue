<template>
  <div class="dashboard-wrapper">
    <transition name="toast-fade"><div v-if="toast.show" class="toast-notification" :class="toast.type">{{ toast.message }}</div></transition>

    <header class="app-header">
      <div class="logo">🍻 Pivoo.cz</div>
      <div class="user-panel">
        <span class="greeting">Přihlášen: <strong>{{ user?.name }}</strong></span>
        <BaseButton variant="logout" @click="handleLogout">Odhlásit</BaseButton>
      </div>
    </header>

    <main class="dashboard-content">
      <div class="action-section">
        <BaseButton variant="primary" @click="isModalOpen = true">🍺 ZAPSAT PIVO</BaseButton>
        <BaseButton variant="secondary" @click="isAddBeerModalOpen = true">➕ Přidat do katalogu</BaseButton>
      </div>

      <div class="welcome-section"><h2>Moje pivní statistiky</h2></div>
      <StatsBoard :stats="stats" />
      <HistoryList :history="history" @edit="openEditModal" @delete="confirmDelete" />
      <hr class="divider" />

      <div class="catalog-header">
        <div class="welcome-section"><h2>Katalog piv</h2></div>
        <SearchBox v-model:searchQuery="searchQuery" v-model:sortBy="sortBy" />
      </div>

      <div v-if="isLoading" class="loading">Točíme piva ze sklepa... ⏳</div>
      <div v-else>
        <div class="beers-grid" v-if="filteredBeers.length > 0">
          <BeerCard v-for="beer in filteredBeers" :key="beer.id" :beer="beer" @showDetail="openBeerDetail" />
        </div>
        <div v-else class="empty-state"><h3>Nic jsme nenašli 🍺</h3></div>
      </div>
    </main>

    <CheckInModal :show="isModalOpen" :beers="beers" :locations="locations" :form="form" @close="isModalOpen = false" @submit="submitCheckIn" />
    <AddBeerModal :show="isAddBeerModalOpen" :breweries="breweries" :form="newBeerForm" @close="isAddBeerModalOpen = false" @submit="submitNewBeer" />
    <EditCheckInModal :show="isEditModalOpen" :beers="beers" :locations="locations" :form="editForm" @close="isEditModalOpen = false" @submit="submitEdit" />
    <DeleteConfirmModal :show="isDeleteConfirmModalOpen" @close="isDeleteConfirmModalOpen = false" @confirm="executeDelete" />
    <BeerDetailModal :show="isBeerDetailModalOpen" :beer="selectedBeer" :reviews="beerReviews" @close="isBeerDetailModalOpen = false" />
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { storeToRefs } from 'pinia'

// NAŠE SKLADY (PINIA)
import { useAuthStore } from '../stores/auth'
import { useCatalogStore } from '../stores/catalog'

// UNIVERZÁLNÍ KOMPONENTY
import BaseButton from '../components/BaseButton.vue'
import SearchBox from '../components/SearchBox.vue'
import BeerCard from '../components/BeerCard.vue'
import StatsBoard from '../components/StatsBoard.vue'
import HistoryList from '../components/HistoryList.vue'

// SPECIFICKÉ MODÁLY
import CheckInModal from '../components/modals/CheckInModal.vue'
import AddBeerModal from '../components/modals/AddBeerModal.vue'
import EditCheckInModal from '../components/modals/EditCheckInModal.vue'
import DeleteConfirmModal from '../components/modals/DeleteConfirmModal.vue'
import BeerDetailModal from '../components/modals/BeerDetailModal.vue'

const router = useRouter()

// 1. PŘIPOJENÍ SKLADŮ
const authStore = useAuthStore()
const catalogStore = useCatalogStore()

// 2. Vytáhneme reaktivní data ze skladu (HTML šablona zůstává nedotčená!)
const { user } = storeToRefs(authStore)
const { beers, locations, breweries, stats, history, isLoading } = storeToRefs(catalogStore)

const toast = ref({ show: false, message: '', type: 'toast-success' })
const showToast = (message, type = 'toast-success') => { toast.value = { show: true, message, type }; setTimeout(() => { toast.value.show = false }, 3000) }

const searchQuery = ref(''); const sortBy = ref('name') 
const filteredBeers = computed(() => {
  let result = beers.value
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    result = result.filter(beer => beer.name.toLowerCase().includes(query) || beer.brewery_name.toLowerCase().includes(query) || (beer.style && beer.style.toLowerCase().includes(query)))
  }
  return result.slice().sort((a, b) => {
    if (sortBy.value === 'name') return a.name.localeCompare(b.name)
    if (sortBy.value === 'rating') return (parseFloat(b.avg_rating) || 0) - (parseFloat(a.avg_rating) || 0)
    if (sortBy.value === 'abv') return (parseFloat(b.abv) || 0) - (parseFloat(a.abv) || 0)
    if (sortBy.value === 'epm') return (parseFloat(b.epm) || 0) - (parseFloat(a.epm) || 0)
    return 0
  })
})

const isModalOpen = ref(false); const isAddBeerModalOpen = ref(false); const isEditModalOpen = ref(false); const isDeleteConfirmModalOpen = ref(false); const isBeerDetailModalOpen = ref(false)
const selectedBeer = ref(null); const beerReviews = ref([]); const recordIdToDelete = ref(null); const selectedEditRecordId = ref(null)

const editForm = ref({ beer_id: '', location_id: '', volume: '0.50', quantity: 1, price: '', rating_beer: 0, note: '' })
const form = ref({ beer_id: '', location_id: '', volume: '0.50', quantity: 1, price: '', rating_beer: 0, note: '' })
const newBeerForm = ref({ name: '', brewery_id: '', style: '', epm: '', abv: '' })

onMounted(() => {
  if (user.value) {
    catalogStore.fetchAllData(user.value.id)
  } else {
    router.push('/')
  }
})

const openBeerDetail = async (beer) => {
  selectedBeer.value = beer; isBeerDetailModalOpen.value = true; beerReviews.value = []
  try {
    const res = await fetch(`https://www.pivoo.cz/backend/api/beer_reviews.php?beer_id=${beer.id}&t=${new Date().getTime()}`)
    const data = await res.json(); if (data.status === 'success') beerReviews.value = data.data
  } catch (error) { showToast("Chyba při stahování detailů.", "toast-error") }
}

const openEditModal = (record) => {
  selectedEditRecordId.value = record.id
  editForm.value = { beer_id: Number(record.beer_id), location_id: Number(record.location_id), volume: record.volume, quantity: Number(record.quantity), price: record.price ? Number(record.price) : '', rating_beer: record.rating_beer ? Number(record.rating_beer) : 0, note: record.note || '' }
  isEditModalOpen.value = true
}

const submitCheckIn = async () => {
  try {
    const res = await fetch('https://www.pivoo.cz/backend/api/checkin.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ user_id: user.value.id, ...form.value }) })
    const result = await res.json()
    if (res.ok) { isModalOpen.value = false; form.value = { beer_id: '', location_id: '', volume: '0.50', quantity: 1, price: '', rating_beer: 0, note: '' }; await catalogStore.fetchAllData(user.value.id); showToast(result.message) } else { showToast(result.message, 'toast-error') }
  } catch (error) { showToast('Chyba připojení.', 'toast-error') }
}

const submitEdit = async () => {
  try {
    const res = await fetch('https://www.pivoo.cz/backend/api/update_checkin.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id: selectedEditRecordId.value, user_id: user.value.id, ...editForm.value }) })
    const result = await res.json()
    if (res.ok) { isEditModalOpen.value = false; await catalogStore.fetchAllData(user.value.id); showToast(result.message) } else { showToast(result.message, 'toast-error') }
  } catch (error) { showToast('Chyba připojení.', 'toast-error') }
}

const confirmDelete = (recordId) => { recordIdToDelete.value = recordId; isDeleteConfirmModalOpen.value = true }
const executeDelete = async () => {
  try {
    const res = await fetch('https://www.pivoo.cz/backend/api/delete_checkin.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id: recordIdToDelete.value, user_id: user.value.id }) })
    const result = await res.json()
    if (res.ok) { isDeleteConfirmModalOpen.value = false; await catalogStore.fetchAllData(user.value.id); showToast(result.message) } else { showToast(result.message, 'toast-error') }
  } catch (error) { showToast('Chyba připojení.', 'toast-error') }
}

const submitNewBeer = async () => {
  try {
    const res = await fetch('https://www.pivoo.cz/backend/api/add_beer.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(newBeerForm.value) })
    const result = await res.json()
    if (res.ok) { isAddBeerModalOpen.value = false; newBeerForm.value = { name: '', brewery_id: '', style: '', epm: '', abv: '' }; await catalogStore.fetchAllData(user.value.id); showToast(result.message) } else { showToast(result.message, 'toast-error') }
  } catch (error) { showToast('Chyba připojení.', 'toast-error') }
}

const handleLogout = () => { authStore.logout(); router.push('/') }
</script>

<style scoped>
.toast-notification { position: fixed; bottom: 2rem; right: 2rem; padding: 1rem 1.5rem; border-radius: 8px; color: white; font-weight: bold; z-index: 9999; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.2); }
.toast-success { background-color: #10b981; }
.toast-error { background-color: #ef4444; }
.toast-fade-enter-active, .toast-fade-leave-active { transition: all 0.3s ease; }
.toast-fade-enter-from, .toast-fade-leave-to { opacity: 0; transform: translateY(20px); }

.dashboard-wrapper { min-height: 100vh; display: flex; flex-direction: column; }
.app-header { background-color: #1f2937; color: white; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
.logo { font-size: 1.5rem; font-weight: bold; color: #eab308; margin: 0; }
.user-panel { display: flex; align-items: center; gap: 1.5rem; }
.dashboard-content { flex-grow: 1; padding: 2rem; max-width: 1200px; margin: 0 auto; width: 100%; box-sizing: border-box; }
.action-section { display: flex; justify-content: center; gap: 1rem; margin-bottom: 2rem; }
.welcome-section h2 { margin-top: 0; color: #1f2937; font-size: 1.75rem; margin-bottom: 0; }
.loading { text-align: center; padding: 3rem; color: #6b7280; font-size: 1.2rem; }
.divider { border: none; height: 1px; background-color: #e5e7eb; margin: 2rem 0; }
.catalog-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
.empty-state { text-align: center; padding: 3rem; background: #f9fafb; border-radius: 12px; border: 1px dashed #d1d5db; color: #6b7280; }
.empty-state h3 { color: #1f2937; margin-top: 0; }
.beers-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem; }

@media (max-width: 600px) {
  .app-header { flex-direction: column; gap: 1rem; padding: 1rem; }
  .dashboard-content { padding: 1rem; }
  .action-section { flex-direction: column; gap: 0.75rem; }
  .catalog-header { flex-direction: column; align-items: stretch; gap: 1rem; }
  .toast-notification { bottom: 1rem; right: 1rem; left: 1rem; text-align: center; }
}
</style>