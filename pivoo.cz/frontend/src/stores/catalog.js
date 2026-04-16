import { defineStore } from 'pinia'
import { apiFetch } from '../api'

export const useCatalogStore = defineStore('catalog', {
  state: () => ({
    beers: [],
    breweries: [],
    locations: [],
    styles: [],      
    countries: [],   
    stats: null,
    detailedStats: null,
    history: [],
    isLoading: false,
    error: null,
  }),
  actions: {
    async fetchAllData() {
      if (this.isLoading) return
      
      this.isLoading = true
      this.error = null
      try {
        const [
          beersRes, 
          breweriesRes, 
          locationsRes, 
          stylesRes, 
          countriesRes, 
          statsRes, 
          historyRes
        ] = await Promise.all([
          apiFetch('/beers.php'),
          apiFetch('/breweries.php'),
          apiFetch('/locations.php'),
          apiFetch('/styles.php'),      
          apiFetch('/countries.php'),   
          apiFetch('/stats.php'),
          apiFetch('/history.php')
        ])

        if (beersRes.status === 'success') this.beers = beersRes.data
        if (breweriesRes.status === 'success') this.breweries = breweriesRes.data
        if (locationsRes.status === 'success') this.locations = locationsRes.data
        if (stylesRes.status === 'success') this.styles = stylesRes.data
        if (countriesRes.status === 'success') this.countries = countriesRes.data
        if (statsRes.status === 'success') this.stats = statsRes.data
        if (historyRes.status === 'success') this.history = historyRes.data
      } catch (err) {
        this.error = 'Nepodařilo se načíst data.'
        console.error('Fetch error:', err)
      } finally {
        this.isLoading = false
      }
    },

    // NOVÁ AKCE: Přepnutí oblíbeného stavu
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
          // Najdeme seznam podle typu
          const listMap = { 'beer': 'beers', 'brewery': 'breweries', 'location': 'locations' }
          const listName = listMap[entityType]
          
          // Najdeme položku v paměti a upravíme její příznak
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