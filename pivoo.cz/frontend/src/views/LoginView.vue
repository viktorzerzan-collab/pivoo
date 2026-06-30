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
import { ref, computed, nextTick } from 'vue'
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
const require2Fa = ref(false)
const isLoading = ref(false)

// 2FA state: pole pro 6 číslic a pole referencí na input elementy
const totpDigits = ref(['', '', '', '', '', ''])
const totpInputRefs = ref([])

// Sestavení výsledného 6-místného kódu z polí
const totpCode = computed(() => totpDigits.value.join(''))

// Zpracování vkládání čísla do okénka (včetně posunu na další)
const handleTotpInput = (event, index) => {
  const val = event.target.value
  
  // Povolí pouze číslice
  if (!/^\d$/.test(val)) {
    totpDigits.value[index] = ''
    return
  }
  
  // Automatický posun na další pole, pokud nejsme na posledním
  if (val && index < 5) {
    totpInputRefs.value[index + 1]?.focus()
  }
}

// Zpracování kláves (pro mazání a krok zpět)
const handleTotpKeydown = (event, index) => {
  if (event.key === 'Backspace' && !totpDigits.value[index] && index > 0) {
    // Pokud je políčko prázdné a stiskneme Backspace, přesune kurzor na předchozí
    totpInputRefs.value[index - 1]?.focus()
  }
}

// Podpora pro vložení kódu ze schránky (Ctrl+V)
const handleTotpPaste = (event) => {
  event.preventDefault()
  const pastedData = event.clipboardData.getData('text').replace(/\D/g, '').slice(0, 6)
  
  if (pastedData) {
    for (let i = 0; i < pastedData.length; i++) {
      totpDigits.value[i] = pastedData[i]
    }
    
    // Nastavení focusu za poslední vložené číslo
    const focusIndex = Math.min(pastedData.length, 5)
    totpInputRefs.value[focusIndex]?.focus()
  }
}

// Zrušení 2FA okna a návrat zpět
const cancel2Fa = () => {
  require2Fa.value = false
  totpDigits.value = ['', '', '', '', '', '']
}

const handleLogin = async () => {
  isLoading.value = true
  
  try {
    const result = await apiFetch('/login.php', { 
      method: 'POST', 
      body: JSON.stringify({ 
        username: username.value, 
        password: password.value,
        totp_code: totpCode.value // Používáme computed vlastnost složenou z 6 okének
      }) 
    })
    
    if (result.status === 'success') {
      if (result.require_2fa) {
        // Backend potvrdil správné heslo, ale vyžaduje 2FA kód
        require2Fa.value = true
        totpDigits.value = ['', '', '', '', '', '']
        
        // Pomocí nextTick počkáme na vykreslení 6 okének a zaměříme první okénko
        nextTick(() => {
          totpInputRefs.value[0]?.focus()
        })
      } else {
        // Přihlášení kompletně dokončeno
        authStore.login(result.user, result.token)
        router.push('/dashboard')
      }
    } else {
      toastStore.showToast(result.message || t('toast.login_error'), 'toast-error')
      
      // V případě nesprávného 2FA kódu vymažeme políčka a hodíme focus zpět na první
      if (require2Fa.value) {
        totpDigits.value = ['', '', '', '', '', '']
        nextTick(() => {
          totpInputRefs.value[0]?.focus()
        })
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
  aspect-ratio: 1; /* Udržuje políčka perfektně čtvercová */
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

/* Ošetření pro odstranění šipek (spin buttonů) u inputů, i když používáme type="text", je to dobrá pojistka pro mobily */
.totp-input::-webkit-outer-spin-button,
.totp-input::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}
</style>
