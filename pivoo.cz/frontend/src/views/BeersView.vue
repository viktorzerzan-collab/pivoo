<template>
  <div class="beers-page">
    <BaseLoader :show="isLoading" />

    <div class="catalog-header-layout">
      <div class="mobile-action-bar">
        <BaseButton v-if="authStore.user" variant="add" @click="openAddModal">
          <template #icon><PlusIcon :size="20" /></template>
          {{ $t('catalog.add_beer') }}
        </BaseButton>
      </div>

      <BasePanel 
        :title="$t('catalog.filters_title')" 
        :icon="FilterIcon" 
        class="filters-section"
        @click="filtersOpen = !filtersOpen"
        style="cursor: pointer;"
      >
        <template #header-actions>
          <ChevronDownIcon :class="{ 'rotated': filtersOpen }" :size="20" class="toggle-icon" />
        </template>

        <transition name="slide-fade">
          <div v-show="filtersOpen" class="filters-body" @click.stop>
            <div class="filters-grid">
              <FilterInput v-model="filters.search" :label="$t('catalog.filter_name_beer')" :placeholder="$t('catalog.placeholder_beer')" />
              <FilterInput v-model="filters.brewery" :label="$t('catalog.filter_brewery')" :placeholder="$t('catalog.placeholder_brewery')" />
              <FilterInput v-model="filters.country" :label="$t('catalog.filter_country')" :placeholder="$t('catalog.placeholder_country')" />

              <BaseSelect v-model="filters.style" :label="$t('catalog.filter_style')" :placeholder="$t('catalog.all_styles')" searchable>
                <option value="">{{ $t('catalog.all_styles') }}</option>
                <option v-for="s in styles" :key="s.id" :value="s.id">{{ s.name }}</option>
              </BaseSelect>

              <FilterRange v-model="filters.epm" :label="$t('modals.add_beer.epm')" :step="0.1" unit="°" />
              <FilterRange v-model="filters.abv" :label="$t('modals.add_beer.abv')" :step="0.1" unit="%" />
              <FilterRange v-model="filters.ibu" :label="$t('modals.add_beer.ibu')" :step="1" unit="IBU" />
            </div>
            
            <div class="filters-footer">
              <BaseButton variant="edit" @click="resetFilters">{{ $t('catalog.reset_filters') }}</BaseButton>
            </div>
          </div>
        </transition>
      </BasePanel>

      <ActiveFilterChips 
        :filters="activeFilters" 
        @remove="removeFilter" 
      />

      <div class="results-bar">
        <div class="sort-control-wrapper">
          <BaseSelect v-model="sortBy" :placeholder="$t('catalog.sort_by')" :searchable="false">
            <option v-for="opt in sortOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
          </BaseSelect>
        </div>

        <span class="results-count">{{ $t('catalog.found_beers') }} <strong>{{ totalItems }}</strong></span>
        
        <div class="desktop-action-bar">
          <BaseButton v-if="authStore.user" variant="add" @click="openAddModal">
            <template #icon><PlusIcon :size="20" /></template>
            {{ $t('catalog.add_beer') }}
          </BaseButton>
        </div>
      </div>
    </div>

    <div class="catalog-container">
      <template v-if="beers.length > 0">
        <div class="beers-grid">
          <BeerCard 
            v-for="beer in beers" 
            :key="beer.id" 
            :beer="beer" 
            @showDetail="openDetail"
          />
        </div>
        
        <div class="desktop-pagination">
          <BasePagination 
            v-if="totalPages > 1"
            v-model:currentPage="currentPage" 
            :total-pages="totalPages"
          />
        </div>

        <div ref="loadMoreTrigger" class="load-more-trigger">
          <div v-if="isAppending" class="mobile-loader">
            {{ $t('catalog.loading_more_beers') }}
          </div>
        </div>
      </template>
      
      <BaseEmptyState 
        v-else-if="!isLoading" 
        :text="$t('catalog.empty_beers')" 
        :icon="BeerIcon"
      >
        <BaseButton variant="edit" class="mt-2" @click="resetFilters">{{ $t('catalog.cancel_filters') }}</BaseButton>
      </BaseEmptyState>
    </div>

    <AddBeerModal 
      :show="isAddModalOpen" 
      :isEditing="false" 
      :form="beerForm" 
      @close="isAddModalOpen = false" 
      @submit="submitBeer" 
    />

    <DetailModal 
      :show="isDetailOpen" 
      :item="selectedItem" 
      type="beer" 
      :reviews="beerReviews"
      @close="isDetailOpen = false" 
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { useI18n } from 'vue-i18n'
import { apiFetch } from '../api'
import { useCatalogStore } from '../stores/catalog'
import { useAuthStore } from '../stores/auth'
import { useToastStore } from '../stores/toast'
import { PlusIcon, FilterIcon, ChevronDownIcon, BeerIcon } from 'lucide-vue-next'

