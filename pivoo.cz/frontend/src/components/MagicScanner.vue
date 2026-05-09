<template>
  <div class="magic-scanner">
    <div class="magic-buttons" v-if="!isProcessing">
      <input 
        type="file" 
        accept="image/*" 
        capture="environment"
        ref="cameraInput" 
        class="hidden-input" 
        @change="handlePhotoSelect" 
      />
      <input 
        type="file" 
        accept="image/*" 
        multiple
        ref="galleryInput" 
        class="hidden-input" 
        @change="handlePhotoSelect" 
      />
      
      <template v-if="selectedFiles.length === 0">
        <div class="photo-buttons-group">
          <BaseButton v-if="isMobileDevice" type="button" variant="primary" @click="triggerCamera" class="magic-btn">
            <template #icon><CameraIcon :size="18" /></template>
            {{ $t('modals.checkin.btn_camera') }}
          </BaseButton>
          
          <BaseButton type="button" variant="primary" @click="triggerGallery" class="magic-btn">
            <template #icon><ImageIcon :size="18" /></template>
            {{ $t('modals.checkin.btn_gallery') }}
          </BaseButton>
        </div>
        
        <BaseButton 
          type="button" 
          :variant="isListening ? 'danger' : 'secondary'" 
          @click="toggleVoiceRecognition" 
          class="magic-btn dictate-btn"
        >
          <template #icon>
            <AudioLinesIcon v-if="isListening" :size="18" class="pulse" />
            <MicIcon v-else :size="18" />
          </template>
          {{ isListening ? $t('modals.checkin.btn_stop_listening') : $t('modals.checkin.btn_dictate') }}
        </BaseButton>
      </template>

      <template v-else>
        <div class="photo-buttons-group photo-buttons-group-full">
          <BaseButton v-if="isMobileDevice" type="button" variant="secondary" @click="triggerCamera" class="magic-btn" :disabled="selectedFiles.length >= 5">
            <template #icon><CameraIcon :size="18" /></template>
            {{ $t('modals.checkin.btn_camera_more') }}
          </BaseButton>
          
          <BaseButton type="button" variant="secondary" @click="triggerGallery" class="magic-btn" :disabled="selectedFiles.length >= 5">
            <template #icon><ImageIcon :size="18" /></template>
            {{ $t('modals.checkin.btn_gallery_more') }}
          </BaseButton>
        </div>
      </template>
    </div>

    <div v-if="magicMessage" class="magic-message" :class="magicMessageType">
      <CameraIcon v-if="isProcessing && processType === 'camera'" :size="18" class="spinning" />
      <MicIcon v-if="isProcessing && processType === 'voice'" :size="18" class="pulse" />
      <span>{{ magicMessage }}</span>
    </div>

    <div v-if="previews.length > 0 && !isProcessing" class="previews-container">
      <p class="previews-title">{{ $t('modals.checkin.selected_photos', { count: previews.length }) }}</p>
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
        {{ $t('modals.checkin.btn_analyze_photos') }}
      </BaseButton>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { CameraIcon, MicIcon, XIcon, Wand2Icon, AudioLinesIcon, ImageIcon } from 'lucide-vue-next'
import { useI18n } from 'vue-i18n'
import BaseButton from './BaseButton.vue'
import { apiFetch } from '../api'

const { t, locale } = useI18n()
const emit = defineEmits(['result'])

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

let recognitionInstance = null
let accumulatedTranscript = ''

onMounted(() => {
  // Jednoduchá a efektivní detekce mobilních zařízení pomocí User Agentu
  isMobileDevice.value = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)
})

onBeforeUnmount(() => {
  previews.value.forEach(url => URL.revokeObjectURL(url))
  if (recognitionInstance) recognitionInstance.stop()
})

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
</script>

<style scoped>
.magic-scanner { margin-bottom: 0.5rem; margin-top: 0.5rem; display: flex; flex-direction: column; gap: 0.75rem; }
.hidden-input { display: none; }
.magic-buttons { display: flex; gap: 0.5rem; }
.magic-btn { flex: 1; justify-content: center; }

/* Nové rozložení skupin tlačítek */
.photo-buttons-group { display: flex; gap: 0.5rem; flex: 2; }
.photo-buttons-group-full { flex: 1; width: 100%; }
.dictate-btn { flex: 1; }

.magic-message { font-size: 0.85rem; padding: 0.75rem; border-radius: var(--radius-sm); font-weight: 600; display: flex; align-items: center; gap: 0.5rem; }
.magic-message.success { background-color: rgba(16, 185, 129, 0.1); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.2); }
.magic-message.warning { background-color: rgba(245, 158, 11, 0.1); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.2); }
.magic-message.error { background-color: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.2); }

.previews-container { display: flex; flex-direction: column; gap: 0.75rem; background: var(--bg-secondary); padding: 0.75rem; border-radius: var(--radius-md); border: 1px solid var(--border-color); }
.previews-title { font-size: 0.85rem; font-weight: 600; color: var(--text-muted); margin: 0; }
.previews-grid { display: flex; flex-wrap: wrap; gap: 0.5rem; }

.preview-item { position: relative; width: 60px; height: 60px; border-radius: var(--radius-sm); overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border: 1px solid var(--border-color); }
.preview-img { width: 100%; height: 100%; object-fit: cover; }

/* Vycentrované tlačítko odstranění s použitím flexboxu */
.remove-btn { 
  position: absolute; 
  top: 4px; 
  right: 4px; 
  background: rgba(239, 68, 68, 0.9); 
  color: white; 
  border: none; 
  border-radius: 50%; 
  width: 22px; 
  height: 22px; 
  display: flex; 
  align-items: center; 
  justify-content: center; 
  cursor: pointer; 
  padding: 0; 
  margin: 0;
  box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}
.remove-btn:hover { background: #ef4444; }

/* OCHRANA PŘED GLOBÁLNÍM STYLEM Z APP.VUE */
.remove-btn :deep(svg) { 
  margin: 0 !important; 
  width: 14px !important; 
  height: 14px !important; 
}

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
  .photo-buttons-group { flex-direction: column; flex: auto; }
}
</style>