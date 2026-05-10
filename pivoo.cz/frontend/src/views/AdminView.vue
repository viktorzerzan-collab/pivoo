<template>
  <div class="admin-page">
    <div class="admin-header">
      <BaseSwitch v-model="activeTab" :options="tabs" />
    </div>

    <div class="admin-layout">
      <BaseLoader :show="isLoading || isUsersLoading" />

      <BasePanel class="admin-section">
        <div class="section-header">
          <div class="header-info">
            <FilterInput 
              v-model="searchQuery" 
              :placeholder="$t('admin.search_placeholder', { section: getTabLabel(activeTab).toLowerCase() })" 
              class="admin-search"
            />
          </div>
          <BaseButton v-if="activeTab !== 'users' && activeTab !== 'pending'" variant="add" @click="openAddModal(activeTab)" class="mobile-full-width">
            <template #icon><PlusIcon :size="20" /></template>
            {{ $t('admin.add_item', { item: currentLabelSingle }) }}
          </BaseButton>
        </div>

        <BaseTable>
          <template #thead>
            <thead>
              <tr v-if="activeTab === 'users'">
                <th>{{ $t('admin.table.user') }}</th>
                <th>{{ $t('admin.table.email') }}</th>
                <th>{{ $t('admin.table.role') }}</th>
                <th class="w-100 text-right">{{ $t('admin.table.actions') }}</th>
              </tr>
              <tr v-else-if="activeTab === 'pending'">
                <th>{{ $t('admin.table.name') }}</th>
                <th>{{ $t('admin.table.type') }}</th>
                <th>{{ $t('admin.table.author') }}</th>
                <th class="w-100 text-right">{{ $t('admin.table.actions') }}</th>
              </tr>
              <tr v-else>
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
            <template v-if="activeTab === 'users'">
              <tr v-for="u in paginatedUsers" :key="u?.id" :class="{ 'banned-row': u.is_banned }">
                <td :data-label="$t('admin.table.user')">
                  <div class="user-cell">
                    <BaseEntityIcon 
                      :image-url="u.avatar ? 'https://www.pivoo.cz/backend/uploads/avatars/' + u.avatar : null"
                      :icon="UserIcon"
                      bg-class="users-bg"
                    />
                    <div class="item-text">
                      <div class="info-top-row">
                        <strong :class="{ 'text-muted line-through': u.is_banned }">{{ u.username }}</strong>
                        <span class="badge mobile-only" :class="u.role">{{ u.role }}</span>
                        <span v-if="u.is_banned" class="badge banned-badge">{{ $t('admin.banned') }}</span>
                      </div>
                      <small class="meta-row">{{ u.first_name }} {{ u.last_name }} <span class="mobile-only meta-sep">• {{ u.email }}</span></small>
                    </div>
                  </div>
                </td>
                <td :data-label="$t('admin.table.email')" class="desktop-only">{{ u.email }}</td>
                <td :data-label="$t('admin.table.role')" class="desktop-only">
                  <span class="badge" :class="u.role">{{ u.role }}</span>
                </td>
                <td :data-label="$t('admin.table.actions')">
                  <BaseActionGroup>
                    <BaseTooltip :text="$t('admin.tooltips.edit_user')" position="top-end">
                      <BaseButton variant="edit" :isIconOnly="true" @click="openEditModal(u, 'users')">
                        <template #icon><PencilIcon :size="16" /></template>
                      </BaseButton>
                    </BaseTooltip>
                    <BaseTooltip v-if="u?.id !== user?.id" :text="$t('admin.tooltips.change_pwd')" position="top-end">
                      <BaseButton variant="add" :isIconOnly="true" @click="openPasswordModal(u)">
                        <template #icon><KeyIcon :size="16" /></template>
                      </BaseButton>
                    </BaseTooltip>
                    <BaseTooltip v-if="u?.id !== user?.id" :text="u.is_banned ? $t('admin.tooltips.unblock') : $t('admin.tooltips.block')" position="top-end">
                      <BaseButton :variant="u.is_banned ? 'primary' : 'edit'" :isIconOnly="true" :style="u.is_banned ? 'background-color: #10b981; color: white;' : ''" @click="openBanModal(u)">
                        <template #icon>
                          <UnlockIcon v-if="u.is_banned" :size="16" />
                          <BanIcon v-else :size="16" />
                        </template>
                      </BaseButton>
                    </BaseTooltip>
                    <BaseTooltip v-if="u?.id !== user?.id" :text="$t('admin.tooltips.delete_user')" position="top-end">
                      <BaseButton variant="danger" :isIconOnly="true" @click="confirmDelete(u.id, activeTab)">
                        <template #icon><Trash2Icon :size="16" /></template>
                      </BaseButton>
                    </BaseTooltip>
                  </BaseActionGroup>
                </td>
              </tr>
            </template>

            <template v-else-if="activeTab === 'pending'">
              <tr v-for="item in paginatedCurrentItems" :key="item?.entity_type + item?.id">
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

            <template v-else>
              <tr v-for="item in paginatedCurrentItems" :key="item?.id">
                <td :data-label="$t('admin.table.name')">
                  <div class="main-item-cell">
                    <BaseEntityIcon 
                      :image-url="activeTab === 'breweries' && item.logo ? 'https://www.pivoo.cz/backend/uploads/logos/' + item.logo : null"
                      :icon="activeTab === 'beers' ? BeerIcon : (activeTab === 'breweries' ? FactoryIcon : (activeTab === 'locations' ? MapPinIcon : HopIcon))"
                      :bg-class="activeTab + '-bg'"
                    />
                    <div class="item-text">
                      <strong>{{ item.name }}</strong>
                      <small v-if="activeTab === 'beers'" class="mobile-only">{{ item.brewery_name }} • {{ item.style }}</small>
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
                <td v-if="activeTab === 'locations'" class="desktop-only">{{ item.type === 'hospoda' ? $t('admin.type_pub') : (item.type === 'jine' ? $t('admin.type_other') : item.type) }}</td>
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
                      <BaseButton variant="edit" :isIconOnly="true" @click="openEditModal(item, activeTab)">
                        <template #icon><PencilIcon :size="16" /></template>
                      </BaseButton>
                    </BaseTooltip>
                    <BaseTooltip :text="$t('admin.tooltips.delete')" position="top-end">
                      <BaseButton variant="danger" :isIconOnly="true" @click="confirmDelete(item.id, activeTab)">
                        <template #icon><Trash2Icon :size="16" /></template>
                      </BaseButton>
                    </BaseTooltip>
                  </BaseActionGroup>
                </td>
              </tr>
            </template>
          </template>
        </BaseTable>
        
        <BaseEmptyState 
          v-if="(activeTab === 'users' && filteredUsers.length === 0) || (activeTab !== 'users' && filteredCurrentItems.length === 0)" 
          :text="$t('admin.empty_search')" 
          :icon="SearchXIcon" 
          :icon-size="40"
        />

        <div class="admin-section-footer">
          <div class="footer-info desktop-only">
            {{ $t('admin.showing') }} <strong>{{ activeTab === 'users' ? paginatedUsers.length : paginatedCurrentItems.length }}</strong> {{ $t('admin.of') }} <strong>{{ activeTab === 'users' ? filteredUsers.length : filteredCurrentItems.length }}</strong> {{ $t('admin.records') }}
          </div>
          <div class="desktop-only">
            <BasePagination v-if="totalPages > 1" v-model:currentPage="currentPage" :total-pages="totalPages" />
          </div>
        </div>
      </BasePanel>
    </div>

    <DeleteConfirmModal :show="deleteModal.show" @close="deleteModal.show = false" @confirm="handleDelete" />
    <AddBeerModal :show="modals.beer" :isEditing="isEditing" :form="formData.beer" @close="modals.beer = false" @submit="submitForm('beer')" />
    <AddBreweryModal :show="modals.brewery" :isEditing="isEditing" :countries="countries" :form="formData.brewery" @close="modals.brewery = false" @submit="submitForm('brewery')" />
    <AddLocationModal :show="modals.location" :isEditing="isEditing" :countries="countries" :form="formData.location" @close="modals.location = false" @submit="submitForm('location')" />
    <EditUserModal :show="modals.user" :form="formData.user" :is-current-user="formData.user.id === user?.id" @close="modals.user = false" @submit="submitForm('user')" @remove-avatar="handleRemoveAvatar" />
    <ChangePasswordModal :show="modals.password" :user="selectedUserForPassword" @close="modals.password = false" @submit="handlePasswordChange" />
    <BanConfirmModal :show="modals.ban" :user="selectedUserForBan" @close="modals.ban = false" @confirm="handleBanConfirm" />
    <RemoveAvatarConfirmModal :show="modals.removeAvatar" :user="selectedUserForAvatarRemove" :is-current-user="selectedUserForAvatarRemove?.id === user?.id" @close="modals.removeAvatar = false" @confirm="executeRemoveAvatar" />
    
    <BaseModal :show="modals.style" @close="modals.style = false">
      <template #header><h2>{{ isEditing ? $t('admin.edit_style') : $t('admin.add_style') }}</h2></template>
      <template #body>
        <form @submit.prevent="submitForm('style')" class="modal-form">
          <BaseInput v-model="formData.style.name" :label="$t('admin.style_name')" required />
          <BaseButton type="submit" variant="add" style="padding: 1rem;">
            <template #icon><SaveIcon :size="18" /></template>
            {{ $t('admin.save_style') }}
          </BaseButton>
        </form>
      </template>
    </BaseModal>

    <BaseModal :show="modals.merge" @close="modals.merge = false">
      <template #header><h2 class="merge-title"><GitMergeIcon color="#8b5cf6" /> {{ $t('admin.merge_title') }}</h2></template>
      <template #body>
        <form @submit.prevent="submitMerge" class="modal-form">
          <p class="merge-desc">{{ $t('admin.merge_desc', { source: mergeForm.source?.name }) }}</p>
          <BaseSelect v-model="mergeForm.target_id" :label="$t('admin.merge_target')" :placeholder="$t('admin.merge_target_placeholder')" searchable required>
            <option disabled value="">{{ $t('admin.merge_target_placeholder') }}</option>
            <option v-for="loc in mergeTargetOptions" :key="loc.id" :value="loc.id">{{ loc.name }} ({{ loc.city || $t('admin.merge_no_city') }})</option>
          </BaseSelect>
          <BaseButton type="submit" variant="primary" style="width: 100%; padding: 1rem; font-weight: 700; background-color: #8b5cf6; color: white;">
            {{ $t('admin.merge_submit') }}
          </BaseButton>
        </form>
      </template>
    </BaseModal>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { 
  PlusIcon, PencilIcon, Trash2Icon, SaveIcon, KeyIcon, BanIcon, 
  UnlockIcon, UserIcon, SearchXIcon, BeerIcon, FactoryIcon, MapPinIcon, HopIcon,
  GitMergeIcon, ListChecksIcon, CheckIcon
} from 'lucide-vue-next'
import { useI18n } from 'vue-i18n'
import { apiFetch } from '../api'
import { useAuthStore } from '../stores/auth'
import { useCatalogStore } from '../stores/catalog'
import { useToastStore } from '../stores/toast'

