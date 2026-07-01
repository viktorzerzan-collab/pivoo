import { ref } from 'vue'
import { apiFetch } from '../api' // Upravte cestu podle reálné struktury, např. '@/api' pokud používáte alias

export function useAdmin() {
  // Data states
  const allUsers = ref([])
  const allBarcodes = ref([])
  const adminBeers = ref([])
  const adminBreweries = ref([])
  const adminLocations = ref([])

  // Loading states
  const isUsersLoading = ref(false)
  const isBarcodesLoading = ref(false)
  const isAdminDataLoading = ref(false)

  // Metody pro načítání dat
  const fetchUsers = async () => {
    isUsersLoading.value = true
    try {
      const res = await apiFetch('/users.php')
      if (res.status === 'success') allUsers.value = res.data
    } catch (e) {
      console.error("Chyba při načítání uživatelů", e)
    } finally {
      isUsersLoading.value = false
    }
  }

  const fetchBarcodes = async () => {
    isBarcodesLoading.value = true
    try {
      const res = await apiFetch('/barcodes.php')
      if (res.status === 'success') allBarcodes.value = res.data
    } catch (e) {
      console.error("Chyba při načítání čárových kódů", e)
    } finally {
      isBarcodesLoading.value = false
    }
  }

  const loadAdminData = async () => {
    isAdminDataLoading.value = true
    try {
      const [beersRes, breweriesRes, locsRes] = await Promise.all([
        apiFetch('/beers.php?limit=10000'),
        apiFetch('/breweries.php?limit=10000'),
        apiFetch('/locations.php?limit=10000&include_all=1')
      ])
      if (beersRes.status === 'success') adminBeers.value = beersRes.data
      if (breweriesRes.status === 'success') adminBreweries.value = breweriesRes.data
      if (locsRes.status === 'success') adminLocations.value = locsRes.data
    } catch (e) {
      console.error("Chyba při načítání admin dat", e)
    } finally {
      isAdminDataLoading.value = false
    }
  }

  return {
    // Refs
    allUsers,
    allBarcodes,
    adminBeers,
    adminBreweries,
    adminLocations,
    isUsersLoading,
    isBarcodesLoading,
    isAdminDataLoading,
    
    // Methods
    fetchUsers,
    fetchBarcodes,
    loadAdminData
  }
}