import FilterInput from '../components/FilterInput.vue'
import FilterRange from '../components/FilterRange.vue'
import BaseSelect from '../components/BaseSelect.vue'
import BeerCard from '../components/BeerCard.vue'
import BaseLoader from '../components/BaseLoader.vue'
import BasePanel from '../components/BasePanel.vue'
import BaseEmptyState from '../components/BaseEmptyState.vue'
import BasePagination from '../components/BasePagination.vue'
import BaseButton from '../components/BaseButton.vue'
import AddBeerModal from '../components/modals/AddBeerModal.vue'
import DetailModal from '../components/modals/DetailModal.vue'
import ActiveFilterChips from '../components/ActiveFilterChips.vue'

const catalogStore = useCatalogStore()
const authStore = useAuthStore()
const toastStore = useToastStore()
const { t } = useI18n()

const { beers, beersPagination, breweries, styles, countries, isLoading } = storeToRefs(catalogStore)

const currentPage = ref(1)
const itemsPerPage = 30
const sortBy = ref('name_asc')

const initialFilters = {
  search: '', brewery: '', style: '', country: '',
  epm: { min: '', max: '' }, abv: { min: '', max: '' }, ibu: { min: '', max: '' }
}
const filters = ref(JSON.parse(JSON.stringify(initialFilters)))

const totalPages = computed(() => beersPagination.value?.total_pages || 1)
const totalItems = computed(() => beersPagination.value?.total || 0)

const activeFilters = computed(() => {
  const active = []
  
  const addMultiChips = (value, key, labelPrefix) => {
    if (value) {
       const parts = String(value).split(',').map(s => s.trim()).filter(s => s)
       parts.forEach(part => {
         active.push({ id: `${key}|${part}`, realKey: key, partValue: part, label: `${labelPrefix}: ${part}` })
       })
    }
  }

  addMultiChips(filters.value.search, 'search', t('catalog.search_prefix'))
  addMultiChips(filters.value.brewery, 'brewery', t('catalog.filter_brewery'))
  addMultiChips(filters.value.country, 'country', t('catalog.filter_country_short'))
  
  if (filters.value.style) {
    const s = styles.value.find(x => x.id == filters.value.style)
    if (s) {
      active.push({ id: 'style', realKey: 'style', label: `Styl: ${s.name}` })
    }
  }

  const ranges = ['epm', 'abv', 'ibu'];
  ranges.forEach(key => {
    const min = filters.value[key].min
    const max = filters.value[key].max
    if (min !== '' || max !== '') {
      active.push({ 
        id: key, 
        realKey: 'range',
        rangeKey: key,
        label: `${key.toUpperCase()}: ${min !== '' ? min : '0'} - ${max !== '' ? max : '∞'}` 
      })
    }
  })
  
  return active
})

const removeFilter = (chip) => {
  if (chip.realKey === 'range') {
    filters.value[chip.rangeKey] = { min: '', max: '' }
  } else if (chip.partValue) {
    let parts = String(filters.value[chip.realKey]).split(',').map(s => s.trim()).filter(s => s)
    parts = parts.filter(p => p !== chip.partValue)
    filters.value[chip.realKey] = parts.join(', ')
  } else {
    filters.value[chip.realKey] = ''
  }
}

const loadMoreTrigger = ref(null)
const isAppending = ref(false)
let observer = null

