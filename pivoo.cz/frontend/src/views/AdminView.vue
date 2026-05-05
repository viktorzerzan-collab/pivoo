<template>
  <div class="admin-page">
    <div class="admin-header">
      <BaseSwitch v-model="activeTab" :options="tabs" />
    </div>

    <div class="admin-layout">
      <BaseLoader :show="isLoading || isUsersLoading" />

      <div class="admin-section">
        <div class="section-header">
          <div class="header-info">
            <FilterInput 
              v-model="searchQuery" 
              :placeholder="$t('admin.search_placeholder', { section: getTabLabel(activeTab).toLowerCase() })" 
              class="admin-search"
            />
          </div>
          <button v-if="activeTab !== 'users' && activeTab !== 'pending'" class="btn-add" @click="openAddModal(activeTab)">
            <PlusIcon :size="20" /> {{ $t('admin.add_item', { item: currentLabelSingle }) }}
          </button>
        </div>

        <div class="admin-table-wrapper">
          <table class="admin-table">
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
            <tbody>
              <template v-if="activeTab === 'users'">
                <tr v-for="u in paginatedUsers" :key="u?.id" :class="{ 'banned-row': u.is_banned }">
                  <td :data-label="$t('admin.table.user')">
                    <div class="td-content user-cell">
                      <div class="section-icon users-bg" :class="{ 'has-image': u.avatar }">
                        <img v-if="u.avatar" :src="'https://www.pivoo.cz/backend/uploads/avatars/' + u.avatar" alt="Avatar" />
                        <UserIcon v-else :size="20" color="var(--primary)" stroke-width="2" />
                      </div>
                      <div class="user-info">
                        <div class="info-top-row">
                          <strong :style="u.is_banned ? 'text-decoration: line-through; color: var(--text-muted);' : ''">
                            {{ u.username }}
                          </strong>
                          <span class="badge mobile-only" :class="u.role" style="font-size: 0.6rem; padding: 2px 6px;">
                            {{ u.role }}
                          </span>
                          <span v-if="u.is_banned" class="badge banned-badge">{{ $t('admin.banned') }}</span>
                        </div>
                        <small class="user-meta-row">
                          {{ u.first_name }} {{ u.last_name }}
                          <span class="mobile-only meta-sep">• {{ u.email }}</span>
                        </small>
                      </div>
                    </div>
                  </td>
                  <td :data-label="$t('admin.table.email')" class="desktop-only">
                    <div class="td-content email-text">{{ u.email }}</div>
                  </td>
                  <td :data-label="$t('admin.table.role')" class="desktop-only">
                    <div class="td-content">
                      <span class="badge" :class="u.role">{{ u.role }}</span>
                    </div>
                  </td>
                  <td :data-label="$t('admin.table.actions')">
                    <div class="td-content action-buttons">
                      <BaseTooltip :text="$t('admin.tooltips.edit_user')" position="top-end">
                        <button class="btn-edit is-icon-only" @click="openEditModal(u, 'users')">
                          <PencilIcon :size="16" />
                        </button>
                      </BaseTooltip>
                      
                      <BaseTooltip :text="$t('admin.tooltips.change_pwd')" position="top-end">
                        <button v-if="u?.id !== user?.id" class="btn-primary is-icon-only" style="background-color: #3b82f6;" @click="openPasswordModal(u)">
                          <KeyIcon :size="16" />
                        </button>
                      </BaseTooltip>

                      <BaseTooltip :text="u.is_banned ? $t('admin.tooltips.unblock') : $t('admin.tooltips.block')" position="top-end">
                        <button v-if="u?.id !== user?.id" class="is-icon-only admin-action-btn" :style="u.is_banned ? 'background-color: #10b981;' : 'background-color: #64748b;'" @click="openBanModal(u)">
                          <UnlockIcon v-if="u.is_banned" :size="16" />
                          <BanIcon v-else :size="16" />
                        </button>
                      </BaseTooltip>

                      <BaseTooltip :text="$t('admin.tooltips.delete_user')" position="top-end">
                        <button v-if="u?.id !== user?.id" class="btn-danger is-icon-only" @click="confirmDelete(u.id, activeTab)">
                          <Trash2Icon :size="16" />
                        </button>
                      </BaseTooltip>
                    </div>
                  </td>
                </tr>
              </template>

              <template v-else-if="activeTab === 'pending'">
                <tr v-for="item in paginatedCurrentItems" :key="item?.entity_type + item?.id">
                  <td :data-label="$t('admin.table.name')">
                    <div class="td-content main-item-cell">
                      <div class="section-icon" :class="item.entity_type + 's-bg'">
                        <BeerIcon v-if="item.entity_type === 'beer'" :size="20" color="var(--primary)" />
                        <FactoryIcon v-else-if="item.entity_type === 'brewery'" :size="20" color="var(--primary)" />
                        <MapPinIcon v-else-if="item.entity_type === 'location'" :size="20" color="var(--primary)" />
                      </div>
                      <div class="item-text">
                        <div class="info-top-row">
                          <strong>{{ item.name }}</strong>
                        </div>
                      </div>
                    </div>
                  </td>
                  <td :data-label="$t('admin.table.type')" class="desktop-only">
                    <div class="td-content">
                      <span class="badge" style="background: var(--bg-app); border: 1px solid var(--border); color: var(--text-muted);">
                         {{ $t('admin.entity_' + item.entity_type) }}
                      </span>
                    </div>
                  </td>
                  <td :data-label="$t('admin.table.author')" class="desktop-only">
                    <div class="td-content">
                      {{ item.created_by_user || $t('admin.unknown_author') }}
                    </div>
                  </td>
                  <td :data-label="$t('admin.table.actions')">
                    <div class="td-content action-buttons">
                      <BaseTooltip :text="$t('admin.tooltips.approve')" position="top-end">
                        <button class="btn-primary is-icon-only" style="background-color: #10b981; color: white;" @click="handleApprove(item, 'approve')">
                          <CheckIcon :size="16" />
                        </button>
                      </BaseTooltip>
                      <BaseTooltip :text="$t('admin.tooltips.reject')" position="top-end">
                        <button class="btn-danger is-icon-only" @click="handleApprove(item, 'reject')">
                          <Trash2Icon :size="16" />
                        </button>
                      </BaseTooltip>
                    </div>
                  </td>
                </tr>
              </template>

              <template v-else>
                <tr v-for="item in paginatedCurrentItems" :key="item?.id">
                  <td :data-label="$t('admin.table.name')">
                    <div class="td-content main-item-cell">
                      <div class="section-icon" :class="[activeTab + '-bg', { 'has-image': activeTab === 'breweries' && item.logo }]">
                        <img v-if="activeTab === 'breweries' && item.logo" :src="'https://www.pivoo.cz/backend/uploads/logos/' + item.logo" alt="Logo" />
                        <BeerIcon v-else-if="activeTab === 'beers'" :size="20" color="var(--primary)" />
                        <FactoryIcon v-else-if="activeTab === 'breweries'" :size="20" color="var(--primary)" />
                        <MapPinIcon v-else-if="activeTab === 'locations'" :size="20" color="var(--primary)" />
                        <HopIcon v-else-if="activeTab === 'styles'" :size="20" color="var(--primary)" />
                      </div>
                      <div class="item-text">
                        <div class="info-top-row">
                          <strong>{{ item.name }}</strong>
                        </div>
                        
                        <small v-if="activeTab === 'beers'" class="mobile-only">{{ item.brewery_name }} • {{ item.style }}</small>
                        
                        <small v-if="['breweries', 'locations'].includes(activeTab)" class="mobile-only combined-meta">
                          <img v-if="item.country_code" :src="`https://flagcdn.com/w20/${item.country_code}.png`" class="mobile-flag" />
                          {{ item.city || $t('admin.unknown_location') }}{{ item.city && item.country ? ', ' : '' }}{{ item.country }}
                        </small>

                        <small v-if="activeTab === 'breweries'" class="mobile-only">{{ $t('admin.beers_in_catalog', { count: item.total_beers_in_catalog || 0 }) }}</small>
                        
                        <small v-if="activeTab === 'locations'" class="mobile-only">
                          {{ $t('admin.type', { type: item.type === 'hospoda' ? $t('admin.type_pub') : (item.type === 'jine' ? $t('admin.type_other') : item.type) }) }}
                        </small>
                      </div>
                    </div>
                  </td>

                  <td v-if="activeTab === 'breweries'" :data-label="$t('admin.table.beers_count')" class="desktop-only">
                    <div class="td-content">
                      <strong>{{ item.total_beers_in_catalog || 0 }}</strong>
                    </div>
                  </td>

                  <td v-if="activeTab === 'beers'" :data-label="$t('admin.table.brewery')" class="desktop-only">
                    <div class="td-content">
                      {{ item.brewery_name }}
                    </div>
                  </td>
                  <td v-if="activeTab === 'beers'" :data-label="$t('admin.table.style')" class="desktop-only">
                    <div class="td-content">
                      {{ item.style }}
                    </div>
                  </td>

                  <td v-if="activeTab === 'locations'" :data-label="$t('admin.table.location_type')" class="desktop-only">
                    <div class="td-content">
                      {{ item.type === 'hospoda' ? $t('admin.type_pub') : (item.type === 'jine' ? $t('admin.type_other') : item.type) }}
                    </div>
                  </td>

                  <td v-if="['breweries', 'locations'].includes(activeTab)" :data-label="$t('admin.table.city')" class="desktop-only">
                    <div class="td-content">
                      {{ item.city || '-' }}
                    </div>
                  </td>

                  <td v-if="['breweries', 'locations'].includes(activeTab)" :data-label="$t('admin.table.country')" class="desktop-only">
                    <div class="td-content country-cell">
                      <img v-if="item.country_code" :src="`https://flagcdn.com/w20/${item.country_code}.png`" class="admin-flag-icon" :alt="item.country" />
                      <span>{{ item.country || '-' }}</span>
                    </div>
                  </td>

                  <td :data-label="$t('admin.table.actions')">
                    <div class="td-content action-buttons">
                      
                      <BaseTooltip v-if="activeTab === 'locations'" :text="$t('admin.tooltips.merge')" position="top-end">
                        <button class="btn-primary is-icon-only" style="background-color: #8b5cf6; color: white; border: none;" @click="openMergeModal(item)">
                          <GitMergeIcon :size="16" />
                        </button>
                      </BaseTooltip>

                      <BaseTooltip :text="$t('admin.tooltips.edit')" position="top-end">
                        <button class="btn-edit is-icon-only" @click="openEditModal(item, activeTab)">
                          <PencilIcon :size="16" />
                        </button>
                      </BaseTooltip>

                      <BaseTooltip :text="$t('admin.tooltips.delete')" position="top-end">
                        <button class="btn-danger is-icon-only" @click="confirmDelete(item.id, activeTab)">
                          <Trash2Icon :size="16" />
                        </button>
                      </BaseTooltip>
                    </div>
                  </td>
                </tr>
              </template>
            </tbody>
          </table>
          
          <div v-if="(activeTab === 'users' && filteredUsers.length === 0) || (activeTab !== 'users' && filteredCurrentItems.length === 0)" class="admin-empty-search">
            <SearchXIcon :size="40" color="var(--text-muted)" />
            <p>{{ $t('admin.empty_search') }}</p>
          </div>

          <div ref="loadMoreTrigger" class="load-more-trigger"></div>
        </div>
        
        <div class="admin-section-footer">
          <div class="footer-info desktop-only">
            {{ $t('admin.showing') }} <strong>{{ activeTab === 'users' ? paginatedUsers.length : paginatedCurrentItems.length }}</strong> {{ $t('admin.of') }} <strong>{{ activeTab === 'users' ? filteredUsers.length : filteredCurrentItems.length }}</strong> {{ $t('admin.records') }}
          </div>
          
          <div class="desktop-only">
            <BasePagination 
              v-if="totalPages > 1"
              v-model:currentPage="currentPage" 
              :total-pages="totalPages" 
            />
          </div>
        </div>
      </div>
    </div>

    <DeleteConfirmModal :show="deleteModal.show" @close="deleteModal.show = false" @confirm="handleDelete" />
    
    <AddBeerModal :show="modals.beer" :isEditing="isEditing" :form="formData.beer" @close="modals.beer = false" @submit="submitForm('beer')" />
    
    <AddBreweryModal :show="modals.brewery" :isEditing="isEditing" :countries="countries" :form="formData.brewery" @close="modals.brewery = false" @submit="submitForm('brewery')" />
    <AddLocationModal :show="modals.location" :isEditing="isEditing" :countries="countries" :form="formData.location" @close="modals.location = false" @submit="submitForm('location')" />
    
    <EditUserModal 
      :show="modals.user" 
      :form="formData.user" 
      :is-current-user="formData.user.id === user?.id"
      @close="modals.user = false" 
      @submit="submitForm('user')" 
      @remove-avatar="handleRemoveAvatar"
    />
    
    <ChangePasswordModal 
      :show="modals.password" 
      :user="selectedUserForPassword" 
      @close="modals.password = false" 
      @submit="handlePasswordChange" 
    />

    <BanConfirmModal 
      :show="modals.ban" 
      :user="selectedUserForBan" 
      @close="modals.ban = false" 
      @confirm="handleBanConfirm" 
    />

    <RemoveAvatarConfirmModal 
      :show="modals.removeAvatar" 
      :user="selectedUserForAvatarRemove"
      :is-current-user="selectedUserForAvatarRemove?.id === user?.id"
      @close="modals.removeAvatar = false"
      @confirm="executeRemoveAvatar"
    />
    
    <BaseModal :show="modals.style" @close="modals.style = false">
      <template #header><h2>{{ isEditing ? $t('admin.edit_style') : $t('admin.add_style') }}</h2></template>
      <template #body>
        <form @submit.prevent="submitForm('style')" style="display: flex; flex-direction: column; gap: 1.5rem;">
          <BaseInput v-model="formData.style.name" :label="$t('admin.style_name')" required />
          <button type="submit" class="btn-add" style="padding: 1rem;"><SaveIcon :size="18" /> {{ $t('admin.save_style') }}</button>
        </form>
      </template>
    </BaseModal>

    <BaseModal :show="modals.merge" @close="modals.merge = false">
      <template #header>
        <h2 style="display: flex; align-items: center; gap: 0.5rem; margin: 0;">
          <GitMergeIcon color="#8b5cf6" /> {{ $t('admin.merge_title') }}
        </h2>
      </template>
      <template #body>
        <form @submit.prevent="submitMerge" style="display: flex; flex-direction: column; gap: 1.5rem;">
          <p style="margin: 0; color: var(--text-muted); line-height: 1.4;">
            {{ $t('admin.merge_desc', { source: mergeForm.source?.name }) }}
          </p>
          
          <BaseSelect 
            v-model="mergeForm.target_id" 
            :label="$t('admin.merge_target')" 
            :placeholder="$t('admin.merge_target_placeholder')"
            searchable 
            required
          >
            <option disabled value="">{{ $t('admin.merge_target_placeholder') }}</option>
            <option v-for="loc in mergeTargetOptions" :key="loc.id" :value="loc.id">
              {{ loc.name }} ({{ loc.city || $t('admin.merge_no_city') }})
            </option>
          </BaseSelect>
          
          <button type="submit" class="btn-primary" style="background-color: #8b5cf6; color: white; padding: 1rem; font-weight: 700; width: 100%;">
            {{ $t('admin.merge_submit') }}
          </button>
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
  UnlockIcon, UserIcon, UsersIcon, SearchXIcon, BeerIcon, FactoryIcon, MapPinIcon, HopIcon,
  GitMergeIcon, ListChecksIcon, CheckIcon
} from 'lucide-vue-next'
import { useI18n } from 'vue-i18n'
import { apiFetch } from '../api'
import { useAuthStore } from '../stores/auth'
import { useCatalogStore } from '../stores/catalog'
import { useToastStore } from '../stores/toast'
import BaseInput from '../components/BaseInput.vue'
import BaseModal from '../components/BaseModal.vue'
import BaseLoader from '../components/BaseLoader.vue'
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

