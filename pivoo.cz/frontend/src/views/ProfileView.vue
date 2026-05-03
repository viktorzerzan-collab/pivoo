<template>
  <div class="profile-view">
    <div class="view-header">
      <h1 class="section-title">{{ $t('nav.profile') }}</h1>
    </div>

    <BaseLoader :show="isLoading" />

    <div class="profile-content" v-if="!isLoading">
      
      <div class="profile-grid">
        
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
            
            <div class="theme-options">
              <label class="theme-card" :class="{ 'active': localThemeMode === 'manual' }">
                <input type="radio" v-model="localThemeMode" value="manual" class="hidden-input" @change="saveThemeSettings">
                <div class="theme-card-content">
                  <SunMoonIcon :size="24" />
                  <strong>{{ $t('views.profile.theme_manual') }}</strong>
                  <span>{{ $t('views.profile.theme_manual_desc') }}</span>
                </div>
              </label>

              <label class="theme-card" :class="{ 'active': localThemeMode === 'auto' }">
                <input type="radio" v-model="localThemeMode" value="auto" class="hidden-input" @change="saveThemeSettings">
                <div class="theme-card-content">
                  <ClockIcon :size="24" />
                  <strong>{{ $t('views.profile.theme_auto') }}</strong>
                  <span>{{ $t('views.profile.theme_auto_desc') }}</span>
                </div>
              </label>
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

              <!-- Nová komponenta -->
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
    </div>

    <!-- Modál pro smazání avataru -->
    <RemoveAvatarConfirmModal
      :show="showRemoveAvatarModal"
      :isCurrentUser="true"
      @close="showRemoveAvatarModal = false"
      @confirm="removeAvatar"
    />

    <!-- Modál pro smazání účtu (požaduje heslo) -->
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

  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { storeToRefs } from 'pinia'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { 
  UserIcon, CameraIcon, KeyIcon, SunMoonIcon, 
  ClockIcon, AlertTriangleIcon, Trash2Icon, BanknoteIcon 
} from 'lucide-vue-next'

import BaseLoader from '../components/BaseLoader.vue'
import BaseInput from '../components/BaseInput.vue'
import BaseButton from '../components/BaseButton.vue'
import BaseModal from '../components/BaseModal.vue'
import BaseSelect from '../components/BaseSelect.vue'
import PasswordStrength from '../components/PasswordStrength.vue'
import RemoveAvatarConfirmModal from '../components/modals/RemoveAvatarConfirmModal.vue'

import { useAuthStore } from '../stores/auth'
import { useToastStore } from '../stores/toast'
import { apiFetch } from '../api'

const router = useRouter()
const authStore = useAuthStore()
const toastStore = useToastStore()
const { user } = storeToRefs(authStore)
const { t } = useI18n()

const isLoading = ref(false)
const isUploading = ref(false)

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

onMounted(() => {
  if (user.value) {
    localThemeMode.value = user.value.theme_mode || 'manual'
    localCurrency.value = user.value.default_currency || 'CZK'
  }
})

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
.view-header { margin-bottom: 2rem; }
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

.theme-options { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
.theme-card { display: block; cursor: pointer; position: relative; }
.theme-card-content { border: 2px solid var(--border); border-radius: var(--radius-sm); padding: 1.25rem; display: flex; flex-direction: column; align-items: center; gap: 0.5rem; text-align: center; transition: all 0.2s ease; background: var(--bg-app); color: var(--text-muted); }
.theme-card:hover .theme-card-content { border-color: var(--primary); background: var(--card-hover-bg); }
.theme-card.active .theme-card-content { border-color: var(--primary); background: rgba(250, 204, 21, 0.1); color: var(--primary); }
.theme-card-content strong { color: var(--text-main); font-size: 1rem; transition: color 0.3s ease; }
.theme-card-content span { font-size: 0.8rem; }

.currency-form { display: flex; align-items: flex-end; gap: 1rem; }

.password-form { display: flex; flex-direction: column; gap: 1.25rem; }
.form-row { display: flex; gap: 1rem; }
.half { flex: 1; }
.form-actions { display: flex; justify-content: flex-end; margin-top: 0.5rem; }

.danger-zone { border-color: rgba(239, 68, 68, 0.3); background: rgba(239, 68, 68, 0.02); }
.danger-title { color: var(--danger) !important; }

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