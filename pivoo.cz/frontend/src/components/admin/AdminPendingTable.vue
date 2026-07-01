<template>
  <div class="admin-pending-table">
    <BaseTable>
      <template #thead>
        <thead>
          <tr>
            <th>{{ $t('admin.table.name') }}</th>
            <th>{{ $t('admin.table.type') }}</th>
            <th>{{ $t('admin.table.author') }}</th>
            <th class="w-100 text-right">{{ $t('admin.table.actions') }}</th>
          </tr>
        </thead>
      </template>

      <template #tbody>
        <tr v-for="item in paginatedItems" :key="item?.entity_type + item?.id">
          <td :data-label="$t('admin.table.name')">
            <div class="main-item-cell">
              <BaseEntityIcon 
                :icon="item.entity_type === 'beer' ? BeerIcon : (item.entity_type === 'brewery' ? FactoryIcon : MapPinIcon)"
                :bg-class="item.entity_type + 's-bg'"
              />
              <div class="item-text"><strong>{{ item.name }}</strong></div>
            </div>
          </td>
          <td :data-label="$t('admin.table.type')" class="desktop-only">
            <span class="badge type-badge">{{ $t('admin.entity_' + item.entity_type) }}</span>
          </td>
          <td :data-label="$t('admin.table.author')" class="desktop-only">{{ item.created_by_user || $t('admin.unknown_author') }}</td>
          <td :data-label="$t('admin.table.actions')">
            <BaseActionGroup>
              <BaseTooltip :text="$t('admin.tooltips.edit')" position="top-end">
                <BaseButton variant="edit" :isIconOnly="true" @click="openEditModal(item, item.entity_type)">
                  <template #icon><PencilIcon :size="16" /></template>
                </BaseButton>
              </BaseTooltip>
              <BaseTooltip :text="$t('admin.tooltips.approve')" position="top-end">
                <BaseButton variant="primary" :isIconOnly="true" style="background-color: #10b981; color: white;" @click="handleApprove(item, 'approve')">
                  <template #icon><CheckIcon :size="16" /></template>
                </BaseButton>
              </BaseTooltip>
              <BaseTooltip :text="$t('admin.tooltips.reject')" position="top-end">
                <BaseButton variant="danger" :isIconOnly="true" @click="handleApprove(item, 'reject')">
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

    <AddBeerModal :show="modals.beer" :isEditing="true" :form="formData.beer" @close="modals.beer = false" @submit="submitForm('beer')" />
    <AddBreweryModal :show="modals.brewery" :isEditing="true" :countries="countries" :form="formData.brewery" @close="modals.brewery = false" @submit="submitForm('brewery')" />
    <AddLocationModal :show="modals.location" :isEditing="true" :countries="countries" :form="formData.location" @close="modals.location = false" @submit="submitForm('location')" />
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { storeToRefs } from 'pinia'
import { BeerIcon, FactoryIcon, MapPinIcon, PencilIcon, CheckIcon, Trash2Icon, SearchXIcon } from 'lucide-vue-next'
import { useI18n } from 'vue-i18n'

import { apiFetch } from '../../api'
import { useCatalogStore } from '../../stores/catalog'
import { useToastStore } from '../../stores/toast'

import BaseButton from '../BaseButton.vue'
import BaseTable from '../BaseTable.vue'
import BaseEntityIcon from '../BaseEntityIcon.vue'
import BaseActionGroup from '../BaseActionGroup.vue'
import BaseEmptyState from '../BaseEmptyState.vue'
import BasePagination from '../BasePagination.vue'
import BaseTooltip from '../BaseTooltip.vue'

import AddBeerModal from '../modals/AddBeerModal.vue'
import AddBreweryModal from '../modals/AddBreweryModal.vue'
import AddLocationModal from '../modals/AddLocationModal.vue'

const props = defineProps({
  searchQuery: {
    type: String,
    default: ''
  }
})

// Emitujeme událost pro překreslení dat v administraci
const emit = defineEmits(['reload-admin-data'])

const { t } = useI18n()
const catalogStore = useCatalogStore()
const toastStore = useToastStore()
const { countries } = storeToRefs(catalogStore)

const currentPage = ref(1)
const itemsPerPage = 30
const isMobileMode = ref(window.innerWidth <= 768)

const handleResize = () => { isMobileMode.value = window.innerWidth <= 768 }