const formData = ref({
  beer: { id: null, name: '', brewery_id: '', style_id: '', epm: '', abv: '', ibu: '', ebc: '', hops: '', malts: '', fermentation: '', tags: '', is_unfiltered: false, is_unpasteurized: false },
  brewery: { id: null, name: '', city: '', zip_code: '', country_id: 1, address: '', email: '', phone: '', website: '', logoFile: null, lat: null, lng: null },
  location: { id: null, name: '', type: 'hospoda', city: '', zip_code: '', country_id: 1, address: '', email: '', phone: '', website: '', opening_hours: '' },
  style: { id: null, name: '' },
  user: { id: null, first_name: '', last_name: '', username: '', email: '', role: 'user', avatar: null }
})

const searchQuery = ref('')
const currentPage = ref(1)
const itemsPerPage = 30
const isMobileMode = ref(window.innerWidth <= 768)

const handleResize = () => {
  isMobileMode.value = window.innerWidth <= 768
}

watch(activeTab, (newTab) => { 
  searchQuery.value = ''; 
  currentPage.value = 1;
  if (newTab === 'pending') {
    catalogStore.fetchPendingApprovals()
  }
})

watch(searchQuery, () => { currentPage.value = 1 })

const tabs = computed(() => [
  { value: 'users', label: t('admin.tabs.users'), icon: UsersIcon },
  { value: 'pending', label: t('admin.tabs.pending'), icon: ListChecksIcon },
  { value: 'beers', label: t('admin.tabs.beers'), icon: BeerIcon },
  { value: 'breweries', label: t('admin.tabs.breweries'), icon: FactoryIcon },
  { value: 'locations', label: t('admin.tabs.locations'), icon: MapPinIcon },
  { value: 'styles', label: t('admin.tabs.styles'), icon: HopIcon }
])

