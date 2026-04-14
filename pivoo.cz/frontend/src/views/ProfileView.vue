<template>
  <div class="profile-page">
    <transition name="toast-fade">
      <div v-if="toast.show" class="toast-notification" :class="toast.type">{{ toast.message }}</div>
    </transition>

    <div class="profile-content">
      
      <div class="panel-card user-info-card">
        <div class="avatar-section">
          <div class="avatar-large">
            <img v-if="user?.avatar" :src="'https://www.pivoo.cz/backend/uploads/avatars/' + user.avatar" alt="Avatar" class="avatar-img" />
            <UserIcon v-else :size="64" color="#94a3b8" />
          </div>
        </div>
        
        <div class="user-details">
          <div class="user-main-info">
            <h3>{{ user?.username }}</h3>
            <p>{{ user?.first_name }} {{ user?.last_name }}</p>
            <span class="badge" :class="user?.role === 'admin' ? 'admin' : 'user'">
              {{ user?.role === 'admin' ? 'Administrátor' : 'Běžný pivař' }}
            </span>
          </div>

          <div class="avatar-actions-grid">
            <BaseFileUpload v-model:file="avatarFile" placeholder="Klikni pro změnu fotky" />
            <div class="btn-group">
              <button v-if="avatarFile" @click="handleAvatarUpload" class="btn-primary">Uložit novou fotku</button>
              <button v-if="user?.avatar" @click="handleAvatarRemove" class="btn-danger">Odstranit fotku</button>
            </div>
          </div>
        </div>
      </div>

      <div class="panel-card">
        <div class="panel-header">
          <h3><PaletteIcon :size="20" class="panel-icon" /> Vzhled aplikace</h3>
        </div>
        <div class="theme-settings">
          <p class="settings-desc">Zvolte, jak má aplikace vypadat, nebo nechte přepínání na denní době.</p>
          
          <div class="theme-options-grid">
            <label class="theme-option" :class="{ active: themeForm.theme_mode === 'manual' }">
              <input type="radio" v-model="themeForm.theme_mode" value="manual" @change="saveThemeSettings" />
              <div class="option-content">
                <div class="option-icon"><MonitorIcon :size="24" /></div>
                <span>Manuální</span>
                <small>Podle horní lišty</small>
              </div>
            </label>

            <label class="theme-option" :class="{ active: themeForm.theme_mode === 'auto' }">
              <input type="radio" v-model="themeForm.theme_mode" value="auto" @change="saveThemeSettings" />
              <div class="option-content">
                <div class="option-icon"><ClockIcon :size="24" /></div>
                <span>Automatický</span>
                <small>Dle denní doby</small>
              </div>
            </label>
          </div>
        </div>
      </div>

      <div class="panel-card">
        <div class="panel-header">
          <h3><KeyIcon :size="20" class="panel-icon" /> Změna hesla</h3>
        </div>
        <form @submit.prevent="handlePasswordChange" class="profile-form">
          <BaseInput v-model="passForm.old_password" type="password" label="Současné heslo" required />
          <BaseInput v-model="passForm.new_password" type="password" label="Nové heslo" required />
          <BaseInput v-model="passForm.new_password_confirm" type="password" label="Potvrzení nového hesla" required />
          <button type="submit" class="btn-primary" style="margin-top: 0.5rem; max-width: 250px;">
            Změnit heslo
          </button>
        </form>
      </div>

      <div class="panel-card danger-zone">
        <div class="panel-header">
          <h3 style="color: #ef4444;"><Trash2Icon :size="20" /> Nebezpečná zóna</h3>
        </div>
        <p class="danger-text">
          Smazáním účtu nenávratně ztratíš svůj pivní deníček. Tato akce nelze vrátit zpět.
        </p>
        <button class="btn-danger" @click="isDeleteModalOpen = true">Trvale smazat účet</button>
      </div>
    </div>

    <BaseModal :show="isDeleteModalOpen" @close="isDeleteModalOpen = false">
      <template #header><h2 style="margin: 0; color: #ef4444;">Smazat účet?</h2></template>
      <template #body>
        <form @submit.prevent="handleAccountDeletion" style="display: flex; flex-direction: column; gap: 1.25rem;">
          <p style="margin: 0; color: var(--text-muted);">Pro potvrzení smazání účtu prosím zadej své aktuální heslo.</p>
          <BaseInput v-model="deletePassword" type="password" label="Tvé heslo *" required />
          <button type="submit" class="btn-danger" style="margin-top: 0.5rem; width: 100%;">Ano, smazat vše</button>
        </form>
      </template>
    </BaseModal>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { storeToRefs } from 'pinia'
import { useRouter } from 'vue-router'
import { UserIcon, KeyIcon, Trash2Icon, PaletteIcon, MonitorIcon, ClockIcon } from 'lucide-vue-next'
import { apiFetch } from '../api'

import { useAuthStore } from '../stores/auth'
import BaseInput from '../components/BaseInput.vue'
import BaseModal from '../components/BaseModal.vue'
import BaseFileUpload from '../components/BaseFileUpload.vue'

const router = useRouter()
const authStore = useAuthStore()
const { user } = storeToRefs(authStore)

const toast = ref({ show: false, message: '', type: 'toast-success' })
const showToast = (message, type = 'toast-success') => { 
  toast.value = { show: true, message, type }; setTimeout(() => { toast.value.show = false }, 3000) 
}

const passForm = ref({ old_password: '', new_password: '', new_password_confirm: '' })
const themeForm = ref({ theme_mode: 'manual' })
const isDeleteModalOpen = ref(false)
const deletePassword = ref('')
const avatarFile = ref(null)

onMounted(() => {
  if (user.value?.theme_mode) {
    themeForm.value.theme_mode = user.value.theme_mode
  }
})