import BaseButton from '../components/BaseButton.vue'
import BaseInput from '../components/BaseInput.vue'
import BaseModal from '../components/BaseModal.vue'
import BaseLoader from '../components/BaseLoader.vue'
import BasePanel from '../components/BasePanel.vue'
import BaseTable from '../components/BaseTable.vue'
import BaseEntityIcon from '../components/BaseEntityIcon.vue'
import BaseActionGroup from '../components/BaseActionGroup.vue'
import BaseEmptyState from '../components/BaseEmptyState.vue'
import BasePagination from '../components/BasePagination.vue'
import FilterInput from '../components/FilterInput.vue'
import BaseSelect from '../components/BaseSelect.vue' 
import BaseTooltip from '../components/BaseTooltip.vue'
import BaseSwitch from '../components/BaseSwitch.vue'
import DeleteConfirmModal from '../components/modals/DeleteConfirmModal.vue'
import AddBeerModal from '../components/modals/AddBeerModal.vue'
import AddBreweryModal from '../components/modals/AddBreweryModal.vue'
import AddLocationModal from '../components/modals/AddLocationModal.vue'
import EditUserModal from '../components/modals/EditUserModal.vue'
import ChangePasswordModal from '../components/modals/ChangePasswordModal.vue'
import BanConfirmModal from '../components/modals/BanConfirmModal.vue'
import RemoveAvatarConfirmModal from '../components/modals/RemoveAvatarConfirmModal.vue'

