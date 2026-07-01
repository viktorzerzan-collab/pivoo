import { ref, computed, watch } from 'vue'

export function useCatalogFilters(initialFilters, paginationRef, isLoadingRef, fetchAction) {
  const currentPage = ref(1)
  const itemsPerPage = 30
  const sortBy = ref('name_asc')
  const filtersOpen = ref(false)
  const isAppending = ref(false)

  // Hluboká kopie výchozích filtrů, aby se nepřepisoval originál
  const filters = ref(JSON.parse(JSON.stringify(initialFilters)))

  // Stránkování napojené na store
  const totalPages = computed(() => paginationRef.value?.total_pages || 1)
  const totalItems = computed(() => paginationRef.value?.total || 0)

  // Univerzální metoda na odstranění filtru z "čipu"
  const removeFilter = (chip) => {
    if (chip.realKey === 'range') {
      filters.value[chip.rangeKey] = { min: '', max: '' }
    } else if (chip.partValue) {
      let parts = String(filters.value[chip.realKey]).split(',').map(s => s.trim()).filter(s => s)
      filters.value[chip.realKey] = parts.filter(p => p !== chip.partValue).join(', ')
    } else {
      filters.value[chip.realKey] = ''
    }
  }

  // Spouštěč načítání dat
  const loadData = async (append = false) => {
    const baseParams = {
      page: currentPage.value,
      limit: itemsPerPage,
      sort: sortBy.value
    }
    // Zavolá externí akci předanou z komponenty a předá jí aktuální stav
    await fetchAction(filters.value, baseParams, append)
  }

  const resetFilters = () => {
    filters.value = JSON.parse(JSON.stringify(initialFilters))
    sortBy.value = 'name_asc'
    currentPage.value = 1
    loadData(false)
  }

  // Funkce pro načtení další stránky (infinite scroll). Umožňuje předat extra podmínku (např. zda jsme v 'list' režimu)
  const loadNextPage = async (additionalCondition = true) => {
    if (currentPage.value < totalPages.value && !isLoadingRef.value && !isAppending.value && additionalCondition) {
      isAppending.value = true
      currentPage.value++
      await loadData(true)
      isAppending.value = false
    }
  }

  // Automatické znovunačtení při změně filtru či řazení
  watch([filters, sortBy], () => {
    currentPage.value = 1
    loadData(false)
  }, { deep: true })

  watch(currentPage, () => {
    if (!isAppending.value) loadData(false)
  })

  // Helper pro skládání multi-hodnot do pole aktivních čipů
  const addMultiChips = (activeArray, value, key, labelPrefix) => {
    if (value) {
       const parts = String(value).split(',').map(s => s.trim()).filter(s => s)
       parts.forEach(part => {
         activeArray.push({ id: `${key}|${part}`, realKey: key, partValue: part, label: `${labelPrefix}: ${part}` })
       })
    }
  }

  return {
    currentPage,
    itemsPerPage,
    sortBy,
    filtersOpen,
    isAppending,
    filters,
    totalPages,
    totalItems,
    removeFilter,
    resetFilters,
    loadNextPage,
    addMultiChips,
    loadData
  }
}