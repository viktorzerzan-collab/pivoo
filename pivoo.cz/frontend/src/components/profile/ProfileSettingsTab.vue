<template>
  <div>
    <div class="profile-grid">
      
      <BasePanel class="avatar-card">
        <BaseImageUpload
          ref="imageUploadRef"
          :currentImageUrl="user?.avatar ? '/backend/uploads/avatars/' + user.avatar : null"
          :disabled="isUploading"
          :uploadTitle="$t('views.profile.change_avatar')"
          @file-selected="onAvatarSelected"
          @error="onAvatarError"
        />

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
      </BasePanel>

      <div class="settings-column">
        
        <BasePanel :title="$t('views.profile.theme_title')" :icon="SunMoonIcon">
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
        </BasePanel>

        <BasePanel :title="$t('views.profile.currency_title')" :icon="BanknoteIcon">
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
        </BasePanel>

        <BasePanel :title="$t('views.profile.password_title')" :icon="KeyIcon">
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
        </BasePanel>

        <BasePanel :title="$t('views.profile.two_fa_title')" :icon="ShieldIcon">
          <p class="setting-desc">{{ $t('views.profile.two_fa_desc') }}</p>
          
          <div v-if="user?.is_2fa_enabled" class="two-fa-status-active">
            <span class="badge role_admin" style="background-color: #10b981; color: white; margin-bottom: 1rem; padding: 0.5rem 1rem; display: inline-block;">
              {{ $t('views.profile.two_fa_enabled') }}
            </span>
            <div>
              <BaseButton @click="disable2fa" variant="danger" :disabled="is2faLoading">
                {{ $t('views.profile.two_fa_disable_btn') }}
              </BaseButton>
            </div>
          </div>

          <div v-else-if="!show2faSetup">
            <BaseButton @click="initiate2fa" variant="add" :disabled="is2faLoading">
              {{ $t('views.profile.two_fa_enable_btn') }}
            </BaseButton>
          </div>

          <div v-else class="two-fa-setup-wrapper">
            <p class="setting-desc" style="font-weight: 500;">{{ $t('views.profile.two_fa_setup_instructions') }}</p>
            
            <div class="qr-container" style="background: white; padding: 1rem; display: inline-block; border-radius: 8px; border: 1px solid var(--border); margin-bottom: 1rem;">
              <img :src="qrCodeUrl" alt="2FA QR Code" style="display: block; max-width: 200px; height: auto;" />
            </div>

            <div style="margin-bottom: 1.5rem;">
              <small class="username">{{ $t('views.profile.two_fa_backup_key') }} <code style="background: var(--bg-app); padding: 0.2rem 0.5rem; border-radius: 4px; color: var(--text-main); font-weight: bold; letter-spacing: 1px;">{{ totpSecret }}</code></small>
            </div>

            <form @submit.prevent="confirm2fa" style="max-width: 300px; display: flex; flex-direction: column; gap: 1rem;">
              <BaseInput 
                v-model="twoFaCode" 
                type="text" 
                inputmode="numeric" 
                pattern="[0-9]*" 
                maxlength="6" 
                :label="$t('views.profile.two_fa_verification_code')" 
                required 
              />
              <div style="display: flex; gap: 1rem;">
                <BaseButton type="button" variant="secondary" style="background: var(--bg-app); border: 1px solid var(--border); color: var(--text-main);" @click="show2faSetup = false; twoFaCode = ''">
                  {{ $t('buttons.cancel') }}
                </BaseButton>
                <BaseButton type="submit" variant="primary" :disabled="is2faLoading || twoFaCode.length !== 6">
                  {{ $t('views.profile.two_fa_verify_btn') }}
                </BaseButton>
              </div>
            </form>
          </div>
        </BasePanel>

        <BasePanel :title="$t('views.profile.danger_zone')" :icon="AlertTriangleIcon" class="danger-zone">
          <p class="setting-desc">{{ $t('views.profile.danger_desc') }}</p>
          <BaseButton @click="showDeleteModal = true" variant="danger">{{ $t('views.profile.delete_account') }}</BaseButton>
        </BasePanel>

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
             <BaseButton type="button" variant="secondary" style="background:var(--bg-app); border:1px solid var(--border); color:var(--text-main);" @click="showDeleteModal = false">{{ $t('buttons.cancel') }}</BaseButton>
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
  KeyIcon, SunMoonIcon, ClockIcon, AlertTriangleIcon, BanknoteIcon, ShieldIcon
} from 'lucide-vue-next'

import BaseInput from '../BaseInput.vue'
import BaseButton from '../BaseButton.vue'
import BaseModal from '../BaseModal.vue'
import BasePanel from '../BasePanel.vue'
import BaseSelect from '../BaseSelect.vue'
import BaseSwitch from '../BaseSwitch.vue'
import PasswordStrength from '../PasswordStrength.vue'
import RemoveAvatarConfirmModal from '../modals/RemoveAvatarConfirmModal.vue'
import BaseImageUpload from '../BaseImageUpload.vue'

