<template>
  <BaseAuthLayout
    :title="$t('app.title')"
    :subtitle="$t('app.subtitle')"
    @submit="handleLogin"
  >
    <template #icon>
      <BeerIcon :size="64" color="var(--primary)" stroke-width="1.5" />
    </template>

    <template v-if="!require2Fa">
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
    </template>

    <template v-else>
      <BaseInput 
        v-model="totpCode" 
        type="text"
        inputmode="numeric"
        pattern="[0-9]*"
        maxlength="6"
        :label="$t('views.login.totp_code')" 
        :placeholder="$t('views.login.totp_placeholder')" 
        required 
      />
    </template>

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
      <div v-if="require2Fa" style="margin-top: 0.5rem; text-align: center;">
        <a href="#" @click.prevent="require2Fa = false; totpCode = ''">{{ $t('buttons.cancel') }}</a>
      </div>
      <template v-else>
        {{ $t('views.login.no_account') }} <router-link to="/register">{{ $t('views.login.register_here') }}</router-link>
      </template>
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
const totpCode = ref('')
const require2Fa = ref(false)
const isLoading = ref(false)

const handleLogin = async () => {
  isLoading.value = true
  
  try {
    const result = await apiFetch('/login.php', { 
      method: 'POST', 
      body: JSON.stringify({ 
        username: username.value, 
        password: password.value,
        totp_code: totpCode.value // Odesílá se v obou krocích (v prvním je prázdný)
      }) 
    })
    
    if (result.status === 'success') {
      if (result.require_2fa) {
        // Backend potvrdil správné heslo, ale vyžaduje 2FA kód
        require2Fa.value = true
        totpCode.value = ''
      } else {
        // Přihlášení kompletně dokončeno (uživatel bud nemá 2FA, nebo kód úspěšně ověřen)
        authStore.login(result.user, result.token)
        router.push('/dashboard')
      }
    } else {
      toastStore.showToast(result.message || t('toast.login_error'), 'toast-error')
    }
  } catch (error) {
    toastStore.showToast(error.message || t('toast.server_error'), 'toast-error')
  } finally {
    isLoading.value = false
  }
}
</script>

<style scoped>
/* Všechny styly se přesunuly do BaseAuthLayout.vue, zde nepotřebujeme nic! */
</style>