const loadBeers = async (append = false) => {
  const params = {
    page: currentPage.value,
    limit: itemsPerPage,
    search: filters.value.search,
    brewery: filters.value.brewery,
    style: filters.value.style,
    country: filters.value.country,
    epm_min: filters.value.epm.min,
    epm_max: filters.value.epm.max,
    abv_min: filters.value.abv.min,
    abv_max: filters.value.abv.max,
    ibu_min: filters.value.ibu.min,
    ibu_max: filters.value.ibu.max,
    sort: sortBy.value
  }
  await catalogStore.fetchBeers(params, append)
}

const loadNextPage = async () => {
  if (currentPage.value < totalPages.value && !isLoading.value && !isAppending.value) {
    isAppending.value = true
    currentPage.value++
    await loadBeers(true)
    isAppending.value = false
  }
}

watch(loadMoreTrigger, (el) => {
  if (observer) observer.disconnect()
  if (el) {
    observer = new IntersectionObserver((entries) => {
      if (entries[0].isIntersecting && window.innerWidth <= 768) {
        loadNextPage()
      }
    }, { rootMargin: '200px' })
    observer.observe(el)
  }
})

onUnmounted(() => {
  if (observer) observer.disconnect()
})

watch([filters, sortBy], () => {
  if (currentPage.value !== 1) {
    isAppending.value = false 
    currentPage.value = 1
  } else {
    loadBeers(false)
  }
}, { deep: true })

watch(currentPage, () => {
  if (!isAppending.value) {
    loadBeers(false)
  }
})

onMounted(async () => {
  await catalogStore.fetchAllData()
  loadBeers(false)
})

const resetFilters = () => {
  filters.value = JSON.parse(JSON.stringify(initialFilters))
  sortBy.value = 'name_asc'
  isAppending.value = false
  if (currentPage.value !== 1) {
    currentPage.value = 1
  } else {
    loadBeers(false)
  }
}

const sortOptions = computed(() => [
  { value: 'name_asc', label: t('catalog.sort.name_asc') },
  { value: 'name_desc', label: t('catalog.sort.name_desc') },
  { value: 'brewery_asc', label: t('catalog.sort.brewery_asc') },
  { value: 'brewery_desc', label: t('catalog.sort.brewery_desc') },
  { value: 'style_asc', label: t('catalog.sort.style_asc') },
  { value: 'style_desc', label: t('catalog.sort.style_desc') },
  { value: 'rating_desc', label: t('catalog.sort.rating_desc') },
  { value: 'rating_asc', label: t('catalog.sort.rating_asc') },
  { value: 'abv_desc', label: t('catalog.sort.abv_desc') },
  { value: 'abv_asc', label: t('catalog.sort.abv_asc') },
  { value: 'epm_desc', label: t('catalog.sort.epm_desc') },
  { value: 'epm_asc', label: t('catalog.sort.epm_asc') },
  { value: 'ibu_desc', label: t('catalog.sort.ibu_desc') },
  { value: 'ibu_asc', label: t('catalog.sort.ibu_asc') },
  { value: 'newest', label: t('catalog.sort.newest') },
  { value: 'oldest', label: t('catalog.sort.oldest') }
])

const isAddModalOpen = ref(false)
const filtersOpen = ref(false)

const isDetailOpen = ref(false)
const selectedItem = ref(null)
const beerReviews = ref([])

const beerForm = ref({ 
  name: '', brewery_id: '', style_id: '', epm: '', abv: '', ibu: '', 
  ebc: '', hops: '', malts: '', fermentation: '', tags: '', 
  is_unfiltered: false, is_unpasteurized: false 
})

const openAddModal = () => {
  beerForm.value = { 
    name: '', brewery_id: '', style_id: '', epm: '', abv: '', ibu: '', 
    ebc: '', hops: '', malts: '', fermentation: '', tags: '', 
    is_unfiltered: false, is_unpasteurized: false 
  }
  isAddModalOpen.value = true
}

