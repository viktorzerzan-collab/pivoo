<template>
  <BaseModal :show="show" @close="$emit('close')" customStyle="overflow: hidden;">
    <template #header>
      <BackgroundWatermark 
        :icon="isEditing ? PencilIcon : PlusCircleIcon" 
        :size="180" 
        :is-modal="true" 
      />

      <h2 class="modal-title" style="position: relative; z-index: 1;">
        <component :is="isEditing ? PencilIcon : PlusCircleIcon" class="title-icon" :size="26" />
        {{ isEditing ? $t('modals.checkin.title_edit') : $t('modals.checkin.title_add') }}
      </h2>
    </template>
    <template #body>
      <form @submit.prevent="emitSubmit" class="checkin-form">
        
        <MagicScanner @result="handleAiResponse" />

        <BaseDatePicker v-model="form.consumed_at" :label="$t('modals.checkin.date_label')" />

        <div class="location-detect-wrapper">
          <BaseSelect v-model="form.location_id" :label="$t('modals.checkin.location_label')" searchable required style="flex: 1; min-width: 0;">
            <option disabled value="">{{ $t('modals.checkin.select_location') }}</option>
            <option v-for="loc in catalogStore.allLocations" :key="loc.id" :value="loc.id">
              {{ loc.is_favorite ? '⭐' : '📍' }} {{ translateLocation(loc.name) }}
            </option>
          </BaseSelect>
          
          <GeoLocateButton 
            :isLocating="isLocating" 
            @locate="autodetectLocation" 
          />
        </div>

        <div v-if="locationMessage" class="location-message" :class="locationMessageType">
          {{ locationMessage }}
          <a v-if="locationMessageType === 'warning'" href="#" @click.prevent="$emit('open-add-location', tempCoords)" class="add-loc-link">
            {{ $t('modals.checkin.add_location_link') }}
          </a>
        </div>

        <BaseSelect v-model="form.brewery_id" :label="$t('modals.checkin.brewery_label')" searchable required>
          <option disabled value="">{{ $t('modals.checkin.select_brewery') }}</option>
          <option v-for="brewery in catalogStore.allBreweries" :key="brewery.id" :value="brewery.id">
            {{ brewery.is_favorite ? '⭐' : '🏭' }} {{ brewery.name }}
          </option>
        </BaseSelect>

        <BaseSelect v-model="form.beer_id" :label="$t('modals.checkin.beer_label')" :disabled="!form.brewery_id" searchable required>
          <option disabled value="">{{ $t('modals.checkin.select_beer') }}</option>
          <option v-for="beer in sortedBeers" :key="beer.id" :value="beer.id">
            {{ beer.is_favorite ? '⭐' : '🍺' }} {{ beer.name }}
          </option>
        </BaseSelect>

        <div class="form-row">
          <BaseSelect class="half" v-model="form.packaging" :label="$t('modals.checkin.packaging_label')" required>
            <option value="draft">{{ $t('packaging.draft') }}</option>
            <option value="bottle">{{ $t('packaging.bottle') }}</option>
            <option value="can">{{ $t('packaging.can') }}</option>
            <option value="pet">{{ $t('packaging.pet') }}</option>
            <option value="keg">{{ $t('packaging.keg') }}</option>
            <option value="mini_keg">{{ $t('packaging.mini_keg') }}</option>
          </BaseSelect>

          <div class="half">
            <BaseSelect v-model="volumeMode" :label="$t('modals.checkin.volume_label')">
              <option value="0.20">{{ $t('modals.checkin.volume.glass') }}</option>
              <option value="0.30">{{ $t('modals.checkin.volume.small') }}</option>
              <option value="0.33">0,33 l</option>
              <option value="0.40">{{ $t('modals.checkin.volume.snyt') }}</option>
              <option value="0.50">{{ $t('modals.checkin.volume.large') }}</option>
              <option value="1.00">{{ $t('modals.checkin.volume.tuplak') }}</option>
              <option value="custom">{{ $t('modals.checkin.volume.custom') }}</option>
            </BaseSelect>
          </div>
        </div>

        <div v-if="volumeMode === 'custom'" class="form-row">
          <div class="half"></div>
          <BaseInput 
            class="half" 
            v-model="customVolume" 
            type="number" 
            step="0.01" 
            min="0.01" 
            :label="$t('modals.checkin.custom_volume_label')" 
            :placeholder="$t('modals.checkin.custom_volume_placeholder')"
            required
          />
        </div>

        <div class="form-row">
          <BaseInput class="half" v-model="form.quantity" type="number" min="1" :label="isEditing ? $t('modals.checkin.quantity_edit_label') : $t('modals.checkin.quantity_label')" required />
          <div class="half"></div>
        </div>

        <div class="form-row">
          <div class="half" style="display: flex; gap: 0.5rem;">
            <BaseInput 
              style="flex: 2;"
              v-model="priceValue" 
              type="number" 
              step="0.01" 
              :label="$t('modals.checkin.price_label')" 
              :disabled="form.is_free" 
            />
            <BaseSelect 
              style="flex: 1;"
              v-model="form.currency" 
              :label="$t('modals.checkin.currency_label')" 
              :disabled="form.is_free"
              :searchable="false"
            >
              <option value="CZK">CZK</option>
              <option value="EUR">EUR</option>
              <option value="PLN">PLN</option>
              <option value="GBP">GBP</option>
            </BaseSelect>
          </div>
          <div class="half">
            <BaseCheckbox 
              v-model="form.is_free" 
              :label="$t('modals.checkin.is_free_label')" 
            />
          </div>
        </div>

        <div class="form-row">
          <div class="rating-box half">
            <label class="input-label">{{ $t('modals.checkin.rating_beer_label') }}</label>
            <StarRating v-model="form.rating_beer" />
          </div>

          <div v-if="showCareRating" class="rating-box half">
            <label class="input-label">{{ $t('modals.checkin.rating_care_label') }}</label>
            <StarRating v-model="form.rating_care" />
          </div>
          <div v-else class="half"></div>
        </div>

        <div class="gallery-box">
          <div class="gallery-header">
            <label class="input-label">{{ $t('modals.checkin.gallery_label') }} ({{ totalPhotos }}/3)</label>
            <span v-if="isCompressing" class="compress-loader">...</span>
          </div>
          <div class="gallery-preview">
            
            <div v-for="photo in existingPhotos" :key="photo.id" class="preview-item">
              <img :src="`/backend/uploads/checkins/${photo.filename}`" />
              <button type="button" class="remove-btn" @click="removeExistingPhoto(photo.id)">
                <XIcon :size="14" />
              </button>
            </div>
            
            <div v-for="(preview, idx) in newPhotoPreviews" :key="'new'+idx" class="preview-item">
              <img :src="preview" />
              <button type="button" class="remove-btn" @click="removeNewPhoto(idx)">
                <XIcon :size="14" />
              </button>
            </div>
            
            <button v-if="totalPhotos < 3" type="button" class="add-photo-btn" @click="triggerPhotoInput">
              <CameraIcon :size="24" class="icon-muted" />
            </button>
          </div>
          <input type="file" ref="photoInput" multiple accept="image/*" class="hidden-input" @change="handlePhotoSelect" />
        </div>

        <BaseInput v-model="form.note" :label="$t('modals.checkin.note_label')" :placeholder="$t('modals.checkin.note_placeholder')" />

        <BaseButton type="submit" :variant="isEditing ? 'edit' : 'primary'" :disabled="isCompressing" style="margin-top: 1rem; width: 100%;">
          <template #icon>
            <component :is="isEditing ? PencilIcon : PlusCircleIcon" :size="18" />
          </template>
          {{ isEditing ? $t('modals.checkin.save_edit') : $t('modals.checkin.save_add') }}
        </BaseButton>

      </form>
    </template>
  </BaseModal>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { PencilIcon, PlusCircleIcon, CameraIcon, XIcon } from 'lucide-vue-next'
