<template>
  <div class="admin-barcodes-table">
    <BaseLoader :show="isBarcodesLoading" />

    <BaseTable>
      <template #thead>
        <thead>
          <tr>
            <th>{{ $t('admin.table.ean') }}</th>
            <th>{{ $t('admin.table.beer') }}</th>
            <th>{{ $t('admin.table.brewery') }}</th>
            <th>{{ $t('admin.table.packaging') }}</th>
            <th>{{ $t('admin.table.volume') }}</th>
            <th class="w-100 text-right">{{ $t('admin.table.actions') }}</th>
          </tr>
        </thead>
      </template>

      <template #tbody>
        <tr v-for="item in paginatedItems" :key="item?.id">
          <td :data-label="$t('admin.table.ean')">
            <div class="main-item-cell">
              <BaseEntityIcon :icon="BarcodeIcon" bg-class="barcodes-bg" />
              <div class="item-text">
                <strong>{{ item.ean }}</strong>
                <small class="mobile-only text-muted">{{ item.beer_name }} ({{ item.volume }} l)</small>
              </div>
            </div>
          </td>
          <td :data-label="$t('admin.table.beer')" class="desktop-only">
            {{ item.beer_name }}
          </td>
          <td :data-label="$t('admin.table.brewery')" class="desktop-only">
            {{ item.brewery_name }}
          </td>
          <td :data-label="$t('admin.table.packaging')" class="desktop-only">
            <span class="badge type-badge">{{ translatePackaging(item.packaging) }}</span>
          </td>
          <td :data-label="$t('admin.table.volume')" class="desktop-only">{{ item.volume }} l</td>
          <td :data-label="$t('admin.table.actions')">
            <BaseActionGroup>
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

    <AddBarcodeModal :show="modals.edit" :isEditing="true" :form="formData" @close="modals.edit = false" @submit="submitEdit" />
    <DeleteConfirmModal :show="modals.delete" @close="modals.delete = false" @confirm="handleDelete" />
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { BarcodeIcon, PencilIcon, Trash2Icon, SearchXIcon } from 'lucide-vue-next'
import { useI18n } from 'vue-i18n'

import { apiFetch } from '../../api'
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

import AddBarcodeModal from '../modals/AddBarcodeModal.vue'
import DeleteConfirmModal from '../modals/DeleteConfirmModal.vue'

const props = defineProps({
  searchQuery: {
    type: String,
    default: ''
  }
})

const { t, te } = useI18n()
const toastStore = useToastStore()
const { allBarcodes, isBarcodesLoading, fetchBarcodes } = useAdmin()

const currentPage = ref(1)
const itemsPerPage = 30
const isMobileMode = ref(window.innerWidth <= 768)

const handleResize = () => { isMobileMode.value = window.innerWidth <= 768 }

// Stavy a data specifická pro čárové kódy
const modals = ref({ edit: false, delete: false })
const formData = ref({ id: null, ean: '', beer_id: '', packaging: 'láhev', volume: '0.50' })
const deleteId = ref(null)

const translatePackaging = (val) => {
  if (!val) return val
  const key = `packaging.${val}`
  return te(key) ? t(key) : val
}

const filteredItems = computed(() => {
  let items = [...allBarcodes.value]
  if (props.searchQuery) {
    const q = props.searchQuery.toLowerCase()
    items = items.filter(item => 
      item.ean.includes(q) || 
      item.beer_name?.toLowerCase().includes(q) || 
      item.brewery_name?.toLowerCase().includes(q)
    )
  }
  return items // Čárové kódy se obvykle neřadí podle názvu, bereme je z API
})

const totalPages = computed(() => Math.ceil(filteredItems.value.length / itemsPerPage))
const paginatedItems = computed(() => isMobileMode.value 
  ? filteredItems.value.slice(0, currentPage.value * itemsPerPage) 
  : filteredItems.value.slice((currentPage.value - 1) * itemsPerPage, currentPage.value * itemsPerPage)
)

watch(() => props.searchQuery, () => { currentPage.value = 1 })

onMounted(() => {
  if (allBarcodes.value.length === 0) fetchBarcodes()
  window.addEventListener('resize', handleResize)
})

onUnmounted(() => {
  window.removeEventListener('resize', handleResize)
})

// --- Akce ---

const openEditModal = (item) => {
  formData.value = { ...item }
  modals.value.edit = true
}

const submitEdit = async () => {
  try {
    const res = await apiFetch('/update_barcode.php', { method: 'POST', body: JSON.stringify(formData.value) })
    if (res.status === 'success') {
      toastStore.showToast(res.message)
      modals.value.edit = false
      fetchBarcodes()
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
  try {
    const res = await apiFetch('/delete_barcode.php', { method: 'POST', body: JSON.stringify({ id: deleteId.value }) })
    if (res.status === 'success') {
      toastStore.showToast("Smazáno")
      fetchBarcodes()
    } else {
      toastStore.showToast(res.message || 'Error', 'toast-error')
    }
  } catch {
    toastStore.showToast(t('toast.delete_error'), 'toast-error')
  } finally {
    modals.value.delete = false
  }
}
</script>

<style scoped>
.admin-barcodes-table { position: relative; display: flex; flex-direction: column; flex: 1; }
.mobile-only { display: none; }
.main-item-cell { display: flex; align-items: center; gap: 1rem; }
.item-text { flex: 1; min-width: 0; display: flex; flex-direction: column; }
.barcodes-bg { background-color: rgba(59, 130, 246, 0.1); color: #3b82f6; }
.type-badge { background: var(--bg-app); border: 1px solid var(--border); color: var(--text-muted); padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem; text-transform: uppercase; font-weight: 600; }
.admin-section-footer { display: flex; justify-content: space-between; align-items: center; padding-top: 1.5rem; border-top: 1px solid var(--border); margin-top: auto; }
.footer-info { font-size: 0.85rem; color: var(--text-muted); font-weight: 500; }
.footer-info strong { color: var(--text-main); }

@media (max-width: 768px) {
  .mobile-only { display: block; }
  .desktop-only { display: none !important; }
}
</style>