const authStore = useAuthStore()
const catalogStore = useCatalogStore()
const toastStore = useToastStore()
const { t } = useI18n()

const { user } = storeToRefs(authStore)
const { beers, breweries, locations, styles, countries, isLoading } = storeToRefs(catalogStore)

const activeTab = ref('users')
const allUsers = ref([])
const isUsersLoading = ref(false)
const deleteModal = ref({ show: false, id: null, type: '' })
const modals = ref({ beer: false, brewery: false, location: false, style: false, user: false, password: false, ban: false, removeAvatar: false, merge: false })
const mergeForm = ref({ source: null, target_id: '' })
const isEditing = ref(false)
const selectedUserForPassword = ref(null)
const selectedUserForBan = ref(null)
const selectedUserForAvatarRemove = ref(null)
const searchQuery = ref('')
const currentPage = ref(1)
const itemsPerPage = 30
const isMobileMode = ref(window.innerWidth <= 768)

const formData = ref({
  beer: { id: null, name: '', brewery_id: '', style_id: '', epm: '', abv: '', ibu: '', ebc: '', hops: '', malts: '', fermentation: '', tags: '', is_unfiltered: false, is_unpasteurized: false },
  brewery: { id: null, name: '', city: '', zip_code: '', country_id: 1, address: '', email: '', phone: '', website: '', logoFile: null, lat: null, lng: null },
  location: { id: null, name: '', type: 'hospoda', city: '', zip_code: '', country_id: 1, address: '', email: '', phone: '', website: '', opening_hours: '' },
  style: { id: null, name: '' },
  user: { id: null, first_name: '', last_name: '', username: '', email: '', role: 'user', avatar: null }
})