const submitBeer = async () => {
  try {
    const res = await apiFetch('/add_beer.php', { 
      method: 'POST', 
      body: JSON.stringify(beerForm.value) 
    })
    if (res.status === 'success') { 
      isAddModalOpen.value = false
      
      const brewery = breweries.value.find(b => b.id == beerForm.value.brewery_id)
      const style = styles.value.find(s => s.id == beerForm.value.style_id)
      
      catalogStore.addBeerLocally({
        id: res.id,
        ...beerForm.value,
        brewery_name: brewery ? brewery.name : '',
        brewery_country: brewery ? brewery.country : '',
        brewery_country_code: brewery ? brewery.country_code : '',
        style: style ? style.name : '',
        avg_rating: null,
        total_checkins: 0,
        is_favorite: 0
      })
      
      toastStore.showToast(t('toast.beer_added'))
    } else {
      toastStore.showToast(res.message || t('toast.beer_add_error'), "toast-error")
    }
  } catch (e) { 
    toastStore.showToast(t('toast.communication_error'), 'toast-error') 
  }
}

const openDetail = async (beer) => {
  selectedItem.value = beer
  isDetailOpen.value = true
  beerReviews.value = [] 
  
  try {
    const res = await apiFetch(`/beer_reviews.php?beer_id=${beer.id}`)
    if (res.status === 'success') {
      beerReviews.value = res.data
    }
  } catch (error) {
    console.error("Chyba při načítání recenzí piva", error)
  }
}
</script>

<style scoped>
.catalog-header-layout { display: flex; flex-direction: column; gap: 0; }

.filters-section { margin-bottom: 1.5rem; position: relative; z-index: 20; }
.filters-section :deep(.panel-header) { border-bottom: none; margin-bottom: 0; padding-bottom: 1rem; }
.filters-section :deep(.panel-header h3) { font-size: 1.1rem; }

.toggle-icon { color: var(--text-muted); transition: transform 0.3s ease; }
.toggle-icon.rotated { transform: rotate(180deg); }
.filters-body { padding-top: 1.5rem; border-top: 1px solid var(--border); }
.filters-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; }
.filters-footer { margin-top: 1.5rem; display: flex; justify-content: flex-end; }

.results-bar { 
  display: flex; 
  justify-content: space-between; 
  align-items: center; 
  margin-bottom: 2rem; 
  padding: 0 0 1rem 0;
  border-bottom: 1px solid var(--border); 
}
.results-count { color: var(--text-muted); font-size: 0.95rem; flex: 1; text-align: center; }
.results-count strong { color: var(--text-main); }
.sort-control-wrapper { width: 260px; }
.desktop-action-bar { width: 260px; display: flex; justify-content: flex-end; }
.mobile-action-bar { display: none; }

.catalog-container { position: relative; min-height: 400px; display: flex; flex-direction: column; width: 100%; }

.beers-grid { 
  display: grid; 
  grid-template-columns: repeat(2, 1fr); 
  gap: 1.5rem; 
  margin-bottom: 2rem; 
}

.mt-2 { margin-top: 0.5rem; }

.slide-fade-enter-active { transition: all 0.3s ease-out; }
.slide-fade-leave-active { transition: all 0.2s cubic-bezier(1, 0.5, 0.8, 1); }
.slide-fade-enter-from, .slide-fade-leave-to { transform: translateY(-10px); opacity: 0; }

.desktop-pagination { display: block; }
.load-more-trigger { height: 20px; width: 100%; }
.mobile-loader { display: none; text-align: center; padding: 1rem; color: var(--text-muted); font-weight: 600; font-size: 0.9rem; }

@media (max-width: 768px) {
  .beers-grid { 
    grid-template-columns: 1fr; 
  }

  .mobile-action-bar { display: block; margin-bottom: 1.5rem; }
  .mobile-action-bar :deep(.base-button) { width: 100%; padding: 1rem; justify-content: center; font-size: 1.1rem; }
  .desktop-action-bar { display: none; }
  
  .results-bar { 
    flex-direction: column; 
    align-items: stretch; 
    gap: 1rem; 
    border-bottom: none;
  }
  .sort-control-wrapper { width: 100%; }
  .results-count { text-align: center; padding-top: 0.5rem; border-top: 1px solid var(--border); }

  .desktop-pagination { display: none; }
  .mobile-loader { display: block; }
}
</style>