// Formuláře a stavy modálů pro editaci
const modals = ref({ beer: false, brewery: false, location: false })
const formData = ref({
  beer: { id: null, name: '', brewery_id: '', style_id: '', epm: '', abv: '', ibu: '', ebc: '', hops: '', malts: '', fermentation: '', tags: '', is_unfiltered: false, is_unpasteurized: false },
  brewery: { id: null, name: '', city: '', zip_code: '', country_id: 1, address: '', email: '', phone: '', website: '', logoFile: null, lat: null, lng: null },
  location: { id: null, name: '', type: 'hospoda', city: '', zip_code: '', country_id: 1, address: '', email: '', phone: '', website: '', opening_hours: '' }
})

// Filtrace a stránkování
const pendingItems = computed(() => catalogStore.pendingApprovals || [])
const filteredItems = computed(() => {
  let items = [...pendingItems.value]
  if (props.searchQuery) {
    const q = props.searchQuery.toLowerCase()
    items = items.filter(item => item.name.toLowerCase().includes(q))
  }
  return items.sort((a, b) => (a.name || '').localeCompare(b.name || '', 'cs'))
})

const totalPages = computed(() => Math.ceil(filteredItems.value.length / itemsPerPage))
const paginatedItems = computed(() => isMobileMode.value 
  ? filteredItems.value.slice(0, currentPage.value * itemsPerPage) 
  : filteredItems.value.slice((currentPage.value - 1) * itemsPerPage, currentPage.value * itemsPerPage)
)

watch(() => props.searchQuery, () => { currentPage.value = 1 })

onMounted(() => {
  catalogStore.fetchPendingApprovals()
  window.addEventListener('resize', handleResize)
})

onUnmounted(() => {
  window.removeEventListener('resize', handleResize)
})

// --- Akce ---

const handleApprove = async (item, action) => { 
  try { 
    const res = await apiFetch('/approve_entity.php', { method: 'POST', body: JSON.stringify({ entity_type: item.entity_type, entity_id: item.id, action }) }); 
    if (res.status === 'success') { 
      toastStore.showToast(res.message); 
      catalogStore.fetchPendingApprovals(); 
      catalogStore.forceRefresh();
      emit('reload-admin-data'); // Upozorníme rodiče, ať obnoví např. počet piv v pivovarech
    } else {
      toastStore.showToast(res.message || 'Error', 'toast-error') 
    }
  } catch { 
    toastStore.showToast(t('toast.communication_error'), 'toast-error') 
  } 
}

const openEditModal = (item, typeParam) => { 
  let key = 'location'
  if (['beer', 'beers'].includes(typeParam)) key = 'beer'
  else if (['brewery', 'breweries'].includes(typeParam)) key = 'brewery'

  formData.value[key] = key === 'beer' ? { ...item, is_unfiltered: !!item.is_unfiltered, is_unpasteurized: !!item.is_unpasteurized } : { ...item, logoFile: null }
  modals.value[key] = true 
}

const submitForm = async (f) => {
  try {
    const endpoint = `update_${f}.php` // Při schvalování už existuje, proto používáme vždy update
    let body;
    if (f === 'brewery') { 
      body = new FormData(); 
      Object.keys(formData.value[f]).forEach(k => { 
        if (formData.value[f][k] != null) body.append(k, formData.value[f][k]) 
      }) 
    }
    else body = JSON.stringify(formData.value[f])
    
    const res = await apiFetch(`/${endpoint}`, { method: 'POST', body });
    if (res.status === 'success') { 
      toastStore.showToast(res.message); 
      modals.value[f] = false; 
      catalogStore.fetchPendingApprovals();
      catalogStore.forceRefresh();
      emit('reload-admin-data');
    } else {
      toastStore.showToast(res.message || 'Error', 'toast-error')
    }
  } catch { 
    toastStore.showToast(t('toast.communication_error'), 'toast-error') 
  }
}
</script>

<style scoped>
.admin-pending-table { position: relative; display: flex; flex-direction: column; flex: 1; }
.mobile-only { display: none; }
.main-item-cell { display: flex; align-items: center; gap: 1rem; }
.item-text { flex: 1; min-width: 0; display: flex; flex-direction: column; }
.type-badge { background: var(--bg-app); border: 1px solid var(--border); color: var(--text-muted); padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem; text-transform: uppercase; font-weight: 600; }
.admin-section-footer { display: flex; justify-content: space-between; align-items: center; padding-top: 1.5rem; border-top: 1px solid var(--border); margin-top: auto; }
.footer-info { font-size: 0.85rem; color: var(--text-muted); font-weight: 500; }
.footer-info strong { color: var(--text-main); }

@media (max-width: 768px) {
  .mobile-only { display: block; }
  .desktop-only { display: none !important; }
}
</style>
