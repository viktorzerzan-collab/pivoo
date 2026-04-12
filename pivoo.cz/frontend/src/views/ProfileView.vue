<template>
  <div class="profile-page">
    <transition name="toast-fade">
      <div v-if="toast.show" class="toast-notification" :class="toast.type">{{ toast.message }}</div>
    </transition>

    <div class="view-header">
      <h2 class="section-title">Můj profil</h2>
      <p class="auth-subtitle">Nastavení tvého pivařského účtu</p>
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
              <button v-if="avatarFile" @click="handleAvatarUpload" class="btn-primary">Uložit novou fotku</button>
              <button v-if="user?.avatar" @click="handleAvatarRemove" class="btn-danger">Odstranit fotku</button>
            </div>
          </div>
        </div>
      </div>

      <div class="panel-card">
        <div class="panel-header">
          <h3><KeyIcon /> Změna hesla</h3>
        </div>
        <form @submit.prevent="handlePasswordChange" class="profile-form">
          <BaseInput v-model="passForm.old_password" type="password" label="Současné heslo" required />
          <div class="form-row">
            <BaseInput v-model="passForm.new_password" type="password" label="Nové heslo" required />
            <BaseInput v-model="passForm.new_password_confirm" type="password" label="Potvrzení nového hesla" required />
          </div>
          <button type="submit" class="btn-primary" style="max-width: 200px;">Změnit heslo</button>
        </form>
      </div>

      <div class="panel-card danger-zone">
        <div class="panel-header"><h3 style="color: #ef4444;"><Trash2Icon /> Zrušení účtu</h3></div>
        <p>Smazáním účtu nenávratně přijdeš o celou historii vypitých piv. Tato akce je nevratná.</p>
        <button class="btn-danger" @click="isDeleteModalOpen = true">Trvale smazat účet</button>
      </div>
    </div>

    <BaseModal :show="isDeleteModalOpen" @close="isDeleteModalOpen = false">
      <template #header><h2 style="color: #ef4444;">⚠️ Smazat účet?</h2></template>
      <template #body>
        <form @submit.prevent="handleAccountDeletion" class="profile-form">
          <p>Pro potvrzení smazání zadej své aktuální heslo.</p>
          <BaseInput v-model="deletePassword" type="password" label="Tvé heslo *" required />
          <button type="submit" class="btn-danger" style="width: 100%;">Ano, vše smazat</button>
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
const isDeleteModalOpen = ref(false)
const deletePassword = ref('')
const avatarFile = ref(null)

const handleAvatarUpload = async () => {
  if (!avatarFile.value) return
  const formData = new FormData()
  formData.append('action', 'upload'); formData.append('avatar', avatarFile.value)

  try {
    const result = await apiFetch('/update_avatar.php', { method: 'POST', body: formData })
    if (result.status === 'success') {
      authStore.updateUser({ avatar: result.avatar })
      avatarFile.value = null; showToast(result.message)
    }
  } catch (error) { showToast('Chyba nahrávání.', 'toast-error') }
}

const handleAvatarRemove = async () => {
  const formData = new FormData(); formData.append('action', 'remove')
  try {
    const result = await apiFetch('/update_avatar.php', { method: 'POST', body: formData })
    if (result.status === 'success') { authStore.updateUser({ avatar: null }); showToast(result.message) }
  } catch (error) { showToast('Chyba komunikace.', 'toast-error') }
}

const handlePasswordChange = async () => {
  if (passForm.value.new_password !== passForm.value.new_password_confirm) {
    showToast('Hesla se neshodují.', 'toast-error'); return
  }
  try {
    const result = await apiFetch('/update_profile.php', { method: 'POST', body: { old_password: passForm.value.old_password, new_password: passForm.value.new_password } })
    if (result.status === 'success') { showToast(result.message); passForm.value = { old_password: '', new_password: '', new_password_confirm: '' } }
    else { showToast(result.message, 'toast-error') }
  } catch (error) { showToast('Chyba serveru.', 'toast-error') }
}

const handleAccountDeletion = async () => {
  try {
    const result = await apiFetch('/delete_profile.php', { method: 'POST', body: { password: deletePassword.value } })
    if (result.status === 'success') { isDeleteModalOpen.value = false; authStore.logout(); router.push('/') }
    else { showToast(result.message, 'toast-error') }
  } catch (error) { showToast('Chyba.', 'toast-error') }
}
</script>

<style scoped>
.profile-content { display: flex; flex-direction: column; gap: 2rem; max-width: 800px; margin: 0 auto; }
.panel-card { background: white; border-radius: 12px; border: 1px solid var(--border); padding: 2rem; }
.panel-header { border-bottom: 1px solid var(--border); padding-bottom: 1rem; margin-bottom: 1.5rem; }
.panel-header h3 { margin: 0; display: flex; align-items: center; gap: 0.5rem; font-size: 1.25rem; }
.user-info-card { display: flex; align-items: center; gap: 2.5rem; }
.avatar-large { width: 140px; height: 140px; border-radius: 50%; background: #f1f5f9; display: flex; align-items: center; justify-content: center; border: 4px solid var(--primary); overflow: hidden; }
.avatar-img { width: 100%; height: 100%; object-fit: cover; }
.user-details { flex: 1; display: flex; flex-direction: column; gap: 1.5rem; }
.avatar-actions-grid { display: flex; flex-direction: column; gap: 1rem; max-width: 300px; }
.btn-group { display: flex; gap: 0.5rem; }
.profile-form { display: flex; flex-direction: column; gap: 1.25rem; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
.danger-zone { border-color: #fca5a5; background: #fff5f5; }
@media (max-width: 650px) { .user-info-card { flex-direction: column; text-align: center; } .form-row { grid-template-columns: 1fr; } }
</style>