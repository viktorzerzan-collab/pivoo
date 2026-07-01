import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { apiFetch } from '../api'
import { useCatalogStore } from '../stores/catalog'
import { useToastStore } from '../stores/toast'

export function useDashboardModals() {
  const catalogStore = useCatalogStore()
  const toastStore = useToastStore()
  const { t } = useI18n()

  // Stavy modálních oken
  const isModalOpen = ref(false)
  const isEditModalOpen = ref(false)
  const isDeleteConfirmModalOpen = ref(false)
  const isAddLocationModalOpen = ref(false)
  const isAddBreweryModalOpen = ref(false)
  const isAddBeerModalOpen = ref(false)

  // Stavy pro identifikaci záznamů
  const recordIdToDelete = ref(null)
  const selectedEditRecordId = ref(null)

  // Stavy formulářů
  const form = ref({ brewery_id: '', beer_id: '', location_id: '', consumed_at: '', packaging: 'točené', volume: '0.50', quantity: 1, price: '', currency: 'CZK', is_free: false, rating_beer: 0, rating_care: 0, note: '' })
  const editForm = ref({ brewery_id: '', beer_id: '', location_id: '', consumed_at: '', packaging: 'točené', volume: '0.50', quantity: 1, price: '', currency: 'CZK', original_price: '', is_free: false, rating_beer: 0, rating_care: 0, note: '', photos: [] })

  const locationForm = ref({ name: '', type: 'hospoda', city: '', zip_code: '', country_id: 1, address: '', email: '', phone: '', website: '', opening_hours: '', lat: null, lng: null })
  const breweryForm = ref({ name: '', city: '', zip_code: '', country_id: 1, address: '', email: '', phone: '', website: '', logoFile: null, lat: null, lng: null, is_magic: false })
  const beerForm = ref({ name: '', brewery_id: '', style_id: '', epm: '', abv: '', ibu: '', ebc: '', hops: '', malts: '', fermentation: '', tags: '', is_unfiltered: false, is_unpasteurized: false, is_magic: false })

  const pendingAiData = ref(null)

  // Otevření hlavního check-in modálu
  const openCheckInModal = () => {
    catalogStore.fetchAllData(true)
    isModalOpen.value = true
  }

  // === MAGIC ADD (AI DATA) ===
  const handleMagicAddBrewery = (aiData) => {
    isModalOpen.value = false
    isEditModalOpen.value = false
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
    isEditModalOpen.value = false
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

  // === PŘIDÁVÁNÍ NOVÝCH ENTIT ===
  const submitNewBrewery = async () => {
    try {
      const formData = new FormData()
      Object.keys(breweryForm.value).forEach(key => {
        if (breweryForm.value[key] !== null && breweryForm.value[key] !== '') formData.append(key, breweryForm.value[key])
      })
      const res = await apiFetch('/add_brewery.php', { method: 'POST', body: formData })
      if (res.status === 'success') {
        isAddBreweryModalOpen.value = false
        
        const country = catalogStore.countries.find(c => c.id == breweryForm.value.country_id)
        const newBrewery = {
          id: res.id,
          name: breweryForm.value.name,
          city: breweryForm.value.city,
          country_id: breweryForm.value.country_id,
          country: country ? country.name_cz : '',
          country_code: country ? country.code : '',
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
        
        const style = catalogStore.styles.find(s => s.id == beerForm.value.style_id)
        const newBeer = {
          id: res.id,
          name: beerForm.value.name,
          brewery_id: beerForm.value.brewery_id,
          style_id: beerForm.value.style_id,
          style: style ? style.name : '',
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
    isEditModalOpen.value = false
    locationForm.value = { name: '', type: 'hospoda', city: '', zip_code: '', country_id: 1, address: '', email: '', phone: '', website: '', opening_hours: '', lat: coords?.lat || null, lng: coords?.lng || null }
    isAddLocationModalOpen.value = true
  }

  const submitNewLocation = async () => {
    try {
      const result = await apiFetch('/add_location.php', { method: 'POST', body: JSON.stringify(locationForm.value) })
      if (result.status === 'success') { 
        isAddLocationModalOpen.value = false
        
        const country = catalogStore.countries.find(c => c.id == locationForm.value.country_id)
        const newLoc = {
           id: result.id,
           name: locationForm.value.name,
           type: locationForm.value.type,
           city: locationForm.value.city,
           country_id: locationForm.value.country_id,
           country: country ? country.name_cz : '',
           country_code: country ? country.code : '',
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

  // === OBSLUHA ZÁZNAMŮ (CHECK-IN) ===
  const openEditModal = async (record) => {
    if (!catalogStore.isFormDataLoaded) {
      await catalogStore.fetchFormData()
    }
    
    selectedEditRecordId.value = record.id
    const currentBeer = catalogStore.allBeers.find(b => b.id == record.beer_id)
    const prefillBreweryId = currentBeer ? currentBeer.brewery_id : ''
    
    editForm.value = { 
      ...record, 
      photos: record.photos ? [...record.photos] : [], 
      consumed_at: record.consumed_at || '', 
      brewery_id: Number(prefillBreweryId), 
      beer_id: Number(record.beer_id), 
      location_id: Number(record.location_id), 
      quantity: Number(record.quantity), 
      is_free: !!Number(record.is_free), 
      currency: record.currency || 'CZK', 
      original_price: record.original_price || record.price 
    }
    isEditModalOpen.value = true
  }

  const submitCheckIn = async (photoData) => {
    try {
      const formData = new FormData()
      Object.keys(form.value).forEach(key => {
        if (form.value[key] !== null && form.value[key] !== '') {
          formData.append(key, form.value[key])
        }
      })
      
      if (photoData && photoData.newPhotos) {
        photoData.newPhotos.forEach(file => formData.append('photos[]', file))
      }

      const res = await apiFetch('/checkin.php', { method: 'POST', body: formData })
      if (res.status === 'success') { 
        isModalOpen.value = false
        
        const beer = catalogStore.allBeers.find(b => b.id == form.value.beer_id)
        const brewery = catalogStore.allBreweries.find(b => b.id == form.value.brewery_id)
        const loc = catalogStore.allLocations.find(l => l.id == form.value.location_id)
        
        const newCheckin = {
           id: res.id,
           beer_id: form.value.beer_id,
           brewery_id: form.value.brewery_id,
           beer_name: beer ? beer.name : 'Neznámé pivo',
           brewery_name: brewery ? brewery.name : 'Neznámý pivovar',
           location_id: form.value.location_id,
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
           note: form.value.note,
           photos: res.photos || []
        }
        catalogStore.addCheckinLocally(newCheckin)
        toastStore.showToast(t('toast.record_added'))
        
        form.value = { brewery_id: '', beer_id: '', location_id: '', consumed_at: '', packaging: 'točené', volume: '0.50', quantity: 1, price: '', currency: 'CZK', is_free: false, rating_beer: 0, rating_care: 0, note: '' }
      } else { toastStore.showToast(res.message || t('toast.record_add_error'), 'toast-error') }
    } catch (e) { toastStore.showToast(e.message || t('toast.communication_error'), 'toast-error') }
  }

  const submitEdit = async (photoData) => {
    try {
      const formData = new FormData()
      formData.append('id', selectedEditRecordId.value)
      
      Object.keys(editForm.value).forEach(key => {
        if (key !== 'photos' && editForm.value[key] !== null && editForm.value[key] !== '') {
          formData.append(key, editForm.value[key])
        }
      })
      
      if (photoData && photoData.newPhotos) {
        photoData.newPhotos.forEach(file => formData.append('photos[]', file))
      }
      if (photoData && photoData.removedPhotoIds) {
        photoData.removedPhotoIds.forEach(id => formData.append('remove_photos[]', id))
      }

      const res = await apiFetch('/update_checkin.php', { method: 'POST', body: formData })
      if (res.status === 'success') { 
         isEditModalOpen.value = false
         
         const beer = catalogStore.allBeers.find(b => b.id == editForm.value.beer_id)
         const brewery = catalogStore.allBreweries.find(b => b.id == editForm.value.brewery_id)
         const loc = catalogStore.allLocations.find(l => l.id == editForm.value.location_id)
         
         catalogStore.updateCheckinLocally({
             id: selectedEditRecordId.value,
             ...editForm.value,
             price: res.price,           
             currency: res.currency,     
             original_price: res.original_price, 
             beer_name: beer ? beer.name : 'Neznámé pivo',
             brewery_name: brewery ? brewery.name : 'Neznámý pivovar',
             location_name: loc ? loc.name : 'Neznámý podnik',
             photos: res.photos || []
         })
         toastStore.showToast(t('toast.record_edited')) 
      }
    } catch (e) { toastStore.showToast(e.message || t('toast.communication_error'), 'toast-error') }
  }

  const confirmDelete = (id) => { 
    recordIdToDelete.value = id; 
    isDeleteConfirmModalOpen.value = true 
  }

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

  return {
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
  }
}