// LOGIKA
const handleResize = () => { isMobileMode.value = window.innerWidth <= 768 }
watch(activeTab, (newTab) => { searchQuery.value = ''; currentPage.value = 1; if (newTab === 'pending') catalogStore.fetchPendingApprovals() })
watch(searchQuery, () => { currentPage.value = 1 })

const tabs = computed(() => [
  { value: 'users', label: t('admin.tabs.users'), icon: UserIcon },
  { value: 'pending', label: t('admin.tabs.pending'), icon: ListChecksIcon },
  { value: 'beers', label: t('admin.tabs.beers'), icon: BeerIcon },
  { value: 'breweries', label: t('admin.tabs.breweries'), icon: FactoryIcon },
  { value: 'locations', label: t('admin.tabs.locations'), icon: MapPinIcon },
  { value: 'styles', label: t('admin.tabs.styles'), icon: HopIcon }
])

const getTabLabel = (val) => tabs.value.find(t => t.value === val)?.label || ''
const currentLabelSingle = computed(() => activeTab.value === 'pending' ? '' : t(`admin.items.${activeTab.value === 'users' ? 'user' : (activeTab.value === 'beers' ? 'beer' : (activeTab.value === 'breweries' ? 'brewery' : (activeTab.value === 'locations' ? 'location' : 'style')))}`))

