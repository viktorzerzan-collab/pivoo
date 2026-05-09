<template>
  <div class="profile-view">
    <div class="view-header">
      <h1 class="section-title">{{ $t('nav.profile') }}</h1>
    </div>

    <div class="profile-tabs-wrapper">
      <BaseSwitch 
        v-model="activeTab"
        :options="[
          { value: 'settings', label: $t('views.profile.tab_settings'), icon: SettingsIcon },
          { value: 'history', label: $t('views.profile.tab_history'), icon: HistoryIcon }
        ]"
      />
    </div>

    <BaseLoader :show="isLoading" />

    <div class="profile-content" v-if="!isLoading">
      
      <div v-show="activeTab === 'settings'" class="profile-grid">
        
        <div class="panel-card avatar-card">
          <div class="avatar-wrapper">
            <img v-if="user?.avatar" :src="'https://www.pivoo.cz/backend/uploads/avatars/' + user.avatar" alt="Avatar" class="avatar-image" />
            <div v-else class="avatar-placeholder"><UserIcon :size="64" color="var(--text-muted)" /></div>
            
            <label class="avatar-upload-overlay" :title="$t('views.profile.change_avatar')">
              <CameraIcon :size="24" />
              <input type="file" class="hidden-input" accept="image/*" @change="handleAvatarChange" :disabled="isUploading" />
            </label>
          </div>

          <div class="user-info-text">
            <h2>{{ user?.first_name }} {{ user?.last_name }}</h2>
            <p class="username">@{{ user?.username }}</p>
            <span class="badge" :class="user?.role">{{ user?.role === 'admin' ? $t('views.profile.admin_role') : $t('views.profile.user_role') }}</span>
          </div>

          <div class="avatar-actions" v-if="newAvatarFile || user?.avatar">
            <BaseButton v-if="newAvatarFile" @click="uploadAvatar" variant="add" :disabled="isUploading" class="full-width-btn">
              {{ $t('views.profile.save_avatar') }}
            </BaseButton>
            <BaseButton v-if="user?.avatar && !newAvatarFile" @click="showRemoveAvatarModal = true" variant="danger" class="full-width-btn outline-danger">
              {{ $t('views.profile.delete_avatar') }}
            </BaseButton>
          </div>
        </div>

        <div class="settings-column">
          
          <div class="panel-card">
            <div class="panel-header">
              <h3><SunMoonIcon :size="20" class="panel-icon" /> {{ $t('views.profile.theme_title') }}</h3>
            </div>
            <p class="setting-desc">{{ $t('views.profile.theme_desc') }}</p>
            
            <div class="theme-options-wrapper">
              <BaseSwitch
                :modelValue="localThemeMode"
                :options="[
                  { value: 'manual', label: $t('views.profile.theme_manual'), icon: SunMoonIcon },
                  { value: 'auto', label: $t('views.profile.theme_auto'), icon: ClockIcon }
                ]"
                :fullWidth="true"
                @update:modelValue="handleThemeChange"
              />
            </div>
          </div>

          <div class="panel-card">
            <div class="panel-header">
              <h3><BanknoteIcon :size="20" class="panel-icon" /> {{ $t('views.profile.currency_title') }}</h3>
            </div>
            <p class="setting-desc">{{ $t('views.profile.currency_desc') }}</p>
            
            <div class="currency-form">
              <BaseSelect v-model="localCurrency" :searchable="false" style="max-width: 300px;">
                <option value="CZK">{{ $t('currencies.CZK') }}</option>
                <option value="EUR">{{ $t('currencies.EUR') }}</option>
                <option value="PLN">{{ $t('currencies.PLN') }}</option>
                <option value="GBP">{{ $t('currencies.GBP') }}</option>
              </BaseSelect>
              <BaseButton @click="saveCurrency" variant="edit" :disabled="localCurrency === authStore.defaultCurrency">{{ $t('buttons.save') }}</BaseButton>
            </div>
          </div>

          <div class="panel-card">
            <div class="panel-header">
              <h3><KeyIcon :size="20" class="panel-icon" /> {{ $t('views.profile.password_title') }}</h3>
            </div>
            
            <form @submit.prevent="changePassword" class="password-form">
              <BaseInput v-model="pwdForm.old_password" type="password" :label="$t('views.profile.old_password')" required />
              
              <div class="form-row">
                <BaseInput class="half" v-model="pwdForm.new_password" type="password" :label="$t('views.profile.new_password')" required />
                <BaseInput class="half" v-model="pwdForm.new_password_confirm" type="password" :label="$t('views.profile.new_password_confirm')" required />
              </div>

              <PasswordStrength 
                ref="pwdStrengthRef"
                :password="pwdForm.new_password" 
                :confirm="pwdForm.new_password_confirm" 
              />

              <div class="form-actions">
                <BaseButton type="submit" variant="edit" :disabled="!isPasswordValid">{{ $t('buttons.save') }}</BaseButton>
              </div>
            </form>
          </div>

          <div class="panel-card danger-zone">
            <div class="panel-header">
              <h3 class="danger-title"><AlertTriangleIcon :size="20" /> {{ $t('views.profile.danger_zone') }}</h3>
            </div>
            <p class="setting-desc">{{ $t('views.profile.danger_desc') }}</p>
            <BaseButton @click="showDeleteModal = true" variant="danger">{{ $t('views.profile.delete_account') }}</BaseButton>
          </div>

        </div>
      </div>

      <div v-show="activeTab === 'history'" class="history-tab-content">
        <div class="panel-card">
          <div class="panel-header">
            <h3><HistoryIcon :size="20" class="panel-icon" /> {{ $t('views.profile.tab_history') }}</h3>
          </div>
          
          <div v-if="isHistoryLoading" style="position: relative; min-height: 200px;">
            <BaseLoader :show="true" />
          </div>
          
          <div v-else>
            <HistoryList v-if="historyRecords.length > 0" :history="historyRecords" @edit="openEditModal" @delete="openDeleteConfirm" />
            
            <div v-else class="empty-state">
              <p>{{ $t('views.profile.no_history') }}</p>
            </div>

            <BasePagination 
              v-if="historyTotalPages > 1"
              v-model:currentPage="historyPage"
              :totalPages="historyTotalPages"
            />
          </div>
        </div>
      </div>
    </div>

    <RemoveAvatarConfirmModal
      :show="showRemoveAvatarModal"
      :isCurrentUser="true"
      @close="showRemoveAvatarModal = false"
      @confirm="removeAvatar"
    />

    <BaseModal :show="showDeleteModal" @close="showDeleteModal = false">
      <template #header>
        <h2 style="display:flex; align-items:center; gap:0.5rem; margin:0; color:var(--danger);"><AlertTriangleIcon :size="26" /> {{ $t('views.profile.delete_confirm_title') }}</h2>
      </template>
      <template #body>
        <p style="margin-bottom:1.5rem; color:var(--text-muted); line-height:1.5;">{{ $t('views.profile.delete_confirm_desc') }}</p>
        <form @submit.prevent="deleteAccount">
          <BaseInput v-model="deletePassword" type="password" :label="$t('views.profile.delete_password')" required style="margin-bottom:1.5rem;" />
          <div style="display:flex; gap:1rem;">
             <BaseButton type="button" variant="secondary" style="flex:1; background:var(--bg-app); border:1px solid var(--border); color:var(--text-main);" @click="showDeleteModal = false">{{ $t('buttons.cancel') }}</BaseButton>
             <BaseButton type="submit" variant="danger" style="flex:1;">{{ $t('views.profile.delete_submit') }}</BaseButton>
          </div>
        </form>
      </template>
    </BaseModal>

    <EditCheckInModal 
      :show="showEditHistoryModal" 
      :form="editForm" 
      @close="showEditHistoryModal = false" 
      @submit="submitEdit" 
    />

    <DeleteConfirmModal
      :show="showDeleteRecordModal"
      @close="showDeleteRecordModal = false"
      @confirm="deleteHistoryRecord"
    />

  </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { 
  UserIcon, CameraIcon, KeyIcon, SunMoonIcon, 
  ClockIcon, AlertTriangleIcon, BanknoteIcon, SettingsIcon, HistoryIcon 
} from 'lucide-vue-next'

