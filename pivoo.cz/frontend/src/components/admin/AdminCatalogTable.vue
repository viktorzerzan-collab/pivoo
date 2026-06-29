<template>
  <div class="admin-catalog-table">
    <BaseLoader :show="isAdminDataLoading || isCatalogLoading" />

    <BaseTable>
      <template #thead>
        <thead>
          <tr>
            <th>{{ $t('admin.table.name') }}</th>
            <th v-if="activeTab === 'breweries'">{{ $t('admin.table.beers_count') }}</th>
            <th v-if="activeTab === 'beers'">{{ $t('admin.table.brewery') }}</th>
            <th v-if="activeTab === 'beers'">{{ $t('admin.table.style') }}</th>
            <th v-if="activeTab === 'locations'">{{ $t('admin.table.location_type') }}</th>
            <th v-if="['breweries', 'locations'].includes(activeTab)">{{ $t('admin.table.city') }}</th>
            <th v-if="['breweries', 'locations'].includes(activeTab)">{{ $t('admin.table.country') }}</th>
            <th class="w-100 text-right">{{ $t('admin.table.actions') }}</th>
          </tr>
        </thead>
      </template>

      <template #tbody>
        <tr v-for="item in paginatedItems" :key="item?.id">
          <td :data-label="$t('admin.table.name')">
            <div class="main-item-cell">
              <BaseEntityIcon 
                :image-url="activeTab === 'breweries' && item.logo ? 'https://www.pivoo.cz/backend/uploads/logos/' + item.logo : null"
                :icon="activeTab === 'beers' ? BeerIcon : (activeTab === 'breweries' ? FactoryIcon : (activeTab === 'locations' ? MapPinIcon : HopIcon))"
                :bg-class="activeTab + '-bg'"
              />
              <div class="item-text">
                <strong>{{ item.name }}</strong>
                <small v-if="activeTab === 'beers'" class="mobile-only text-muted">{{ item.brewery_name }} • {{ item.style }}</small>
                <small v-if="['breweries', 'locations'].includes(activeTab)" class="mobile-only combined-meta">
                  <img v-if="item.country_code" :src="`https://flagcdn.com/w20/${item.country_code}.png`" class="mobile-flag" />
                  {{ item.city || $t('admin.unknown_location') }}{{ item.city && item.country ? ', ' : '' }}{{ item.country }}
                </small>
              </div>
            </div>
          </td>
          <td v-if="activeTab === 'breweries'" class="desktop-only"><strong>{{ item.total_beers_in_catalog || 0 }}</strong></td>
          <td v-if="activeTab === 'beers'" class="desktop-only">{{ item.brewery_name }}</td>
          <td v-if="activeTab === 'beers'" class="desktop-only">{{ item.style }}</td>
          <td v-if="activeTab === 'locations'" class="desktop-only">{{ translateLocationType(item.type) }}</td>
          <td v-if="['breweries', 'locations'].includes(activeTab)" class="desktop-only">{{ item.city || '-' }}</td>
          <td v-if="['breweries', 'locations'].includes(activeTab)" class="desktop-only">
            <div class="country-cell">
              <img v-if="item.country_code" :src="`https://flagcdn.com/w20/${item.country_code}.png`" class="admin-flag-icon" />
              <span>{{ item.country || '-' }}</span>
            </div>
          </td>
          <td :data-label="$t('admin.table.actions')">
            <BaseActionGroup>
              <BaseTooltip v-if="activeTab === 'locations'" :text="$t('admin.tooltips.merge')" position="top-end">
                <BaseButton variant="primary" :isIconOnly="true" style="background-color: #8b5cf6; color: white;" @click="openMergeModal(item)">
                  <template #icon><GitMergeIcon :size="16" /></template>
                </BaseButton>
              </BaseTooltip>
              <BaseTooltip :text="$t('admin.tooltips.edit')" position="top-end">
                <BaseButton variant="edit" :isIconOnly="true" @click="openEditModal(item)">
                  <template #icon><PencilIcon :size="16" /></template>
                </BaseButton>
              </BaseTooltip>
              <BaseTooltip :text="$t('admin.tooltips.delete')" position="top-end">
                <BaseButton variant="danger" :isIconOnly="true" @click="confirmDelete(item.id)">
                  <template #icon><Trash2Icon :size="16" /></template>
                </BaseButton>
              </BaseTooltip>
            </BaseActionGroup>
          </td>
        </tr>
      </template>
    </BaseTable>
    
    <BaseEmptyState 
      v-if="filteredItems.length === 0" 
      :text="$t('admin.empty_search')" 
      :icon="SearchXIcon" 
      :icon-size="40"
    />

    <div class="admin-section-footer">
      <div class="footer-info desktop-only">
        {{ $t('admin.showing') }} <strong>{{ paginatedItems.length }}</strong> {{ $t('admin.of') }} <strong>{{ filteredItems.length }}</strong> {{ $t('admin.records') }}
      </div>
      <div class="desktop-only">
        <BasePagination v-if="totalPages > 1" v-model:currentPage="currentPage" :total-pages="totalPages" />
      </div>
    </div>

    <DeleteConfirmModal :show="modals.delete" @close="modals.delete = false" @confirm="handleDelete" />
    <AddBeerModal :show="modals.beer" :isEditing="isEditing" :form="formData.beer" @close="modals.beer = false" @submit="submitForm('beer')" />
    <AddBreweryModal :show="modals.brewery" :isEditing="isEditing" :countries="countries" :form="formData.brewery" @close="modals.brewery = false" @submit="submitForm('brewery')" />
    <AddLocationModal :show="modals.location" :isEditing="isEditing" :countries="countries" :form="formData.location" @close="modals.location = false" @submit="submitForm('location')" />
    <AddStyleModal :show="modals.style" :isEditing="isEditing" :form="formData.style" @close="modals.style = false" @submit="submitForm('style')" />
    <MergeLocationsModal :show="modals.merge" :form="mergeForm" :targetOptions="mergeTargetOptions" @close="modals.merge = false" @submit="submitMerge" />
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { storeToRefs } from 'pinia'
import { 
  PencilIcon, Trash2Icon, BeerIcon, FactoryIcon, MapPinIcon, 
  HopIcon, GitMergeIcon, SearchXIcon 
} from 'lucide-vue-next'
import { useI18n } from 'vue-i18n'