const getTabLabel = (val) => tabs.value.find(t => t.value === val)?.label || ''

const currentLabelSingle = computed(() => {
  if (activeTab.value === 'pending') return '';
  return t(`admin.items.${activeTab.value === 'users' ? 'user' : (activeTab.value === 'beers' ? 'beer' : (activeTab.value === 'breweries' ? 'brewery' : (activeTab.value === 'locations' ? 'location' : 'style')))}`)
})

const filteredUsers = computed(() => {
  let items = [...allUsers.value]
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    items = items.filter(u => 
      u.username.toLowerCase().includes(query) || 
      u.email.toLowerCase().includes(query) ||
      `${u.first_name} ${u.last_name}`.toLowerCase().includes(query)
    )
  }
  return items.sort((a, b) => (a.username || '').localeCompare(b.username || '', 'cs'))
})

const currentItems = computed(() => ({ 
  beers: beers.value, 
  breweries: breweries.value, 
  locations: locations.value, 
  styles: styles.value,
  pending: catalogStore.pendingApprovals || []
}[activeTab.value] || []))

const filteredCurrentItems = computed(() => {
  let items = [...currentItems.value]
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    items = items.filter(item => item.name.toLowerCase().includes(query))
  }
  return items.sort((a, b) => (a.name || '').localeCompare(b.name || '', 'cs'))
})

