<template>
  <div class="magic-scanner">
    <div class="magic-buttons" v-if="!isProcessing">
      <input 
        type="file" 
        accept="image/*" 
        multiple 
        ref="photoInput" 
        class="hidden-input" 
        @change="handlePhotoSelect" 
      />
      
      <BaseButton type="button" variant="add" @click="triggerPhotoInput" class="magic-btn photo-btn" :disabled="selectedFiles.length >= 5">
        <template #icon><CameraIcon :size="18" /></template>
        {{ selectedFiles.length > 0 ? $t('modals.checkin.btn_add_more_photos') : $t('modals.checkin.btn_photo') }}
      </BaseButton>
      
      <BaseButton v-if="selectedFiles.length === 0" type="button" variant="add" @click="startVoiceRecognition" class="magic-btn voice-btn" :class="{'is-listening': isListening}">
        <template #icon><MicIcon :size="18" :class="{'pulse': isListening}"/></template>
        {{ isListening ? $t('modals.checkin.btn_listening') : $t('modals.checkin.btn_dictate') }}
      </BaseButton>
    </div>

    <div v-if="magicMessage" class="magic-message" :class="magicMessageType">
      <CameraIcon v-if="isProcessing && processType === 'camera'" :size="18" class="spinning" />
      <span>{{ magicMessage }}</span>
    </div>

    <div v-if="previews.length > 0 && !isProcessing" class="previews-container">
      <p class="previews-title">Vybráno fotek: {{ previews.length }} z 5</p>
      <div class="previews-grid">
        <div v-for="(preview, index) in previews" :key="index" class="preview-item">
          <img :src="preview" class="preview-img" alt="Náhled piva" />
          <button type="button" class="remove-btn" @click.stop="removePhoto(index)">
            <XIcon :size="14" />
          </button>
        </div>
      </div>
      
      <BaseButton type="button" variant="primary" @click="processImages" class="analyze-btn">
        <template #icon><Wand2Icon :size="18" /></template>
        Analyzovat fotky
      </BaseButton>
    </div>
  </div>
</template>

<script setup>
import { ref, onBeforeUnmount } from 'vue'
import { CameraIcon, MicIcon, XIcon, Wand2Icon } from 'lucide-vue-next'
import { useI18n } from 'vue-i18n'
import BaseButton from './BaseButton.vue'
import { apiFetch } from '../api'

const { t, locale } = useI18n()
const emit = defineEmits(['result'])

const photoInput = ref(null)
const selectedFiles = ref([])
const previews = ref([])

const isProcessing = ref(false)
const processType = ref('') // 'camera' nebo 'voice'
const isListening = ref(false)
const magicMessage = ref('')
const magicMessageType = ref('')

// Vyčištění blob URL adres při zničení komponenty pro uvolnění paměti
onBeforeUnmount(() => {
  previews.value.forEach(url => URL.revokeObjectURL(url))
})

const triggerPhotoInput = () => {
  if (photoInput.value) {
    photoInput.value.click()
  }
}

const handlePhotoSelect = (event) => {
  const files = Array.from(event.target.files)
  if (!files.length) return

  let added = 0
  for (const file of files) {
    if (selectedFiles.value.length < 5) {
      selectedFiles.value.push(file)
      previews.value.push(URL.createObjectURL(file))
      added++
    } else {
      magicMessageType.value = 'warning'
      magicMessage.value = 'Můžete nahrát maximálně 5 fotek.'
      break
    }
  }

  // Reset inputu, aby šla stejná fotka vybrat znovu, pokud ji smaže
  if (photoInput.value) photoInput.value.value = ''
}

const removePhoto = (index) => {
  URL.revokeObjectURL(previews.value[index]) // Uvolnění paměti
  previews.value.splice(index, 1)
  selectedFiles.value.splice(index, 1)
}