import { apiFetch } from '../../api'
import { useCatalogStore } from '../../stores/catalog'
import { useToastStore } from '../../stores/toast'
import { useAdmin } from '../../composables/useAdmin'

import BaseButton from '../BaseButton.vue'
import BaseLoader from '../BaseLoader.vue'
import BaseTable from '../BaseTable.vue'
import BaseEntityIcon from '../BaseEntityIcon.vue'
import BaseActionGroup from '../BaseActionGroup.vue'
import BaseEmptyState from '../BaseEmptyState.vue'
import BasePagination from '../BasePagination.vue'
import BaseTooltip from '../BaseTooltip.vue'

import DeleteConfirmModal from '../modals/DeleteConfirmModal.vue'
import AddBeerModal from '../modals/AddBeerModal.vue'
import AddBreweryModal from '../modals/AddBreweryModal.vue'
import AddLocationModal from '../modals/AddLocationModal.vue'
import AddStyleModal from '../modals/AddStyleModal.vue'
import MergeLocationsModal from '../modals/MergeLocationsModal.vue'

const props = defineProps({
  activeTab: { type: String, required: true },
  searchQuery: { type: String, default: '' }
})

const { t, te } = useI18n()
const catalogStore = useCatalogStore()
const toastStore = useToastStore()

const { styles, countries, isLoading: isCatalogLoading } = storeToRefs(catalogStore)
const { adminBeers, adminBreweries, adminLocations, isAdminDataLoading, loadAdminData } = useAdmin()

