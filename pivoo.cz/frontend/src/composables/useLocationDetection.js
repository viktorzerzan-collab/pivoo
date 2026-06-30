import { ref } from 'vue'
import { apiFetch } from '../api'

export function useLocationDetection(catalogStore, form, t, translateLocation) {
  const isLocating = ref(false)
  const locationMessage = ref('')
  const locationMessageType = ref('')
  const tempCoords = ref(null)
  
  const showNearbyModal = ref(false)
  const nearbyLocations = ref([])

  const calculateDistance = (lat1, lon1, lat2, lon2) => {
    const R = 6371
    const dLat = (lat2 - lat1) * Math.PI / 180
    const dLon = (lon2 - lon1) * Math.PI / 180
    const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
              Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
              Math.sin(dLon/2) * Math.sin(dLon/2)
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a))
    return R * c
  }

  const autodetectLocation = () => {
    if (!navigator.geolocation) {
      locationMessage.value = t('modals.checkin.geolocation_not_supported')
      locationMessageType.value = 'error'
      return
    }
    
    isLocating.value = true
    locationMessage.value = ''
    
    navigator.geolocation.getCurrentPosition(
      async (pos) => {
        const lat = pos.coords.latitude
        const lng = pos.coords.longitude
        tempCoords.value = { lat, lng }

        const nearby = []

        // Běžné vyhledávání okolních podniků
        catalogStore.allLocations.forEach(loc => {
          if (loc.lat && loc.lng && loc.type !== 'mesto') {
            const dist = calculateDistance(lat, lng, loc.lat, loc.lng)
            // Zvýšený limit na 150 metrů (0.150 km)
            if (dist <= 0.150) {
              nearby.push({ ...loc, distance: dist })
            }
          }
        })

        // Pokusit se zjistit obec přes Nominatim API a případně ji rovnou založit
        try {
          const res = await fetch(`https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lng}&format=json&addressdetails=1`, {
            headers: {
              'Accept-Language': 'cs-CZ,cs;q=0.9'
            }
          })
          const data = await res.json()
          
          if (data && data.address) {
            const cityName = data.address.city || data.address.town || data.address.village || data.address.municipality || data.address.suburb || data.address.hamlet
            
            if (cityName) {
              let cityLoc = catalogStore.allLocations.find(l => l.type === 'mesto' && l.name === cityName)
              
              if (!cityLoc) {
                const zipCode = data.address.postcode || ''
                let countryId = null
                
                if (data.address.country_code && catalogStore.countries && catalogStore.countries.length) {
                  const cCode = data.address.country_code.toLowerCase()
                  const foundCountry = catalogStore.countries.find(c => 
                    (c.code && c.code.toLowerCase() === cCode) || 
                    (c.iso && c.iso.toLowerCase() === cCode)
                  )
                  if (foundCountry) {
                    countryId = foundCountry.id
                  }
                }

                const createRes = await apiFetch('/add_location.php', {
                  method: 'POST',
                  body: JSON.stringify({
                    name: cityName,
                    type: 'mesto',
                    zip_code: zipCode,
                    country_id: countryId,
                    lat: lat,
                    lng: lng
                  })
                })
                
                if (createRes.status === 'success') {
                  cityLoc = {
                    // ZDE JE OPRAVA: API vrací ID přímo v response objektu, případně se podíváme do data fallbackem
                    id: createRes.id || createRes.data?.id,
                    name: cityName,
                    type: 'mesto',
                    zip_code: zipCode,
                    country_id: countryId,
                    lat: lat,
                    lng: lng,
                    is_favorite: 0,
                    is_wishlist: 0
                  }
                  catalogStore.addLocationLocally(cityLoc)
                }
              }
              
              if (cityLoc) {
                if (!nearby.some(n => n.id === cityLoc.id)) {
                  nearby.push({ ...cityLoc, distance: 0 })
                }
              }
            }
          }
        } catch (e) {
          console.warn("Nepodařilo se zjistit obec pro Check-in:", e)
        }

        nearby.sort((a, b) => a.distance - b.distance)

        isLocating.value = false

        if (nearby.length > 0) {
          nearbyLocations.value = nearby
          showNearbyModal.value = true
          
          if (nearby.length === 1) {
            locationMessage.value = t('modals.checkin.found_location', { 
              name: translateLocation(nearby[0].name), 
              dist: nearby[0].type === 'mesto' ? 'Město' : (nearby[0].distance * 1000).toFixed(0) 
            })
          } else {
            locationMessage.value = t('modals.checkin.multiple_locations')
          }
          locationMessageType.value = 'success'
        } else {
          form.location_id = ''
          locationMessage.value = t('modals.checkin.no_locations')
          locationMessageType.value = 'warning'
        }
      },
      (err) => {
        isLocating.value = false
        locationMessage.value = t('modals.checkin.location_error')
        locationMessageType.value = 'error'
      },
      { enableHighAccuracy: true, timeout: 10000 }
    )
  }

  const selectNearbyLocation = (loc) => {
    form.location_id = loc.id
    locationMessage.value = t('modals.checkin.selected_location', { 
      name: translateLocation(loc.name), 
      dist: loc.type === 'mesto' ? 'Město' : (loc.distance * 1000).toFixed(0) 
    })
    locationMessageType.value = 'success'
    showNearbyModal.value = false
  }
  
  const resetLocationState = () => {
    locationMessage.value = ''
    showNearbyModal.value = false
    nearbyLocations.value = []
  }

  return {
    isLocating,
    locationMessage,
    locationMessageType,
    tempCoords,
    showNearbyModal,
    nearbyLocations,
    autodetectLocation,
    selectNearbyLocation,
    resetLocationState
  }
}
