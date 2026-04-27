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
    // Načtení základních dat (ponechán pouze parametr 'silent' pro skryté načtení u stolu v hospodě)
    async fetchAllData(silent = false) {
      if (this.isLoading && !silent) return
      
      if (!silent) this.isLoading = true
      this.error = null
      
      try {
        const results = await Promise.allSettled([
          apiFetch('/beers.php?limit=2000'),
          apiFetch('/breweries.php?limit=2000'),   
          apiFetch('/locations.php?limit=2000&include_all=1'),   
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
        if (!silent) this.error = 'Došlo ke kritické chybě při načítání dat.'
        console.error('Fetch error:', err)
      } finally {
        if (!silent) this.isLoading = false
      }
    },

    // Lokální aktualizace katalogů
    addBeerLocally(beer) {
      this.beers.unshift(beer)
    },
    addBreweryLocally(brewery) {
      this.breweries.unshift(brewery)
    },
    addLocationLocally(location) {
      this.locations.unshift(location)
    },
    
    // Lokální aktualizace historie a statistik
    addCheckinLocally(record) {
      this.history.unshift(record)
      if (this.history.length > 12) this.history.pop()
      
      if (this.stats) {
        this.stats.total_beers = (this.stats.total_beers || 0) + parseInt(record.quantity || 1)
        this.stats.total_liters = parseFloat((parseFloat(this.stats.total_liters || 0) + (parseFloat(record.volume || 0) * parseInt(record.quantity || 1))).toFixed(2))
        if (!record.is_free && record.price) {
           this.stats.total_price = parseFloat((parseFloat(this.stats.total_price || 0) + (parseFloat(record.price || 0) * parseInt(record.quantity || 1))).toFixed(2))
        }
      }
    },
    updateCheckinLocally(record) {
      const index = this.history.findIndex(r => r.id === record.id)
      if (index !== -1) {
        this.history[index] = { ...this.history[index], ...record }
      }
    },
    removeCheckinLocally(id) {
      this.history = this.history.filter(r => r.id !== id)
    },

    // Načítání piv s podporou filtrů a stránkování
    async fetchBeers(params = {}, append = false) {
      this.isLoading = !append
      try {
        const cleanParams = Object.fromEntries(Object.entries(params).filter(([_, v]) => v !== '' && v !== null))
        const query = new URLSearchParams(cleanParams).toString()
        
        const res = await apiFetch(`/beers.php?${query}`)
        if (res.status === 'success') {
          this.beers = append ? [...this.beers, ...res.data] : res.data
          this.beersPagination = res.pagination
        }
      } catch (error) {
        console.error('Chyba při načítání piv:', error)
      } finally {
        this.isLoading = false
      }
    },

    // Načítání pivovarů s podporou filtrů a stránkování
    async fetchBreweries(params = {}, append = false) {
      this.isLoading = !append
      try {
        const cleanParams = Object.fromEntries(Object.entries(params).filter(([_, v]) => v !== '' && v !== null))
        const query = new URLSearchParams(cleanParams).toString()
        
        const res = await apiFetch(`/breweries.php?${query}`)
        if (res.status === 'success') {
          this.breweries = append ? [...this.breweries, ...res.data] : res.data
          this.breweriesPagination = res.pagination
        }
      } catch (error) {
        console.error('Chyba při načítání pivovarů:', error)
      } finally {
        this.isLoading = false
      }
    },

    // Načítání lokací s podporou filtrů a stránkování
    async fetchLocations(params = {}, append = false) {
      this.isLoading = !append
      try {
        const cleanParams = Object.fromEntries(Object.entries(params).filter(([_, v]) => v !== '' && v !== null))
        const query = new URLSearchParams(cleanParams).toString()
        
        const res = await apiFetch(`/locations.php?${query}`)
        if (res.status === 'success') {
          this.locations = append ? [...this.locations, ...res.data] : res.data
          this.locationsPagination = res.pagination
        }
      } catch (error) {
        console.error('Chyba při načítání podniků:', error)
      } finally {
        this.isLoading = false
      }
    },

    // Metoda pro přepínání oblíbených položek
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