const isMobileMode = ref(window.innerWidth <= 768)
const currentPage = ref(1)
const itemsPerPage = 30
const handleResize = () => { isMobileMode.value = window.innerWidth <= 768 }

const modals = ref({ beer: false, brewery: false, location: false, style: false, merge: false, delete: false })
const isEditing = ref(false)
const deleteId = ref(null)
const mergeForm = ref({ source: null, target_id: '' })

const formData = ref({
  beer: { id: null, name: '', brewery_id: '', style_id: '', epm: '', abv: '', ibu: '', ebc: '', hops: '', malts: '', fermentation: '', tags: '', is_unfiltered: false, is_unpasteurized: false },
  brewery: { id: null, name: '', city: '', zip_code: '', country_id: 1, address: '', email: '', phone: '', website: '', logoFile: null, lat: null, lng: null },
  location: { id: null, name: '', type: 'hospoda', city: '', zip_code: '', country_id: 1, address: '', email: '', phone: '', website: '', opening_hours: '' },
  style: { id: null, name: '' }
})

// Dynamický výpočet aktuální entity pro uložení/mazání
const getSingleType = (tab) => ({ beers: 'beer', breweries: 'brewery', locations: 'location', styles: 'style' }[tab])

const currentItems = computed(() => ({ 
  beers: adminBeers.value, 
  breweries: adminBreweries.value, 
  locations: adminLocations.value, 
  styles: styles.value 
}[props.activeTab] || []))

const filteredItems = computed(() => {
  let items = [...currentItems.value]
  if (props.searchQuery) {
    const q = props.searchQuery.toLowerCase()
    items = items.filter(item => item.name.toLowerCase().includes(q))
  }
  return items.sort((a, b) => (a.name || '').localeCompare(b.name || '', 'cs'))
})

const mergeTargetOptions = computed(() => mergeForm.value.source ? catalogStore.allLocations.filter(l => l.id !== mergeForm.value.source.id).sort((a, b) => (a.name || '').localeCompare(b.name || '', 'cs')) : [])
const totalPages = computed(() => Math.ceil(filteredItems.value.length / itemsPerPage))
const paginatedItems = computed(() => isMobileMode.value 
  ? filteredItems.value.slice(0, currentPage.value * itemsPerPage) 
  : filteredItems.value.slice((currentPage.value - 1) * itemsPerPage, currentPage.value * itemsPerPage)
)

watch(() => props.searchQuery, () => { currentPage.value = 1 })
watch(() => props.activeTab, () => { currentPage.value = 1 })

onMounted(() => {
  if (adminBeers.value.length === 0) loadAdminData()
  window.addEventListener('resize', handleResize)
})

onUnmounted(() => { window.removeEventListener('resize', handleResize) })

const translateLocationType = (val) => {
  if (!val) return val
  const key = `dynamic.location_types.${val}`
  return te(key) ? t(key) : val
}

// Otevření okna pro přidání - Vystaveno pro volání z rodiče (AdminView)
const openAddModal = () => { 
  isEditing.value = false
  Object.keys(modals.value).forEach(m => modals.value[m] = false)
  const key = getSingleType(props.activeTab)
  
  if (key === 'beer') formData.value.beer = { id: null, name: '', brewery_id: '', style_id: '', epm: '', abv: '', ibu: '', ebc: '', hops: '', malts: '', fermentation: '', tags: '', is_unfiltered: false, is_unpasteurized: false }
  else if (key === 'brewery') formData.value.brewery = { id: null, name: '', city: '', zip_code: '', country_id: 1, address: '', email: '', phone: '', website: '', logoFile: null, lat: null, lng: null }
  else if (key === 'location') formData.value.location = { id: null, name: '', type: 'hospoda', city: '', zip_code: '', country_id: 1, address: '', email: '', phone: '', website: '', opening_hours: '' }
  else if (key === 'style') formData.value.style = { id: null, name: '' }
  
  modals.value[key] = true 
}

// Expozice metody ven
defineExpose({ openAddModal })

