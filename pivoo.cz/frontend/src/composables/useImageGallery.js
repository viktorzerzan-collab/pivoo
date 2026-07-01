import { ref, computed } from 'vue'

export function useImageGallery(toastStore, t) {
  const isCompressing = ref(false)
  const existingPhotos = ref([])
  const removedPhotoIds = ref([])
  const newPhotos = ref([])
  const newPhotoPreviews = ref([])
  const photoInput = ref(null)

  const totalPhotos = computed(() => existingPhotos.value.length + newPhotos.value.length)

  const triggerPhotoInput = () => {
    if (photoInput.value) photoInput.value.click()
  }

  const compressImage = (file) => {
    return new Promise((resolve, reject) => {
      const reader = new FileReader()
      reader.readAsDataURL(file)
      reader.onload = (event) => {
        const img = new Image()
        img.src = event.target.result
        img.onload = () => {
          let width = img.width
          let height = img.height
          const maxDim = 1920

          if (width > maxDim || height > maxDim) {
            if (width > height) {
              height = Math.round((height * maxDim) / width)
              width = maxDim
            } else {
              width = Math.round((width * maxDim) / height)
              height = maxDim
            }
          }

          const canvas = document.createElement('canvas')
          canvas.width = width
          canvas.height = height
          const ctx = canvas.getContext('2d')
          ctx.drawImage(img, 0, 0, width, height)

          canvas.toBlob((blob) => {
            if (blob) {
              resolve(new File([blob], file.name.replace(/\.[^/.]+$/, "") + ".webp", {
                type: 'image/webp',
                lastModified: Date.now()
              }))
            } else {
              reject(new Error("Compression failed"))
            }
          }, 'image/webp', 0.85)
        }
        img.onerror = (e) => reject(e)
      }
      reader.onerror = (e) => reject(e)
    })
  }

  const handlePhotoSelect = async (e) => {
    const files = Array.from(e.target.files)
    if (!files.length) return
    
    isCompressing.value = true
    
    for (const file of files) {
      if (totalPhotos.value >= 3) {
        toastStore.showToast(t('modals.checkin.max_photos_reached'), 'warning')
        break;
      }
      try {
        const compressedFile = await compressImage(file)
        newPhotos.value.push(compressedFile)
        newPhotoPreviews.value.push(URL.createObjectURL(compressedFile))
      } catch(err) {
        console.error("Chyba při zmenšování obrázku", err)
        toastStore.showToast(t('modals.checkin.photo_error'), 'error')
      }
    }
    
    isCompressing.value = false
    if (photoInput.value) photoInput.value.value = ''
  }

  const removeExistingPhoto = (id) => {
    existingPhotos.value = existingPhotos.value.filter(p => p.id !== id)
    removedPhotoIds.value.push(id)
  }

  const removeNewPhoto = (idx) => {
    URL.revokeObjectURL(newPhotoPreviews.value[idx])
    newPhotoPreviews.value.splice(idx, 1)
    newPhotos.value.splice(idx, 1)
  }

  const resetGallery = (initialPhotos = []) => {
    existingPhotos.value = [...initialPhotos]
    removedPhotoIds.value = []
    newPhotos.value = []
    newPhotoPreviews.value.forEach(url => URL.revokeObjectURL(url))
    newPhotoPreviews.value = []
  }

  return {
    isCompressing,
    existingPhotos,
    removedPhotoIds,
    newPhotos,
    newPhotoPreviews,
    photoInput,
    totalPhotos,
    triggerPhotoInput,
    handlePhotoSelect,
    removeExistingPhoto,
    removeNewPhoto,
    resetGallery
  }
}