const mergeTargetOptions = computed(() => {
  if (!mergeForm.value.source) return []
  return catalogStore.allLocations
    .filter(l => l.id !== mergeForm.value.source.id)
    .sort((a, b) => (a.name || '').localeCompare(b.name || '', 'cs'))
})

const totalPages = computed(() => {
  const total = activeTab.value === 'users' ? filteredUsers.value.length : filteredCurrentItems.value.length
  return Math.ceil(total / itemsPerPage)
})

const paginatedUsers = computed(() => {
  if (isMobileMode.value) {
    return filteredUsers.value.slice(0, currentPage.value * itemsPerPage)
  }
  const start = (currentPage.value - 1) * itemsPerPage
  return filteredUsers.value.slice(start, start + itemsPerPage)
})

const paginatedCurrentItems = computed(() => {
  if (isMobileMode.value) {
    return filteredCurrentItems.value.slice(0, currentPage.value * itemsPerPage)
  }
  const start = (currentPage.value - 1) * itemsPerPage
  return filteredCurrentItems.value.slice(start, start + itemsPerPage)
})

const loadMoreTrigger = ref(null)
let observer = null

watch(loadMoreTrigger, (el) => {
  if (observer) observer.disconnect()
  if (el) {
    observer = new IntersectionObserver((entries) => {
      if (entries[0].isIntersecting && isMobileMode.value) {
        if (currentPage.value < totalPages.value) {
          currentPage.value++
        }
      }
    }, { rootMargin: '200px' })
    observer.observe(el)
  }
})

