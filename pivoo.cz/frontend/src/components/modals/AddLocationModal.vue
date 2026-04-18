<template>
  <BaseModal :show="show" @close="$emit('close')" customStyle="max-width: 600px;">
    <template #header>
      <h2 class="modal-title">
        <MapPinIcon class="title-icon" :size="26" />
        {{ isEditing ? 'Upravit podnik' : 'Nový podnik' }}
      </h2>
    </template>
    <template #body>
      <form @submit.prevent="$emit('submit')" class="add-form">
        <BaseInput v-model="form.name" label="Název podniku *" required />
        
        <BaseSelect v-model="form.type" label="Typ podniku" required>
          <option value="hospoda">Hospoda / Bar</option>
          <option value="pivoteka">Pivotéka</option>
          <option value="obchod">Obchod</option>
          <option value="jine">Jiné</option>
        </BaseSelect>

        <template v-if="form.type !== 'jine'">
          <BaseInput v-model="form.address" label="Ulice a číslo popisné" />

          <div class="form-row">
            <BaseInput v-model="form.city" label="Město" class="half" />
            <BaseInput v-model="form.zip_code" label="PSČ" class="half" />
          </div>

          <BaseSelect v-model="form.country_id" label="Země">
            <option v-for="c in countries" :key="c.id" :value="c.id">
              {{ c.name_cz }}
            </option>
          </BaseSelect>

          <div class="map-container-wrapper">
            <label class="form-label d-block mb-2">Poloha na mapě (přetáhněte špendlík)</label>
            <div ref="mapContainerRef" class="admin-map-element"></div>
            <div class="coords-display">
              GPS: {{ form.lat || '???' }}, {{ form.lng || '???' }}
            </div>
          </div>

          <div class="form-row">
            <BaseInput v-model="form.email" type="email" label="E-mail" class="half" />
            <BaseInput v-model="form.phone" label="Telefon" class="half" />
          </div>

          <BaseInput v-model="form.website" type="url" label="Webové stránky" />
          
          <OpeningHoursInput v-model="form.opening_hours" label="Otevírací doba" />
        </template>

        <BaseButton type="submit" variant="add" style="margin-top: 1rem; width: 100%;">
          Uložit podnik
        </BaseButton>
      </form>
    </template>
  </BaseModal>
</template>

<script setup>
import { ref, watch, nextTick, onBeforeUnmount } from 'vue'
import { MapPinIcon } from 'lucide-vue-next'
import BaseModal from '../BaseModal.vue'
import BaseInput from '../BaseInput.vue'
import BaseButton from '../BaseButton.vue'
import BaseSelect from '../BaseSelect.vue'
import OpeningHoursInput from '../OpeningHoursInput.vue'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'

const props = defineProps({
  show: Boolean,
  isEditing: Boolean,
  form: Object,
  countries: Array
})
const emit = defineEmits(['close', 'submit'])

const map = ref(null)
const marker = ref(null)
// OPRAVA: Reference na DOM element
const mapContainerRef = ref(null)

const destroyMap = () => {
  if (map.value) {
    map.value.remove()
    map.value = null
    marker.value = null
  }
}

const initMap = () => {
  if (props.form.type === 'jine') return;
  // Ochrana před chybějícím DOM elementem
  if (!mapContainerRef.value) return;

  destroyMap()

  const lat = parseFloat(props.form.lat) || 49.8175
  const lng = parseFloat(props.form.lng) || 15.4730
  const zoom = props.form.lat ? 16 : 6

  // OPRAVA: Mapu inicializujeme nad ref elementem
  map.value = L.map(mapContainerRef.value).setView([lat, lng], zoom)

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap'
  }).addTo(map.value)

  marker.value = L.marker([lat, lng], { draggable: true }).addTo(map.value)

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

watch(() => props.form.type, (newType) => {
  if (props.show && newType !== 'jine') {
    nextTick(() => initMap())
  }
})

onBeforeUnmount(() => destroyMap())
</script>

<style scoped>
.modal-title { display: flex; align-items: center; gap: 0.5rem; margin: 0; color: var(--text-main); font-size: 1.5rem; transition: color 0.5s ease; }
.title-icon { color: var(--blue); }
.add-form { display: flex; flex-direction: column; gap: 1rem; }
.form-row { display: flex; gap: 1rem; }
.half { flex: 1; }

.map-container-wrapper { margin: 0.5rem 0; }
.form-label { font-size: 0.9rem; font-weight: 600; color: var(--text-main); }
.mb-2 { margin-bottom: 0.5rem; }
.d-block { display: block; }
.admin-map-element {
  height: 250px;
  width: 100%;
  border-radius: 8px;
  border: 1px solid var(--border);
  z-index: 1;
}
.coords-display { font-family: monospace; font-size: 0.8rem; margin-top: 5px; color: var(--text-muted); }

@media (max-width: 600px) {
  .form-row { flex-direction: column; }
}
</style>