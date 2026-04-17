// frontend/src/stores/catalog.js
import { defineStore } from 'pinia'
import { apiFetch } from '../api'

export const useCatalogStore = defineStore('catalog', {
  state: () => ({
    beers: [],
    beersPagination: null,
    breweries: [],
    breweriesPagination: null,
    locations: [],
    locationsPagination: null,
    styles: [],      
    countries: [],   
    stats: null,
    detailedStats: null,
    history: [],
    isLoading: false,
    error: null,
  }),
  actions: {
    // Zachováno primárně pro Dashboard (potřebujeme naplnit selectboxy pro CheckIn)
    async fetchAllData() {
      if (this.isLoading) return
      
      this.isLoading = true
      this.error = null
      
      try {
        const results = await Promise.allSettled([
          apiFetch('/beers.php?limit=2000'),      // Pro selecty potřebujeme maximum záznamů
          apiFetch('/breweries.php?limit=2000'),   
          apiFetch('/locations.php?limit=2000'),   
          apiFetch('/styles.php'),      
          apiFetch('/countries.php'),   
          apiFetch('/stats.php'),
          apiFetch('/history.php')
        ])

        const [
          beersRes, breweriesRes, locationsRes, 
          stylesRes, countriesRes, statsRes, historyRes
        ] = results

        if (beersRes.status === 'fulfilled' && beersRes.value.status === 'success') this.beers = beersRes.value.data
        if (breweriesRes.status === 'fulfilled' && breweriesRes.value.status === 'success') this.breweries = breweriesRes.value.data
        if (locationsRes.status === 'fulfilled' && locationsRes.value.status === 'success') this.locations = locationsRes.value.data
        if (stylesRes.status === 'fulfilled' && stylesRes.value.status === 'success') this.styles = stylesRes.value.data
        if (countriesRes.status === 'fulfilled' && countriesRes.value.status === 'success') this.countries = countriesRes.value.data
        if (statsRes.status === 'fulfilled' && statsRes.value.status === 'success') this.stats = statsRes.value.data
        if (historyRes.status === 'fulfilled' && historyRes.value.status === 'success') this.history = historyRes.value.data

      } catch (err) {
        this.error = 'Došlo ke kritické chybě při načítání dat.'
        console.error('Fetch error:', err)
      } finally {
        this.isLoading = false
      }
    },

    // --- NOVÉ AKCE PRO STRÁNKOVÁNÍ A FILTROVÁNÍ NA SERVERU ---

    async fetchBeers(params = {}) {
      this.isLoading = true
      try {
        // Odstraníme prázdné parametry (null, undefined, prázdný řetězec)
        const cleanParams = Object.fromEntries(Object.entries(params).filter(([_, v]) => v !== '' && v !== null))
        const query = new URLSearchParams(cleanParams).toString()
        
        const res = await apiFetch(`/beers.php?${query}`)
        if (res.status === 'success') {
          this.beers = res.data
          this.beersPagination = res.pagination
        }
      } catch (error) {
        console.error('Chyba při načítání piv:', error)
      } finally {
        this.isLoading = false
      }
    },

    async fetchBreweries(params = {}) {
      this.isLoading = true
      try {
        const cleanParams = Object.fromEntries(Object.entries(params).filter(([_, v]) => v !== '' && v !== null))
        const query = new URLSearchParams(cleanParams).toString()
        
        const res = await apiFetch(`/breweries.php?${query}`)
        if (res.status === 'success') {
          this.breweries = res.data
          this.breweriesPagination = res.pagination
        }
      } catch (error) {
        console.error('Chyba při načítání pivovarů:', error)
      } finally {
        this.isLoading = false
      }
    },

    async fetchLocations(params = {}) {
      this.isLoading = true
      try {
        const cleanParams = Object.fromEntries(Object.entries(params).filter(([_, v]) => v !== '' && v !== null))
        const query = new URLSearchParams(cleanParams).toString()
        
        const res = await apiFetch(`/locations.php?${query}`)
        if (res.status === 'success') {
          this.locations = res.data
          this.locationsPagination = res.pagination
        }
      } catch (error) {
        console.error('Chyba při načítání podniků:', error)
      } finally {
        this.isLoading = false
      }
    },

    // Původní metoda pro oblíbené
    async toggleFavorite(entityId, entityType) {
      try {
        const res = await apiFetch('/toggle_favorite.php', {
          method: 'POST',
          body: JSON.stringify({ 
            entity_id: entityId, 
            entity_type: entityType 
          })
        })
        
        if (res.status === 'success') {
          const listMap = { 'beer': 'beers', 'brewery': 'breweries', 'location': 'locations' }
          const listName = listMap[entityType]
          
          const item = this[listName].find(i => i.id == entityId)
          if (item) {
            item.is_favorite = res.is_favorite ? 1 : 0
          }
          return true
        }
      } catch (error) {
        console.error('Chyba při přepínání oblíbených:', error)
      }
      return false
    }
  }
})