const fetchUsers = async () => {
  isUsersLoading.value = true
  try {
    const res = await apiFetch('/users.php')
    if (res.status === 'success') allUsers.value = res.data
  } finally { isUsersLoading.value = false }
}

onMounted(() => { 
  catalogStore.fetchAllData()
  fetchUsers() 
  window.addEventListener('resize', handleResize)
})

onUnmounted(() => {
  window.removeEventListener('resize', handleResize)
  if (observer) observer.disconnect()
})

const openAddModal = (t) => { 
  isEditing.value = false
  Object.keys(modals.value).forEach(m => modals.value[m] = false)
  const keyMap = t === 'breweries' ? 'brewery' : (t === 'beers' ? 'beer' : (t === 'locations' ? 'location' : (t === 'styles' ? 'style' : 'user')))
  
  if (keyMap === 'beer') {
    formData.value.beer = { id: null, name: '', brewery_id: '', style_id: '', epm: '', abv: '', ibu: '', ebc: '', hops: '', malts: '', fermentation: '', tags: '', is_unfiltered: false, is_unpasteurized: false }
  } else if (keyMap === 'brewery') {
    formData.value.brewery = { id: null, name: '', city: '', zip_code: '', country_id: 1, address: '', email: '', phone: '', website: '', logoFile: null, lat: null, lng: null }
  } else if (keyMap === 'location') {
    formData.value.location = { id: null, name: '', type: 'hospoda', city: '', zip_code: '', country_id: 1, address: '', email: '', phone: '', website: '', opening_hours: '' }
  } else if (keyMap === 'style') {
    formData.value.style = { id: null, name: '' }
  } else if (keyMap === 'user') {
    formData.value.user = { id: null, first_name: '', last_name: '', username: '', email: '', role: 'user', avatar: null }
  }
  
  modals.value[keyMap] = true 
}