const saveThemeSettings = async () => {
  try {
    const result = await apiFetch('/update_profile.php', {
      method: 'POST',
      body: JSON.stringify({ 
        action: 'update_theme',
        theme_mode: themeForm.value.theme_mode 
      })
    })
    
    if (result.status === 'success') {
      authStore.updateUser({ theme_mode: themeForm.value.theme_mode })
      showToast(result.message)
    }
  } catch (error) { showToast('Chyba při ukládání nastavení.', 'toast-error') }
}

const handleAvatarUpload = async () => {
  if (!avatarFile.value) return
  const formData = new FormData()
  formData.append('action', 'upload')
  formData.append('avatar', avatarFile.value)

  try {
    const result = await apiFetch('/update_avatar.php', {
      method: 'POST',
      body: formData
    })
    
    if (result.status === 'success') {
      authStore.updateUser({ avatar: result.avatar })
      avatarFile.value = null
      showToast(result.message)
    } else { showToast(result.message, 'toast-error') }
  } catch (error) { showToast('Chyba komunikace.', 'toast-error') }
}

const handleAvatarRemove = async () => {
  const formData = new FormData()
  formData.append('action', 'remove')
  try {
    const result = await apiFetch('/update_avatar.php', {
      method: 'POST',
      body: formData
    })
    
    if (result.status === 'success') {
      authStore.updateUser({ avatar: null })
      showToast(result.message)
    }
  } catch (error) { showToast('Chyba komunikace.', 'toast-error') }
}

const handlePasswordChange = async () => {
  if (passForm.value.new_password !== passForm.value.new_password_confirm) {
    showToast('Nová hesla se neshodují.', 'toast-error')
    return
  }
  try {
    const result = await apiFetch('/update_profile.php', {
      method: 'POST',
      body: JSON.stringify({ 
        action: 'change_password',
        old_password: passForm.value.old_password, 
        new_password: passForm.value.new_password 
      })
    })
    
    if (result.status === 'success') {
      showToast(result.message)
      passForm.value = { old_password: '', new_password: '', new_password_confirm: '' }
    } else { showToast(result.message, 'toast-error') }
  } catch (error) { showToast('Chyba komunikace se serverem.', 'toast-error') }
}

const handleAccountDeletion = async () => {
  try {
    const result = await apiFetch('/delete_profile.php', {
      method: 'POST',
      body: JSON.stringify({ password: deletePassword.value })
    })
    
    if (result.status === 'success') {
      isDeleteModalOpen.value = false; authStore.logout(); router.push('/')
    } else { showToast(result.message, 'toast-error') }
  } catch (error) { showToast('Chyba komunikace.', 'toast-error') }
}
</script>

<style scoped>
.profile-content { display: flex; flex-direction: column; gap: 2rem; max-width: 800px; margin: 0 auto; }
.panel-card { background: var(--bg-panel); border-radius: 12px; border: 1px solid var(--border); box-shadow: var(--shadow-sm); padding: 1.5rem; transition: background-color 0.5s ease, border-color 0.5s ease; }
.panel-header { border-bottom: 1px solid var(--border); padding-bottom: 1rem; margin-bottom: 1.5rem; }
.panel-header h3 { margin: 0; display: flex; align-items: center; gap: 0.5rem; font-size: 1.25rem; color: var(--text-main); }
.panel-icon { color: var(--primary); }

.user-info-card { display: flex; align-items: center; gap: 2.5rem; padding: 2.5rem; }
.avatar-section { flex-shrink: 0; }
.avatar-large { width: 150px; height: 150px; border-radius: 50%; background: var(--bg-app); display: flex; align-items: center; justify-content: center; border: 4px solid var(--primary); overflow: hidden; box-shadow: var(--shadow-md); }
.avatar-img { width: 100%; height: 100%; object-fit: cover; }

.user-details { flex-grow: 1; display: flex; flex-direction: column; gap: 1.5rem; }
.user-main-info h3 { margin: 0; font-size: 1.75rem; color: var(--text-main); }
.user-main-info p { margin: 0.25rem 0 0.75rem; color: var(--text-muted); font-size: 1.1rem; }

.avatar-actions-grid { display: flex; flex-direction: column; gap: 1rem; max-width: 350px; }
.btn-group { display: flex; gap: 0.75rem; }

.theme-settings { display: flex; flex-direction: column; gap: 1.5rem; }
.settings-desc { color: var(--text-muted); margin: 0; font-size: 0.95rem; }

.theme-options-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
.theme-option {
  position: relative;
  cursor: pointer;
  border: 2px solid var(--border);
  border-radius: 12px;
  padding: 1.25rem;
  transition: all 0.2s ease;
  background: var(--bg-app);
}
.theme-option input { position: absolute; opacity: 0; }
.theme-option.active { border-color: var(--primary); background: rgba(250, 204, 21, 0.1); }
.option-content { display: flex; flex-direction: column; align-items: center; text-align: center; gap: 0.25rem; }
.option-icon { color: var(--text-muted); margin-bottom: 0.5rem; }
.active .option-icon { color: var(--primary); }
.option-content span { font-weight: 700; color: var(--text-main); }
.option-content small { color: var(--text-muted); font-size: 0.8rem; }

.profile-form { display: flex; flex-direction: column; gap: 1.25rem; }
.danger-zone { border: 1px solid #fca5a5; background: rgba(239, 68, 68, 0.05); }
.danger-text { color: #ef4444; margin-top: 0; margin-bottom: 1.5rem; line-height: 1.5; }

@media (max-width: 700px) {
  .user-info-card { flex-direction: column; text-align: center; }
  .avatar-actions-grid { align-items: center; margin: 0 auto; }
  .theme-options-grid { grid-template-columns: 1fr; }
}
</style>