import BaseLoader from '../components/BaseLoader.vue'
import BaseInput from '../components/BaseInput.vue'
import BaseButton from '../components/BaseButton.vue'
import BaseModal from '../components/BaseModal.vue'
import BaseSelect from '../components/BaseSelect.vue'
import BaseSwitch from '../components/BaseSwitch.vue'
import BasePagination from '../components/BasePagination.vue'
import PasswordStrength from '../components/PasswordStrength.vue'
import RemoveAvatarConfirmModal from '../components/modals/RemoveAvatarConfirmModal.vue'
import EditCheckInModal from '../components/modals/EditCheckInModal.vue'
import DeleteConfirmModal from '../components/modals/DeleteConfirmModal.vue'
import HistoryList from '../components/HistoryList.vue'

import { useAuthStore } from '../stores/auth'
import { useToastStore } from '../stores/toast'
import { useCatalogStore } from '../stores/catalog'
import { apiFetch } from '../api'

const router = useRouter()
const authStore = useAuthStore()
const toastStore = useToastStore()
const catalogStore = useCatalogStore()
const { user } = storeToRefs(authStore)
const { allBeers, allBreweries, allLocations } = storeToRefs(catalogStore)
const { t } = useI18n()

const isLoading = ref(false)
const isUploading = ref(false)
const activeTab = ref('settings')