const openEditModal = (item, typeParam) => { 
  isEditing.value = true
  const key = typeParam === 'styles' ? 'style' : (typeParam === 'beers' ? 'beer' : (typeParam === 'locations' ? 'location' : (typeParam === 'breweries' ? 'brewery' : 'user')))
  if (key === 'beer') {
    formData.value.beer = { ...item, is_unfiltered: !!item.is_unfiltered, is_unpasteurized: !!item.is_unpasteurized }
  } else {
    formData.value[key] = { ...item, logoFile: null }
  }
  modals.value[key] = true 
}

const openMergeModal = (locationItem) => {
  mergeForm.value = { source: locationItem, target_id: '' }
  modals.value.merge = true
}

const submitMerge = async () => {
  if (!mergeForm.value.source || !mergeForm.value.target_id) return
  
  try {
    const res = await apiFetch('/merge_locations.php', {
      method: 'POST',
      body: JSON.stringify({
        source_id: mergeForm.value.source.id,
        target_id: mergeForm.value.target_id
      })
    })

    if (res.status === 'success') {
      toastStore.showToast(res.message)
      modals.value.merge = false
      catalogStore.locations = catalogStore.locations.filter(l => l.id !== mergeForm.value.source.id)
    } else {
      toastStore.showToast(res.message || 'Nepodařilo se podniky sloučit.', 'toast-error')
    }
  } catch (error) {
    toastStore.showToast(t('toast.communication_error'), 'toast-error')
  }
}

const openPasswordModal = (u) => { selectedUserForPassword.value = u; modals.value.password = true }

const handlePasswordChange = async (payload) => {
  try {
    const res = await apiFetch('/admin_change_password.php', { method: 'POST', body: JSON.stringify(payload) })
    if (res.status === 'success') { 
      toastStore.showToast(res.message)
      modals.value.password = false 
    } 
    else { toastStore.showToast(res.message || 'Nepodařilo se změnit heslo.', 'toast-error') }
  } catch (e) { toastStore.showToast(t('toast.communication_error'), 'toast-error') }
}

const handleRemoveAvatar = (userId) => {
  selectedUserForAvatarRemove.value = allUsers.value.find(u => u.id === userId)
  modals.value.removeAvatar = true
}

const executeRemoveAvatar = async (userId) => {
  try {
    const res = await apiFetch('/admin_remove_avatar.php', { method: 'POST', body: JSON.stringify({ user_id: userId }) })
    if (res.status === 'success') {
      toastStore.showToast(res.message)
      modals.value.removeAvatar = false
      modals.value.user = false
      fetchUsers() 
    } else { toastStore.showToast(res.message || 'Nepodařilo se smazat fotku.', 'toast-error') }
  } catch (e) { toastStore.showToast(t('toast.communication_error'), 'toast-error') }
}

const openBanModal = (u) => { selectedUserForBan.value = u; modals.value.ban = true }

const handleBanConfirm = async (u) => {
  const newStatus = u.is_banned ? 0 : 1;
  try {
    const res = await apiFetch('/admin_toggle_ban.php', { method: 'POST', body: JSON.stringify({ user_id: u.id, is_banned: newStatus }) })
    if (res.status === 'success') { 
      toastStore.showToast(res.message)
      modals.value.ban = false
      fetchUsers() 
    } 
    else { toastStore.showToast(res.message || 'Chyba při změně blokace.', 'toast-error') }
  } catch (e) { toastStore.showToast(t('toast.communication_error'), 'toast-error') }
}

