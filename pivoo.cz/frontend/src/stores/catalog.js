import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useCatalogStore = defineStore('catalog', () => {
  const beers = ref([])
  const locations = ref([])
  const breweries = ref([])
  const stats = ref(null)
  const history = ref([])
  const isLoading = ref(true)

  // Jedna mocná funkce, která stáhne úplně všechno naráz
  const fetchAllData = async (userId) => {
    isLoading.value = true
    try {
      const ts = new Date().getTime()
      // Promise.all zajistí, že se všechny dotazy na server pošlou paralelně (obrovské zrychlení)
      const [resBeers, resLocs, resBrews, resStats, resHist] = await Promise.all([
        fetch(`https://www.pivoo.cz/backend/api/beers.php?t=${ts}`),
        fetch(`https://www.pivoo.cz/backend/api/locations.php?t=${ts}`),
        fetch(`https://www.pivoo.cz/backend/api/breweries.php?t=${ts}`),
        fetch(`https://www.pivoo.cz/backend/api/stats.php?user_id=${userId}&t=${ts}`),
        fetch(`https://www.pivoo.cz/backend/api/history.php?user_id=${userId}&t=${ts}`)
      ])

      const dataBeers = await resBeers.json()
      const dataLocs = await resLocs.json()
      const dataBrews = await resBrews.json()
      const dataStats = await resStats.json()
      const dataHist = await resHist.json()

      // Jakmile dorazí data, uložíme je do našeho skladu
      if (dataBeers.status === 'success') beers.value = dataBeers.data
      if (dataLocs.status === 'success') locations.value = dataLocs.data
      if (dataBrews.status === 'success') breweries.value = dataBrews.data
      if (dataStats.status === 'success') stats.value = dataStats.stats
      if (dataHist.status === 'success') history.value = dataHist.data

    } catch (error) {
      console.error("Chyba při stahování dat ze serveru:", error)
    } finally {
      isLoading.value = false
    }
  }

  return { 
    beers, locations, breweries, stats, history, isLoading, fetchAllData 
  }
})