// Uživatelské nastavení - Stav
const newAvatarFile = ref(null)
const showRemoveAvatarModal = ref(false)

const pwdForm = ref({
  old_password: '',
  new_password: '',
  new_password_confirm: ''
})

const pwdStrengthRef = ref(null)
const isPasswordValid = computed(() => pwdStrengthRef.value?.isValid)

const localThemeMode = ref('manual')
const localCurrency = ref('CZK')

const showDeleteModal = ref(false)
const deletePassword = ref('')

// Historie - Stav
const isHistoryLoading = ref(false)
const historyRecords = ref([])
const historyPage = ref(1)
const historyTotalPages = ref(1)

const showEditHistoryModal = ref(false)
const selectedEditRecordId = ref(null)
const editForm = ref({ brewery_id: '', beer_id: '', location_id: '', consumed_at: '', packaging: 'točené', volume: '0.50', quantity: 1, price: '', currency: 'CZK', original_price: '', is_free: false, rating_beer: 0, rating_care: 0, note: '' })

const showDeleteRecordModal = ref(false)
const recordIdToDelete = ref(null)

onMounted(() => {
  if (user.value) {
    localThemeMode.value = user.value.theme_mode || 'manual'
    localCurrency.value = user.value.default_currency || 'CZK'
  }
  
  if (allBeers.value.length === 0) {
    catalogStore.fetchAllData()
  }
})

// === TABS & HISTORIE LOGIKA ===
const fetchHistory = async () => {
  isHistoryLoading.value = true
  try {
    const res = await apiFetch(`/history.php?page=${historyPage.value}&limit=12`)
    if (res.status === 'success') {
      historyRecords.value = res.data
      historyTotalPages.value = res.pagination ? res.pagination.total_pages : 1
    }
  } catch (err) {
    toastStore.showToast(t('toast.communication_error'), 'toast-error')
  } finally {
    isHistoryLoading.value = false
  }
}

watch(activeTab, (newTab) => {
  if (newTab === 'history' && historyRecords.value.length === 0) {
    fetchHistory()
  }
})

watch(historyPage, () => {
  if (activeTab.value === 'history') {
    fetchHistory()
  }
})

