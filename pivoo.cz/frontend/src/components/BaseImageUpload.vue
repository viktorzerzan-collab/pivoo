<template>
  <div class="base-image-upload">
    <div class="avatar-wrapper" :style="{ width: size + 'px', height: size + 'px' }">
      <img v-if="localPreviewUrl" :src="localPreviewUrl" alt="Náhled obrázku" class="avatar-image" />
      
      <img v-else-if="currentImageUrl" :src="currentImageUrl" alt="Aktuální obrázek" class="avatar-image" />
      
      <div v-else class="avatar-placeholder">
        <UserIcon :size="Math.floor(size / 2.2)" color="var(--text-muted)" />
      </div>
      
      <label class="avatar-upload-overlay" :title="uploadTitle">
        <CameraIcon :size="24" />
        <input 
          type="file" 
          class="hidden-input" 
          accept="image/*" 
          @change="handleFileChange" 
          :disabled="disabled" 
        />
      </label>
    </div>
  </div>
</template>

<script setup>
import { ref, onBeforeUnmount } from 'vue'
import { UserIcon, CameraIcon } from 'lucide-vue-next'

const props = defineProps({
  currentImageUrl: { type: String, default: null },
  size: { type: Number, default: 140 },
  disabled: { type: Boolean, default: false },
  uploadTitle: { type: String, default: 'Změnit fotku' }
})

const emit = defineEmits(['file-selected', 'error'])

const localPreviewUrl = ref(null)

// Uvolnění paměti z dočasné URL při zničení komponenty
onBeforeUnmount(() => {
  if (localPreviewUrl.value) {
    URL.revokeObjectURL(localPreviewUrl.value)
  }
})

// Metoda na kompresi obrázku do WEBP
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
        const max_size = 800 // Maximální šířka/výška

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

        canvas.toBlob((blob) => {
          if (!blob) {
            reject(new Error('Chyba při kompresi obrázku'))
            return
          }
          const newFile = new File([blob], file.name.replace(/\.[^/.]+$/, "") + ".webp", { type: 'image/webp' })
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
  if (!file) return

  try {
    // 1. Zpracování a komprese
    const compressedFile = await compressImage(file)
    
    // 2. Vytvoření lokálního náhledu (reaktivní zobrazení)
    if (localPreviewUrl.value) {
      URL.revokeObjectURL(localPreviewUrl.value) // uvolnění starého náhledu
    }
    localPreviewUrl.value = URL.createObjectURL(file) // ukážeme rovnou originál (rychlejší než číst z blob)

    // 3. Emitnutí zkomprimovaného souboru rodiči
    emit('file-selected', compressedFile)

  } catch (err) {
    emit('error', err.message || 'Nepodařilo se zpracovat obrázek.')
  }
}

// Expose metoda pro resetování náhledu (zavolá ji rodič po úspěšném uložení)
const resetPreview = () => {
  if (localPreviewUrl.value) {
    URL.revokeObjectURL(localPreviewUrl.value)
    localPreviewUrl.value = null
  }
}

defineExpose({ resetPreview })
</script>

<style scoped>
.base-image-upload {
  display: flex;
  flex-direction: column;
  align-items: center;
}

.avatar-wrapper {
  position: relative;
  border-radius: 50%;
  border: 4px solid var(--bg-app);
  box-shadow: var(--shadow-floating);
  overflow: hidden;
  background: var(--bg-app);
  transition: all 0.3s ease;
  flex-shrink: 0;
}

.avatar-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.avatar-placeholder {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.avatar-upload-overlay {
  position: absolute;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  opacity: 0;
  cursor: pointer;
  transition: opacity 0.2s;
}

.avatar-wrapper:hover .avatar-upload-overlay {
  opacity: 1;
}

.hidden-input {
  display: none;
}
</style>