import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useCatalogStore = defineStore('catalog', () => {
  const beers = ref([])
  const locations = ref([])
  const breweries = ref([])
  const styles = ref([])
  const stats = ref(null)
  const history = ref([])
  const isLoading = ref(true)

  // ZMĚNA: Funkce už nepotřebuje přijímat userId jako parametr
  const fetchAllData = async () => {
    isLoading.value = true
    try {
      const ts = new Date().getTime()
      
      // Získáme uložený token z prohlížeče
      const token = localStorage.getItem('pivoo_token')
      
      // Připravíme si hlavičku pro všechny GET požadavky
      const headers = { 'Authorization': `Bearer ${token}` }

      const [resBeers, resLocs, resBrews, resStyles, resStats, resHist] = await Promise.all([
        fetch(`https://www.pivoo.cz/backend/api/beers.php?t=${ts}`, { headers }),
        fetch(`https://www.pivoo.cz/backend/api/locations.php?t=${ts}`, { headers }),
        fetch(`https://www.pivoo.cz/backend/api/breweries.php?t=${ts}`, { headers }),
        fetch(`https://www.pivoo.cz/backend/api/styles.php?t=${ts}`, { headers }),
        // ZMĚNA: Z URL úplně mizí user_id! Backend si ho pozná z hlavičky (tokenu)
        fetch(`https://www.pivoo.cz/backend/api/stats.php?t=${ts}`, { headers }),
        fetch(`https://www.pivoo.cz/backend/api/history.php?t=${ts}`, { headers })
      ])

      const dataBeers = await resBeers.json()
      const dataLocs = await resLocs.json()
      const dataBrews = await resBrews.json()
      const dataStyles = await resStyles.json()
      const dataStats = await resStats.json()
      const dataHist = await resHist.json()

      if (dataBeers.status === 'success') beers.value = dataBeers.data
      if (dataLocs.status === 'success') locations.value = dataLocs.data
      if (dataBrews.status === 'success') breweries.value = dataBrews.data
      if (dataStyles.status === 'success') styles.value = dataStyles.data
      if (dataStats.status === 'success') stats.value = dataStats.stats
      if (dataHist.status === 'success') history.value = dataHist.data

    } catch (error) {
      console.error("Chyba při stahování dat ze serveru:", error)
    } finally {
      isLoading.value = false
    }
  }

  return { beers, locations, breweries, styles, stats, history, isLoading, fetchAllData }
})