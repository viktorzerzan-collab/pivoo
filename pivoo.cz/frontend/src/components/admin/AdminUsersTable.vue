<template>
  <div class="admin-users-table">
    <BaseLoader :show="isUsersLoading" />

    <BaseTable>
      <template #thead>
        <thead>
          <tr>
            <th>{{ $t('admin.table.user') }}</th>
            <th>{{ $t('admin.table.email') }}</th>
            <th>{{ $t('admin.table.role') }}</th>
            <th class="w-100 text-right">{{ $t('admin.table.actions') }}</th>
          </tr>
        </thead>
      </template>

      <template #tbody>
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
                <BaseButton variant="edit" :isIconOnly="true" @click="openEditModal(u)">
                  <template #icon><PencilIcon :size="16" /></template>
                </BaseButton>
              </BaseTooltip>
              
              <BaseTooltip v-if="u.is_2fa_enabled" :text="$t('admin.tooltips.reset_2fa')" position="top-end">
                <BaseButton variant="secondary" :isIconOnly="true" @click="handleReset2FA(u)">
                  <template #icon><ShieldOffIcon :size="16" /></template>
                </BaseButton>
              </BaseTooltip>

              <BaseTooltip v-if="u?.id !== currentUser?.id" :text="$t('admin.tooltips.change_pwd')" position="top-end">
                <BaseButton variant="add" :isIconOnly="true" @click="openPasswordModal(u)">
                  <template #icon><KeyIcon :size="16" /></template>
                </BaseButton>
              </BaseTooltip>
              <BaseTooltip v-if="u?.id !== currentUser?.id" :text="u.is_banned ? $t('admin.tooltips.unblock') : $t('admin.tooltips.block')" position="top-end">
                <BaseButton :variant="u.is_banned ? 'primary' : 'edit'" :isIconOnly="true" :style="u.is_banned ? 'background-color: #10b981; color: white;' : ''" @click="openBanModal(u)">
                  <template #icon>
                    <UnlockIcon v-if="u.is_banned" :size="16" />
                    <BanIcon v-else :size="16" />
                  </template>
                </BaseButton>
              </BaseTooltip>
              <BaseTooltip v-if="u?.id !== currentUser?.id" :text="$t('admin.tooltips.delete_user')" position="top-end">
                <BaseButton variant="danger" :isIconOnly="true" @click="confirmDelete(u.id)">
                  <template #icon><Trash2Icon :size="16" /></template>
                </BaseButton>
              </BaseTooltip>
            </BaseActionGroup>
          </td>
        </tr>
      </template>
    </BaseTable>
    
    <BaseEmptyState 
      v-if="filteredUsers.length === 0" 
      :text="$t('admin.empty_search')" 
      :icon="SearchXIcon" 
      :icon-size="40"
    />

    <div class="admin-section-footer">
      <div class="footer-info desktop-only">
        {{ $t('admin.showing') }} <strong>{{ paginatedUsers.length }}</strong> {{ $t('admin.of') }} <strong>{{ filteredUsers.length }}</strong> {{ $t('admin.records') }}
      </div>
      <div class="desktop-only">
        <BasePagination v-if="totalPages > 1" v-model:currentPage="currentPage" :total-pages="totalPages" />
      </div>
    </div>

    <EditUserModal :show="modals.edit" :form="formData" :is-current-user="formData.id === currentUser?.id" @close="modals.edit = false" @submit="submitEdit" @remove-avatar="handleRemoveAvatar" />
    <ChangePasswordModal :show="modals.password" :user="selectedUserForPassword" @close="modals.password = false" @submit="handlePasswordChange" />
    <BanConfirmModal :show="modals.ban" :user="selectedUserForBan" @close="modals.ban = false" @confirm="handleBanConfirm" />
    <RemoveAvatarConfirmModal :show="modals.removeAvatar" :user="selectedUserForAvatarRemove" :is-current-user="selectedUserForAvatarRemove?.id === currentUser?.id" @close="modals.removeAvatar = false" @confirm="executeRemoveAvatar" />
    <DeleteConfirmModal :show="modals.delete" @close="modals.delete = false" @confirm="handleDelete" />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { 
  PencilIcon, Trash2Icon, KeyIcon, BanIcon, 
  UnlockIcon, UserIcon, SearchXIcon, ShieldOffIcon
} from 'lucide-vue-next'
import { useI18n } from 'vue-i18n'

import { apiFetch } from '../../api'
import { useAuthStore } from '../../stores/auth'
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

import EditUserModal from '../modals/EditUserModal.vue'
import ChangePasswordModal from '../modals/ChangePasswordModal.vue'
import BanConfirmModal from '../modals/BanConfirmModal.vue'
import RemoveAvatarConfirmModal from '../modals/RemoveAvatarConfirmModal.vue'
import DeleteConfirmModal from '../modals/DeleteConfirmModal.vue'

const props = defineProps({
  searchQuery: {
    type: String,
    default: ''
  }
})

const authStore = useAuthStore()
const toastStore = useToastStore()
const { t } = useI18n()

// Přejmenováno na currentUser pro lepší odlišení
const { user: currentUser } = storeToRefs(authStore)
const { allUsers, isUsersLoading, fetchUsers } = useAdmin()

const currentPage = ref(1)
const itemsPerPage = 30
const isMobileMode = ref(window.innerWidth <= 768)

