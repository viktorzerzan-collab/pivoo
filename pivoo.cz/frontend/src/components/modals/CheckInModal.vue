<template>
  <BaseModal :show="show" @close="$emit('close')">
    <template #header>
      <h2 class="modal-title"><BeerIcon class="title-icon" :size="28" /> Zaznamenat vypitá piva</h2>
    </template>
    <template #body>
      <form @submit.prevent="$emit('submit')" class="checkin-form">
        
        <div class="magic-scan-wrapper">
          <input type="file" accept="image/*" capture="environment" ref="magicInput" class="hidden-input" @change="processMagicScan" />
          <BaseButton type="button" variant="add" @click="magicInput.click()" :disabled="isScanning" style="width: 100%;">
            <template #icon><CameraIcon :size="18" :class="{'spinning': isScanning}"/></template>
            {{ isScanning ? 'Analyzuji etiketu...' : 'Rozpoznat pivo z etikety/plechovky' }}
          </BaseButton>
        </div>

        <div v-if="magicMessage" class="magic-message" :class="magicMessageType">
          <span>{{ magicMessage }}</span>
          <a v-if="magicAction === 'add_brewery'" href="#" @click.prevent="$emit('magic-add-brewery', scannedAiData)" class="magic-link">
            Přidat nový pivovar
          </a>
          <a v-if="magicAction === 'add_beer'" href="#" @click.prevent="$emit('magic-add-beer', scannedAiData)" class="magic-link">
            Přidat pivo do katalogu
          </a>
        </div>

        <BaseDatePicker v-model="form.consumed_at" label="Kdy to bylo?" />

        <div class="location-detect-wrapper">
          <BaseSelect v-model="form.location_id" label="Kde to bylo?" searchable required style="flex: 1;">
            <option disabled value="">-- Vyber lokaci --</option>
            <option v-for="loc in sortedLocations" :key="loc.id" :value="loc.id">
              {{ loc.is_favorite ? '⭐' : '📍' }} {{ loc.name }}
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
            Přidat nový podnik zde
          </a>
        </div>

        <BaseSelect v-model="form.brewery_id" label="Pivovar" searchable required>
          <option disabled value="">-- Vyber pivovar --</option>
          <option v-for="brewery in sortedBreweries" :key="brewery.id" :value="brewery.id">
            {{ brewery.is_favorite ? '⭐' : '🏭' }} {{ brewery.name }}
          </option>
        </BaseSelect>

        <BaseSelect v-model="form.beer_id" label="Které pivo jsi pil?" :disabled="!form.brewery_id" searchable required>
          <option disabled value="">-- Vyber pivo --</option>
          <option v-for="beer in sortedBeers" :key="beer.id" :value="beer.id">
            {{ beer.is_favorite ? '⭐' : '🍺' }} {{ beer.name }}
          </option>
        </BaseSelect>

        <div class="form-row">
          <BaseSelect class="half" v-model="form.packaging" label="Forma balení" required>
            <option value="točené">Točené</option>
            <option value="lahev">Lahev</option>
            <option value="plechovka">Plechovka</option>
            <option value="pet">PET lahev</option>
            <option value="sud">Soukromý sud</option>
          </BaseSelect>

          <div class="half">
            <BaseSelect v-model="volumeMode" label="Objem">
              <option value="0.20">Sklenička (0.2l)</option>
              <option value="0.30">Malé (0.3l)</option>
              <option value="0.40">Šnyt (0.4l)</option>
              <option value="0.50">Velké (0.5l)</option>
              <option value="1.00">Tuplák (1.0l)</option>
              <option value="custom">Vlastní...</option>
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
            label="Zadej objem (litry)" 
            placeholder="např. 0.25"
            required
          />
        </div>

        <div class="form-row">
          <BaseInput class="half" v-model="form.quantity" type="number" min="1" label="Počet vypitých kusů" required />
          <div class="half"></div>
        </div>

        <div class="form-row align-end">
          <div class="half" style="display: flex; gap: 0.5rem;">
            <BaseInput 
              style="flex: 2;"
              v-model="form.price" 
              type="number" 
              step="0.01" 
              label="Cena za kus" 
              :disabled="form.is_free" 
            />
            <BaseSelect 
              style="flex: 1;"
              v-model="form.currency" 
              label="Měna" 
              :disabled="form.is_free"
              :searchable="false"
            >
              <option value="CZK">CZK</option>
              <option value="EUR">EUR</option>
              <option value="USD">USD</option>
              <option value="PLN">PLN</option>
              <option value="HUF">HUF</option>
              <option value="GBP">GBP</option>
              <option value="AUD">AUD</option>
            </BaseSelect>
          </div>
          <div class="half">
            <BaseCheckbox 
              v-model="form.is_free" 
              label="Neplatil jsem" 
            />
          </div>
        </div>

        <div class="form-row">
          <div class="rating-box half">
            <label class="input-label">Hodnocení piva</label>
            <StarRating v-model="form.rating_beer" />
          </div>

          <div v-if="showCareRating" class="rating-box half">
            <label class="input-label">Obsluha a péče</label>
            <StarRating v-model="form.rating_care" />
          </div>
          <div v-else class="half"></div>
        </div>

        <BaseInput v-model="form.note" label="Poznámka" placeholder="Jaké bylo?" />

        <BaseButton type="submit" variant="primary" style="margin-top: 1rem; width: 100%;">
          Zapsat do deníčku
        </BaseButton>

      </form>
    </template>
  </BaseModal>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { BeerIcon, CameraIcon } from 'lucide-vue-next'
