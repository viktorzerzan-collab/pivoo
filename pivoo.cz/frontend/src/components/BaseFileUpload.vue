<template>
  <div class="file-upload-wrapper">
    <label v-if="label" class="upload-label">{{ label }}</label>
    
    <div class="upload-container" @click="triggerFileInput" :class="{ 'has-preview': previewUrl, 'is-compressing': isCompressing }">
      <div v-if="isCompressing" class="compressing-overlay">
        <span class="spinner"></span> Komprimuji obrázek...
      </div>
      
      <div v-else-if="previewUrl" class="image-preview">
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
        :disabled="isCompressing"
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
const isCompressing = ref(false)

const triggerFileInput = () => {
  if (!isCompressing.value) {
    fileInput.value.click()
  }
}

// Funkce pro kompresi obrázku na straně klienta
const compressImage = (file) => {
  return new Promise((resolve, reject) => {
    const reader = new FileReader()
    reader.readAsDataURL(file)
    reader.onload = event => {
      const img = new Image()
      img.src = event.target.result
      img.onload = () => {
        const canvas = document.createElement('canvas')
        let width = img.width
        let height = img.height
        const max_size = 1000 // Maximální šířka nebo výška bude 1000px

        if (width > height) {
          if (width > max_size) {
            height *= max_size / width
            width = max_size
          }
        } else {
          if (height > max_size) {
            width *= max_size / height
            height = max_size
          }
        }

        canvas.width = width
        canvas.height = height
        const ctx = canvas.getContext('2d')
        ctx.drawImage(img, 0, 0, width, height)

        // Převod na WebP s 80% kvalitou
        canvas.toBlob((blob) => {
          if (!blob) {
            reject(new Error('Chyba při kompresi.'))
            return
          }
          // Vytvoření nového File objektu
          const newFileName = file.name.replace(/\.[^/.]+$/, "") + ".webp"
          const newFile = new File([blob], newFileName, {
            type: 'image/webp',
            lastModified: Date.now()
          })
          resolve(newFile)
        }, 'image/webp', 0.8)
      }
      img.onerror = (e) => reject(e)
    }
    reader.onerror = (e) => reject(e)
  })
}

const handleFileChange = async (event) => {
  const file = event.target.files[0]
  error.value = ''
  
  if (file) {
    // Kontrola, zda jde o obrázek
    if (!file.type.startsWith('image/')) {
      error.value = 'Vybraný soubor není obrázek.'
      if (fileInput.value) fileInput.value.value = ''
      return
    }

    isCompressing.value = true
    try {
      // Provedeme kompresi
      const compressedFile = await compressImage(file)
      
      // I po kompresi zkontrolujeme velikost (pro jistotu)
      if (compressedFile.size > props.maxSizeMB * 1024 * 1024) {
        error.value = `I po kompresi je soubor příliš velký. Maximum je ${props.maxSizeMB} MB.`
        fileName.value = ''
        if (previewUrl.value) URL.revokeObjectURL(previewUrl.value)
        previewUrl.value = null
        emit('update:file', null)
        return
      }

      fileName.value = compressedFile.name
      if (previewUrl.value) URL.revokeObjectURL(previewUrl.value)
      previewUrl.value = URL.createObjectURL(compressedFile)
      
      emit('update:file', compressedFile)
    } catch (e) {
      console.error(e)
      error.value = 'Nepodařilo se zpracovat a zkomprimovat obrázek.'
    } finally {
      isCompressing.value = false
      // Reset inputu, aby šel nahrát stejný soubor znovu, pokud by si to uživatel rozmyslel
      if (fileInput.value) fileInput.value.value = ''
    }
  }
}

// Uvolnění paměti při zničení komponenty
onUnmounted(() => {
  if (previewUrl.value) URL.revokeObjectURL(previewUrl.value)
})
</script>

<style scoped>
.file-upload-wrapper { display: flex; flex-direction: column; gap: 0.4rem; width: 100%; }

.upload-label { font-weight: 600; color: var(--text-main); font-size: 0.9rem; transition: color 0.5s ease; }

.upload-container {
  position: relative;
  display: flex; align-items: center; justify-content: center;
  min-height: 80px;
  border-radius: 12px; border: 2px dashed var(--border); 
  background-color: var(--bg-app);
  cursor: pointer; transition: all 0.3s ease;
  overflow: hidden;
}

.upload-container:not(.is-compressing):hover { 
  border-color: var(--primary); 
  background-color: rgba(250, 204, 21, 0.05); 
}

.upload-container.is-compressing {
  cursor: not-allowed;
  opacity: 0.8;
}

.compressing-overlay {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
  color: var(--text-main);
  font-weight: 600;
  font-size: 0.9rem;
  padding: 1.5rem;
}

.spinner {
  width: 24px;
  height: 24px;
  border: 3px solid var(--border);
  border-top: 3px solid var(--primary);
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.upload-placeholder {
  display: flex; flex-direction: column; align-items: center; gap: 0.5rem; padding: 1.5rem;
}

.upload-icon { color: var(--text-muted); transition: color 0.5s ease; }
.file-name { color: var(--text-muted); font-size: 0.9rem; transition: color 0.5s ease; }

.image-preview { width: 100%; height: 150px; position: relative; }
.image-preview img { width: 100%; height: 100%; object-fit: cover; }

.change-overlay {
  position: absolute; inset: 0; background: rgba(0,0,0,0.5);
  color: white; display: flex; align-items: center; justify-content: center;
  font-weight: 600; opacity: 0; transition: opacity 0.2s;
}
.image-preview:hover .change-overlay { opacity: 1; }

.hidden-input { display: none; }
.error-text { color: var(--danger); font-size: 0.8rem; font-weight: 600; margin-top: 0.2rem; }
</style>