const submitForm = async (t_form) => {
  try {
    const endpoint = isEditing.value ? `update_${t_form}.php` : `add_${t_form}.php`
    let bodyData;
    if (t_form === 'brewery') {
      bodyData = new FormData();
      Object.keys(formData.value[t_form]).forEach(key => {
         if (formData.value[t_form][key] !== null && formData.value[t_form][key] !== undefined && formData.value[t_form][key] !== '') {
           bodyData.append(key, formData.value[t_form][key])
         }
      });
    } else {
      bodyData = JSON.stringify(formData.value[t_form])
    }
    const res = await apiFetch(`/${endpoint}`, { method: 'POST', body: bodyData })
    if (res.status === 'success') { 
      toastStore.showToast(res.message)
      modals.value[t_form] = false

      if (t_form === 'user') {
        fetchUsers()
      } else {
        if (isEditing.value) {
          const arrName = t_form === 'style' ? 'styles' : (t_form === 'brewery' ? 'breweries' : t_form + 's')
          const index = catalogStore[arrName].findIndex(x => x.id === formData.value[t_form].id)
          if (index !== -1) {
             catalogStore[arrName][index] = { ...catalogStore[arrName][index], ...formData.value[t_form] }
          }
        } else {
          const newItem = { ...formData.value[t_form], id: res.id }
          if (t_form === 'beer') catalogStore.addBeerLocally({ ...newItem, avg_rating: null, total_checkins: 0, is_favorite: 0 })
          else if (t_form === 'brewery') catalogStore.addBreweryLocally({ ...newItem, avg_rating: null, total_beers_in_catalog: 0, is_favorite: 0 })
          else if (t_form === 'location') catalogStore.addLocationLocally({ ...newItem, avg_rating: null, total_visits: 0, is_favorite: 0 })
          else catalogStore.fetchAllData()
        }
      }
    } else { toastStore.showToast(res.message || 'Chyba při ukládání.', 'toast-error') }
  } catch (e) { toastStore.showToast(t('toast.communication_error'), 'toast-error') }
}

const confirmDelete = (id, typeParam) => {
  const typeMap = { users: 'user', beers: 'beer', breweries: 'brewery', locations: 'location', styles: 'style' }
  deleteModal.value = { show: true, id, type: typeMap[typeParam] } 
}

const handleDelete = async () => {
  try {
    const res = await apiFetch(`/delete_${deleteModal.value.type}.php`, { method: 'POST', body: JSON.stringify({ id: deleteModal.value.id }) })
    if (res.status === 'success') {
      toastStore.showToast("Smazáno")
      if (deleteModal.value.type === 'user') {
        fetchUsers()
      } else {
        const arrName = deleteModal.value.type === 'brewery' ? 'breweries' : (deleteModal.value.type === 'style' ? 'styles' : deleteModal.value.type + 's')
        catalogStore[arrName] = catalogStore[arrName].filter(x => x.id !== deleteModal.value.id)
      }
    } else { toastStore.showToast(res.message || 'Nepodařilo se smazat.', 'toast-error') }
  } catch(e) { toastStore.showToast(t('toast.delete_error'), 'toast-error') } 
  finally { deleteModal.value.show = false }
}

const handleApprove = async (item, action) => {
  try {
    const res = await apiFetch('/approve_entity.php', {
      method: 'POST',
      body: JSON.stringify({
        entity_type: item.entity_type,
        entity_id: item.id,
        action: action
      })
    })
    if (res.status === 'success') {
      toastStore.showToast(res.message)
      catalogStore.fetchPendingApprovals()
      catalogStore.fetchAllData()
    } else {
      toastStore.showToast(res.message || 'Chyba při zpracování požadavku.', 'toast-error')
    }
  } catch (e) {
    toastStore.showToast(t('toast.communication_error'), 'toast-error')
  }
}
</script>

<style scoped>
.admin-page { flex: 1; display: flex; flex-direction: column; }
.admin-header { margin-bottom: 2rem; overflow-x: auto; padding-bottom: 0.5rem; border-bottom: 1px solid var(--border); }

.admin-layout { position: relative; flex: 1; min-height: 400px; display: flex; flex-direction: column; }
.admin-section { display: flex; flex-direction: column; flex: 1; background: var(--bg-panel); border: 1px solid var(--border); border-radius: var(--radius-md); padding: 2rem; transition: background-color 0.3s ease; }

.section-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 2.5rem; gap: 2rem; }
.header-info { display: flex; flex-direction: column; gap: 1.25rem; flex: 1; }
.admin-search { max-width: 380px; }