import BaseModal from '../BaseModal.vue'
import BaseInput from '../BaseInput.vue'
import BaseButton from '../BaseButton.vue'
import BaseSelect from '../BaseSelect.vue'
import BaseDatePicker from '../BaseDatePicker.vue'
import StarRating from '../StarRating.vue'
import BaseCheckbox from '../BaseCheckbox.vue'
import GeoLocateButton from '../GeoLocateButton.vue'
import { apiFetch } from '../../api'

const props = defineProps({ 
  show: Boolean, 
  breweries: Array,
  beers: Array, 
  locations: Array, 
  form: Object 
})

const emit = defineEmits(['close', 'submit', 'open-add-location', 'magic-add-brewery', 'magic-add-beer'])

const volumeMode = ref(props.form.volume)
const customVolume = ref('')

const isLocating = ref(false)
const locationMessage = ref('')
const locationMessageType = ref('')
const tempCoords = ref(null)

// --- AI SKEN ETIKETY STAVY A LOGIKA ---
const magicInput = ref(null)
const isScanning = ref(false)
const magicMessage = ref('')
const magicMessageType = ref('')
const magicAction = ref('')
const scannedAiData = ref(null)

// Pomocná funkce pro vyčištění textu (odstraní diakritiku, mezery, převede na malá písmena)
const normalizeStr = (str) => {
  if (!str) return '';
  return str.normalize("NFD").replace(/[\u0300-\u036f]/g, "").toLowerCase().trim();
}

const processMagicScan = async (event) => {
  const file = event.target.files[0]
  if (!file) return

  isScanning.value = true
  magicMessage.value = ''
  magicAction.value = ''
  scannedAiData.value = null

  const formData = new FormData()
  formData.append('image', file)

  try {
    const res = await apiFetch('/analyze_beer.php', {
      method: 'POST',
      body: formData,
      timeout: 30000 
    })

    if (res.status === 'success' && res.data) {
      const ai = res.data
      scannedAiData.value = ai

      if (ai.is_new_brewery || !ai.brewery_id) {
         magicMessageType.value = 'warning'
         magicMessage.value = `Pivovar "${ai.brewery_name}" neznáme.`
         magicAction.value = 'add_brewery'
      } else {
         const aiBeerNorm = normalizeStr(ai.beer_name);
         
         // 1. Pokus: Hledáme pivo se stejným ID pivovaru
         let matchingBeer = props.beers.find(b => {
            const bNameNorm = normalizeStr(b.name);
            return b.brewery_id == ai.brewery_id &&
                   (bNameNorm === aiBeerNorm || bNameNorm.includes(aiBeerNorm) || aiBeerNorm.includes(bNameNorm));
         })

         // 2. Záchranný pokus: AI se mohla splést v ID pivovaru. Hledáme čistě podle názvu v celé databázi.
         if (!matchingBeer && aiBeerNorm.length > 3) {
            matchingBeer = props.beers.find(b => {
               const bNameNorm = normalizeStr(b.name);
               return bNameNorm === aiBeerNorm || bNameNorm.includes(aiBeerNorm) || aiBeerNorm.includes(bNameNorm);
            })
            // Pokud jsme ho našli, vnutíme AI správné ID pivovaru z databáze
            if (matchingBeer) {
               ai.brewery_id = matchingBeer.brewery_id;
               ai.brewery_name = props.breweries.find(br => br.id == matchingBeer.brewery_id)?.name || ai.brewery_name;
            }
         }

         if (matchingBeer) {
            props.form.brewery_id = ai.brewery_id
            setTimeout(() => {
                props.form.beer_id = matchingBeer.id
            }, 100)
            magicMessageType.value = 'success'
            magicMessage.value = `Nalezeno: ${ai.brewery_name} - ${matchingBeer.name}`
         } else {
            props.form.brewery_id = ai.brewery_id
            magicMessageType.value = 'warning'
            magicMessage.value = `Pivo "${ai.beer_name}" od tohoto pivovaru ještě není v katalogu.`
            magicAction.value = 'add_beer'
         }
      }
    } else {
      magicMessageType.value = 'error'
      magicMessage.value = res.message || 'Chyba při analýze etikety.'
    }
  } catch (e) {
    magicMessageType.value = 'error'
    magicMessage.value = 'Chyba AI: ' + e.message
  } finally {
    isScanning.value = false
    if (magicInput.value) magicInput.value.value = ''
  }
}