import { useI18n } from 'vue-i18n'
import BaseModal from '../BaseModal.vue'
import BaseInput from '../BaseInput.vue'
import BaseButton from '../BaseButton.vue'
import BaseSelect from '../BaseSelect.vue'
import BaseDatePicker from '../BaseDatePicker.vue'
import StarRating from '../StarRating.vue'
import BaseCheckbox from '../BaseCheckbox.vue'
import GeoLocateButton from '../GeoLocateButton.vue'
import MagicScanner from '../MagicScanner.vue'
import BackgroundWatermark from '../BackgroundWatermark.vue'

import { useCatalogStore } from '../../stores/catalog'
import { useAuthStore } from '../../stores/auth'
import { useToastStore } from '../../stores/toast'

const catalogStore = useCatalogStore()
const authStore = useAuthStore()
const toastStore = useToastStore()
const { t, te } = useI18n()

const props = defineProps({ 
  show: Boolean, 
  form: Object,
  isEditing: { type: Boolean, default: false }
})

const emit = defineEmits(['close', 'submit', 'open-add-location', 'magicAddBrewery', 'magicAddBeer'])

const volumeMode = ref(props.form.volume || '')
const customVolume = ref('')

const isLocating = ref(false)
const locationMessage = ref('')
const locationMessageType = ref('')
const tempCoords = ref(null)

