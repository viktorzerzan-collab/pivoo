import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { apiFetch } from '../api'
import { useCatalogStore } from '../stores/catalog'
import { useToastStore } from '../stores/toast'

export function useBreweries(closeAddModal) {
  const catalogStore = useCatalogStore()
  const toastStore = useToastStore()
  const { t } = useI18n()

  const form = ref({ 
    name: '', city: '', zip_code: '', country_id: 1, 
    address: '', email: '', phone: '', website: '', logoFile: null 
  })

  const initialFilters = { search: '', city: '', country: '' }

  const fetchAction = async (filterVals, baseParams, append) => {
    const params = {
      ...baseParams,
      search: filterVals.search,
      city: filterVals.city,
      country: filterVals.country
    }
    await catalogStore.fetchBreweries(params, append)
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

  const submitBrewery = async () => {
    try {
      const formData = new FormData()
      Object.keys(form.value).forEach(key => { 
        if (form.value[key] !== null && form.value[key] !== '') {
          formData.append(key, form.value[key]) 
        }
      })
      
      const result = await apiFetch('/add_brewery.php', { method: 'POST', body: formData })
      
      if (result.status === 'success') { 
        closeAddModal()
        const country = catalogStore.countries.find(c => c.id == form.value.country_id)
        catalogStore.addBreweryLocally({
           id: result.id,
           name: form.value.name,
           city: form.value.city,
           country_id: form.value.country_id,
           country: country ? country.name_cz : '',
           country_code: country ? country.code : '',
           is_favorite: 0,
           avg_rating: null,
           total_beers_in_catalog: 0
        })
        toastStore.showToast(t('toast.brewery_added'))
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
    submitBrewery
  }
}
