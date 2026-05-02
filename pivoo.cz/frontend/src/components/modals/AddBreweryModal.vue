<template>
  <BaseModal :show="show" @close="$emit('close')" customStyle="max-width: 600px;">
    <template #header>
      <h2 class="modal-title">
        <FactoryIcon class="title-icon" :size="26" />
        {{ isEditing ? $t('modals.add_brewery.title_edit') : $t('modals.add_brewery.title_add') }}
      </h2>
    </template>
    <template #body>
      <form @submit.prevent="$emit('submit')" class="add-form">
        
        <div v-if="form.is_magic" class="magic-banner">
          <SparklesIcon :size="20" class="magic-icon" />
          <span>{{ $t('modals.add_beer.magic_banner') }}</span>
        </div>

        <div style="margin-bottom: 0.5rem;">
          <BaseFileUpload v-model:file="form.logoFile" :label="$t('modals.add_brewery.logo')" :placeholder="$t('modals.add_brewery.logo_placeholder')" />
        </div>

        <BaseInput v-model="form.name" :label="$t('modals.add_brewery.name')" required />
        
        <BaseInput v-model="form.address" :label="$t('modals.add_brewery.address')" />
        
        <div class="form-row">
          <BaseInput v-model="form.city" :label="$t('modals.add_brewery.city')" style="flex: 2;" />
          <BaseInput v-model="form.zip_code" :label="$t('modals.add_brewery.zip')" style="flex: 1;" />
        </div>
        
        <BaseSelect v-model="form.country_id" :label="$t('modals.add_brewery.country')">
          <option v-for="c in countries" :key="c.id" :value="c.id">
            {{ c.name_cz }}
          </option>
        </BaseSelect>

        <div class="map-container-wrapper">
          <label class="form-label d-block mb-2">{{ $t('modals.add_brewery.map_label') }}</label>
          <div ref="mapContainerRef" class="admin-map-element"></div>
          <div class="coords-display">
            GPS: {{ form.lat || '???' }}, {{ form.lng || '???' }}
          </div>
        </div>

        <div class="form-row">
          <BaseInput v-model="form.email" type="email" :label="$t('modals.add_brewery.email')" style="flex: 1;" />
          <BaseInput v-model="form.phone" :label="$t('modals.add_brewery.phone')" style="flex: 1;" />
        </div>

        <BaseInput v-model="form.website" type="url" :label="$t('modals.add_brewery.website')" />

        <OpeningHoursInput v-model="form.opening_hours" :label="$t('opening_hours.label')" />

        <BaseButton type="submit" variant="add" style="margin-top: 0.5rem; width: 100%;">
          <template #icon><SaveIcon :size="18" /></template>{{ $t('modals.add_brewery.save') }}
        </BaseButton>
      </form>
    </template>
  </BaseModal>
</template>

<script setup>
import { ref, watch, nextTick, onBeforeUnmount } from 'vue'
import { FactoryIcon, SaveIcon, SparklesIcon } from 'lucide-vue-next'
import BaseModal from '../BaseModal.vue'
import BaseInput from '../BaseInput.vue'
import BaseButton from '../BaseButton.vue'
import BaseFileUpload from '../BaseFileUpload.vue'
import BaseSelect from '../BaseSelect.vue'
import OpeningHoursInput from '../OpeningHoursInput.vue'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'

import markerIconUrl from 'leaflet/dist/images/marker-icon.png'
import markerShadowUrl from 'leaflet/dist/images/marker-shadow.png'

const props = defineProps({
  show: Boolean,
  isEditing: Boolean,
  form: Object,
  countries: Array
})
const emit = defineEmits(['close', 'submit'])

const map = ref(null)
const marker = ref(null)
const mapContainerRef = ref(null)

const destroyMap = () => {
  if (map.value) {
    map.value.remove()
    map.value = null
    marker.value = null
  }
}

const initMap = () => {
  if (!mapContainerRef.value) return;

  destroyMap()

  const lat = parseFloat(props.form.lat) || 49.8175
  const lng = parseFloat(props.form.lng) || 15.4730
  const zoom = props.form.lat ? 16 : 6

  map.value = L.map(mapContainerRef.value).setView([lat, lng], zoom)

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap'
  }).addTo(map.value)

  const customIcon = L.icon({
    iconUrl: markerIconUrl,
    shadowUrl: markerShadowUrl,
    iconSize: [25, 41],
    iconAnchor: [12, 41],
    popupAnchor: [1, -34]
  })

  marker.value = L.marker([lat, lng], { icon: customIcon, draggable: true }).addTo(map.value)

  marker.value.on('dragend', (e) => {
    const newPos = e.target.getLatLng()
    props.form.lat = newPos.lat.toFixed(8)
    props.form.lng = newPos.lng.toFixed(8)
  })

  map.value.on('click', (e) => {
    const newPos = e.latlng
    marker.value.setLatLng(newPos)
    props.form.lat = newPos.lat.toFixed(8)
    props.form.lng = newPos.lng.toFixed(8)
  })

  setTimeout(() => {
    if (map.value) map.value.invalidateSize()
  }, 250)
}

watch(() => props.show, (isVisible) => {
  if (isVisible) {
    nextTick(() => initMap())
  } else {
    destroyMap()
  }
})

watch(() => props.form.id, () => {
  if (props.show) {
    nextTick(() => initMap())
  }
})

onBeforeUnmount(() => destroyMap())
</script>

<style scoped>
.modal-title { display: flex; align-items: center; gap: 0.5rem; margin: 0; color: var(--text-main); font-size: 1.5rem; transition: color 0.3s ease; }
.title-icon { color: var(--blue); }
.add-form { display: flex; flex-direction: column; gap: 1.25rem; }
.form-row { display: flex; gap: 1rem; }
.map-container-wrapper { margin: 0.5rem 0; }
.form-label { font-size: 0.9rem; font-weight: 600; color: var(--text-main); }
.mb-2 { margin-bottom: 0.5rem; }
.d-block { display: block; }
.admin-map-element {
  height: 250px;
  width: 100%;
  border-radius: var(--radius-sm);
  border: 1px solid var(--border);
  z-index: 1;
}
.coords-display { font-family: monospace; font-size: 0.8rem; margin-top: 5px; color: var(--text-muted); }

.magic-banner {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  background-color: rgba(139, 92, 246, 0.1);
  border: 1px solid rgba(139, 92, 246, 0.3);
  color: #8b5cf6;
  padding: 0.75rem 1rem;
  border-radius: var(--radius-sm);
  font-size: 0.9rem;
  font-weight: 600;
  margin-bottom: 0.5rem;
}
.magic-icon { flex-shrink: 0; }
</style>