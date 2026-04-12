<template>
  <div class="profile-page">
    <transition name="toast-fade">
      <div v-if="toast.show" class="toast-notification" :class="toast.type">{{ toast.message }}</div>
    </transition>

    <div class="view-header">
      <h2 class="section-title">Můj profil</h2>
      <p class="auth-subtitle">Nastavení účtu a zabezpečení</p>
    </div>

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
              <BaseButton v-if="avatarFile" @click="handleAvatarUpload" variant="primary">Uložit novou fotku</BaseButton>
              <BaseButton v-if="user?.avatar" @click="handleAvatarRemove" variant="secondary" class="remove-btn">Odstranit současnou</BaseButton>
            </div>
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
          <BaseButton type="submit" variant="primary" style="margin-top: 0.5rem; max-width: 250px;">
            Změnit heslo
          </BaseButton>
        </form>
      </div>

      <div class="panel-card danger-zone">
        <div class="panel-header">
          <h3 style="color: #ef4444;"><Trash2Icon :size="20" /> Nebezpečná zóna</h3>
        </div>
        <p class="danger-text">
          Smazáním účtu nenávratně ztratíš svůj pivní deníček. Tato akce nelze vrátit zpět.
        </p>
        <BaseButton variant="danger" @click="isDeleteModalOpen = true">Trvale smazat účet</BaseButton>
      </div>
    </div>

    <BaseModal :show="isDeleteModalOpen" @close="isDeleteModalOpen = false">
      <template #header><h2 style="margin: 0; color: #ef4444;">Smazat účet?</h2></template>
      <template #body>
        <form @submit.prevent="handleAccountDeletion" style="display: flex; flex-direction: column; gap: 1.25rem;">
          <p style="margin: 0; color: #4b5563;">Pro potvrzení smazání účtu prosím zadej své aktuální heslo.</p>
          <BaseInput v-model="deletePassword" type="password" label="Tvé heslo *" required />
          <BaseButton type="submit" variant="danger" style="margin-top: 0.5rem; width: 100%;">Ano, smazat vše</BaseButton>
        </form>
      </template>
    </BaseModal>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { storeToRefs } from 'pinia'
import { useRouter } from 'vue-router'
import { UserIcon, KeyIcon, Trash2Icon } from 'lucide-vue-next'

import { useAuthStore } from '../stores/auth'
import BaseButton from '../components/BaseButton.vue'
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
const isDeleteModalOpen = ref(false)
const deletePassword = ref('')
const avatarFile = ref(null)

const handleAvatarUpload = async () => {
  if (!avatarFile.value) return
  const formData = new FormData()
  formData.append('action', 'upload')
  formData.append('avatar', avatarFile.value)

  try {
    const res = await fetch('https://www.pivoo.cz/backend/api/update_avatar.php', {
      method: 'POST',
      headers: { 'Authorization': `Bearer ${authStore.token}` },
      body: formData
    })
    const result = await res.json()
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
    const res = await fetch('https://www.pivoo.cz/backend/api/update_avatar.php', {
      method: 'POST',
      headers: { 'Authorization': `Bearer ${authStore.token}` },
      body: formData
    })
    const result = await res.json()
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
    const res = await fetch('https://www.pivoo.cz/backend/api/update_profile.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${authStore.token}` },
      body: JSON.stringify({ old_password: passForm.value.old_password, new_password: passForm.value.new_password })
    })
    const result = await res.json()
    if (result.status === 'success') {
      showToast(result.message)
      passForm.value = { old_password: '', new_password: '', new_password_confirm: '' }
    } else { showToast(result.message, 'toast-error') }
  } catch (error) { showToast('Chyba komunikace se serverem.', 'toast-error') }
}

const handleAccountDeletion = async () => {
  try {
    const res = await fetch('https://www.pivoo.cz/backend/api/delete_profile.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${authStore.token}` },
      body: JSON.stringify({ password: deletePassword.value })
    })
    const result = await res.json()
    if (result.status === 'success') {
      isDeleteModalOpen.value = false; authStore.logout(); router.push('/')
    } else { showToast(result.message, 'toast-error') }
  } catch (error) { showToast('Chyba komunikace.', 'toast-error') }
}
</script>

<style scoped>
.profile-content { display: flex; flex-direction: column; gap: 2rem; max-width: 800px; margin: 0 auto; }
.panel-card { background: var(--bg-panel); border-radius: 12px; border: 1px solid var(--border); box-shadow: var(--shadow-sm); padding: 1.5rem; }
.panel-header { border-bottom: 1px solid var(--border); padding-bottom: 1rem; margin-bottom: 1.5rem; }
.panel-header h3 { margin: 0; display: flex; align-items: center; gap: 0.5rem; font-size: 1.25rem; color: #334155; }
.panel-icon { color: var(--primary); }

.user-info-card { display: flex; align-items: center; gap: 2.5rem; padding: 2.5rem; }
.avatar-section { flex-shrink: 0; }
.avatar-large { width: 150px; height: 150px; border-radius: 50%; background: #f1f5f9; display: flex; align-items: center; justify-content: center; border: 4px solid var(--primary); overflow: hidden; box-shadow: var(--shadow-md); }
.avatar-img { width: 100%; height: 100%; object-fit: cover; }

.user-details { flex-grow: 1; display: flex; flex-direction: column; gap: 1.5rem; }
.user-main-info h3 { margin: 0; font-size: 1.75rem; color: #1e293b; }
.user-main-info p { margin: 0.25rem 0 0.75rem; color: #64748b; font-size: 1.1rem; }

.avatar-actions-grid { display: flex; flex-direction: column; gap: 1rem; max-width: 350px; }
.btn-group { display: flex; gap: 0.75rem; }
.remove-btn { color: #ef4444; border-color: #fca5a5; background: white; }
.remove-btn:hover { background-color: #fef2f2; }

.profile-form { display: flex; flex-direction: column; gap: 1.25rem; }
.danger-zone { border: 1px solid #fca5a5; background: #fff5f5; }
.danger-text { color: #b91c1c; margin-top: 0; margin-bottom: 1.5rem; line-height: 1.5; }

@media (max-width: 700px) {
  .user-info-card { flex-direction: column; text-align: center; }
  .avatar-actions-grid { align-items: center; margin: 0 auto; }
}
</style>