.admin-table-wrapper { overflow-x: auto; flex: 1; margin-bottom: 1.5rem; }
.admin-table { width: 100%; border-collapse: collapse; }
.admin-table th { 
  text-align: left; padding: 1rem 0.75rem; border-bottom: 2px solid var(--border); 
  color: var(--text-muted); font-size: 0.75rem; font-weight: 700; text-transform: uppercase; 
  letter-spacing: 0.05em; background: transparent;
}
.text-right { text-align: right !important; }

.admin-table td { padding: 1.25rem 0.75rem; border-bottom: 1px solid var(--border); vertical-align: middle; color: var(--text-main); }
.admin-table tr:hover td { background-color: var(--card-hover-bg); }

.section-icon { 
  width: 42px; height: 42px; border-radius: var(--radius-sm); display: flex; 
  align-items: center; justify-content: center; flex-shrink: 0;
  overflow: hidden;
  transition: all 0.3s ease;
  background: #1e293b; 
}
.section-icon img { width: 100%; height: 100%; object-fit: cover; }
.section-icon.has-image { padding: 0; background: transparent; border: 1px solid var(--border); }

.beers-bg, .breweries-bg, .locations-bg, .users-bg, .styles-bg, .beer-bg, .brewery-bg, .location-bg { background: #1e293b; }

.user-cell, .main-item-cell { display: flex; align-items: center; gap: 1rem; }

.info-top-row { display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; }

.user-meta-row { display: block; width: 100%; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.meta-sep { color: var(--text-muted); opacity: 0.7; margin-left: 4px; }

.combined-meta { display: none; align-items: center; gap: 5px; }
.mobile-flag { width: 16px; height: auto; border-radius: 1px; }

.action-buttons { display: flex; gap: 0.5rem; justify-content: flex-end; }
.admin-action-btn { color: white; border: none; transition: transform 0.2s; }
.admin-action-btn:hover { transform: scale(1.05); }

.banned-badge { background: rgba(239, 68, 68, 0.1); color: #ef4444; font-size: 0.6rem; border: 1px solid rgba(239, 68, 68, 0.2); }
.banned-row td { opacity: 0.7; }

.admin-section-footer { display: flex; justify-content: space-between; align-items: center; padding-top: 1.5rem; border-top: 1px solid var(--border); margin-top: auto; }
.footer-info { font-size: 0.85rem; color: var(--text-muted); font-weight: 500; }
.footer-info strong { color: var(--text-main); }

.country-cell { display: flex; align-items: center; gap: 0.5rem; }
.admin-flag-icon { width: 18px; height: auto; border-radius: 2px; box-shadow: 0 0 1px rgba(0,0,0,0.3); }

:deep(.pagination-wrapper) { margin-top: 0; padding: 0; }

.mobile-only { display: none; }
.load-more-trigger { height: 20px; width: 100%; }

@media (max-width: 768px) {
  .section-header { flex-direction: column; align-items: stretch; gap: 1rem; margin-bottom: 1.5rem; }
  .admin-search { max-width: none; }
  .section-header .btn-add { width: 100%; padding: 1rem; order: -1; }
  .admin-section { padding: 1.25rem; }
  .mobile-only { display: block; }
  .combined-meta { display: flex !important; }
  .desktop-only { display: none !important; }
  
  .admin-table thead { display: none; }
  .admin-table, .admin-table tbody, .admin-table tr, .admin-table td { display: block; width: 100%; }
  
  .admin-table tr { margin-bottom: 1rem; border: 1px solid var(--border); border-radius: var(--radius-md); background: var(--bg-app); padding: 1rem; }
  .admin-table td { text-align: left; padding: 0.5rem 0; border: none; display: flex; justify-content: space-between; align-items: center; gap: 1rem; }
  .admin-table td::before { content: attr(data-label); font-weight: 700; color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; flex-shrink: 0; }
  
  .td-content { flex: 1; min-width: 0; display: flex; justify-content: flex-end; }
  
  .admin-table td[data-label="Uživatel"]::before,
  .admin-table td[data-label="Název"]::before { display: none; }
  .admin-table td[data-label="Uživatel"] .td-content,
  .admin-table td[data-label="Název"] .td-content { justify-content: flex-start; }
  
  .action-buttons { width: 100%; justify-content: flex-start; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--border); }
  .action-buttons button { flex: 1; }
}
</style>