const isAiUpdating = ref(false)
const isCompressing = ref(false)

const existingPhotos = ref([])
const removedPhotoIds = ref([])
const newPhotos = ref([])
const newPhotoPreviews = ref([])
const photoInput = ref(null)

const totalPhotos = computed(() => existingPhotos.value.length + newPhotos.value.length)

const priceValue = computed({
  get: () => props.isEditing ? props.form.original_price : props.form.price,
  set: (val) => {
    if (props.isEditing) props.form.original_price = val
    else props.form.price = val
  }
})

const translateLocation = (val) => {
  if (!val) return val
  const key = `dynamic.locations.${val}`
  return te(key) ? t(key) : val
}

const handleAiResponse = (res) => {
  if (res.status === 'success' && res.data) {
    const ai = res.data

    if (ai.brewery_id && !catalogStore.allBreweries.some(b => b.id == ai.brewery_id)) {
      catalogStore.addBreweryLocally({
        id: ai.brewery_id,
        name: ai.brewery_name || 'Neznámý pivovar'
      })
    }

    if (ai.beer_id && !catalogStore.allBeers.some(b => b.id == ai.beer_id)) {
      catalogStore.addBeerLocally({
        id: ai.beer_id,
        name: ai.beer_name || 'Neznámé pivo',
        brewery_id: ai.brewery_id
      })
    }

    isAiUpdating.value = true

    if (ai.brewery_id) props.form.brewery_id = ai.brewery_id
    if (ai.location_id) props.form.location_id = ai.location_id
    if (ai.currency) props.form.currency = ai.currency

    setTimeout(() => {
      if (ai.beer_id) props.form.beer_id = ai.beer_id
      
      if (ai.volume) {
        const volStr = Number(ai.volume).toFixed(2)
        const standardVolumes = ['0.20', '0.30', '0.33', '0.40', '0.50', '1.00']
        if (standardVolumes.includes(volStr)) {
          volumeMode.value = volStr
          customVolume.value = ''
        } else {
          volumeMode.value = 'custom'
          customVolume.value = volStr
        }
      }
      
      if (ai.quantity) props.form.quantity = ai.quantity
      if (ai.packaging) props.form.packaging = ai.packaging
      
      if (ai.price) {
        if (props.isEditing) props.form.original_price = ai.price
        else props.form.price = ai.price
      }

      toastStore.showToast(t('modals.checkin.msg_success') || 'Pivo bylo úspěšně rozpoznáno!', 'success')
      isAiUpdating.value = false
    }, 150)
  }
}

watch(volumeMode, (newVal) => {
  if (newVal !== 'custom' && newVal !== '') {
    props.form.volume = newVal
  } else if (newVal === 'custom') {
    props.form.volume = customVolume.value
  }
})

