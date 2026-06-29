import { ref, computed, watch, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { apiFetch } from '../api'
import { useAuthStore } from '../stores/auth'
import { useToastStore } from '../stores/toast'

export function useStatistics() {
  const authStore = useAuthStore()
  const toastStore = useToastStore()
  const { t, tm, te } = useI18n()

  // Základní stavy
  const isLoading = ref(true)
  const scope = ref('me')

  const periodSelection = ref({
    mode: 'month',
    from: null,
    to: null,
    year: new Date().getFullYear(),
    month: new Date().getMonth()
  })

  const scopeOptions = computed(() => [
    { value: 'me', label: t('statistics.scope_me') },
    { value: 'global', label: t('statistics.scope_global') }
  ])

  // Data pro statistiky
  const statsData = ref({
    beers: [], breweries: [], locations: [], 
    top_rated_beers: [], top_rated_locations: [], 
    days: [], months: [], month_days: [],
    collector: { unique_count: 0, total_count: 0 },
    overview: null,
    styles: [], prices: { avg_price: 0, min_price: 0, max_price: 0 },
    price_details: { min_beer: null, max_beer: null }
  })

  // Stav pro detailní modál
  const isDetailModalVisible = ref(false)
  const detailModalItem = ref({})
  const detailModalType = ref('')
  const detailModalReviews = ref([])

  const openDetailModal = async (item) => {
    if (!item || !item.id || !item.type) return

    detailModalType.value = item.type
    detailModalItem.value = {}
    detailModalReviews.value = []
    isDetailModalVisible.value = true

    try {
      let endpoint = ''
      if (item.type === 'beer') endpoint = `/beers.php?id=${item.id}`
      else if (item.type === 'brewery') endpoint = `/breweries.php?id=${item.id}`
      else if (item.type === 'location') endpoint = `/locations.php?id=${item.id}`

      const res = await apiFetch(endpoint)
      if (res.status === 'success' && res.data) {
        detailModalItem.value = Array.isArray(res.data) ? res.data[0] : res.data
      }

      if (item.type === 'beer') {
        const revRes = await apiFetch(`/beer_reviews.php?beer_id=${item.id}`)
        if (revRes.status === 'success' && revRes.data) {
          detailModalReviews.value = revRes.data
        }
      }
    } catch (error) {
      console.error("Nepodařilo se načíst detail:", error)
      toastStore.showToast(t('toast.communication_error'), 'toast-error')
      isDetailModalVisible.value = false
    }
  }

  // Měny a kurzy
  const userCurrency = computed(() => authStore.defaultCurrency || 'CZK')
  const exchangeRate = ref(1.0)
  const isLoadingRate = ref(false)

  const fetchRate = async () => {
    if (userCurrency.value === 'CZK') {
      exchangeRate.value = 1.0;
      return;
    }
    isLoadingRate.value = true;
    try {
      const res = await fetch(`https://cdn.jsdelivr.net/npm/@fawazahmed0/currency-api@latest/v1/currencies/czk.json`);
      const data = await res.json();
      const rate = data.czk[userCurrency.value.toLowerCase()];
      if (rate) {
        exchangeRate.value = rate;
      }
    } catch (e) {
      console.error("Nepodařilo se načíst kurz pro statistiky:", e);
    } finally {
      isLoadingRate.value = false;
    }
  }

  const avgPrice = computed(() => Math.round((statsData.value.prices?.avg_price || 0) * exchangeRate.value))
  const minPrice = computed(() => Math.round((statsData.value.prices?.min_price || 0) * exchangeRate.value))
  const maxPrice = computed(() => Math.round((statsData.value.prices?.max_price || 0) * exchangeRate.value))

  // Pomocné funkce pro překlady
  const translateDynamic = (val) => {
    if (!val) return val
    const key = `dynamic.locations.${val}`
    return te(key) ? t(key) : val
  }

  const translateStyle = (val) => {
    if (!val) return t('cards.no_style')
    const key = `dynamic.styles.${val}`
    return te(key) ? t(key) : val
  }

  // Výpočty pro grafy a karty
  const collectorPercent = computed(() => {
    if (!statsData.value.collector || statsData.value.collector.total_count == 0) return 0
    return Math.round((statsData.value.collector.unique_count / statsData.value.collector.total_count) * 100)
  })

  const dayActivity = computed(() => {
    const dayNames = tm('days')
    const labels = [dayNames.monday, dayNames.tuesday, dayNames.wednesday, dayNames.thursday, dayNames.friday, dayNames.saturday, dayNames.sunday].map(d => d.substring(0, 2))
    
    if (!statsData.value.days || statsData.value.days.length === 0) return []
    const maxVal = Math.max(...statsData.value.days.map(d => parseInt(d.count)), 0)
    return labels.map((name, index) => {
      const dbDay = statsData.value.days.find(d => parseInt(d.day_index) === index)
      const count = dbDay ? parseInt(dbDay.count) : 0
      return { label: name, count: count, percent: maxVal > 0 ? (count / maxVal) * 100 : 0, isWeekend: index >= 5 }
    })
  })

  const monthActivity = computed(() => {
    const monthNames = tm('months_short')
    const labels = [
      monthNames.jan, monthNames.feb, monthNames.mar, monthNames.apr, 
      monthNames.may, monthNames.jun, monthNames.jul, monthNames.aug, 
      monthNames.sep, monthNames.oct, monthNames.nov, monthNames.dec
    ]
    
    if (!statsData.value.months || statsData.value.months.length === 0) return []
    const maxVal = Math.max(...statsData.value.months.map(m => parseInt(m.count)), 0)
    return labels.map((name, index) => {
      const dbMonth = statsData.value.months.find(m => parseInt(m.month_index) === (index + 1))
      const count = dbMonth ? parseInt(dbMonth.count) : 0
      return { label: name, count: count, percent: maxVal > 0 ? (count / maxVal) * 100 : 0 }
    })
  })

  const monthDaysActivity = computed(() => {
    if (periodSelection.value.mode !== 'month' || periodSelection.value.year === null) return []

    const daysInMonth = new Date(periodSelection.value.year, periodSelection.value.month + 1, 0).getDate()
    const labels = Array.from({ length: daysInMonth }, (_, i) => String(i + 1))

    if (!statsData.value.month_days || statsData.value.month_days.length === 0) return []
    const maxVal = Math.max(...statsData.value.month_days.map(d => parseInt(d.count)), 0)

    return labels.map((label, index) => {
      const dayNum = index + 1
      const dbDay = statsData.value.month_days.find(d => parseInt(d.day_index) === dayNum)
      const count = dbDay ? parseInt(dbDay.count) : 0
      return {
        label: label,
        count: count,
        percent: maxVal > 0 ? (count / maxVal) * 100 : 0
      }
    })
  })

  // Hlavní funkce pro načtení dat
  const fetchDetailedStats = async () => {
    isLoading.value = true
    try {
      const { from, to } = periodSelection.value
      let url = `/detailed_stats.php?scope=${scope.value}`
      
      if (from && to) {
        url += `&date_from=${from}&date_to=${to}`
      }

      const res = await apiFetch(url)
      if (res.status === 'success') {
        statsData.value = res.data
      }
    } catch (error) {
      toastStore.showToast(t('toast.communication_error'), 'toast-error')
    } finally {
      isLoading.value = false
    }
  }

  // Watchery a lifecycle hooks
  watch([periodSelection, scope], () => fetchDetailedStats(), { deep: true })
  watch(userCurrency, () => fetchRate())

  onMounted(() => {
    fetchRate()
  })

  return {
    isLoading,
    scope,
    scopeOptions,
    periodSelection,
    statsData,
    
    isDetailModalVisible,
    detailModalItem,
    detailModalType,
    detailModalReviews,
    openDetailModal,
    
    userCurrency,
    isLoadingRate,
    avgPrice,
    minPrice,
    maxPrice,
    
    translateDynamic,
    translateStyle,
    collectorPercent,
    dayActivity,
    monthActivity,
    monthDaysActivity
  }
}
