<template>
  <BaseModal :show="show" @close="$emit('close')">
    <template #header><h2 class="modal-title">✏️ Upravit záznam</h2></template>
    <template #body>
      <form @submit.prevent="$emit('submit')" class="checkin-form">
        
        <BaseDatePicker v-model="form.consumed_at" label="Kdy to bylo?" />

        <BaseSelect v-model="form.location_id" label="Kde to bylo?" required>
          <option disabled value="">-- Vyber lokaci --</option>
          <option v-for="loc in locations" :key="loc.id" :value="loc.id">
            {{ loc.name }}
          </option>
        </BaseSelect>

        <BaseSelect v-model="form.brewery_id" label="Pivovar" required>
          <option disabled value="">-- Vyber pivovar --</option>
          <option v-for="brewery in breweries" :key="brewery.id" :value="brewery.id">
            {{ brewery.name }}
          </option>
        </BaseSelect>

        <BaseSelect v-model="form.beer_id" label="Které pivo jsi pil?" :disabled="!form.brewery_id" required>
          <option disabled value="">-- Vyber pivo --</option>
          <option v-for="beer in filteredBeers" :key="beer.id" :value="beer.id">
            {{ beer.name }}
          </option>
        </BaseSelect>

        <div class="form-row">
          <BaseSelect class="half" v-model="form.packaging" label="Forma balení" required>
            <option value="točené">🍺 Točené</option>
            <option value="lahev">🍾 Lahev</option>
            <option value="plechovka">🥫 Plechovka</option>
            <option value="pet">🧴 PET lahev</option>
            <option value="sud">🛢️ Soukromý sud</option>
          </BaseSelect>

          <BaseSelect class="half" v-model="form.volume" label="Objem">
            <option value="0.30">Malé (0.3l)</option>
            <option value="0.40">Šnyt (0.4l)</option>
            <option value="0.50">Velké (0.5l)</option>
            <option value="1.00">Tuplák (1.0l)</option>
          </BaseSelect>
        </div>

        <div class="form-row">
          <BaseInput class="half" v-model="form.quantity" type="number" min="1" label="Počet" required />
          <BaseInput class="half" v-model="form.price" type="number" step="1" label="Cena za kus (Kč)" />
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
import { computed, watch } from 'vue'
import BaseModal from '../BaseModal.vue'
import BaseInput from '../BaseInput.vue'
import BaseButton from '../BaseButton.vue'
import BaseSelect from '../BaseSelect.vue'
import BaseDatePicker from '../BaseDatePicker.vue'
// IMPORT HVĚZDIČEK
import StarRating from '../StarRating.vue'

const props = defineProps({ 
  show: Boolean, 
  breweries: Array,
  beers: Array, 
  locations: Array, 
  form: Object 
})
defineEmits(['close', 'submit'])

const filteredBeers = computed(() => {
  if (!props.form.brewery_id) return []
  return props.beers.filter(b => b.brewery_id == props.form.brewery_id)
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
.modal-title { margin: 0; color: #1e293b; font-size: 1.5rem; }
.checkin-form { display: flex; flex-direction: column; gap: 1.25rem; }
.form-row { display: flex; gap: 1rem; }
.half { flex: 1; }

.rating-box { display: flex; flex-direction: column; gap: 0.4rem; justify-content: center; }
.input-label { font-size: 0.9rem; font-weight: 600; color: #475569; }

@media (max-width: 600px) { 
  .form-row { flex-direction: column; gap: 1.25rem; } 
  .half:empty { display: none; }
}
</style>