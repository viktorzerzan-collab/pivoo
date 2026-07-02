import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { apiFetch } from '../api'
import { useCatalogStore } from '../stores/catalog'
import { useToastStore } from '../stores/toast'

export function useBeers(openDetail, openAddModal, closeAddModal) {
  const catalogStore = useCatalogStore()
  const toastStore = useToastStore()
  const { t } = useI18n()

  const beerReviews = ref([])
  const beerForm = ref({ 
    name: '', brewery_id: '', style_id: '', epm: '', abv: '', ibu: '', 
    ebc: '', hops: '', malts: '', fermentation: '', tags: '', 
    is_unfiltered: false, is_unpasteurized: false 
  })

  const initialFilters = {
    search: '', brewery: '', style: '', country: '',
    epm: { min: '', max: '' }, abv: { min: '', max: '' }, ibu: { min: '', max: '' }
  }

  const fetchAction = async (filterVals, baseParams, append) => {
    const params = {
      ...baseParams,
      search: filterVals.search,
      brewery: filterVals.brewery,
      style: filterVals.style,
      country: filterVals.country,
      epm_min: filterVals.epm.min,
      epm_max: filterVals.epm.max,
      abv_min: filterVals.abv.min,
      abv_max: filterVals.abv.max,
      ibu_min: filterVals.ibu.min,
      ibu_max: filterVals.ibu.max
    }
    await catalogStore.fetchBeers(params, append)
  }

  // Funkce, která vrátí computed vlastnost pro aktivní filtry
  const getActiveFilters = (filters, styles, addMultiChips) => computed(() => {
    const active = []
    addMultiChips(active, filters.value.search, 'search', t('catalog.search_prefix'))
    addMultiChips(active, filters.value.brewery, 'brewery', t('catalog.filter_brewery'))
    addMultiChips(active, filters.value.country, 'country', t('catalog.filter_country_short'))
    
    if (filters.value.style) {
      const s = styles.value.find(x => x.id == filters.value.style)
      if (s) active.push({ id: 'style', realKey: 'style', label: `Styl: ${s.name}` })
    }

    ['epm', 'abv', 'ibu'].forEach(key => {
      const min = filters.value[key].min
      const max = filters.value[key].max
      if (min !== '' || max !== '') {
        active.push({ 
          id: key, 
          realKey: 'range',
          rangeKey: key,
          label: `${key.toUpperCase()}: ${min !== '' ? min : '0'} - ${max !== '' ? max : '∞'}` 
        })
      }
    })
    return active
  })

  const sortOptions = computed(() => [
    { value: 'name_asc', label: t('catalog.sort.name_asc') },
    { value: 'name_desc', label: t('catalog.sort.name_desc') },
    { value: 'brewery_asc', label: t('catalog.sort.brewery_asc') },
    { value: 'brewery_desc', label: t('catalog.sort.brewery_desc') },
    { value: 'style_asc', label: t('catalog.sort.style_asc') },
    { value: 'style_desc', label: t('catalog.sort.style_desc') },
    { value: 'rating_desc', label: t('catalog.sort.rating_desc') },
    { value: 'rating_asc', label: t('catalog.sort.rating_asc') },
    { value: 'abv_desc', label: t('catalog.sort.abv_desc') },
    { value: 'abv_asc', label: t('catalog.sort.abv_asc') },
    { value: 'epm_desc', label: t('catalog.sort.epm_desc') },
    { value: 'epm_asc', label: t('catalog.sort.epm_asc') },
    { value: 'ibu_desc', label: t('catalog.sort.ibu_desc') },
    { value: 'ibu_asc', label: t('catalog.sort.ibu_asc') },
    { value: 'newest', label: t('catalog.sort.newest') },
    { value: 'oldest', label: t('catalog.sort.oldest') }
  ])

  const handleOpenAddModal = () => {
    beerForm.value = { 
      name: '', brewery_id: '', style_id: '', epm: '', abv: '', ibu: '', 
      ebc: '', hops: '', malts: '', fermentation: '', tags: '', 
      is_unfiltered: false, is_unpasteurized: false 
    }
    openAddModal()
  }

  const submitBeer = async () => {
    try {
      const res = await apiFetch('/add_beer.php', { method: 'POST', body: JSON.stringify(beerForm.value) })
      if (res.status === 'success') { 
        closeAddModal()
        catalogStore.addBeerLocally({ id: res.id, ...beerForm.value })
        toastStore.showToast(t('toast.beer_added'))
      }
    } catch (e) { 
      toastStore.showToast(t('toast.communication_error'), 'toast-error') 
    }
  }

  const handleOpenDetail = async (beer) => {
    openDetail(beer)
    beerReviews.value = [] 
    try {
      const res = await apiFetch(`/beer_reviews.php?beer_id=${beer.id}`)
      if (res.status === 'success') beerReviews.value = res.data
    } catch (error) { 
      console.error("Chyba při načítání recenzí", error) 
    }
  }

  return {
    initialFilters,
    fetchAction,
    getActiveFilters,
    sortOptions,
    beerReviews,
    beerForm,
    handleOpenAddModal,
    submitBeer,
    handleOpenDetail
  }
}
