import { defineStore } from 'pinia'
import { ref } from 'vue'
import { apiFetch } from '../api' // Importujeme naši novou funkci

export const useCatalogStore = defineStore('catalog', () => {
  const beers = ref([])
  const locations = ref([])
  const breweries = ref([])
  const styles = ref([])
  const stats = ref(null)
  const history = ref([])
  const isLoading = ref(true)

  const fetchAllData = async () => {
    isLoading.value = true
    try {
      const ts = new Date().getTime()
      
      // Voláme API elegantně a krátce
      const [dataBeers, dataLocs, dataBrews, dataStyles, dataStats, dataHist] = await Promise.all([
        apiFetch(`/beers.php?t=${ts}`),
        apiFetch(`/locations.php?t=${ts}`),
        apiFetch(`/breweries.php?t=${ts}`),
        apiFetch(`/styles.php?t=${ts}`),
        apiFetch(`/stats.php?t=${ts}`),
        apiFetch(`/history.php?t=${ts}`)
      ])

      if (dataBeers.status === 'success') beers.value = dataBeers.data
      if (dataLocs.status === 'success') locations.value = dataLocs.data
      if (dataBrews.status === 'success') breweries.value = dataBrews.data
      if (dataStyles.status === 'success') styles.value = dataStyles.data
      if (dataStats.status === 'success') stats.value = dataStats.stats
      if (dataHist.status === 'success') history.value = dataHist.data

    } catch (error) {
      console.error("Chyba při stahování dat:", error)
    } finally {
      isLoading.value = false
    }
  }

  return { beers, locations, breweries, styles, stats, history, isLoading, fetchAllData }
})