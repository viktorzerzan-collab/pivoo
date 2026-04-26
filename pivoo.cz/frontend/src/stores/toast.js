import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useToastStore = defineStore('toast', () => {
  const show = ref(false)
  const message = ref('')
  const type = ref('toast-success')
  let timeoutId = null

  const showToast = (msg, toastType = 'toast-success') => {
    message.value = msg
    type.value = toastType
    show.value = true

    // Pokud už nějaký odpočet běží, zrušíme ho, aby nová zpráva nezmizela předčasně
    if (timeoutId) {
      clearTimeout(timeoutId)
    }

    // Automatické skrytí po 3 vteřinách
    timeoutId = setTimeout(() => {
      show.value = false
    }, 3000)
  }

  return { show, message, type, showToast }
})