const filteredUsers = computed(() => {
  let items = [...allUsers.value]
  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase()
    items = items.filter(u => u.username.toLowerCase().includes(q) || u.email.toLowerCase().includes(q) || `${u.first_name} ${u.last_name}`.toLowerCase().includes(q))
  }
  return items.sort((a, b) => (a.username || '').localeCompare(b.username || '', 'cs'))
})

const currentItems = computed(() => ({ beers: beers.value, breweries: breweries.value, locations: locations.value, styles: styles.value, pending: catalogStore.pendingApprovals || [] }[activeTab.value] || []))
const filteredCurrentItems = computed(() => {
  let items = [...currentItems.value]
  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase()
    items = items.filter(item => item.name.toLowerCase().includes(q))
  }
  return items.sort((a, b) => (a.name || '').localeCompare(b.name || '', 'cs'))
})

const mergeTargetOptions = computed(() => mergeForm.value.source ? catalogStore.allLocations.filter(l => l.id !== mergeForm.value.source.id).sort((a, b) => (a.name || '').localeCompare(b.name || '', 'cs')) : [])
const totalPages = computed(() => Math.ceil((activeTab.value === 'users' ? filteredUsers.value.length : filteredCurrentItems.value.length) / itemsPerPage))
const paginatedUsers = computed(() => isMobileMode.value ? filteredUsers.value.slice(0, currentPage.value * itemsPerPage) : filteredUsers.value.slice((currentPage.value - 1) * itemsPerPage, currentPage.value * itemsPerPage))
const paginatedCurrentItems = computed(() => isMobileMode.value ? filteredCurrentItems.value.slice(0, currentPage.value * itemsPerPage) : filteredCurrentItems.value.slice((currentPage.value - 1) * itemsPerPage, currentPage.value * itemsPerPage))

const loadMoreTrigger = ref(null)
let observer = null
watch(loadMoreTrigger, (el) => {
  if (observer) observer.disconnect()
  if (el) {
    observer = new IntersectionObserver((entries) => { if (entries[0].isIntersecting && isMobileMode.value && currentPage.value < totalPages.value) currentPage.value++ }, { rootMargin: '200px' })
    observer.observe(el)
  }
})

const fetchUsers = async () => { isUsersLoading.value = true; try { const res = await apiFetch('/users.php'); if (res.status === 'success') allUsers.value = res.data } finally { isUsersLoading.value = false } }
onMounted(() => { catalogStore.fetchAllData(); fetchUsers(); window.addEventListener('resize', handleResize) })
onUnmounted(() => { window.removeEventListener('resize', handleResize); if (observer) observer.disconnect() })

const openAddModal = (t) => { 
  isEditing.value = false
  Object.keys(modals.value).forEach(m => modals.value[m] = false)
  const keyMap = t === 'breweries' ? 'brewery' : (t === 'beers' ? 'beer' : (t === 'locations' ? 'location' : (t === 'styles' ? 'style' : 'user')))
  if (keyMap === 'beer') formData.value.beer = { id: null, name: '', brewery_id: '', style_id: '', epm: '', abv: '', ibu: '', ebc: '', hops: '', malts: '', fermentation: '', tags: '', is_unfiltered: false, is_unpasteurized: false }
  else if (keyMap === 'brewery') formData.value.brewery = { id: null, name: '', city: '', zip_code: '', country_id: 1, address: '', email: '', phone: '', website: '', logoFile: null, lat: null, lng: null }
  else if (keyMap === 'location') formData.value.location = { id: null, name: '', type: 'hospoda', city: '', zip_code: '', country_id: 1, address: '', email: '', phone: '', website: '', opening_hours: '' }
  else if (keyMap === 'style') formData.value.style = { id: null, name: '' }
  else if (keyMap === 'user') formData.value.user = { id: null, first_name: '', last_name: '', username: '', email: '', role: 'user', avatar: null }
  modals.value[keyMap] = true 
}