// Stavy a data specifická pro uživatele
const modals = ref({ edit: false, password: false, ban: false, removeAvatar: false, delete: false })
const formData = ref({ id: null, first_name: '', last_name: '', username: '', email: '', role: 'user', avatar: null })
const selectedUserForPassword = ref(null)
const selectedUserForBan = ref(null)
const selectedUserForAvatarRemove = ref(null)
const deleteId = ref(null)

const handleResize = () => { isMobileMode.value = window.innerWidth <= 768 }

// Reset stránky při hledání
watch(() => props.searchQuery, () => { currentPage.value = 1 })

const filteredUsers = computed(() => {
  let items = [...allUsers.value]
  if (props.searchQuery) {
    const q = props.searchQuery.toLowerCase()
    items = items.filter(u => u.username.toLowerCase().includes(q) || u.email.toLowerCase().includes(q) || `${u.first_name} ${u.last_name}`.toLowerCase().includes(q))
  }
  return items.sort((a, b) => (a.username || '').localeCompare(b.username || '', 'cs'))
})

const totalPages = computed(() => Math.ceil(filteredUsers.value.length / itemsPerPage))
const paginatedUsers = computed(() => isMobileMode.value 
  ? filteredUsers.value.slice(0, currentPage.value * itemsPerPage) 
  : filteredUsers.value.slice((currentPage.value - 1) * itemsPerPage, currentPage.value * itemsPerPage)
)

onMounted(() => {
  if (allUsers.value.length === 0) fetchUsers()
  window.addEventListener('resize', handleResize)
})

onUnmounted(() => {
  window.removeEventListener('resize', handleResize)
})

// --- Akce ---

const handleReset2FA = async (u) => {
  if (!confirm(t('admin.tooltips.reset_2fa_confirm', { user: u.username }))) return;
  try {
    const payload = {
      id: u.id, username: u.username, email: u.email,
      first_name: u.first_name, last_name: u.last_name, role: u.role, reset_2fa: true
    };
    const res = await apiFetch('/update_user.php', { method: 'POST', body: JSON.stringify(payload) })
    if (res.status === 'success') {
      toastStore.showToast(t('toast.two_fa_reset_success'), 'toast-success')
      fetchUsers()
    } else {
      toastStore.showToast(res.message || 'Error', 'toast-error')
    }
  } catch (e) {
    toastStore.showToast(t('toast.communication_error'), 'toast-error')
  }
}

const openEditModal = (u) => {
  formData.value = { ...u }
  modals.value.edit = true
}

const submitEdit = async () => {
  try {
    const res = await apiFetch('/update_user.php', { method: 'POST', body: JSON.stringify(formData.value) })
    if (res.status === 'success') {
      toastStore.showToast(res.message)
      modals.value.edit = false
      fetchUsers()
    } else {
      toastStore.showToast(res.message || 'Error', 'toast-error')
    }
  } catch {
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
    } else {
      toastStore.showToast(res.message || 'Error', 'toast-error')
    }
  } catch {
    toastStore.showToast(t('toast.communication_error'), 'toast-error')
  }
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
      modals.value.edit = false
      fetchUsers()
    } else {
      toastStore.showToast(res.message || 'Error', 'toast-error')
    }
  } catch {
    toastStore.showToast(t('toast.communication_error'), 'toast-error')
  }
}

const openBanModal = (u) => { selectedUserForBan.value = u; modals.value.ban = true }

const handleBanConfirm = async (u) => {
  try {
    const res = await apiFetch('/admin_toggle_ban.php', { method: 'POST', body: JSON.stringify({ user_id: u.id, is_banned: u.is_banned ? 0 : 1 }) })
    if (res.status === 'success') {
      toastStore.showToast(res.message)
      modals.value.ban = false
      fetchUsers()
    } else {
      toastStore.showToast(res.message || 'Error', 'toast-error')
    }
  } catch {
    toastStore.showToast(t('toast.communication_error'), 'toast-error')
  }
}

const confirmDelete = (id) => { deleteId.value = id; modals.value.delete = true }

const handleDelete = async () => {
  try {
    const res = await apiFetch('/delete_user.php', { method: 'POST', body: JSON.stringify({ id: deleteId.value }) })
    if (res.status === 'success') {
      toastStore.showToast("Smazáno") // Zde můžete případně nahradit i18n
      fetchUsers()
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
.admin-users-table { position: relative; display: flex; flex-direction: column; flex: 1; }
.mobile-only { display: none; }
.user-cell { display: flex; align-items: center; gap: 1rem; }
.item-text { flex: 1; min-width: 0; display: flex; flex-direction: column; }
.info-top-row { display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; }
.meta-row { display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.meta-sep { color: var(--text-muted); opacity: 0.7; margin-left: 4px; }
.banned-badge { background: rgba(239, 68, 68, 0.1); color: #ef4444; font-size: 0.6rem; border: 1px solid rgba(239, 68, 68, 0.2); }
.banned-row td { opacity: 0.7; }
.line-through { text-decoration: line-through; }
.admin-section-footer { display: flex; justify-content: space-between; align-items: center; padding-top: 1.5rem; border-top: 1px solid var(--border); margin-top: auto; }
.footer-info { font-size: 0.85rem; color: var(--text-muted); font-weight: 500; }
.footer-info strong { color: var(--text-main); }
.badge { padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; }

@media (max-width: 768px) {
  .mobile-only { display: block; }
  .desktop-only { display: none !important; }
}
</style>