watch(customVolume, (newVal) => {
  if (volumeMode.value === 'custom') {
    props.form.volume = newVal
  }
})

watch(() => props.form.is_free, (isFree) => {
  if (isFree) {
    if (props.isEditing) props.form.original_price = ''
    props.form.price = ''
  }
})

watch(() => props.show, (newVal) => {
  if (newVal) {
    locationMessage.value = ''
    
    existingPhotos.value = props.isEditing && props.form.photos ? [...props.form.photos] : []
    removedPhotoIds.value = []
    newPhotos.value = []
    newPhotoPreviews.value.forEach(url => URL.revokeObjectURL(url))
    newPhotoPreviews.value = []
    
    if (props.form.location_id) props.form.location_id = Number(props.form.location_id)
    if (props.form.brewery_id) props.form.brewery_id = Number(props.form.brewery_id)
    if (props.form.beer_id) props.form.beer_id = Number(props.form.beer_id)

    const currentVol = String(props.form.volume)
    const standardVolumes = ['0.20', '0.30', '0.33', '0.40', '0.50', '1.00']
    
    if (standardVolumes.includes(currentVol)) {
      volumeMode.value = currentVol
      customVolume.value = ''
    } else if (currentVol && currentVol !== 'undefined') {
      volumeMode.value = 'custom'
      customVolume.value = currentVol
    } else {
      volumeMode.value = '0.50'
    }

    if (!props.form.consumed_at && !props.isEditing) {
      const now = new Date();
      const localDateTime = now.getFullYear() + '-' + 
        String(now.getMonth() + 1).padStart(2, '0') + '-' + 
        String(now.getDate()).padStart(2, '0') + ' ' + 
        String(now.getHours()).padStart(2, '0') + ':' + 
        String(now.getMinutes()).padStart(2, '0') + ':' + 
        String(now.getSeconds()).padStart(2, '0');
      
      props.form.consumed_at = localDateTime;
    }

    if (!props.form.currency) {
      props.form.currency = authStore.defaultCurrency || 'CZK'
    }
    
    if (props.isEditing && !props.form.original_price && props.form.price) {
      props.form.original_price = props.form.price
    }
  }
})

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
      toastStore.showToast(t('modals.checkin.max_photos_reached') || 'Můžete nahrát maximálně 3 fotky.', 'warning')
      break;
    }
    try {
      const compressedFile = await compressImage(file)
      newPhotos.value.push(compressedFile)
      newPhotoPreviews.value.push(URL.createObjectURL(compressedFile))
    } catch(err) {
      console.error("Chyba při zmenšování obrázku", err)
      toastStore.showToast(t('modals.checkin.photo_error') || 'Nepodařilo se zpracovat fotku.', 'error')
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

const emitSubmit = () => {
  emit('submit', { 
    newPhotos: newPhotos.value, 
    removedPhotoIds: removedPhotoIds.value 
  })
}

const calculateDistance = (lat1, lon1, lat2, lon2) => {
  const R = 6371
  const dLat = (lat2 - lat1) * Math.PI / 180
  const dLon = (lon2 - lon1) * Math.PI / 180
  const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
            Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
            Math.sin(dLon/2) * Math.sin(dLon/2)
  const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a))
  return R * c
}

const autodetectLocation = () => {
  if (!navigator.geolocation) {
    locationMessage.value = 'Geolokace není prohlížečem podporována.'
    locationMessageType.value = 'error'
    return
  }
  
  isLocating.value = true
  locationMessage.value = ''
  
  navigator.geolocation.getCurrentPosition(
    (pos) => {
      isLocating.value = false
      const lat = pos.coords.latitude
      const lng = pos.coords.longitude
      tempCoords.value = { lat, lng }

      let nearestLoc = null
      let minDistance = Infinity

      catalogStore.allLocations.forEach(loc => {
        if (loc.lat && loc.lng) {
          const dist = calculateDistance(lat, lng, loc.lat, loc.lng)
          if (dist < minDistance) {
            minDistance = dist
            nearestLoc = loc
          }
        }
      })

      if (nearestLoc && minDistance <= 0.05) {
        props.form.location_id = nearestLoc.id
        locationMessage.value = `📍 Nalezeno: ${translateLocation(nearestLoc.name)} (${(minDistance * 1000).toFixed(0)} m)`
        locationMessageType.value = 'success'
      } else {
        props.form.location_id = ''
        locationMessage.value = 'Žádný známý podnik v okolí 50m.'
        locationMessageType.value = 'warning'
      }
    },
    (err) => {
      isLocating.value = false
      locationMessage.value = 'Nepodařilo se zjistit polohu. Zkontrolujte oprávnění.'
      locationMessageType.value = 'error'
    },
    { enableHighAccuracy: true, timeout: 10000 }
  )
}

