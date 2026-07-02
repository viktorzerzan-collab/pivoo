import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { apiFetch } from '../api'
import { useCatalogStore } from '../stores/catalog'
import { useToastStore } from '../stores/toast'

export function useLocations(openAddModal, closeAddModal, currentPage) {
  const catalogStore = useCatalogStore()
  const toastStore = useToastStore()
  const { t } = useI18n()

  const form = ref({ 
    name: '', type: 'hospoda', city: '', zip_code: '', country_id: 1, 
    address: '', email: '', phone: '', website: '', opening_hours: '', 
    lat: null, lng: null 
  })

  const initialFilters = { search: '', city: '', country: '' }

  const fetchAction = async (filterVals, baseParams, append) => {
    const params = {
      ...baseParams,
      search: filterVals.search,
      city: filterVals.city,
      country: filterVals.country,
      exclude_types: 'mesto,jine'
    }
    await catalogStore.fetchLocations(params, append)
  }

  const getActiveFilters = (filters, addMultiChips) => computed(() => {
    const active = []
    addMultiChips(active, filters.value.search, 'search', t('catalog.search_prefix'))
    addMultiChips(active, filters.value.city, 'city', t('catalog.filter_city'))
    addMultiChips(active, filters.value.country, 'country', t('catalog.filter_country_short'))
    return active
  })

  const sortOptions = computed(() => [
    { value: 'name_asc', label: t('catalog.sort.name_asc') },
    { value: 'name_desc', label: t('catalog.sort.name_desc') },
    { value: 'city_asc', label: t('catalog.sort.city_asc') },
    { value: 'city_desc', label: t('catalog.sort.city_desc') },
    { value: 'rating_desc', label: t('catalog.sort.rating_desc') },
    { value: 'rating_asc', label: t('catalog.sort.rating_asc') },
    { value: 'newest', label: t('catalog.sort.newest') },
    { value: 'oldest', label: t('catalog.sort.oldest') }
  ])

  const handleOpenAddModal = () => {
    form.value = { 
      name: '', type: 'hospoda', city: '', zip_code: '', country_id: 1, 
      address: '', email: '', phone: '', website: '', opening_hours: '', 
      lat: null, lng: null 
    }
    openAddModal()
  }

  const submitLocation = async () => {
    try {
      const result = await apiFetch('/add_location.php', { method: 'POST', body: JSON.stringify(form.value) })
      if (result.status === 'success') { 
        closeAddModal()
        const country = catalogStore.countries.find(c => c.id == form.value.country_id)
        catalogStore.addLocationLocally({
           id: result.id,
           name: form.value.name,
           type: form.value.type,
           city: form.value.city,
           country_id: form.value.country_id,
           country: country ? country.name_cz : '',
           country_code: country ? country.code : '',
           is_favorite: 0,
           avg_rating: null,
           total_visits: 0,
           address: form.value.address,
           zip_code: form.value.zip_code,
           email: form.value.email,
           phone: form.value.phone,
           website: form.value.website,
           opening_hours: form.value.opening_hours,
           lat: form.value.lat,
           lng: form.value.lng
        })
        if (currentPage) {
          currentPage.value = 1
        }
        toastStore.showToast(t('toast.location_added')) 
      } else {
        toastStore.showToast(result.message || t('toast.location_add_error'), 'toast-error')
      }
    } catch (e) { 
      toastStore.showToast(t('toast.communication_error'), 'toast-error') 
    }
  }

  return {
    form,
    initialFilters,
    fetchAction,
    getActiveFilters,
    sortOptions,
    handleOpenAddModal,
    submitLocation
  }
}