const openEditModal = (record) => {
  selectedEditRecordId.value = record.id
  const currentBeer = allBeers.value.find(b => b.id == record.beer_id)
  const prefillBreweryId = currentBeer ? currentBeer.brewery_id : ''
  editForm.value = { 
    ...record, 
    consumed_at: record.consumed_at || '', 
    brewery_id: Number(prefillBreweryId), 
    beer_id: Number(record.beer_id), 
    location_id: Number(record.location_id), 
    quantity: Number(record.quantity), 
    is_free: !!Number(record.is_free), 
    currency: record.currency || 'CZK', 
    original_price: record.original_price || record.price 
  }
  showEditHistoryModal.value = true
}

const submitEdit = async () => {
  try {
    const res = await apiFetch('/update_checkin.php', { method: 'POST', body: JSON.stringify({ id: selectedEditRecordId.value, ...editForm.value }) })
    if (res.status === 'success') { 
       showEditHistoryModal.value = false
       
       const beer = allBeers.value.find(b => b.id == editForm.value.beer_id)
       const brewery = allBreweries.value.find(b => b.id == editForm.value.brewery_id)
       const loc = allLocations.value.find(l => l.id == editForm.value.location_id)
       
       catalogStore.updateCheckinLocally({
           id: selectedEditRecordId.value,
           ...editForm.value,
           price: res.price,           
           currency: res.currency,     
           original_price: res.original_price, 
           beer_name: beer ? beer.name : 'Neznámé pivo',
           brewery_name: brewery ? brewery.name : 'Neznámý pivovar',
           location_name: loc ? loc.name : 'Neznámý podnik'
       })
       toastStore.showToast(t('toast.record_edited'), 'toast-success') 
       fetchHistory()
    } else {
      toastStore.showToast(res.message || 'Error', 'toast-error')
    }
  } catch (e) { 
    toastStore.showToast(e.message || t('toast.communication_error'), 'toast-error') 
  }
}

const openDeleteConfirm = (id) => {
  recordIdToDelete.value = id
  showDeleteRecordModal.value = true
}

const deleteHistoryRecord = async () => {
  try {
    const res = await apiFetch('/delete_checkin.php', {
      method: 'POST',
      body: JSON.stringify({ id: recordIdToDelete.value })
    })
    
    if (res.status === 'success') {
      showDeleteRecordModal.value = false
      catalogStore.removeCheckinLocally(recordIdToDelete.value)
      toastStore.showToast(t('toast.record_deleted'), 'toast-success')
      fetchHistory()
    } else {
      toastStore.showToast(res.message || t('toast.delete_error'), 'toast-error')
    }
  } catch (e) {
    toastStore.showToast(t('toast.communication_error'), 'toast-error')
  }
}


// === UŽIVATELSKÉ NASTAVENÍ LOGIKA ===
const handleAvatarChange = (event) => {
  const file = event.target.files[0]
  if (file) {
    newAvatarFile.value = file
  }
}

const compressImage = (file) => {
  return new Promise((resolve, reject) => {
    const reader = new FileReader()
    reader.readAsDataURL(file)
    reader.onload = event => {
      const img = new Image()
      img.src = event.target.result
      img.onload = () => {
        const canvas = document.createElement('canvas')
        let width = img.width
        let height = img.height
        const max_size = 800

        if (width > height) {
          if (width > max_size) {
            height *= max_size / width
            width = max_size
          }
        } else {
          if (height > max_size) {
            width *= max_size / height
            height = max_size
          }
        }

        canvas.width = width
        canvas.height = height
        const ctx = canvas.getContext('2d')
        ctx.drawImage(img, 0, 0, width, height)

        canvas.toBlob((blob) => {
          if (!blob) {
            reject(new Error('Chyba při kompresi'))
            return
          }
          const newFile = new File([blob], file.name.replace(/\.[^/.]+$/, "") + ".webp", { type: 'image/webp' })
          resolve(newFile)
        }, 'image/webp', 0.8)
      }
      img.onerror = (e) => reject(e)
    }
    reader.onerror = (e) => reject(e)
  })
}

