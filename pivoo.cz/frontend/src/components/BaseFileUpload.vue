<template>
  <div class="file-upload-wrapper">
    <label v-if="label" class="upload-label">{{ label }}</label>
    
    <div class="upload-container" @click="triggerFileInput" :class="{ 'has-preview': previewUrl }">
      <div v-if="previewUrl" class="image-preview">
        <img :src="previewUrl" alt="Náhled" />
        <div class="change-overlay">Změnit obrázek</div>
      </div>
      
      <div v-else class="upload-placeholder">
        <UploadIcon :size="24" class="upload-icon" />
        <span class="file-name">{{ fileName || placeholder }}</span>
      </div>

      <input 
        type="file" 
        ref="fileInput" 
        class="hidden-input" 
        :accept="accept"
        @change="handleFileChange"
      />
    </div>
    
    <small v-if="error" class="error-text">{{ error }}</small>
  </div>
</template>

<script setup>
import { ref, onUnmounted } from 'vue'
import { UploadIcon } from 'lucide-vue-next'

const props = defineProps({
  label: String,
  placeholder: { type: String, default: 'Vybrat soubor...' },
  accept: { type: String, default: 'image/*' },
  maxSizeMB: { type: Number, default: 5 }
})

const emit = defineEmits(['update:file'])
const fileInput = ref(null)
const fileName = ref('')
const error = ref('')
const previewUrl = ref(null)

const triggerFileInput = () => {
  fileInput.value.click()
}

const handleFileChange = (event) => {
  const file = event.target.files[0]
  error.value = ''
  
  if (file) {
    if (file.size > props.maxSizeMB * 1024 * 1024) {
      error.value = `Soubor je příliš velký. Maximum je ${props.maxSizeMB} MB.`
      fileName.value = ''
      previewUrl.value = null
      emit('update:file', null)
      return
    }

    fileName.value = file.name
    // Vytvoření dočasné URL pro náhled v prohlížeči
    if (previewUrl.value) URL.revokeObjectURL(previewUrl.value) // Vyčištění staré URL
    previewUrl.value = URL.createObjectURL(file)
    
    emit('update:file', file)
  }
}

// Důležité: uvolnění paměti při zničení komponenty
onUnmounted(() => {
  if (previewUrl.value) URL.revokeObjectURL(previewUrl.value)
})
</script>

<style scoped>
.file-upload-wrapper { display: flex; flex-direction: column; gap: 0.4rem; width: 100%; }
.upload-label { font-weight: 600; color: #334155; font-size: 0.9rem; }

.upload-container {
  position: relative;
  display: flex; align-items: center; justify-content: center;
  min-height: 80px;
  border-radius: 12px; border: 2px dashed var(--border); 
  background-color: var(--bg-app);
  cursor: pointer; transition: all 0.2s ease;
  overflow: hidden;
}

.upload-container:hover { border-color: var(--primary); background-color: #fefce8; }

.upload-placeholder {
  display: flex; flex-direction: column; align-items: center; gap: 0.5rem; padding: 1.5rem;
}

.upload-icon { color: #94a3b8; }
.file-name { color: #64748b; font-size: 0.9rem; }

.image-preview { width: 100%; height: 150px; position: relative; }
.image-preview img { width: 100%; height: 100%; object-fit: cover; }

.change-overlay {
  position: absolute; inset: 0; background: rgba(0,0,0,0.4);
  color: white; display: flex; align-items: center; justify-content: center;
  font-weight: 600; opacity: 0; transition: opacity 0.2s;
}
.image-preview:hover .change-overlay { opacity: 1; }

.hidden-input { display: none; }
.error-text { color: #ef4444; font-size: 0.8rem; font-weight: 600; margin-top: 0.2rem; }
</style>