<template>
  <BaseModal :show="show" @close="$emit('close')">
    <template #header>
      <h2 class="modal-title"><PencilIcon class="title-icon" :size="24" /> Upravit záznam</h2>
    </template>
    <template #body>
      <form @submit.prevent="$emit('submit')" class="checkin-form">
        
        <BaseDatePicker v-model="form.consumed_at" label="Kdy to bylo?" />

        <BaseSelect v-model="form.location_id" label="Kde to bylo?" searchable required>
          <option disabled value="">-- Vyber lokaci --</option>
          <option v-for="loc in catalogStore.allLocations" :key="loc.id" :value="loc.id">
            {{ loc.name }}
          </option>
        </BaseSelect>

        <BaseSelect v-model="form.brewery_id" label="Pivovar" searchable required>
          <option disabled value="">-- Vyber pivovar --</option>
          <option v-for="brewery in catalogStore.allBreweries" :key="brewery.id" :value="brewery.id">
            {{ brewery.name }}
          </option>
        </BaseSelect>

        <BaseSelect v-model="form.beer_id" label="Které pivo jsi pil?" :disabled="!form.brewery_id" searchable required>
          <option disabled value="">-- Vyber pivo --</option>
          <option v-for="beer in filteredBeers" :key="beer.id" :value="beer.id">
            {{ beer.name }}
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
          <BaseInput class="half" v-model="form.quantity" type="number" min="1" label="Počet kusů" required />
          <div class="half"></div>
        </div>

        <div class="form-row align-end">
          <div class="half" style="display: flex; gap: 0.5rem;">
            <BaseInput 
              style="flex: 2;"
              v-model="form.original_price" 
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

        <BaseButton type="submit" variant="edit" style="margin-top: 1rem; width: 100%;">
          Uložit změny
        </BaseButton>

      </form>
    </template>
  </BaseModal>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { PencilIcon } from 'lucide-vue-next'
import BaseModal from '../BaseModal.vue'
import BaseInput from '../BaseInput.vue'
import BaseButton from '../BaseButton.vue'
import BaseSelect from '../BaseSelect.vue'
import BaseDatePicker from '../BaseDatePicker.vue'
import StarRating from '../StarRating.vue'
import BaseCheckbox from '../BaseCheckbox.vue'

import { useCatalogStore } from '../../stores/catalog'
const catalogStore = useCatalogStore()

const props = defineProps({ 
  show: Boolean, 
  form: Object 
})
defineEmits(['close', 'submit'])

const volumeMode = ref('')
const customVolume = ref('')

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
    props.form.original_price = ''
    props.form.price = ''
  }
})

watch(() => props.show, (isOpen) => {
  if (isOpen) {
    const currentVol = String(props.form.volume)
    const standardVolumes = ['0.20', '0.30', '0.40', '0.50', '1.00']
    
    if (standardVolumes.includes(currentVol)) {
      volumeMode.value = currentVol
      customVolume.value = ''
    } else {
      volumeMode.value = 'custom'
      customVolume.value = currentVol
    }

    if (!props.form.currency) {
      props.form.currency = 'CZK'
    }
    if (!props.form.original_price && props.form.price) {
      props.form.original_price = props.form.price
    }
  }
})

const filteredBeers = computed(() => {
  if (!props.form.brewery_id) return []
  return catalogStore.allBeers.filter(b => b.brewery_id == props.form.brewery_id)
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
.title-icon { color: var(--orange); }
.checkin-form { display: flex; flex-direction: column; gap: 1.25rem; }
.form-row { display: flex; gap: 1rem; }
.half { flex: 1; }

.align-end { align-items: flex-end; }

.rating-box { display: flex; flex-direction: column; gap: 0.4rem; justify-content: center; }
.input-label { font-size: 0.9rem; font-weight: 600; color: var(--text-muted); transition: color 0.3s ease; }

@media (max-width: 600px) { 
  .form-row { flex-direction: column; gap: 1.25rem; } 
  .half:empty { display: none; }
  .align-end { align-items: stretch; }
}
</style>