const uploadAvatar = async () => {
  if (!newAvatarFile.value) return
  isUploading.value = true
  
  try {
    const compressedFile = await compressImage(newAvatarFile.value)
    const formData = new FormData()
    formData.append('action', 'upload')
    formData.append('avatar', compressedFile)

    const res = await apiFetch('/update_avatar.php', {
      method: 'POST',
      body: formData
    })

    if (res.status === 'success') {
      authStore.updateUser({ avatar: res.avatar })
      toastStore.showToast('Profilová fotka uložena.', 'toast-success')
      newAvatarFile.value = null
    } else {
      toastStore.showToast(res.message || 'Chyba při nahrávání fotky.', 'toast-error')
    }
  } catch (err) {
    toastStore.showToast('Chyba při komunikaci se serverem.', 'toast-error')
  } finally {
    isUploading.value = false
  }
}

const removeAvatar = async () => {
  isUploading.value = true
  showRemoveAvatarModal.value = false

  try {
    const formData = new FormData()
    formData.append('action', 'remove')

    const res = await apiFetch('/update_avatar.php', {
      method: 'POST',
      body: formData
    })

    if (res.status === 'success') {
      authStore.updateUser({ avatar: null })
      toastStore.showToast('Profilová fotka odstraněna.', 'toast-success')
    } else {
      toastStore.showToast(res.message || 'Chyba při mazání fotky.', 'toast-error')
    }
  } catch (err) {
    toastStore.showToast('Chyba při komunikaci se serverem.', 'toast-error')
  } finally {
    isUploading.value = false
  }
}

// Funkce pro zpracování změny BaseSwitch (v-model se rovnou nepropisuje bez emit)
const handleThemeChange = async (newVal) => {
  localThemeMode.value = newVal
  await saveThemeSettings()
}

const saveThemeSettings = async () => {
  authStore.updateUser({ theme_mode: localThemeMode.value })
  try {
    const res = await apiFetch('/update_profile.php', {
      method: 'POST',
      body: JSON.stringify({ 
        action: 'update_theme',
        theme_mode: localThemeMode.value 
      })
    })
    if (res.status === 'success') {
      toastStore.showToast(t('toast.theme_saved'), 'toast-success')
    } else {
      toastStore.showToast(t('toast.theme_error'), 'toast-error')
    }
  } catch (err) {
    toastStore.showToast(t('toast.communication_error'), 'toast-error')
  }
}

const saveCurrency = async () => {
  try {
    const res = await apiFetch('/update_profile.php', {
      method: 'POST',
      body: JSON.stringify({
        action: 'update_currency',
        default_currency: localCurrency.value
      })
    })

    if (res.status === 'success') {
      authStore.updateUser({ default_currency: localCurrency.value })
      toastStore.showToast(res.message, 'toast-success')
    } else {
      toastStore.showToast(res.message || 'Chyba při ukládání měny.', 'toast-error')
    }
  } catch (err) {
    toastStore.showToast(t('toast.communication_error'), 'toast-error')
  }
}

const changePassword = async () => {
  if (!isPasswordValid.value) {
    return
  }

  try {
    const res = await apiFetch('/update_profile.php', {
      method: 'POST',
      body: JSON.stringify({
        action: 'change_password',
        old_password: pwdForm.value.old_password,
        new_password: pwdForm.value.new_password
      })
    })

    if (res.status === 'success') {
      toastStore.showToast('Heslo bylo úspěšně změněno.', 'toast-success')
      pwdForm.value = { old_password: '', new_password: '', new_password_confirm: '' }
    } else {
      toastStore.showToast(res.message || 'Změna hesla se nezdařila.', 'toast-error')
    }
  } catch (err) {
    toastStore.showToast('Chyba komunikace se serverem.', 'toast-error')
  }
}