const openEditModal = (item, typeParam) => { 
  isEditing.value = true
  const key = typeParam === 'styles' ? 'style' : (typeParam === 'beers' ? 'beer' : (typeParam === 'locations' ? 'location' : (typeParam === 'breweries' ? 'brewery' : 'user')))
  formData.value[key] = key === 'beer' ? { ...item, is_unfiltered: !!item.is_unfiltered, is_unpasteurized: !!item.is_unpasteurized } : { ...item, logoFile: null }
  modals.value[key] = true 
}

const openMergeModal = (item) => { mergeForm.value = { source: item, target_id: '' }; modals.value.merge = true }
const submitMerge = async () => { if (!mergeForm.value.source || !mergeForm.value.target_id) return; try { const res = await apiFetch('/merge_locations.php', { method: 'POST', body: JSON.stringify({ source_id: mergeForm.value.source.id, target_id: mergeForm.value.target_id }) }); if (res.status === 'success') { toastStore.showToast(res.message); modals.value.merge = false; catalogStore.locations = catalogStore.locations.filter(l => l.id !== mergeForm.value.source.id) } else { toastStore.showToast(res.message || 'Error', 'toast-error') } } catch { toastStore.showToast(t('toast.communication_error'), 'toast-error') } }
const openPasswordModal = (u) => { selectedUserForPassword.value = u; modals.value.password = true }
const handlePasswordChange = async (payload) => { try { const res = await apiFetch('/admin_change_password.php', { method: 'POST', body: JSON.stringify(payload) }); if (res.status === 'success') { toastStore.showToast(res.message); modals.value.password = false } else toastStore.showToast(res.message || 'Error', 'toast-error') } catch { toastStore.showToast(t('toast.communication_error'), 'toast-error') } }
const handleRemoveAvatar = (userId) => { selectedUserForAvatarRemove.value = allUsers.value.find(u => u.id === userId); modals.value.removeAvatar = true }
const executeRemoveAvatar = async (userId) => { try { const res = await apiFetch('/admin_remove_avatar.php', { method: 'POST', body: JSON.stringify({ user_id: userId }) }); if (res.status === 'success') { toastStore.showToast(res.message); modals.value.removeAvatar = false; modals.value.user = false; fetchUsers() } else toastStore.showToast(res.message || 'Error', 'toast-error') } catch { toastStore.showToast(t('toast.communication_error'), 'toast-error') } }
const openBanModal = (u) => { selectedUserForBan.value = u; modals.value.ban = true }
const handleBanConfirm = async (u) => { try { const res = await apiFetch('/admin_toggle_ban.php', { method: 'POST', body: JSON.stringify({ user_id: u.id, is_banned: u.is_banned ? 0 : 1 }) }); if (res.status === 'success') { toastStore.showToast(res.message); modals.value.ban = false; fetchUsers() } else toastStore.showToast(res.message || 'Error', 'toast-error') } catch { toastStore.showToast(t('toast.communication_error'), 'toast-error') } }

const submitForm = async (f) => {
  try {
    const endpoint = isEditing.value ? `update_${f}.php` : `add_${f}.php`
    let body;
    if (f === 'brewery') { body = new FormData(); Object.keys(formData.value[f]).forEach(k => { if (formData.value[f][k] != null) body.append(k, formData.value[f][k]) }) }
    else body = JSON.stringify(formData.value[f])
    const res = await apiFetch(`/${endpoint}`, { method: 'POST', body });
    if (res.status === 'success') { toastStore.showToast(res.message); modals.value[f] = false; if (f === 'user') fetchUsers(); else catalogStore.fetchAllData() } 
    else toastStore.showToast(res.message || 'Error', 'toast-error')
  } catch { toastStore.showToast(t('toast.communication_error'), 'toast-error') }
}