const openEditModal = (item) => { 
  isEditing.value = true
  const key = getSingleType(props.activeTab)
  formData.value[key] = key === 'beer' 
    ? { ...item, is_unfiltered: !!item.is_unfiltered, is_unpasteurized: !!item.is_unpasteurized } 
    : { ...item, logoFile: null }
  modals.value[key] = true 
}

const submitForm = async (f) => {
  try {
    const endpoint = isEditing.value ? `update_${f}.php` : `add_${f}.php`
    let body;
    if (f === 'brewery') { 
      body = new FormData(); 
      Object.keys(formData.value[f]).forEach(k => { 
        if (formData.value[f][k] != null) body.append(k, formData.value[f][k]) 
      }) 
    } else {
      body = JSON.stringify(formData.value[f])
    }
    
    const res = await apiFetch(`/${endpoint}`, { method: 'POST', body });
    if (res.status === 'success') { 
      toastStore.showToast(res.message); 
      modals.value[f] = false; 
      catalogStore.forceRefresh();
      loadAdminData(); 
    } else {
      toastStore.showToast(res.message || 'Error', 'toast-error')
    }
  } catch { 
    toastStore.showToast(t('toast.communication_error'), 'toast-error') 
  }
}

const confirmDelete = (id) => { 
  deleteId.value = id
  modals.value.delete = true 
}

const handleDelete = async () => { 
  const f = getSingleType(props.activeTab)
  try { 
    const res = await apiFetch(`/delete_${f}.php`, { method: 'POST', body: JSON.stringify({ id: deleteId.value }) }); 
    if (res.status === 'success') { 
      toastStore.showToast("Smazáno"); 
      catalogStore.forceRefresh();
      loadAdminData();
    } else {
      toastStore.showToast(res.message || 'Error', 'toast-error') 
    }
  } catch { 
    toastStore.showToast(t('toast.delete_error'), 'toast-error') 
  } finally { 
    modals.value.delete = false 
  } 
}

const openMergeModal = (item) => { 
  mergeForm.value = { source: item, target_id: '' }
  modals.value.merge = true 
}

const submitMerge = async () => { 
  if (!mergeForm.value.source || !mergeForm.value.target_id) return; 
  try { 
    const res = await apiFetch('/merge_locations.php', { method: 'POST', body: JSON.stringify({ source_id: mergeForm.value.source.id, target_id: mergeForm.value.target_id }) }); 
    if (res.status === 'success') { 
      toastStore.showToast(res.message); 
      modals.value.merge = false; 
      
      // Vyčistíme starou položku lokálně a obnovíme admin data
      catalogStore.locations = catalogStore.locations.filter(l => l.id !== mergeForm.value.source.id);
      adminLocations.value = adminLocations.value.filter(l => l.id !== mergeForm.value.source.id);
      loadAdminData();
    } else { 
      toastStore.showToast(res.message || 'Error', 'toast-error') 
    } 
  } catch { 
    toastStore.showToast(t('toast.communication_error'), 'toast-error') 
  } 
}
</script>

<style scoped>
.admin-catalog-table { position: relative; display: flex; flex-direction: column; flex: 1; }
.mobile-only { display: none; }
.main-item-cell { display: flex; align-items: center; gap: 1rem; }
.item-text { flex: 1; min-width: 0; display: flex; flex-direction: column; }
.combined-meta { display: none; align-items: center; gap: 5px; }
.mobile-flag { width: 16px; height: auto; }
.country-cell { display: flex; align-items: center; gap: 0.5rem; }
.admin-flag-icon { width: 18px; height: auto; border-radius: 2px; }
.admin-section-footer { display: flex; justify-content: space-between; align-items: center; padding-top: 1.5rem; border-top: 1px solid var(--border); margin-top: auto; }
.footer-info { font-size: 0.85rem; color: var(--text-muted); font-weight: 500; }
.footer-info strong { color: var(--text-main); }

@media (max-width: 768px) {
  .mobile-only { display: block; }
  .combined-meta { display: flex !important; }
  .desktop-only { display: none !important; }
}
</style>
