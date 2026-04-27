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
        
        <div v-if="duplicateWarning" class="duplicate-warning">
          <AlertTriangleIcon :size="20" class="warning-icon" />
          <div class="warning-text">
            {{ duplicateWarning }}
          </div>
        </div>

        <BaseInput v-model="form.name" label="Název podniku *" required />
        
        <BaseSelect v-model="form.type" label="Typ podniku" required>
          <option value="hospoda">Hospoda / Bar</option>
          <option value="pivoteka">Pivotéka</option>
          <option value="obchod">Obchod</option>
          <option value="jine">Jiné</option>
        </BaseSelect>

        <template v-if="form.type !== 'jine'">
          
          <div class="map-container-wrapper">
            <label class="form-label d-block mb-2">Poloha na mapě (přetáhněte špendlík pro automatické načtení adresy)</label>
            <div ref="mapContainerRef" class="admin-map-element"></div>
            <div class="coords-display">
              GPS: {{ form.lat || '???' }}, {{ form.lng || '???' }}
              <span v-if="isGeocoding" style="color: var(--blue); margin-left: 10px;">(Načítám adresu z mapy...)</span>
            </div>
          </div>

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
import { MapPinIcon, AlertTriangleIcon } from 'lucide-vue-next'
import BaseModal from '../BaseModal.vue'
import BaseInput from '../BaseInput.vue'
import BaseButton from '../BaseButton.vue'
import BaseSelect from '../BaseSelect.vue'
import OpeningHoursInput from '../OpeningHoursInput.vue'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'

// Přidáno ruční načítání obrázků pro Leaflet z dist složky
import markerIconUrl from 'leaflet/dist/images/marker-icon.png'
import markerShadowUrl from 'leaflet/dist/images/marker-shadow.png'

// OPRAVENO: Import store pro zjišťování duplicit (přidáno ../ navíc)
import { useCatalogStore } from '../../stores/catalog'

const props = defineProps({
  show: Boolean,
  isEditing: Boolean,
  form: Object,
  countries: Array
})
const emit = defineEmits(['close', 'submit'])

const catalogStore = useCatalogStore()

const map = ref(null)
const marker = ref(null)
const mapContainerRef = ref(null)

// Stavy pro duplicity a geocoding
const duplicateWarning = ref('')
const isGeocoding = ref(false)

// Vzorec pro výpočet vzdálenosti
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

// Funkce pro kontrolu duplicit
const checkDuplicates = (lat, lng) => {
  const duplicates = []
  catalogStore.locations.forEach(loc => {
    if (loc.lat && loc.lng && loc.id !== props.form.id) { // Při úpravě ignorujeme sám sebe
      const dist = calculateDistance(lat, lng, loc.lat, loc.lng)
      if (dist <= 0.05) { // 50 metrů
        duplicates.push(loc.name)
      }
    }
  })

  if (duplicates.length > 0) {
    duplicateWarning.value = `Pozor! V okruhu 50 metrů již existuje: ${duplicates.join(', ')}. Jste si jisti, že vytváříte zcela nový podnik?`
  } else {
    duplicateWarning.value = ''
  }
}

// Zjišťování adresy z GPS přes Nominatim API
const fetchAddressFromGPS = async (lat, lng) => {
  if (props.isEditing) return // Při editaci nepřepisujeme ručně upravenou adresu

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

const destroyMap = () => {
  if (map.value) {
    map.value.remove()
    map.value = null
    marker.value = null
  }
}

const initMap = () => {
  if (props.form.type === 'jine') return;
  if (!mapContainerRef.value) return;

  destroyMap()

  const lat = parseFloat(props.form.lat) || 49.8175
  const lng = parseFloat(props.form.lng) || 15.4730
  const zoom = props.form.lat ? 16 : 6

  map.value = L.map(mapContainerRef.value).setView([lat, lng], zoom)

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap'
  }).addTo(map.value)

  // Aplikování správných ikon pro Leaflet ve Vite
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
    
    checkDuplicates(props.form.lat, props.form.lng)
    fetchAddressFromGPS(props.form.lat, props.form.lng)
  })

  map.value.on('click', (e) => {
    const newPos = e.latlng
    marker.value.setLatLng(newPos)
    props.form.lat = newPos.lat.toFixed(8)
    props.form.lng = newPos.lng.toFixed(8)
    
    checkDuplicates(props.form.lat, props.form.lng)
    fetchAddressFromGPS(props.form.lat, props.form.lng)
  })

  setTimeout(() => {
    if (map.value) map.value.invalidateSize()
  }, 250)
}

watch(() => props.show, (isVisible) => {
  if (isVisible) {
    duplicateWarning.value = ''
    nextTick(() => {
      initMap()
      
      // Pokud se modál otevře a přišly nám už souřadnice (např. z CheckIn modálu), rovnou načteme adresu
      if (!props.isEditing && props.form.lat && props.form.lng) {
        checkDuplicates(props.form.lat, props.form.lng)
        fetchAddressFromGPS(props.form.lat, props.form.lng)
      }
    })
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

/* Styly pro varování před duplicitou */
.duplicate-warning {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  background-color: rgba(245, 158, 11, 0.1);
  border: 1px solid rgba(245, 158, 11, 0.4);
  padding: 1rem;
  border-radius: 8px;
  color: #d97706;
}
.warning-icon { flex-shrink: 0; margin-top: 2px; }
.warning-text { font-size: 0.9rem; font-weight: 600; line-height: 1.4; }

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