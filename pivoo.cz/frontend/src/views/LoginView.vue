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
      <div class="totp-container">
        <label class="base-label">{{ $t('views.login.totp_code') }}</label>
        <div class="totp-inputs">
          <input
            v-for="(digit, index) in 6"
            :key="index"
            :ref="el => totpInputRefs[index] = el"
            v-model="totpDigits[index]"
            type="text"
            inputmode="numeric"
            pattern="[0-9]*"
            maxlength="1"
            class="totp-input"
            @input="handleTotpInput($event, index)"
            @keydown="handleTotpKeydown($event, index)"
            @paste="handleTotpPaste($event)"
            required
          />
        </div>
      </div>
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
        <a href="#" @click.prevent="cancel2Fa">{{ $t('buttons.cancel') }}</a>
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
import { useTotpInputs } from '../composables/useTotpInputs'

const router = useRouter()
const authStore = useAuthStore()
const toastStore = useToastStore() 
const { t } = useI18n()

const username = ref('')
const password = ref('')
const require2Fa = ref(false)
const isLoading = ref(false)

// Vytažení stavů a metod z našeho nového composable
const {
  totpDigits,
  totpInputRefs,
  totpCode,
  handleTotpInput,
  handleTotpKeydown,
  handleTotpPaste,
  resetTotp,
  clearTotp
} = useTotpInputs()

const cancel2Fa = () => {
  require2Fa.value = false
  clearTotp()
}

const handleLogin = async () => {
  isLoading.value = true
  
  try {
    const result = await apiFetch('/login.php', { 
      method: 'POST', 
      body: JSON.stringify({ 
        username: username.value, 
        password: password.value,
        totp_code: totpCode.value // Používáme computed string z composable
      }) 
    })
    
    if (result.status === 'success') {
      if (result.require_2fa) {
        require2Fa.value = true
        resetTotp() // Automaticky vyčistí a nastaví focus na první okénko
      } else {
        authStore.login(result.user, result.token)
        router.push('/dashboard')
      }
    } else {
      toastStore.showToast(result.message || t('toast.login_error'), 'toast-error')
      
      if (require2Fa.value) {
        resetTotp() // V případě chyby (např. špatný kód) vyčistí pole a vrátí focus na začátek
      }
    }
  } catch (error) {
    toastStore.showToast(error.message || t('toast.server_error'), 'toast-error')
  } finally {
    isLoading.value = false
  }
}
</script>

<style scoped>
.totp-container {
  display: flex;
  flex-direction: column;
  gap: 0.35rem; 
  width: 100%;
  text-align: left;
}

.base-label {
  font-size: 0.9rem;
  font-weight: 600;
  color: var(--text-main);
  transition: color 0.5s ease;
}

.totp-inputs {
  display: flex;
  justify-content: space-between;
  gap: 0.5rem;
  width: 100%;
}

.totp-input {
  width: 100%;
  aspect-ratio: 1;
  padding: 0;
  text-align: center;
  font-size: 1.25rem;
  font-weight: 600;
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  background-color: transparent;
  backdrop-filter: blur(3px);
  -webkit-backdrop-filter: blur(3px);
  color: var(--text-main);
  font-family: inherit;
  transition: all 0.3s ease;
  outline: none;
  box-shadow: none;
}

.totp-input:focus {
  border-color: var(--primary);
  box-shadow: 0 0 0 3px rgba(250, 204, 21, 0.15);
  background-color: rgba(255, 255, 255, 0.05);
}

.totp-input::-webkit-outer-spin-button,
.totp-input::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}
</style>
