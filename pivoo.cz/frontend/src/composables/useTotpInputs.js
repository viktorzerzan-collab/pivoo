import { ref, computed, nextTick } from 'vue'

export function useTotpInputs() {
  // Pole pro 6 samostatných číslic a pole referencí na HTML inputy
  const totpDigits = ref(['', '', '', '', '', ''])
  const totpInputRefs = ref([])

  // Sestavení výsledného stringu kódu
  const totpCode = computed(() => totpDigits.value.join(''))

  // Zpracování zápisu číslice a automatický posun doprava
  const handleTotpInput = (event, index) => {
    const val = event.target.value
    
    if (!/^\d$/.test(val)) {
      totpDigits.value[index] = ''
      return
    }
    
    if (val && index < 5) {
      totpInputRefs.value[index + 1]?.focus()
    }
  }

  // Zpracování klávesy Backspace pro návrat kurzoru doleva
  const handleTotpKeydown = (event, index) => {
    if (event.key === 'Backspace' && !totpDigits.value[index] && index > 0) {
      totpInputRefs.value[index - 1]?.focus()
    }
  }

  // Zpracování hromadného vložení kódu přes Ctrl+V / schránku
  const handleTotpPaste = (event) => {
    event.preventDefault()
    const pastedData = event.clipboardData.getData('text').replace(/\D/g, '').slice(0, 6)
    
    if (pastedData) {
      for (let i = 0; i < pastedData.length; i++) {
        totpDigits.value[i] = pastedData[i]
      }
      
      const focusIndex = Math.min(pastedData.length, 5)
      totpInputRefs.value[focusIndex]?.focus()
    }
  }

  // Vyčištění políček a vrácení kurzoru na první pozici (např. při chybě nebo inicializaci)
  const resetTotp = () => {
    totpDigits.value = ['', '', '', '', '', '']
    nextTick(() => {
      totpInputRefs.value[0]?.focus()
    })
  }

  // Pouhé vymazání hodnot (využijeme při stisku tlačítka Cancel)
  const clearTotp = () => {
    totpDigits.value = ['', '', '', '', '', '']
  }

  return {
    totpDigits,
    totpInputRefs,
    totpCode,
    handleTotpInput,
    handleTotpKeydown,
    handleTotpPaste,
    resetTotp,
    clearTotp
  }
}
