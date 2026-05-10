// frontend/src/stores/catalog.js
import { defineStore } from 'pinia'
import { apiFetch } from '../api'

export const useCatalogStore = defineStore('catalog', {
  state: () => ({
    beers: [],
    allBeers: [], 
    beersPagination: null,
    breweries: [],
    allBreweries: [], 
    breweriesPagination: null,
    locations: [],
    allLocations: [], 
    locationsPagination: null,
    styles: [],      
    countries: [],   
    stats: null,
    detailedStats: null,
    history: [],
    pendingApprovals: [], // Fronta ke schválení
    isLoading: false,
    isInitialLoaded: false, // Sleduje, zda už byla prvotní data načtena
    isFormDataLoaded: false, // PŘIDÁNO: Sleduje, zda se stáhly kompletní číselníky
    isLoadingFormData: false, // PŘIDÁNO: Chrání před vícenásobným spouštěním stahování
    error: null,
  }),
  actions: {
    // ZMĚNA: Přidán parametr 'force' pro možnost vynuceného obnovení
    async fetchAllData(silent = false, force = false) {
      if (this.isLoading && !silent) return
      
      // Pokud už jsou data načtena a nevyžadujeme vynucenou aktualizaci, tak zbytečně nestahujeme
      if (this.isInitialLoaded && !force) return
      
      if (!silent) this.isLoading = true
      this.error = null
      
      try {
        // ZMĚNA: Odsud zmizely 3 nejtěžší 'compact=1' requesty. Dashboard se tak načte bleskově.
        const results = await Promise.allSettled([
          apiFetch('/beers.php'),
          apiFetch('/breweries.php'),   
          apiFetch('/locations.php?include_all=1'),   
          apiFetch('/styles.php'),      
          apiFetch('/countries.php'),   
          apiFetch('/stats.php?period=month'),
          apiFetch('/history.php')
        ])

        const [beersRes, breweriesRes, locationsRes, stylesRes, countriesRes, statsRes, historyRes] = results

        if (beersRes.status === 'fulfilled' && beersRes.value.status === 'success') this.beers = beersRes.value.data
        if (breweriesRes.status === 'fulfilled' && breweriesRes.value.status === 'success') this.breweries = breweriesRes.value.data
        if (locationsRes.status === 'fulfilled' && locationsRes.value.status === 'success') this.locations = locationsRes.value.data
        if (stylesRes.status === 'fulfilled' && stylesRes.value.status === 'success') this.styles = stylesRes.value.data
        if (countriesRes.status === 'fulfilled' && countriesRes.value.status === 'success') this.countries = countriesRes.value.data
        if (statsRes.status === 'fulfilled' && statsRes.value.status === 'success') this.stats = statsRes.value.data
        if (historyRes.status === 'fulfilled' && historyRes.value.status === 'success') this.history = historyRes.value.data

        this.isInitialLoaded = true

        // PŘIDÁNO: Nyní odstartujeme stahování "těžkých" dat formulářů na pozadí, aniž by to blokovalo UI (nečekáme na await)
        this.fetchFormData(force)

      } catch (err) {
        if (!silent) this.error = 'Došlo ke kritické chybě při načítání dat.'
        console.error('Fetch error:', err)
      } finally {
        if (!silent) this.isLoading = false
      }
    },

    // PŘIDÁNO: Samostatná funkce pro načtení obřích seznamů pro formuláře
    async fetchFormData(force = false) {
      if (this.isLoadingFormData) return
      if (this.isFormDataLoaded && !force) return

      this.isLoadingFormData = true
      try {
        const results = await Promise.allSettled([
          apiFetch('/beers.php?compact=1'),
          apiFetch('/breweries.php?compact=1'),
          apiFetch('/locations.php?compact=1&include_all=1')
        ])

        const [allBeersRes, allBreweriesRes, allLocationsRes] = results

        if (allBeersRes.status === 'fulfilled' && allBeersRes.value.status === 'success') this.allBeers = allBeersRes.value.data
        if (allBreweriesRes.status === 'fulfilled' && allBreweriesRes.value.status === 'success') this.allBreweries = allBreweriesRes.value.data
        if (allLocationsRes.status === 'fulfilled' && allLocationsRes.value.status === 'success') this.allLocations = allLocationsRes.value.data
        
        this.isFormDataLoaded = true
      } catch (err) {
        console.error('Chyba při načítání dat pro formuláře:', err)
      } finally {
        this.isLoadingFormData = false
      }
    },

    async fetchPendingApprovals() {
      this.isLoading = true
      try {
        const [beersRes, breweriesRes, locsRes] = await Promise.all([
          apiFetch('/beers.php?status=pending&limit=100'),
          apiFetch('/breweries.php?status=pending&limit=100'),
          apiFetch('/locations.php?status=pending&limit=100&include_all=1')
        ])

        const pending = []
        if (beersRes.status === 'success') {
          pending.push(...beersRes.data.map(b => ({ ...b, entity_type: 'beer' })))
        }
        if (breweriesRes.status === 'success') {
          pending.push(...breweriesRes.data.map(b => ({ ...b, entity_type: 'brewery' })))
        }
        if (locsRes.status === 'success') {
          pending.push(...locsRes.data.map(l => ({ ...l, entity_type: 'location' })))
        }

        this.pendingApprovals = pending.sort((a, b) => (a.name || '').localeCompare(b.name || '', 'cs'))
      } catch (err) {
        console.error('Chyba při načítání fronty schvalování:', err)
      } finally {
        this.isLoading = false
      }
    },

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

          // Pokud se oblíbenost mění, doporučuje se formulářová data při nejbližší příležitosti obnovit
          this.isFormDataLoaded = false;
          
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