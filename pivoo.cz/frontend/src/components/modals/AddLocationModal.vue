<template>
  <BaseModal :show="show" @close="$emit('close')" customStyle="max-width: 600px; overflow: hidden;">
    <template #header>
      <BackgroundWatermark 
        :icon="isEditing ? PencilIcon : PlusCircleIcon" 
        :size="180" 
        :is-modal="true" 
      />

      <h2 class="modal-title" style="position: relative; z-index: 1;">
        <component :is="isEditing ? PencilIcon : PlusCircleIcon" class="title-icon" :size="26" />
        {{ isEditing ? $t('modals.add_location.title_edit') : $t('modals.add_location.title_add') }}
      </h2>
    </template>
    <template #body>
      <form @submit.prevent="$emit('submit')" class="add-form" style="position: relative; z-index: 1;">
        
        <div v-if="duplicateWarning" class="duplicate-warning">
          <AlertTriangleIcon :size="20" class="warning-icon" />
          <div class="warning-text">
            {{ duplicateWarning }}
          </div>
        </div>

        <BaseInput v-model="form.name" :label="$t('modals.add_location.name')" required />
        
        <BaseSelect v-model="form.type" :label="$t('modals.add_location.type')" required>
          <option value="hospoda">{{ $t('modals.add_location.type_pub') }}</option>
          <option value="pivoteka">{{ $t('modals.add_location.type_shop') }}</option>
          <option value="obchod">{{ $t('modals.add_location.type_store') }}</option>
          <option value="jine">{{ $t('modals.add_location.type_other') }}</option>
        </BaseSelect>

        <template v-if="form.type !== 'jine'">
          
          <BaseMapPicker 
            v-model:lat="form.lat" 
            v-model:lng="form.lng" 
            :label="$t('modals.add_location.map_label')" 
            :show="show && form.type !== 'jine'"
            @location-changed="handleLocationChange"
          >
            <template #append-coords>
              <span v-if="isGeocoding" style="color: var(--blue); margin-left: 10px;">{{ $t('modals.add_location.geocoding') }}</span>
            </template>
          </BaseMapPicker>

          <BaseInput v-model="form.address" :label="$t('modals.add_brewery.address')" />

          <div class="form-row">
            <BaseInput v-model="form.city" :label="$t('modals.add_brewery.city')" class="half" />
            <BaseInput v-model="form.zip_code" :label="$t('modals.add_brewery.zip')" class="half" />
          </div>

          <BaseSelect v-model="form.country_id" :label="$t('modals.add_brewery.country')">
            <option v-for="c in countries" :key="c.id" :value="c.id">
              {{ c.name_cz }}
            </option>
          </BaseSelect>

          <div class="form-row">
            <BaseInput v-model="form.email" type="email" :label="$t('modals.add_brewery.email')" class="half" />
            <BaseInput v-model="form.phone" :label="$t('modals.add_brewery.phone')" class="half" />
          </div>

          <BaseInput v-model="form.website" type="url" :label="$t('modals.add_brewery.website')" />
          
          <OpeningHoursInput v-model="form.opening_hours" :label="$t('opening_hours.label')" />
        </template>

        <BaseButton type="submit" variant="add" style="margin-top: 1rem; width: 100%;">
          <template #icon>
            <component :is="isEditing ? PencilIcon : PlusCircleIcon" :size="18" />
          </template>
          {{ $t('modals.add_location.save') }}
        </BaseButton>
      </form>
    </template>
  </BaseModal>
</template>

<script setup>
import { ref, watch, nextTick } from 'vue'
import { AlertTriangleIcon, PlusCircleIcon, PencilIcon } from 'lucide-vue-next'
import { useI18n } from 'vue-i18n'
import BaseModal from '../BaseModal.vue'
import BackgroundWatermark from '../BackgroundWatermark.vue'
import BaseInput from '../BaseInput.vue'
import BaseButton from '../BaseButton.vue'
import BaseSelect from '../BaseSelect.vue'
import OpeningHoursInput from '../OpeningHoursInput.vue'
import BaseMapPicker from '../BaseMapPicker.vue'

import { useCatalogStore } from '../../stores/catalog'

const props = defineProps({
  show: Boolean,
  isEditing: Boolean,
  form: Object,
  countries: Array
})
const emit = defineEmits(['close', 'submit'])

const catalogStore = useCatalogStore()
const { t } = useI18n()

const duplicateWarning = ref('')
const isGeocoding = ref(false)

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

const checkDuplicates = (lat, lng) => {
  const duplicates = []
  catalogStore.locations.forEach(loc => {
    if (loc.lat && loc.lng && loc.id !== props.form.id) { 
      const dist = calculateDistance(lat, lng, loc.lat, loc.lng)
      if (dist <= 0.05) { 
        duplicates.push(loc.name)
      }
    }
  })

  if (duplicates.length > 0) {
    duplicateWarning.value = t('modals.add_location.duplicate_warning', { duplicates: duplicates.join(', ') })
  } else {
    duplicateWarning.value = ''
  }
}

const fetchAddressFromGPS = async (lat, lng) => {
  if (props.isEditing) return 

  isGeocoding.value = true
  try {
    const res = await fetch(`https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lng}&format=json&addressdetails=1`)
    const data = await res.json()
    if (data && data.address) {
      if (!props.form.city) props.form.city = data.address.city || data.address.town || data.address.village || ''
      if (!props.form.zip_code) props.form.zip_code = data.address.postcode || ''
      if (!props.form.address) {
        const street = data.address.road || data.address.pedestrian || ''
        const houseNum = data.address.house_number || ''
        props.form.address = `${street} ${houseNum}`.trim()
      }
    }
  } catch (e) {
    console.error('Chyba při získávání adresy:', e)
  } finally {
    isGeocoding.value = false
  }
}

const handleLocationChange = ({ lat, lng }) => {
  checkDuplicates(lat, lng)
  fetchAddressFromGPS(lat, lng)
}

watch(() => props.show, (isVisible) => {
  if (isVisible) {
    duplicateWarning.value = ''
    nextTick(() => {
      if (!props.isEditing && props.form.lat && props.form.lng && props.form.type !== 'jine') {
        checkDuplicates(props.form.lat, props.form.lng)
        fetchAddressFromGPS(props.form.lat, props.form.lng)
      }
    })
  }
})
</script>

<style scoped>
.modal-title { display: flex; align-items: center; gap: 0.5rem; margin: 0; color: var(--text-main); font-size: 1.5rem; transition: color 0.3s ease; }
.title-icon { color: var(--blue); }
.add-form { display: flex; flex-direction: column; gap: 1rem; }
.form-row { display: flex; gap: 1rem; }
.half { flex: 1; }

.duplicate-warning {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  background-color: rgba(245, 158, 11, 0.1);
  border: 1px solid rgba(245, 158, 11, 0.4);
  padding: 1rem;
  border-radius: var(--radius-sm);
  color: #d97706;
}
.warning-icon { flex-shrink: 0; margin-top: 2px; }
.warning-text { font-size: 0.9rem; font-weight: 600; line-height: 1.4; }

@media (max-width: 600px) {
  .form-row { flex-direction: column; }
}
</style>