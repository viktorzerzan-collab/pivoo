import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { apiFetch } from '../api'
import { useToastStore } from '../stores/toast' 

export function useRegister() {
  const router = useRouter()
  const toastStore = useToastStore() 
  const { t } = useI18n()

  const isLoading = ref(false)
  const avatarFile = ref(null)
  const isPasswordValid = ref(false)
  
  const form = ref({
    first_name: '', 
    last_name: '', 
    username: '', 
    email: '', 
    birthdate: '', 
    password: '', 
    password_confirm: '',
    default_currency: 'CZK'
  })

  const handleRegister = async () => {
    // Bezpečnostní pojistka, kdyby uživatel obešel disabled stav tlačítka
    if (!isPasswordValid.value) {
      return
    }
    
    isLoading.value = true
    
    // Příprava dat pro odeslání (včetně souboru)
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

  // Vrátíme vše, co template (Vue komponenta) potřebuje pro vykreslení a interakci
  return {
    isLoading,
    avatarFile,
    form,
    isPasswordValid,
    handleRegister
  }
}
