<template>
  <div class="auth-wrapper">
    <div class="auth-card">
      <div class="logo-container">
        <UserPlusIcon :size="56" color="var(--primary)" stroke-width="1.5" />
        <h1 class="logo-text">{{ $t('views.register.title') }}</h1>
      </div>
      <p class="auth-subtitle">{{ $t('views.register.subtitle') }}</p>

      <form @submit.prevent="handleRegister" class="auth-form">
        
        <div class="avatar-upload-row">
          <BaseFileUpload v-model:file="avatarFile" :label="$t('views.register.avatar')" />
        </div>

        <div class="form-row">
          <BaseInput v-model="form.first_name" :label="$t('views.register.first_name')" :placeholder="$t('views.register.first_name_placeholder')" required />
          <BaseInput v-model="form.last_name" :label="$t('views.register.last_name')" :placeholder="$t('views.register.last_name_placeholder')" required />
        </div>

        <BaseInput v-model="form.username" :label="$t('views.register.username')" :placeholder="$t('views.register.username_placeholder')" required />
        
        <div class="form-row">
          <BaseInput v-model="form.email" type="email" :label="$t('views.register.email')" :placeholder="$t('views.register.email_placeholder')" required />
          <BaseSelect v-model="form.default_currency" :label="$t('views.register.currency')" required>
            <option value="CZK">{{ $t('currencies.CZK') }}</option>
            <option value="EUR">{{ $t('currencies.EUR') }}</option>
            <option value="PLN">{{ $t('currencies.PLN') }}</option>
            <option value="GBP">{{ $t('currencies.GBP') }}</option>
          </BaseSelect>
        </div>
        
        <BaseDatePicker v-model="form.birthdate" :label="$t('views.register.birthdate')" required />

        <div class="form-row">
          <BaseInput v-model="form.password" type="password" :label="$t('views.register.password')" :placeholder="$t('views.register.password_placeholder')" required />
          <BaseInput v-model="form.password_confirm" type="password" :label="$t('views.register.password_confirm')" :placeholder="$t('views.register.password_placeholder')" required />
        </div>

        <!-- Opravené volání s přesměrováním stavu přes událost -->
        <PasswordStrength 
          :password="form.password" 
          :confirm="form.password_confirm" 
          @validityChange="isPasswordValid = $event"
        />

        <BaseButton type="submit" variant="primary" style="margin-top: 1rem; width: 100%;" :disabled="isLoading || !isPasswordValid">
          <template #icon><UserPlusIcon :size="18" /></template>
          {{ isLoading ? $t('views.register.processing') : $t('views.register.submit') }}
        </BaseButton>

        <div class="auth-footer-link">
          {{ $t('views.register.has_account') }} <router-link to="/">{{ $t('views.register.login_here') }}</router-link>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { UserPlusIcon } from 'lucide-vue-next'
import { useI18n } from 'vue-i18n'
import { apiFetch } from '../api'
import { useToastStore } from '../stores/toast' 

import BaseInput from '../components/BaseInput.vue'
import BaseButton from '../components/BaseButton.vue'
import BaseFileUpload from '../components/BaseFileUpload.vue'
import BaseDatePicker from '../components/BaseDatePicker.vue' 
import BaseSelect from '../components/BaseSelect.vue' 
import PasswordStrength from '../components/PasswordStrength.vue'

const router = useRouter()
const toastStore = useToastStore() 
const { t } = useI18n()

const isLoading = ref(false)
const avatarFile = ref(null)
const form = ref({
  first_name: '', last_name: '', username: '', 
  email: '', birthdate: '', password: '', password_confirm: '',
  default_currency: 'CZK'
})

// Místo reaktivního odkazování na DOM element držíme jednoduchý boolean
const isPasswordValid = ref(false)

const handleRegister = async () => {
  if (!isPasswordValid.value) {
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
      toastStore.showToast(t('toast.register_success'))
      router.push('/')
    } else {
      toastStore.showToast(result.message || t('toast.register_error'), 'toast-error')
    }
  } catch (error) {
    toastStore.showToast(t('toast.communication_error'), 'toast-error')
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
  align-items: flex-start;
  justify-content: center; 
  background-color: var(--bg-app); 
  padding: 4rem 1rem; 
  transition: background-color 0.3s ease;
}

.auth-card { 
  background: var(--bg-panel); 
  padding: 3.5rem 2.5rem; 
  border-radius: var(--radius-md); 
  box-shadow: var(--shadow-floating); 
  width: 100%; 
  max-width: 700px; 
  text-align: center; 
  border: 1px solid var(--border); 
  transition: background-color 0.3s ease, border-color 0.3s ease;
  margin: auto;
}

.logo-container { display: flex; flex-direction: column; align-items: center; gap: 0.5rem; }

.logo-text { 
  font-size: 2.5rem; 
  font-weight: 800; 
  color: var(--text-main); 
  letter-spacing: -0.05em; 
  margin: 0; 
  transition: color 0.3s ease;
}

.auth-subtitle { 
  color: var(--text-muted); 
  font-size: 1.1rem; 
  margin-bottom: 2.5rem; 
  margin-top: 0.25rem; 
  transition: color 0.3s ease;
}

.auth-form { display: flex; flex-direction: column; gap: 1.5rem; text-align: left; }
.form-row { display: flex; gap: 1rem; }
.form-row > * { flex: 1; }
.avatar-upload-row { margin-bottom: 0.5rem; }

.auth-footer-link { 
  margin-top: 2rem; 
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
  .auth-wrapper { padding: 2rem 1rem; }
  .form-row { flex-direction: column; gap: 1.5rem; }
  .auth-card { padding: 2rem 1.5rem; }
}
</style>