// Odeslání všech vybraných fotek na backend
const processImages = async () => {
  if (selectedFiles.value.length === 0) return

  isProcessing.value = true
  processType.value = 'camera'
  magicMessageType.value = 'warning'
  magicMessage.value = 'Umělá inteligence zkoumá tvé fotky...'

  const formData = new FormData()
  selectedFiles.value.forEach((file) => {
    formData.append('images[]', file) // Backend bude očekávat pole obrázků
  })

  try {
    const res = await apiFetch('/analyze_beer.php', {
      method: 'POST',
      body: formData,
      timeout: 45000 // Delší timeout, více obrázků může trvat déle
    })
    
    emit('result', res) // Předáme výsledek dál do CheckInModal
    
    if (res.status === 'success') {
      clearScanner()
    } else {
      magicMessageType.value = 'error'
      magicMessage.value = res.message || 'Nepodařilo se rozpoznat data.'
    }
  } catch (e) {
    magicMessageType.value = 'error'
    magicMessage.value = 'Chyba AI: ' + e.message
  } finally {
    isProcessing.value = false
  }
}

// Hlasové ovládání (přesunuto z CheckInModal)
const startVoiceRecognition = () => {
  const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition
  if (!SpeechRecognition) {
    magicMessageType.value = 'error'
    magicMessage.value = t('modals.checkin.msg_unsupported_mic')
    return
  }
  
  const recognition = new SpeechRecognition()
  const langMap = { 'cs': 'cs-CZ', 'en': 'en-US', 'de': 'de-DE', 'pl': 'pl-PL' }
  recognition.lang = langMap[locale.value] || 'cs-CZ'
  recognition.interimResults = false
  recognition.maxAlternatives = 1

  recognition.onstart = () => {
    isListening.value = true
    magicMessage.value = t('modals.checkin.msg_speak')
    magicMessageType.value = 'success'
  }

  recognition.onresult = async (event) => {
    isListening.value = false
    const transcript = event.results[0][0].transcript
    await processVoiceTranscript(transcript)
  }

  recognition.onerror = (event) => {
    isListening.value = false
    magicMessageType.value = 'error'
    magicMessage.value = t('modals.checkin.msg_mic_error') + event.error
  }

  recognition.start()
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
     
     if (res.status === 'success') {
       clearScanner()
     } else {
       magicMessageType.value = 'error'
       magicMessage.value = res.message || 'Nepodařilo se rozpoznat data.'
     }
   } catch (e) {
     magicMessageType.value = 'error'
     magicMessage.value = 'Chyba AI: ' + e.message
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
</script>

<style scoped>
.magic-scanner { margin-bottom: 0.5rem; margin-top: 0.5rem; display: flex; flex-direction: column; gap: 0.75rem; }
.hidden-input { display: none; }
.magic-buttons { display: flex; gap: 0.5rem; }
.magic-btn { flex: 1; justify-content: center; }

.photo-btn { background-color: var(--primary); }
.voice-btn { background-color: #8b5cf6; }
.voice-btn:hover { background-color: #7c3aed; }
.voice-btn.is-listening { background-color: #ef4444; }

.magic-message { font-size: 0.85rem; padding: 0.75rem; border-radius: var(--radius-sm); font-weight: 600; display: flex; align-items: center; gap: 0.5rem; }
.magic-message.success { background-color: rgba(16, 185, 129, 0.1); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.2); }
.magic-message.warning { background-color: rgba(245, 158, 11, 0.1); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.2); }
.magic-message.error { background-color: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.2); }

.previews-container { display: flex; flex-direction: column; gap: 0.75rem; background: var(--bg-secondary); padding: 0.75rem; border-radius: var(--radius-md); border: 1px solid var(--border-color); }
.previews-title { font-size: 0.85rem; font-weight: 600; color: var(--text-muted); margin: 0; }
.previews-grid { display: flex; flex-wrap: wrap; gap: 0.5rem; }

.preview-item { position: relative; width: 60px; height: 60px; border-radius: var(--radius-sm); overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border: 1px solid var(--border-color); }
.preview-img { width: 100%; height: 100%; object-fit: cover; }
.remove-btn { position: absolute; top: 2px; right: 2px; background: rgba(239, 68, 68, 0.9); color: white; border: none; border-radius: 50%; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; cursor: pointer; padding: 0; }
.remove-btn:hover { background: red; }

.analyze-btn { width: 100%; justify-content: center; }

.spinning { animation: spin 1.5s linear infinite; }
@keyframes spin { 100% { transform: rotate(360deg); } }

.pulse { animation: pulse 1.5s infinite; }
@keyframes pulse {
  0% { transform: scale(1); }
  50% { transform: scale(1.2); }
  100% { transform: scale(1); }
}

@media (max-width: 600px) { 
  .magic-buttons { flex-direction: column; }
}
</style>