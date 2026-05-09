<template>
  <BaseModal :show="show" @close="$emit('close')">
    <template #header>
      <h2 class="modal-title"><PencilIcon class="title-icon" :size="24" /> {{ $t('modals.checkin.title_edit') }}</h2>
    </template>
    <template #body>
      <form @submit.prevent="$emit('submit')" class="checkin-form">
        
        <BaseDatePicker v-model="form.consumed_at" :label="$t('modals.checkin.date_label')" />

        <BaseSelect v-model="form.location_id" :label="$t('modals.checkin.location_label')" searchable required>
          <option disabled value="">{{ $t('modals.checkin.select_location') }}</option>
          <option v-for="loc in catalogStore.allLocations" :key="loc.id" :value="loc.id">
            {{ translateLocation(loc.name) }}
          </option>
        </BaseSelect>

        <BaseSelect v-model="form.brewery_id" :label="$t('modals.checkin.brewery_label')" searchable required>
          <option disabled value="">{{ $t('modals.checkin.select_brewery') }}</option>
          <option v-for="brewery in catalogStore.allBreweries" :key="brewery.id" :value="brewery.id">
            {{ brewery.name }}
          </option>
        </BaseSelect>

        <BaseSelect v-model="form.beer_id" :label="$t('modals.checkin.beer_label')" :disabled="!form.brewery_id" searchable required>
          <option disabled value="">{{ $t('modals.checkin.select_beer') }}</option>
          <option v-for="beer in filteredBeers" :key="beer.id" :value="beer.id">
            {{ beer.name }}
          </option>
        </BaseSelect>

        <div class="form-row">
          <BaseSelect class="half" v-model="form.packaging" :label="$t('modals.checkin.packaging_label')" required>
            <option value="točené">{{ $t('modals.checkin.packaging.draft') }}</option>
            <option value="lahev">{{ $t('modals.checkin.packaging.bottle') }}</option>
            <option value="plechovka">{{ $t('modals.checkin.packaging.can') }}</option>
            <option value="pet">{{ $t('modals.checkin.packaging.pet') }}</option>
            <option value="sud">{{ $t('modals.checkin.packaging.keg') }}</option>
          </BaseSelect>

          <div class="half">
            <BaseSelect v-model="volumeMode" :label="$t('modals.checkin.volume_label')">
              <option value="0.20">{{ $t('modals.checkin.volume.glass') }}</option>
              <option value="0.30">{{ $t('modals.checkin.volume.small') }}</option>
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
          <BaseInput class="half" v-model="form.quantity" type="number" min="1" :label="$t('modals.checkin.quantity_edit_label')" required />
          <div class="half"></div>
        </div>

        <div class="form-row align-end">
          <div class="half" style="display: flex; gap: 0.5rem;">
            <BaseInput 
              style="flex: 2;"
              v-model="form.original_price" 
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

        <BaseInput v-model="form.note" :label="$t('modals.checkin.note_label')" :placeholder="$t('modals.checkin.note_placeholder')" />

        <BaseButton type="submit" variant="edit" style="margin-top: 1rem; width: 100%;">
          {{ $t('modals.checkin.save_edit') }}
        </BaseButton>

      </form>
    </template>
  </BaseModal>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { PencilIcon } from 'lucide-vue-next'
import { useI18n } from 'vue-i18n'
import BaseModal from '../BaseModal.vue'
import BaseInput from '../BaseInput.vue'
import BaseButton from '../BaseButton.vue'
import BaseSelect from '../BaseSelect.vue'
import BaseDatePicker from '../BaseDatePicker.vue'
import StarRating from '../StarRating.vue'
import BaseCheckbox from '../BaseCheckbox.vue'

import { useCatalogStore } from '../../stores/catalog'
import { useAuthStore } from '../../stores/auth'

const catalogStore = useCatalogStore()
const authStore = useAuthStore()
const { t, te } = useI18n()

const props = defineProps({ 
  show: Boolean, 
  form: Object 
})
defineEmits(['close', 'submit'])

const volumeMode = ref('')
const customVolume = ref('')

const translateLocation = (val) => {
  if (!val) return val
  const key = `dynamic.locations.${val}`
  return te(key) ? t(key) : val
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
      props.form.currency = authStore.defaultCurrency || 'CZK'
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