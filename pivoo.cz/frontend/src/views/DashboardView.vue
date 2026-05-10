<template>
  <div class="dashboard-page">
    <div class="section-actions">
      <button class="btn-add" @click="openCheckInModal">
        <PlusCircleIcon /> {{ $t('views.dashboard.record_beers') }}
      </button>
    </div>

    <div class="dashboard-layout">
      <BaseLoader :show="isLoading" />

      <div class="dashboard-content">
        <div class="panel-card">
          <div class="panel-header">
            <h3><BeerIcon class="panel-icon" /> {{ $t('views.dashboard.me_and_beer_in') }} {{ currentMonthName }}</h3>
          </div>
          <StatsBoard :stats="stats" />
        </div>

        <div class="panel-card">
          <div class="panel-header">
            <h3><HistoryIcon class="panel-icon" /> {{ $t('views.dashboard.recent_records') }}</h3>
          </div>
          <HistoryList :history="history" @edit="openEditModal" @delete="confirmDelete" />
          
          <div v-if="!isLoading && (!history || history.length === 0)" class="empty-dashboard">
            <BeerIcon :size="48" color="#cbd5e1" stroke-width="1" />
            <p>{{ $t('views.dashboard.no_records') }}</p>
          </div>
        </div>
      </div>
    </div>

    <CheckInModal 
      :show="isModalOpen" 
      :form="form" 
      :isEditing="false"
      @close="isModalOpen = false" 
      @submit="submitCheckIn"
      @open-add-location="openAddLocationFromCheckin" 
      @magic-add-brewery="handleMagicAddBrewery"
      @magic-add-beer="handleMagicAddBeer"
    />
    
    <CheckInModal 
      :show="isEditModalOpen" 
      :form="editForm" 
      :isEditing="true"
      @close="isEditModalOpen = false" 
      @submit="submitEdit"
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
import { ref, onMounted, computed, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { PlusCircleIcon, HistoryIcon, BeerIcon } from 'lucide-vue-next'
import { useI18n } from 'vue-i18n'
import { apiFetch } from '../api'
import { useAuthStore } from '../stores/auth'
import { useCatalogStore } from '../stores/catalog'
import { useToastStore } from '../stores/toast'
import BaseLoader from '../components/BaseLoader.vue'
import StatsBoard from '../components/StatsBoard.vue'
import HistoryList from '../components/HistoryList.vue'
import CheckInModal from '../components/modals/CheckInModal.vue'
import DeleteConfirmModal from '../components/modals/DeleteConfirmModal.vue'
import AddLocationModal from '../components/modals/AddLocationModal.vue'
import AddBreweryModal from '../components/modals/AddBreweryModal.vue'
import AddBeerModal from '../components/modals/AddBeerModal.vue'

const authStore = useAuthStore()
const catalogStore = useCatalogStore()
const toastStore = useToastStore()
const { t, tm } = useI18n()

const { beers, breweries, locations, allBeers, allBreweries, allLocations, stats, history, countries, styles, isLoading } = storeToRefs(catalogStore)

const currentMonthName = computed(() => {
  const months = tm('views.dashboard.months')
  return months[new Date().getMonth()]
})

const isModalOpen = ref(false)
const isEditModalOpen = ref(false)
const isDeleteConfirmModalOpen = ref(false)
const isAddLocationModalOpen = ref(false)
const isAddBreweryModalOpen = ref(false)
const isAddBeerModalOpen = ref(false)

const recordIdToDelete = ref(null)
const selectedEditRecordId = ref(null)

const form = ref({ brewery_id: '', beer_id: '', location_id: '', consumed_at: '', packaging: 'točené', volume: '0.50', quantity: 1, price: '', currency: 'CZK', is_free: false, rating_beer: 0, rating_care: 0, note: '' })
const editForm = ref({ brewery_id: '', beer_id: '', location_id: '', consumed_at: '', packaging: 'točené', volume: '0.50', quantity: 1, price: '', currency: 'CZK', original_price: '', is_free: false, rating_beer: 0, rating_care: 0, note: '' })

const locationForm = ref({ name: '', type: 'hospoda', city: '', zip_code: '', country_id: 1, address: '', email: '', phone: '', website: '', opening_hours: '', lat: null, lng: null })
const breweryForm = ref({ name: '', city: '', zip_code: '', country_id: 1, address: '', email: '', phone: '', website: '', logoFile: null, lat: null, lng: null, is_magic: false })
const beerForm = ref({ name: '', brewery_id: '', style_id: '', epm: '', abv: '', ibu: '', ebc: '', hops: '', malts: '', fermentation: '', tags: '', is_unfiltered: false, is_unpasteurized: false, is_magic: false })

const pendingAiData = ref(null)

onMounted(() => { if (authStore.user) catalogStore.fetchAllData() })
watch(() => authStore.user, (newUser) => { if (newUser) catalogStore.fetchAllData() })

const openCheckInModal = () => {
  catalogStore.fetchAllData(true)
  isModalOpen.value = true
}

const handleMagicAddBrewery = (aiData) => {
  isModalOpen.value = false
  pendingAiData.value = aiData
  
  breweryForm.value = { 
    name: aiData.brewery_name, 
    city: aiData.brewery_metadata?.city || '', 
    address: aiData.brewery_metadata?.address || '',
    zip_code: aiData.brewery_metadata?.zip_code || '',
    country_id: aiData.brewery_metadata?.country_id || 1,
    email: aiData.brewery_metadata?.email || '',
    phone: aiData.brewery_metadata?.phone || '',
    website: aiData.brewery_metadata?.website || '',
    lat: aiData.brewery_metadata?.lat || null,
    lng: aiData.brewery_metadata?.lng || null,
    logoFile: null,
    is_magic: true 
  }
  isAddBreweryModalOpen.value = true
}

const handleMagicAddBeer = (aiData) => {
  isModalOpen.value = false
  pendingAiData.value = aiData
  beerForm.value = {
    name: aiData.beer_name,
    brewery_id: aiData.brewery_id,
    style_id: aiData.style_id || '',
    epm: aiData.epm || '',
    abv: aiData.abv || '',
    ibu: aiData.ibu || '',
    ebc: aiData.ebc || '',
    is_unfiltered: !!aiData.is_unfiltered,
    is_unpasteurized: !!aiData.is_unpasteurized,
    is_magic: true
  }
  isAddBeerModalOpen.value = true
}

const submitNewBrewery = async () => {
  try {
    const formData = new FormData()
    Object.keys(breweryForm.value).forEach(key => {
      if (breweryForm.value[key] !== null && breweryForm.value[key] !== '') formData.append(key, breweryForm.value[key])
    })
    const res = await apiFetch('/add_brewery.php', { method: 'POST', body: formData })
    if (res.status === 'success') {
      isAddBreweryModalOpen.value = false
      
      const newBrewery = {
        id: res.id,
        name: breweryForm.value.name,
        city: breweryForm.value.city,
        country_id: breweryForm.value.country_id,
        is_favorite: 0,
        avg_rating: null,
        total_beers_in_catalog: 0
      }
      catalogStore.addBreweryLocally(newBrewery)
      toastStore.showToast(t('toast.brewery_added'))
      
      if (pendingAiData.value) {
         pendingAiData.value.brewery_id = res.id
         handleMagicAddBeer(pendingAiData.value)
      }
    }
  } catch (e) { toastStore.showToast(t('toast.brewery_add_error'), 'toast-error') }
}

const submitNewBeer = async () => {
  try {
    const res = await apiFetch('/add_beer.php', { method: 'POST', body: JSON.stringify(beerForm.value) })
    if (res.status === 'success') {
      isAddBeerModalOpen.value = false
      
      const newBeer = {
        id: res.id,
        name: beerForm.value.name,
        brewery_id: beerForm.value.brewery_id,
        style_id: beerForm.value.style_id,
        is_unfiltered: beerForm.value.is_unfiltered,
        is_unpasteurized: beerForm.value.is_unpasteurized,
        avg_rating: null,
        total_checkins: 0,
        is_favorite: 0
      }
      catalogStore.addBeerLocally(newBeer)
      toastStore.showToast(t('toast.beer_added'))
      
      form.value.brewery_id = newBeer.brewery_id
      setTimeout(() => { form.value.beer_id = newBeer.id }, 100)
      
      pendingAiData.value = null
      isModalOpen.value = true
    }
  } catch (e) { toastStore.showToast(t('toast.beer_add_error'), 'toast-error') }
}

const openAddLocationFromCheckin = (coords) => {
  isModalOpen.value = false
  locationForm.value = { name: '', type: 'hospoda', city: '', zip_code: '', country_id: 1, address: '', email: '', phone: '', website: '', opening_hours: '', lat: coords?.lat || null, lng: coords?.lng || null }
  isAddLocationModalOpen.value = true
}

const submitNewLocation = async () => {
  try {
    const result = await apiFetch('/add_location.php', { method: 'POST', body: JSON.stringify(locationForm.value) })
    if (result.status === 'success') { 
      isAddLocationModalOpen.value = false
      
      const newLoc = {
         id: result.id,
         name: locationForm.value.name,
         type: locationForm.value.type,
         city: locationForm.value.city,
         country_id: locationForm.value.country_id,
         is_favorite: 0,
         avg_rating: null,
         total_visits: 0
      }
      catalogStore.addLocationLocally(newLoc)
      toastStore.showToast(t('toast.location_added'))
      
      form.value.location_id = result.id
      isModalOpen.value = true
    }
  } catch (e) { toastStore.showToast(t('toast.location_add_error'), 'toast-error') }
}

const openEditModal = async (record) => {
  if (!catalogStore.isFormDataLoaded) {
    await catalogStore.fetchFormData()
  }
  
  selectedEditRecordId.value = record.id
  const currentBeer = allBeers.value.find(b => b.id == record.beer_id)
  const prefillBreweryId = currentBeer ? currentBeer.brewery_id : ''
  editForm.value = { ...record, consumed_at: record.consumed_at || '', brewery_id: Number(prefillBreweryId), beer_id: Number(record.beer_id), location_id: Number(record.location_id), quantity: Number(record.quantity), is_free: !!Number(record.is_free), currency: record.currency || 'CZK', original_price: record.original_price || record.price }
  isEditModalOpen.value = true
}

const submitCheckIn = async () => {
  try {
    const res = await apiFetch('/checkin.php', { method: 'POST', body: JSON.stringify(form.value) })
    if (res.status === 'success') { 
      isModalOpen.value = false
      
      const beer = allBeers.value.find(b => b.id == form.value.beer_id)
      const brewery = allBreweries.value.find(b => b.id == form.value.brewery_id)
      const loc = allLocations.value.find(l => l.id == form.value.location_id)
      
      const newCheckin = {
         id: res.id,
         beer_id: form.value.beer_id,
         beer_name: beer ? beer.name : 'Neznámé pivo',
         brewery_name: brewery ? brewery.name : 'Neznámý pivovar',
         location_name: loc ? loc.name : 'Neznámý podnik',
         consumed_at: form.value.consumed_at || new Date().toISOString().slice(0, 19).replace('T', ' '),
         packaging: form.value.packaging,
         volume: form.value.volume,
         quantity: form.value.quantity,
         price: res.price,           
         currency: res.currency,     
         original_price: res.original_price, 
         is_free: form.value.is_free,
         rating_beer: form.value.rating_beer,
         rating_care: form.value.rating_care,
         note: form.value.note
      }
      catalogStore.addCheckinLocally(newCheckin)
      toastStore.showToast(t('toast.record_added'))
      
      form.value = { brewery_id: '', beer_id: '', location_id: '', consumed_at: '', packaging: 'točené', volume: '0.50', quantity: 1, price: '', currency: 'CZK', is_free: false, rating_beer: 0, rating_care: 0, note: '' }
    } else { toastStore.showToast(res.message || t('toast.record_add_error'), 'toast-error') }
  } catch (e) { toastStore.showToast(e.message || t('toast.communication_error'), 'toast-error') }
}

const submitEdit = async () => {
  try {
    const res = await apiFetch('/update_checkin.php', { method: 'POST', body: JSON.stringify({ id: selectedEditRecordId.value, ...editForm.value }) })
    if (res.status === 'success') { 
       isEditModalOpen.value = false
       
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
       toastStore.showToast(t('toast.record_edited')) 
    }
  } catch (e) { toastStore.showToast(e.message || t('toast.communication_error'), 'toast-error') }
}

const confirmDelete = (id) => { recordIdToDelete.value = id; isDeleteConfirmModalOpen.value = true }

const executeDelete = async () => {
  try {
    const res = await apiFetch('/delete_checkin.php', { method: 'POST', body: JSON.stringify({ id: recordIdToDelete.value }) })
    if (res.status === 'success') { 
      isDeleteConfirmModalOpen.value = false
      catalogStore.removeCheckinLocally(recordIdToDelete.value)
      toastStore.showToast(t('toast.record_deleted')) 
    }
  } catch (e) { toastStore.showToast(t('toast.communication_error'), 'toast-error') }
}
</script>

<style scoped>
.dashboard-layout { position: relative; min-height: 400px; }
.section-actions { display: flex; justify-content: flex-end; margin-bottom: 1.5rem; }
.btn-add { display: flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1.5rem; font-weight: 600; }
.dashboard-content { display: flex; flex-direction: column; gap: 2rem; }
.panel-card { background: var(--bg-panel); border-radius: var(--radius-md); border: 1px solid var(--border); padding: 1.5rem; transition: background-color 0.3s ease, border-color 0.3s ease; }
.panel-header { border-bottom: 1px solid var(--border); padding-bottom: 1rem; margin-bottom: 1.5rem; transition: border-color 0.3s ease; }
.panel-header h3 { margin: 0; display: flex; align-items: center; gap: 0.5rem; font-size: 1.25rem; color: var(--text-main); transition: color 0.3s ease; }
.panel-icon { color: var(--primary); }
.empty-dashboard { text-align: center; color: var(--text-muted); padding: 2rem 0; display: flex; flex-direction: column; align-items: center; gap: 0.5rem; transition: color 0.3s ease; }
@media (max-width: 600px) {
  .section-actions .btn-add { width: 100%; padding: 1rem; justify-content: center; font-size: 1.1rem; }
  .panel-card { padding: 1rem; }
}
</style>