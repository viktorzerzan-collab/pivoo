<template>
  <div class="magic-scanner">
    
    <div v-show="isScanningBarcode" class="barcode-scanner-container">
      <div class="scanner-header">
        <span class="scanner-title"><BarcodeIcon :size="18" /> {{ $t('modals.checkin.scanner_title') }}</span>
        <button type="button" @click="stopBarcodeScanner" class="close-scanner-btn">
          <XIcon :size="18" />
        </button>
      </div>
      <div id="barcode-reader" class="barcode-reader"></div>
    </div>

    <div class="magic-buttons" v-if="!isProcessing && !isScanningBarcode">
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
        
        <BaseButton v-if="isMobileDevice" type="button" variant="primary" @click="startBarcodeScanner" class="magic-btn barcode-btn">
          <template #icon><BarcodeIcon :size="18" /></template>
          {{ $t('modals.checkin.btn_scan_barcode') }}
        </BaseButton>

        <div class="photo-buttons-group">
          <BaseButton v-if="isMobileDevice" type="button" variant="secondary" @click="triggerCamera" class="magic-btn">
            <template #icon><CameraIcon :size="18" /></template>
            {{ $t('modals.checkin.btn_camera') }}
          </BaseButton>
          
          <BaseButton type="button" variant="secondary" @click="triggerGallery" class="magic-btn">
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
      <BarcodeIcon v-if="isProcessing && processType === 'barcode'" :size="18" class="pulse" />
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
import { CameraIcon, MicIcon, XIcon, Wand2Icon, AudioLinesIcon, ImageIcon, BarcodeIcon } from 'lucide-vue-next'
import { useI18n } from 'vue-i18n'
import BaseButton from './BaseButton.vue'
import { useMagicScanner } from '../composables/useMagicScanner'

const { t, locale } = useI18n()
const emit = defineEmits(['result'])

const {
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
} = useMagicScanner(t, locale, emit)
</script>

<style scoped>
.magic-scanner { margin-bottom: 0.5rem; margin-top: 0.5rem; display: flex; flex-direction: column; gap: 0.75rem; }
.hidden-input { display: none; }
.magic-buttons { display: flex; flex-direction: column; gap: 0.5rem; }
.magic-btn { flex: 1; justify-content: center; }

/* Skener kontejner */
.barcode-scanner-container { background: var(--bg-app); border: 1px solid var(--border); border-radius: var(--radius-md); overflow: hidden; }
.scanner-header { display: flex; justify-content: space-between; align-items: center; padding: 0.75rem; background: var(--bg-panel); border-bottom: 1px solid var(--border); }
.scanner-title { font-weight: 600; font-size: 0.95rem; color: var(--text-main); display: flex; align-items: center; gap: 0.5rem; }
.close-scanner-btn { background: none; border: none; color: var(--text-muted); cursor: pointer; display: flex; padding: 4px; border-radius: var(--radius-sm); transition: all 0.2s; }
.close-scanner-btn:hover { background: rgba(239, 68, 68, 0.1); color: var(--danger); }
.barcode-reader { width: 100%; border: none; }
.barcode-reader :deep(video) { border-radius: 0 0 var(--radius-md) var(--radius-md); object-fit: cover; }

/* Hlavní tlačítko skeneru */
.barcode-btn { background-color: var(--primary); color: white; margin-bottom: 0.25rem; }
.barcode-btn:hover { background-color: var(--primary-hover); }

/* Rozložení skupin tlačítek */
.photo-buttons-group { display: flex; gap: 0.5rem; flex: 2; width: 100%; }
.photo-buttons-group-full { flex: 1; width: 100%; }
.dictate-btn { flex: 1; width: 100%; }

.magic-message { font-size: 0.85rem; padding: 0.75rem; border-radius: var(--radius-sm); font-weight: 600; display: flex; align-items: center; gap: 0.5rem; }
.magic-message.success { background-color: rgba(16, 185, 129, 0.1); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.2); }
.magic-message.warning { background-color: rgba(245, 158, 11, 0.1); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.2); }
.magic-message.error { background-color: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.2); }

.previews-container { display: flex; flex-direction: column; gap: 0.75rem; background: var(--bg-secondary); padding: 0.75rem; border-radius: var(--radius-md); border: 1px solid var(--border-color); }
.previews-title { font-size: 0.85rem; font-weight: 600; color: var(--text-muted); margin: 0; }
.previews-grid { display: flex; flex-wrap: wrap; gap: 0.5rem; }

.preview-item { position: relative; width: 60px; height: 60px; border-radius: var(--radius-sm); overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border: 1px solid var(--border-color); }
.preview-img { width: 100%; height: 100%; object-fit: cover; }

/* Vycentrované tlačítko odstranění */
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
.remove-btn :deep(svg) { margin: 0 !important; width: 14px !important; height: 14px !important; }

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
  .photo-buttons-group { flex-direction: column; }
}
</style>