import { useAuthStore } from '../../stores/auth'
import { useToastStore } from '../../stores/toast'
import { apiFetch } from '../../api'

const router = useRouter()
const authStore = useAuthStore()
const toastStore = useToastStore()
const { user } = storeToRefs(authStore)
const { t } = useI18n()

const isUploading = ref(false)

// Uživatelské nastavení - Stav
const newAvatarFile = ref(null)
const imageUploadRef = ref(null)
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

// Stav pro 2FA
const show2faSetup = ref(false)
const qrCodeUrl = ref('')
const totpSecret = ref('')
const twoFaCode = ref('')
const is2faLoading = ref(false)

onMounted(() => {
  if (user.value) {
    localThemeMode.value = user.value.theme_mode || 'manual'
    localCurrency.value = user.value.default_currency || 'CZK'
  }
})

// Ošetření výběru fotky z BaseImageUpload
const onAvatarSelected = (file) => {
  newAvatarFile.value = file
}

// Ošetření chyby při kompresi z BaseImageUpload
const onAvatarError = (msg) => {
  toastStore.showToast(msg, 'toast-error')
}

const uploadAvatar = async () => {
  if (!newAvatarFile.value) return
  isUploading.value = true
  
  try {
    const formData = new FormData()
    formData.append('action', 'upload')
    formData.append('avatar', newAvatarFile.value)

    const res = await apiFetch('/update_avatar.php', {
      method: 'POST',
      body: formData
    })

    if (res.status === 'success') {
      authStore.updateUser({ avatar: res.avatar })
      toastStore.showToast('Profilová fotka uložena.', 'toast-success')
      
      newAvatarFile.value = null
      if (imageUploadRef.value) {
        imageUploadRef.value.resetPreview()
      }
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
      
      newAvatarFile.value = null
      if (imageUploadRef.value) {
        imageUploadRef.value.resetPreview()
      }
    } else {
      toastStore.showToast(res.message || 'Chyba při mazání fotky.', 'toast-error')
    }
  } catch (err) {
    toastStore.showToast('Chyba při komunikaci se serverem.', 'toast-error')
  } finally {
    isUploading.value = false
  }
}

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

// === 2FA LOGIKA ===
const initiate2fa = async () => {
  is2faLoading.value = true
  try {
    const res = await apiFetch('/update_profile.php', {
      method: 'POST',
      body: JSON.stringify({ action: 'setup_2fa' })
    })
    if (res.status === 'success') {
      qrCodeUrl.value = res.qr_url
      totpSecret.value = res.secret
      show2faSetup.value = true
    } else {
      toastStore.showToast(res.message || 'Chyba inicializace 2FA.', 'toast-error')
    }
  } catch (err) {
    toastStore.showToast(t('toast.communication_error'), 'toast-error')
  } finally {
    is2faLoading.value = false
  }
}

const confirm2fa = async () => {
  if (twoFaCode.value.length !== 6) return
  is2faLoading.value = true
  try {
    const res = await apiFetch('/update_profile.php', {
      method: 'POST',
      body: JSON.stringify({
        action: 'confirm_2fa',
        totp_code: twoFaCode.value
      })
    })
    if (res.status === 'success') {
      authStore.updateUser({ is_2fa_enabled: true })
      toastStore.showToast(res.message, 'toast-success')
      show2faSetup.value = false
      twoFaCode.value = ''
    } else {
      toastStore.showToast(res.message || 'Neplatný kód.', 'toast-error')
    }
  } catch (err) {
    toastStore.showToast(t('toast.communication_error'), 'toast-error')
  } finally {
    is2faLoading.value = false
  }
}

const disable2fa = async () => {
  is2faLoading.value = true
  try {
    const res = await apiFetch('/update_profile.php', {
      method: 'POST',
      body: JSON.stringify({ action: 'disable_2fa' })
    })
    if (res.status === 'success') {
      authStore.updateUser({ is_2fa_enabled: false })
      toastStore.showToast(res.message, 'toast-success')
    } else {
      toastStore.showToast(res.message || 'Chyba při vypínání 2FA.', 'toast-error')
    }
  } catch (err) {
    toastStore.showToast(t('toast.communication_error'), 'toast-error')
  } finally {
    is2faLoading.value = false
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
.profile-grid { display: grid; grid-template-columns: 320px 1fr; gap: 2rem; align-items: start; }
.avatar-card { display: flex; flex-direction: column; align-items: center; text-align: center; gap: 1.5rem; }
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

.danger-zone :deep(.panel-header h3) { color: var(--danger) !important; }
.danger-zone { border-color: rgba(239, 68, 68, 0.3); background: rgba(239, 68, 68, 0.02); }

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