const sortedBeers = computed(() => {
  if (!props.form.brewery_id) return []
  return catalogStore.allBeers.filter(b => b.brewery_id == props.form.brewery_id);
})

watch(() => props.form.brewery_id, (newVal, oldVal) => {
  if (!isAiUpdating.value && oldVal !== undefined && oldVal !== '') {
    props.form.beer_id = ''
  }
})

const showCareRating = computed(() => {
  const loc = catalogStore.allLocations.find(l => l.id == props.form.location_id)
  return loc ? loc.type === 'hospoda' : false
})

watch(() => props.form.location_id, () => {
  if (!showCareRating.value) {
    props.form.rating_care = 0
  }
})
</script>

<style scoped>
.modal-title { display: flex; align-items: center; gap: 0.5rem; margin: 0; color: var(--text-main); font-size: 1.5rem; transition: color 0.3s ease; }
.title-icon { color: var(--primary); }

.checkin-form { position: relative; z-index: 1; display: flex; flex-direction: column; gap: 1.25rem; }
.form-row { display: flex; gap: 1rem; }
.half { flex: 1; }

.location-detect-wrapper { 
  display: flex; 
  align-items: flex-end; 
  gap: 0.5rem; 
  flex-wrap: nowrap; 
}

.location-message { font-size: 0.85rem; padding: 0.5rem 0.75rem; border-radius: var(--radius-sm); font-weight: 600; margin-top: -0.5rem; }
.location-message.success { background-color: rgba(16, 185, 129, 0.1); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.2); }
.location-message.warning { background-color: rgba(245, 158, 11, 0.1); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.2); display: flex; justify-content: space-between; align-items: center; }
.location-message.error { background-color: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.2); }

.add-loc-link { color: var(--blue); text-decoration: underline; cursor: pointer; }
.add-loc-link:hover { color: var(--blue-hover); }

.align-end { align-items: flex-end; }
.rating-box { display: flex; flex-direction: column; gap: 0.4rem; justify-content: center; }
.input-label { font-size: 0.9rem; font-weight: 600; color: var(--text-muted); transition: color 0.3s ease; }

.gallery-box { background: var(--bg-app); border: 1px solid var(--border); padding: 0.75rem; border-radius: var(--radius-md); }
.gallery-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem; }
.compress-loader { font-size: 0.8rem; font-weight: 600; color: var(--primary); animation: pulse 1s infinite; }
.gallery-preview { display: flex; flex-wrap: wrap; gap: 0.5rem; }
.preview-item { position: relative; width: 60px; height: 60px; border-radius: var(--radius-sm); border: 1px solid var(--border); overflow: hidden; }
.preview-item img { width: 100%; height: 100%; object-fit: cover; }

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

.add-photo-btn { width: 60px; height: 60px; border: 1px dashed var(--border); border-radius: var(--radius-sm); background: transparent; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: background 0.2s ease; }
.add-photo-btn:hover { background: var(--bg-panel); }
.icon-muted { color: var(--text-muted); }
.hidden-input { display: none; }

@keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.5; } 100% { opacity: 1; } }

@media (max-width: 600px) { 
  .form-row { flex-direction: column; gap: 1.25rem; } 
  .half:empty { display: none; }
  .align-end { align-items: stretch; }
}
</style>