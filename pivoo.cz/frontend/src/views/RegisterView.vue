<template>
  <BaseAuthLayout
    :title="$t('views.register.title')"
    :subtitle="$t('views.register.subtitle')"
    @submit="handleRegister"
  >
    <template #icon>
      <UserPlusIcon :size="56" color="var(--primary)" stroke-width="1.5" />
    </template>

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

    <PasswordStrength 
      :password="form.password" 
      :confirm="form.password_confirm" 
      @validityChange="isPasswordValid = $event"
    />

    <BaseButton type="submit" variant="primary" style="margin-top: 1rem; width: 100%;" :disabled="isLoading || !isPasswordValid">
      <template #icon><UserPlusIcon :size="18" /></template>
      {{ isLoading ? $t('views.register.processing') : $t('views.register.submit') }}
    </BaseButton>

    <template #footer>
      {{ $t('views.register.has_account') }} <router-link to="/">{{ $t('views.register.login_here') }}</router-link>
    </template>
  </BaseAuthLayout>
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
import BaseAuthLayout from '../components/BaseAuthLayout.vue'

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
/* Ponechali jsme pouze specifické styly formuláře pro vedle sebe ležící pole */
.form-row { display: flex; gap: 1rem; }
.form-row > * { flex: 1; }
.avatar-upload-row { margin-bottom: 0.5rem; }

@media (max-width: 600px) {
  .form-row { flex-direction: column; gap: 1.5rem; }
}
</style>