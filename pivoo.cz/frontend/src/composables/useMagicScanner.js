import { ref, onMounted, onBeforeUnmount } from 'vue'
import { Html5Qrcode } from 'html5-qrcode'
import { apiFetch } from '../api'

export function useMagicScanner(t, locale, emit) {
  const cameraInput = ref(null)
  const galleryInput = ref(null)
  const selectedFiles = ref([])
  const previews = ref([])

  const isProcessing = ref(false)
  const processType = ref('') 
  const isListening = ref(false)
  const magicMessage = ref('')
  const magicMessageType = ref('')

  const isMobileDevice = ref(false)
  const isScanningBarcode = ref(false)
  let html5QrCode = null

  let recognitionInstance = null
  let accumulatedTranscript = ''

  onMounted(() => {
    isMobileDevice.value = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)
  })

  onBeforeUnmount(() => {
    previews.value.forEach(url => URL.revokeObjectURL(url))
    if (recognitionInstance) recognitionInstance.stop()
    if (html5QrCode && html5QrCode.isScanning) {
      html5QrCode.stop().then(() => html5QrCode.clear()).catch(() => {})
    }
  })

  // === LOGIKA SKENERU ČÁROVÝCH KÓDŮ ===

  const startBarcodeScanner = () => {
    isScanningBarcode.value = true
    magicMessage.value = ''
    
    setTimeout(() => {
      html5QrCode = new Html5Qrcode("barcode-reader")
      html5QrCode.start(
        { facingMode: "environment" },
        { 
          fps: 10, 
          qrbox: { width: 250, height: 150 },
          formatsToSupport: [ 0, 1 ] 
        },
        async (decodedText) => {
          await handleBarcodeScanned(decodedText)
        },
        (errorMessage) => {
          // Ignorujeme běžné chyby čtení na pozadí
        }
      ).catch((err) => {
        magicMessageType.value = 'error'
        magicMessage.value = t('modals.checkin.msg_barcode_camera_error')
        stopBarcodeScanner()
      })
    }, 100)
  }

  const stopBarcodeScanner = () => {
    if (html5QrCode) {
      html5QrCode.stop().then(() => {
        html5QrCode.clear()
        html5QrCode = null
      }).catch(err => console.log("Chyba při zastavování skeneru", err))
    }
    isScanningBarcode.value = false
  }

  const handleBarcodeScanned = async (ean) => {
    stopBarcodeScanner()
    isProcessing.value = true
    processType.value = 'barcode'
    magicMessageType.value = 'warning'
    magicMessage.value = t('modals.checkin.btn_processing')

    try {
      const res = await apiFetch(`/lookup_barcode.php?ean=${ean}`)
      
      if (res.status === 'success') {
        emit('result', res)
        magicMessage.value = ''
      } else if (res.status === 'not_found') {
        magicMessageType.value = 'warning'
        magicMessage.value = t('modals.checkin.msg_barcode_not_found')
      } else {
        magicMessageType.value = 'error'
        magicMessage.value = res.message || t('modals.checkin.msg_ai_failed')
      }
    } catch (e) {
      magicMessageType.value = 'error'
      magicMessage.value = t('modals.checkin.msg_ai_error') + e.message
    } finally {
      isProcessing.value = false
    }
  }

  // === LOGIKA FOTEK A HLASU ===

  const triggerCamera = () => {
    if (cameraInput.value) cameraInput.value.click()
  }

  const triggerGallery = () => {
    if (galleryInput.value) galleryInput.value.click()
  }

  const handlePhotoSelect = (event) => {
    const files = Array.from(event.target.files)
    if (!files.length) return

    for (const file of files) {
      if (selectedFiles.value.length < 5) {
        selectedFiles.value.push(file)
        previews.value.push(URL.createObjectURL(file))
      } else {
        magicMessageType.value = 'warning'
        magicMessage.value = t('modals.checkin.msg_max_photos')
        break
      }
    }
    
    if (cameraInput.value) cameraInput.value.value = ''
    if (galleryInput.value) galleryInput.value.value = ''
  }

  const removePhoto = (index) => {
    URL.revokeObjectURL(previews.value[index])
    previews.value.splice(index, 1)
    selectedFiles.value.splice(index, 1)
  }

  const processImages = async () => {
    if (selectedFiles.value.length === 0) return
    isProcessing.value = true
    processType.value = 'camera'
    magicMessageType.value = 'warning'
    magicMessage.value = t('modals.checkin.msg_ai_analyzing')

    const formData = new FormData()
    selectedFiles.value.forEach((file) => formData.append('images[]', file))

    try {
      const res = await apiFetch('/analyze_beer.php', {
        method: 'POST',
        body: formData,
        timeout: 45000 
      })
      emit('result', res)
      if (res.status === 'success') clearScanner()
      else {
        magicMessageType.value = 'error'
        magicMessage.value = res.message || t('modals.checkin.msg_ai_failed')
      }
    } catch (e) {
      magicMessageType.value = 'error'
      magicMessage.value = t('modals.checkin.msg_ai_error') + e.message
    } finally {
      isProcessing.value = false
    }
  }

  const toggleVoiceRecognition = () => {
    if (isListening.value) {
      if (recognitionInstance) recognitionInstance.stop()
    } else {
      startVoiceRecognition()
    }
  }

  const startVoiceRecognition = () => {
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition
    if (!SpeechRecognition) {
      magicMessageType.value = 'error'
      magicMessage.value = t('modals.checkin.msg_unsupported_mic')
      return
    }
    
    recognitionInstance = new SpeechRecognition()
    const langMap = { 'cs': 'cs-CZ', 'en': 'en-US', 'de': 'de-DE', 'pl': 'pl-PL' }
    recognitionInstance.lang = langMap[locale.value] || 'cs-CZ'
    
    recognitionInstance.continuous = true
    recognitionInstance.interimResults = false

    accumulatedTranscript = ''

    recognitionInstance.onstart = () => {
      isListening.value = true
      magicMessage.value = t('modals.checkin.msg_speak')
      magicMessageType.value = 'success'
    }

    recognitionInstance.onresult = (event) => {
      for (let i = event.resultIndex; i < event.results.length; ++i) {
        if (event.results[i].isFinal) {
          accumulatedTranscript += event.results[i][0].transcript + ' '
        }
      }
    }

    recognitionInstance.onend = async () => {
      isListening.value = false
      if (accumulatedTranscript.trim()) {
        await processVoiceTranscript(accumulatedTranscript.trim())
      } else if (!isProcessing.value) {
        magicMessage.value = ''
      }
    }

    recognitionInstance.onerror = (event) => {
      isListening.value = false
      magicMessageType.value = 'error'
      magicMessage.value = t('modals.checkin.msg_mic_error') + event.error
    }

    recognitionInstance.start()
  }

  const processVoiceTranscript = async (text) => {
     isProcessing.value = true
     processType.value = 'voice'
     magicMessage.value = t('modals.checkin.btn_processing')
     magicMessageType.value = 'warning'

     try {
       const res = await apiFetch('/analyze_voice.php', {
         method: 'POST',
         body: JSON.stringify({ text }),
         timeout: 30000
       })
       emit('result', res)
       if (res.status === 'success') clearScanner()
       else {
         magicMessageType.value = 'error'
         magicMessage.value = res.message || t('modals.checkin.msg_ai_failed')
       }
     } catch (e) {
       magicMessageType.value = 'error'
       magicMessage.value = t('modals.checkin.msg_ai_error') + e.message
     } finally {
       isProcessing.value = false
     }
  }

  const clearScanner = () => {
    previews.value.forEach(url => URL.revokeObjectURL(url))
    previews.value = []
    selectedFiles.value = []
    magicMessage.value = ''
  }

  return {
    cameraInput,
    galleryInput,
    selectedFiles,
    previews,
    isProcessing,
    processType,
    isListening,
    magicMessage,
    magicMessageType,
    isMobileDevice,
    isScanningBarcode,
    startBarcodeScanner,
    stopBarcodeScanner,
    triggerCamera,
    triggerGallery,
    handlePhotoSelect,
    removePhoto,
    processImages,
    toggleVoiceRecognition
  }
}