watch(volumeMode, (newVal) => {
  if (newVal !== 'custom') {
    props.form.volume = newVal
  } else {
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
    props.form.price = ''
  }
})

watch(() => props.show, (newVal) => {
  if (newVal) {
    locationMessage.value = ''
    magicMessage.value = '' 
    const currentVol = props.form.volume
    const standardVolumes = ['0.20', '0.30', '0.40', '0.50', '1.00']
    
    if (standardVolumes.includes(currentVol)) {
      volumeMode.value = currentVol
    } else if (currentVol) {
      volumeMode.value = 'custom'
      customVolume.value = currentVol
    }

    if (!props.form.consumed_at) {
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
      props.form.currency = 'CZK'
    }
  }
})

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

      props.locations.forEach(loc => {
        if (loc.lat && loc.lng) {
          const dist = calculateDistance(lat, lng, loc.lat, loc.lng)
          if (dist < minDistance) {
            minDistance = dist
            nearestLoc = loc
          }
        }
      })

      if (nearestLoc && minDistance <= 0.03) {
        props.form.location_id = nearestLoc.id
        locationMessage.value = `📍 Nalezeno: ${nearestLoc.name} (${(minDistance * 1000).toFixed(0)} m)`
        locationMessageType.value = 'success'
      } else {
        props.form.location_id = ''
        locationMessage.value = 'Žádný známý podnik v okolí 30m.'
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

const sortByFavorite = (a, b) => (b.is_favorite || 0) - (a.is_favorite || 0);

const sortedLocations = computed(() => {
  return [...props.locations].sort(sortByFavorite);
})

const sortedBreweries = computed(() => {
  return [...props.breweries].sort(sortByFavorite);
})

const sortedBeers = computed(() => {
  if (!props.form.brewery_id) return []
  const filtered = props.beers.filter(b => b.brewery_id == props.form.brewery_id)
  return [...filtered].sort(sortByFavorite)
})

watch(() => props.form.brewery_id, () => {
  props.form.beer_id = ''
})

const showCareRating = computed(() => {
  const loc = props.locations.find(l => l.id == props.form.location_id)
  return loc ? loc.type === 'hospoda' : false
})

watch(() => props.form.location_id, () => {
  if (!showCareRating.value) {
    props.form.rating_care = 0
  }
})
</script>

<style scoped>
.modal-title { display: flex; align-items: center; gap: 0.5rem; margin: 0; color: var(--text-main); font-size: 1.5rem; transition: color 0.5s ease; }
.title-icon { color: var(--primary); }
.checkin-form { display: flex; flex-direction: column; gap: 1.25rem; }
.form-row { display: flex; gap: 1rem; }
.half { flex: 1; }

.location-detect-wrapper { display: flex; align-items: flex-end; gap: 0.5rem; }

.location-message { font-size: 0.85rem; padding: 0.5rem 0.75rem; border-radius: 6px; font-weight: 600; margin-top: -0.5rem; }
.location-message.success { background-color: rgba(16, 185, 129, 0.1); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.2); }
.location-message.warning { background-color: rgba(245, 158, 11, 0.1); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.2); display: flex; justify-content: space-between; align-items: center; }
.location-message.error { background-color: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.2); }

.add-loc-link { color: var(--blue); text-decoration: underline; cursor: pointer; }
.add-loc-link:hover { color: var(--blue-hover); }

/* --- AI SKEN CSS --- */
.magic-scan-wrapper { margin-bottom: 0.5rem; margin-top: 0.5rem; }
.hidden-input { display: none; }
.magic-message { font-size: 0.85rem; padding: 0.75rem; border-radius: 8px; font-weight: 600; margin-top: -0.5rem; margin-bottom: 0.5rem; display: flex; flex-direction: column; gap: 0.4rem; }
.magic-message.success { background-color: rgba(16, 185, 129, 0.1); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.2); }
.magic-message.warning { background-color: rgba(245, 158, 11, 0.1); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.2); }
.magic-message.error { background-color: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.2); }
.magic-link { color: var(--blue); text-decoration: underline; cursor: pointer; align-self: flex-start; }
.magic-link:hover { color: var(--blue-hover); }
.spinning { animation: spin 1.5s linear infinite; }
@keyframes spin { 100% { transform: rotate(360deg); } }

.align-end { align-items: flex-end; }
.rating-box { display: flex; flex-direction: column; gap: 0.4rem; justify-content: center; }
.input-label { font-size: 0.9rem; font-weight: 600; color: var(--text-muted); transition: color 0.5s ease; }

@media (max-width: 600px) { 
  .form-row { flex-direction: column; gap: 1.25rem; } 
  .half:empty { display: none; }
  .align-end { align-items: stretch; }
}
</style>