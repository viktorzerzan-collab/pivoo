// frontend/src/stores/catalog.js
import { defineStore } from 'pinia'
import { apiFetch } from '../api'

export const useCatalogStore = defineStore('catalog', {
  state: () => ({
    beers: [],
    allBeers: [], // PŘIDÁNO: Kompaktní seznam pro selecty
    beersPagination: null,
    breweries: [],
    allBreweries: [], // PŘIDÁNO: Kompaktní seznam pro selecty
    breweriesPagination: null,
    locations: [],
    allLocations: [], // PŘIDÁNO: Kompaktní seznam pro selecty
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
    async fetchAllData(silent = false) {
      if (this.isLoading && !silent) return
      
      if (!silent) this.isLoading = true
      this.error = null
      
      try {
        const results = await Promise.allSettled([
          apiFetch('/beers.php'),
          apiFetch('/breweries.php'),   
          apiFetch('/locations.php?include_all=1'),   
          apiFetch('/styles.php'),      
          apiFetch('/countries.php'),   
          apiFetch('/stats.php?period=month'),
          apiFetch('/history.php'),
          // PŘIDÁNO: Paralelní stažení odlehčených (compact) dat pro formuláře
          apiFetch('/beers.php?compact=1'),
          apiFetch('/breweries.php?compact=1'),
          apiFetch('/locations.php?compact=1&include_all=1')
        ])

        const [
          beersRes, breweriesRes, locationsRes, 
          stylesRes, countriesRes, statsRes, historyRes,
          allBeersRes, allBreweriesRes, allLocationsRes
        ] = results

        if (beersRes.status === 'fulfilled' && beersRes.value.status === 'success') this.beers = beersRes.value.data
        if (breweriesRes.status === 'fulfilled' && breweriesRes.value.status === 'success') this.breweries = breweriesRes.value.data
        if (locationsRes.status === 'fulfilled' && locationsRes.value.status === 'success') this.locations = locationsRes.value.data
        if (stylesRes.status === 'fulfilled' && stylesRes.value.status === 'success') this.styles = stylesRes.value.data
        if (countriesRes.status === 'fulfilled' && countriesRes.value.status === 'success') this.countries = countriesRes.value.data
        if (statsRes.status === 'fulfilled' && statsRes.value.status === 'success') this.stats = statsRes.value.data
        if (historyRes.status === 'fulfilled' && historyRes.value.status === 'success') this.history = historyRes.value.data

        // PŘIDÁNO: Uložení kompaktních dat do storu
        if (allBeersRes.status === 'fulfilled' && allBeersRes.value.status === 'success') this.allBeers = allBeersRes.value.data
        if (allBreweriesRes.status === 'fulfilled' && allBreweriesRes.value.status === 'success') this.allBreweries = allBreweriesRes.value.data
        if (allLocationsRes.status === 'fulfilled' && allLocationsRes.value.status === 'success') this.allLocations = allLocationsRes.value.data

      } catch (err) {
        if (!silent) this.error = 'Došlo ke kritické chybě při načítání dat.'
        console.error('Fetch error:', err)
      } finally {
        if (!silent) this.isLoading = false
      }
    },

    // PŘIDÁNO: Uložení i do all* polí pro okamžitou viditelnost nově přidaných položek v menu
    addBeerLocally(beer) {
      this.beers.unshift(beer)
      this.allBeers.unshift({ id: beer.id, name: beer.name, brewery_id: beer.brewery_id, is_favorite: beer.is_favorite || 0, is_wishlist: beer.is_wishlist || 0 })
    },
    addBreweryLocally(brewery) {
      this.breweries.unshift(brewery)
      this.allBreweries.unshift({ id: brewery.id, name: brewery.name, is_favorite: brewery.is_favorite || 0, is_wishlist: brewery.is_wishlist || 0 })
    },
    addLocationLocally(location) {
      this.locations.unshift(location)
      this.allLocations.unshift({ id: location.id, name: location.name, type: location.type, city: location.city, is_favorite: location.is_favorite || 0, is_wishlist: location.is_wishlist || 0 })
    },
    
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
          const allListMap = { 'beer': 'allBeers', 'brewery': 'allBreweries', 'location': 'allLocations' }
          
          const listName = listMap[entityType]
          const allListName = allListMap[entityType]
          
          const item = this[listName].find(i => i.id == entityId)
          if (item) {
            item.is_favorite = res.is_favorite ? 1 : 0
          }

          const allItem = this[allListName].find(i => i.id == entityId)
          if (allItem) {
            allItem.is_favorite = res.is_favorite ? 1 : 0
          }

          return true
        }
      } catch (error) {
        console.error('Chyba při přepínání oblíbených:', error)
      }
      return false
    },

    async toggleWishlist(entityId, entityType) {
      try {
        const res = await apiFetch('/toggle_wishlist.php', {
          method: 'POST',
          body: JSON.stringify({ 
            entity_id: entityId, 
            entity_type: entityType 
          })
        })
        
        if (res.status === 'success') {
          const listMap = { 'beer': 'beers', 'brewery': 'breweries', 'location': 'locations' }
          const allListMap = { 'beer': 'allBeers', 'brewery': 'allBreweries', 'location': 'allLocations' }
          
          const listName = listMap[entityType]
          const allListName = allListMap[entityType]
          
          const item = this[listName].find(i => i.id == entityId)
          if (item) {
            item.is_wishlist = res.is_wishlist ? 1 : 0
          }

          const allItem = this[allListName].find(i => i.id == entityId)
          if (allItem) {
            allItem.is_wishlist = res.is_wishlist ? 1 : 0
          }

          return true
        }
      } catch (error) {
        console.error('Chyba při přepínání wishlistu:', error)
      }
      return false
    }
  }
})