<template>
  <BaseAuthLayout
    :title="$t('app.title')"
    :subtitle="$t('app.subtitle')"
    @submit="handleLogin"
  >
    <template #icon>
      <BeerIcon :size="64" color="var(--primary)" stroke-width="1.5" />
    </template>

    <BaseInput 
      v-model="username" 
      :label="$t('views.login.username')" 
      :placeholder="$t('views.login.username_placeholder')" 
      required 
    />
    
    <BaseInput 
      v-model="password" 
      type="password" 
      :label="$t('views.login.password')" 
      :placeholder="$t('views.login.password_placeholder')" 
      required 
    />

    <BaseButton 
      type="submit" 
      variant="primary" 
      style="margin-top: 1rem; width: 100%;" 
      :disabled="isLoading"
    >
      <template #icon><LogInIcon :size="18" /></template>
      {{ isLoading ? $t('auth.logging_in') : $t('auth.enter') }}
    </BaseButton>

    <template #footer>
      {{ $t('views.login.no_account') }} <router-link to="/register">{{ $t('views.login.register_here') }}</router-link>
    </template>
  </BaseAuthLayout>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { LogInIcon, BeerIcon } from 'lucide-vue-next'
import { useI18n } from 'vue-i18n'
import { apiFetch } from '../api'
import { useAuthStore } from '../stores/auth'
import { useToastStore } from '../stores/toast' 
import BaseInput from '../components/BaseInput.vue'
import BaseButton from '../components/BaseButton.vue'
import BaseAuthLayout from '../components/BaseAuthLayout.vue'

const router = useRouter()
const authStore = useAuthStore()
const toastStore = useToastStore() 
const { t } = useI18n()

const username = ref('')
const password = ref('')
const isLoading = ref(false)

const handleLogin = async () => {
  isLoading.value = true
  
  try {
    const result = await apiFetch('/login.php', { 
      method: 'POST', 
      body: JSON.stringify({ 
        username: username.value, 
        password: password.value 
      }) 
    })
    
    if (result.status === 'success') {
      authStore.login(result.user, result.token)
      router.push('/dashboard')
    } else {
      toastStore.showToast(result.message || t('toast.login_error'), 'toast-error')
    }
  } catch (error) {
    toastStore.showToast(t('toast.server_error'), 'toast-error')
  } finally {
    isLoading.value = false
  }
}
</script>

<style scoped>
/* Všechny styly se přesunuly do BaseAuthLayout.vue, zde nepotřebujeme nic! */
</style>