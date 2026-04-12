<template>
  <div class="auth-wrapper">
    <div class="auth-card">
      <div class="logo-container">
        <UserPlusIcon :size="56" color="var(--primary)" stroke-width="1.5" />
        <h1 class="logo-text">Nová registrace</h1>
      </div>
      <p class="auth-subtitle">Přidej se k pivní komunitě Pivoo.cz</p>

      <div v-if="errorMessage" class="auth-error-banner">
        {{ errorMessage }}
      </div>

      <form @submit.prevent="handleRegister" class="auth-form">
        
        <div class="avatar-upload-row">
          <BaseFileUpload v-model:file="avatarFile" label="Profilová fotka (Volitelné)" />
        </div>

        <div class="form-row">
          <BaseInput v-model="form.first_name" label="Křestní jméno" placeholder="Jan" required />
          <BaseInput v-model="form.last_name" label="Příjmení" placeholder="Novák" required />
        </div>

        <BaseInput v-model="form.username" label="Přezdívka (Username)" placeholder="Např. Honza88" required />
        <BaseInput v-model="form.email" type="email" label="E-mail" placeholder="jan@novak.cz" required />
        <BaseInput v-model="form.birthdate" type="date" label="Datum narození" required />

        <div class="form-row">
          <BaseInput v-model="form.password" type="password" label="Heslo" placeholder="••••••••" required />
          <BaseInput v-model="form.password_confirm" type="password" label="Potvrzení hesla" placeholder="••••••••" required />
        </div>

        <BaseButton type="submit" variant="primary" style="margin-top: 1rem; width: 100%;" :disabled="isLoading">
          <template #icon><UserPlusIcon :size="18" /></template>
          {{ isLoading ? 'Zpracovávám...' : 'Vytvořit účet' }}
        </BaseButton>

        <div class="auth-footer-link">
          Už máš účet? <router-link to="/">Přihlas se zde</router-link>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { UserPlusIcon } from 'lucide-vue-next'
import { apiFetch } from '../api'

import BaseInput from '../components/BaseInput.vue'
import BaseButton from '../components/BaseButton.vue'
import BaseFileUpload from '../components/BaseFileUpload.vue'

const router = useRouter()
const isLoading = ref(false)
const errorMessage = ref('')

const avatarFile = ref(null)
const form = ref({
  first_name: '', last_name: '', username: '', 
  email: '', birthdate: '', password: '', password_confirm: ''
})

const handleRegister = async () => {
  if (form.value.password !== form.value.password_confirm) {
    errorMessage.value = 'Zadaná hesla se neshodují.'
    return
  }
  
  isLoading.value = true; errorMessage.value = ''
  
  const formData = new FormData()
  Object.keys(form.value).forEach(key => formData.append(key, form.value[key]))
  if (avatarFile.value) {
    formData.append('avatar', avatarFile.value)
  }

  try {
    const result = await apiFetch('/register.php', {
      method: 'POST',
      body: formData 
    })
    
    if (result.status === 'success') {
      router.push('/')
    } else {
      errorMessage.value = result.message || 'Registrace se nezdařila.'
    }
  } catch (error) {
    errorMessage.value = 'Chyba při komunikaci se serverem.'
  } finally {
    isLoading.value = false
  }
}
</script>

<style scoped>
.auth-wrapper { min-height: 100vh; display: flex; align-items: center; justify-content: center; background-color: var(--bg-app); padding: 1rem; }
.auth-card { background: var(--bg-panel); padding: 3rem 2.5rem; border-radius: 16px; box-shadow: var(--shadow-md); width: 100%; max-width: 550px; text-align: center; border: 1px solid var(--border); }
.logo-container { display: flex; flex-direction: column; align-items: center; gap: 0.5rem; }
.logo-text { font-size: 2.25rem; font-weight: 800; color: var(--text-main); letter-spacing: -0.025em; margin: 0; }
.auth-subtitle { color: var(--text-muted); font-size: 1rem; margin-bottom: 2rem; margin-top: 0.25rem; }
.auth-form { display: flex; flex-direction: column; gap: 1.25rem; text-align: left; }
.form-row { display: flex; gap: 1rem; }
.form-row > * { flex: 1; }
.avatar-upload-row { margin-bottom: 0.5rem; }
.auth-error-banner { background-color: #fee2e2; color: #ef4444; padding: 0.75rem; border-radius: 8px; margin-bottom: 1.5rem; font-size: 0.9rem; border: 1px solid #fca5a5; font-weight: 600; }
.auth-footer-link { margin-top: 1.5rem; text-align: center; color: var(--text-muted); font-size: 0.95rem; }
.auth-footer-link a { color: var(--primary-hover); text-decoration: none; font-weight: 700; transition: color 0.2s; }
.auth-footer-link a:hover { color: var(--primary); text-decoration: underline; }

@media (max-width: 600px) {
  .form-row { flex-direction: column; gap: 1.25rem; }
  .auth-card { padding: 2rem 1.5rem; }
}
</style>