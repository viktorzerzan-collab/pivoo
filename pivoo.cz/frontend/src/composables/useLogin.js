import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { apiFetch } from '../api'
import { useAuthStore } from '../stores/auth'
import { useToastStore } from '../stores/toast'
import { useTotpInputs } from './useTotpInputs'

export function useLogin() {
  const router = useRouter()
  const authStore = useAuthStore()
  const toastStore = useToastStore()
  const { t } = useI18n()

  const username = ref('')
  const password = ref('')
  const require2Fa = ref(false)
  const isLoading = ref(false)

  // Využití existujícího composable pro 2FA
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

  return {
    username,
    password,
    require2Fa,
    isLoading,
    totpDigits,
    totpInputRefs,
    handleTotpInput,
    handleTotpKeydown,
    handleTotpPaste,
    cancel2Fa,
    handleLogin
  }
}
