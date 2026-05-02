<template>
  <div class="auth-wrapper">
    <div class="auth-card">
      <div class="logo-container">
        <UserPlusIcon :size="56" color="var(--primary)" stroke-width="1.5" />
        <h1 class="logo-text">Nová registrace</h1>
      </div>
      <p class="auth-subtitle">Přidej se k pivní komunitě Pivoo.cz</p>

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
        
        <BaseDatePicker v-model="form.birthdate" label="Datum narození" required />

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
import { useToastStore } from '../stores/toast' 

import BaseInput from '../components/BaseInput.vue'
import BaseButton from '../components/BaseButton.vue'
import BaseFileUpload from '../components/BaseFileUpload.vue'
import BaseDatePicker from '../components/BaseDatePicker.vue' 

const router = useRouter()
const toastStore = useToastStore() 
const isLoading = ref(false)

const avatarFile = ref(null)
const form = ref({
  first_name: '', last_name: '', username: '', 
  email: '', birthdate: '', password: '', password_confirm: ''
})

const handleRegister = async () => {
  if (form.value.password !== form.value.password_confirm) {
    toastStore.showToast('Zadaná hesla se neshodují.', 'toast-error')
    return
  }
  
  isLoading.value = true
  
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
      toastStore.showToast('Registrace proběhla úspěšně! Nyní se můžeš přihlásit.')
      router.push('/')
    } else {
      toastStore.showToast(result.message || 'Registrace se nezdařila.', 'toast-error')
    }
  } catch (error) {
    toastStore.showToast('Chyba při komunikaci se serverem.', 'toast-error')
  } finally {
    isLoading.value = false
  }
}
</script>

<style scoped>
.auth-wrapper { 
  width: 100%;
  min-height: 100vh; 
  display: flex; 
  align-items: center; 
  justify-content: center; 
  background-color: var(--bg-app); 
  padding: 1rem; 
  transition: background-color 0.3s ease;
}

.auth-card { 
  background: var(--bg-panel); 
  padding: 3rem 2.5rem; 
  border-radius: var(--radius-md); 
  box-shadow: var(--shadow-floating); 
  width: 100%; 
  max-width: 700px; 
  text-align: center; 
  border: 1px solid var(--border); 
  transition: background-color 0.3s ease, border-color 0.3s ease;
}

.logo-container { display: flex; flex-direction: column; align-items: center; gap: 0.5rem; }

.logo-text { 
  font-size: 2.25rem; 
  font-weight: 800; 
  color: var(--text-main); 
  letter-spacing: -0.025em; 
  margin: 0; 
  transition: color 0.3s ease;
}

.auth-subtitle { 
  color: var(--text-muted); 
  font-size: 1rem; 
  margin-bottom: 2rem; 
  margin-top: 0.25rem; 
  transition: color 0.3s ease;
}

.auth-form { display: flex; flex-direction: column; gap: 1.25rem; text-align: left; }
.form-row { display: flex; gap: 1rem; }
.form-row > * { flex: 1; }
.avatar-upload-row { margin-bottom: 0.5rem; }

.auth-footer-link { 
  margin-top: 1.5rem; 
  text-align: center; 
  color: var(--text-muted); 
  font-size: 0.95rem; 
  transition: color 0.3s ease;
}

.auth-footer-link a { 
  color: var(--primary-hover); 
  text-decoration: none; 
  font-weight: 700; 
  transition: color 0.2s; 
}

.auth-footer-link a:hover { color: var(--primary); text-decoration: underline; }

@media (max-width: 600px) {
  .form-row { flex-direction: column; gap: 1.25rem; }
  .auth-card { padding: 2rem 1.5rem; }
}
</style>