const confirmDelete = (id, typeParam) => { deleteModal.value = { show: true, id, type: { users: 'user', beers: 'beer', breweries: 'brewery', locations: 'location', styles: 'style' }[typeParam] } }
const handleDelete = async () => { try { const res = await apiFetch(`/delete_${deleteModal.value.type}.php`, { method: 'POST', body: JSON.stringify({ id: deleteModal.value.id }) }); if (res.status === 'success') { toastStore.showToast("Smazáno"); if (deleteModal.value.type === 'user') fetchUsers(); else catalogStore.fetchAllData() } else toastStore.showToast(res.message || 'Error', 'toast-error') } catch { toastStore.showToast(t('toast.delete_error'), 'toast-error') } finally { deleteModal.value.show = false } }
const handleApprove = async (item, action) => { try { const res = await apiFetch('/approve_entity.php', { method: 'POST', body: JSON.stringify({ entity_type: item.entity_type, entity_id: item.id, action }) }); if (res.status === 'success') { toastStore.showToast(res.message); catalogStore.fetchPendingApprovals(); catalogStore.fetchAllData() } else toastStore.showToast(res.message || 'Error', 'toast-error') } catch { toastStore.showToast(t('toast.communication_error'), 'toast-error') } }
</script>

<style scoped>
.admin-page { flex: 1; display: flex; flex-direction: column; }
.admin-header { margin-bottom: 2rem; overflow-x: auto; padding-bottom: 0.5rem; border-bottom: 1px solid var(--border); }
.admin-layout { position: relative; flex: 1; min-height: 400px; display: flex; flex-direction: column; }
.admin-section { display: flex; flex-direction: column; flex: 1; }
.section-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 2.5rem; gap: 2rem; }
.header-info { flex: 1; }
.admin-search { max-width: 380px; }
.user-cell, .main-item-cell { display: flex; align-items: center; gap: 1rem; }
.item-text { flex: 1; min-width: 0; display: flex; flex-direction: column; }
.info-top-row { display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; }
.meta-row { display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.meta-sep { color: var(--text-muted); opacity: 0.7; margin-left: 4px; }
.combined-meta { display: none; align-items: center; gap: 5px; }
.mobile-flag { width: 16px; height: auto; }
.banned-badge { background: rgba(239, 68, 68, 0.1); color: #ef4444; font-size: 0.6rem; border: 1px solid rgba(239, 68, 68, 0.2); }
.banned-row td { opacity: 0.7; }
.line-through { text-decoration: line-through; }
.admin-section-footer { display: flex; justify-content: space-between; align-items: center; padding-top: 1.5rem; border-top: 1px solid var(--border); margin-top: auto; }
.footer-info { font-size: 0.85rem; color: var(--text-muted); font-weight: 500; }
.footer-info strong { color: var(--text-main); }
.country-cell { display: flex; align-items: center; gap: 0.5rem; }
.admin-flag-icon { width: 18px; height: auto; border-radius: 2px; }
.modal-form { display: flex; flex-direction: column; gap: 1.5rem; }
.merge-title { display: flex; align-items: center; gap: 0.5rem; margin: 0; }
.merge-desc { margin: 0; color: var(--text-muted); line-height: 1.4; }
.type-badge { background: var(--bg-app); border: 1px solid var(--border); color: var(--text-muted); }
.load-more-trigger { height: 20px; width: 100%; }

@media (max-width: 768px) {
  .section-header { flex-direction: column; align-items: stretch; gap: 1rem; }
  .admin-search { max-width: none; }
  .mobile-full-width { order: -1; padding: 1rem; width: 100%; justify-content: center; }
  .mobile-only { display: block; }
  .combined-meta { display: flex !important; }
  .desktop-only { display: none !important; }
}
</style>