const deleteAccount = async () => {
  if (!deletePassword.value) return

  try {
    const res = await apiFetch('/delete_profile.php', {
      method: 'POST',
      body: JSON.stringify({ password: deletePassword.value })
    })

    if (res.status === 'success') {
      showDeleteModal.value = false
      authStore.logout()
      toastStore.showToast('Účet byl úspěšně smazán. Sbohem!', 'toast-success')
      router.push('/')
    } else {
      toastStore.showToast(res.message || 'Ověření selhalo.', 'toast-error')
    }
  } catch (err) {
    toastStore.showToast('Chyba při mazání účtu.', 'toast-error')
  }
}
</script>

<style scoped>
.profile-view { flex: 1; display: flex; flex-direction: column; }
.view-header { margin-bottom: 1.5rem; }

.profile-tabs-wrapper { margin-bottom: 2rem; display: flex; justify-content: flex-start; }

.profile-content { display: flex; flex-direction: column; }
.profile-grid { display: grid; grid-template-columns: 320px 1fr; gap: 2rem; align-items: start; }

.panel-card { background: var(--bg-panel); border: 1px solid var(--border); border-radius: var(--radius-md); padding: 1.5rem; transition: background-color 0.3s ease, border-color 0.3s ease; }
.panel-header { display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--border); padding-bottom: 1rem; margin-bottom: 1.5rem; transition: border-color 0.3s ease; }
.panel-header h3 { margin: 0; display: flex; align-items: center; gap: 0.5rem; font-size: 1.25rem; color: var(--text-main); transition: color 0.3s ease; }
.panel-icon { color: var(--primary); }
.setting-desc { color: var(--text-muted); font-size: 0.95rem; margin-bottom: 1.25rem; line-height: 1.5; transition: color 0.3s ease; }

.avatar-card { display: flex; flex-direction: column; align-items: center; text-align: center; gap: 1.5rem; }
.avatar-wrapper { position: relative; width: 140px; height: 140px; border-radius: 50%; border: 4px solid var(--bg-app); box-shadow: var(--shadow-floating); overflow: hidden; background: var(--bg-app); transition: all 0.3s ease; }
.avatar-image { width: 100%; height: 100%; object-fit: cover; }
.avatar-placeholder { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; }
.avatar-upload-overlay { position: absolute; inset: 0; background: rgba(0, 0, 0, 0.5); display: flex; align-items: center; justify-content: center; color: white; opacity: 0; cursor: pointer; transition: opacity 0.2s; }
.avatar-wrapper:hover .avatar-upload-overlay { opacity: 1; }

.user-info-text { display: flex; flex-direction: column; align-items: center; gap: 0.5rem; }
.user-info-text h2 { margin: 0; font-size: 1.5rem; color: var(--text-main); transition: color 0.3s ease; }
.username { color: var(--text-muted); font-size: 1rem; margin: 0; font-weight: 500; transition: color 0.3s ease; }

.avatar-actions { width: 100%; display: flex; flex-direction: column; gap: 0.5rem; }
.full-width-btn { width: 100%; justify-content: center; }

.settings-column { display: flex; flex-direction: column; gap: 2rem; }

.theme-options-wrapper { margin-top: 1rem; }

.currency-form { display: flex; align-items: flex-end; gap: 1rem; }

.password-form { display: flex; flex-direction: column; gap: 1.25rem; }
.form-row { display: flex; gap: 1rem; }
.half { flex: 1; }
.form-actions { display: flex; justify-content: flex-end; margin-top: 0.5rem; }

.danger-zone { border-color: rgba(239, 68, 68, 0.3); background: rgba(239, 68, 68, 0.02); }
.danger-title { color: var(--danger) !important; }

.empty-state { text-align: center; padding: 3rem; color: var(--text-muted); }

@media (max-width: 900px) {
  .profile-grid { grid-template-columns: 1fr; }
  .avatar-card { max-width: 400px; margin: 0 auto; width: 100%; }
}

@media (max-width: 600px) {
  .form-row { flex-direction: column; }
  .currency-form { flex-direction: column; align-items: stretch; }
  .currency-form .base-select-group { max-width: 100% !important; }
}
</style>