<template>
  <BaseModal :show="show" @close="$emit('close')">
    <template #header>
      <h2 class="modal-title">
        <BarcodeIcon class="title-icon" :size="24" />
        {{ isEditing ? $t('admin.barcode.edit_title') : $t('admin.barcode.add_title') }}
      </h2>
    </template>
    
    <template #body>
      <form @submit.prevent="handleSubmit" class="modal-form">
        
        <BaseInput 
          v-model="form.ean" 
          :label="$t('admin.barcode.ean')" 
          placeholder="Např. 8594000130001" 
          required 
          autofocus
        />

        <BaseSelect 
          v-model="selectedBreweryId" 
          :label="$t('admin.barcode.brewery')" 
          searchable
          required
        >
          <option value="">{{ $t('admin.barcode.select_brewery') }}</option>
          <option v-for="b in breweries" :key="b.id" :value="b.id">{{ b.name }}</option>
        </BaseSelect>

        <BaseSelect 
          v-model="form.beer_id" 
          :label="$t('admin.barcode.beer')" 
          searchable
          :disabled="!selectedBreweryId"
          required
        >
          <option value="">{{ $t('admin.barcode.select_beer') }}</option>
          <option v-for="beer in filteredBeers" :key="beer.id" :value="beer.id">{{ beer.name }}</option>
        </BaseSelect>

        <div class="form-row">
          <BaseSelect 
            v-model="form.packaging" 
            :label="$t('admin.barcode.packaging')" 
            class="half"
            required
          >
            <option value="láhev">{{ $t('packaging.bottle') }}</option>
            <option value="plechovka">{{ $t('packaging.can') }}</option>
            <option value="PET">{{ $t('packaging.pet') }}</option>
            <option value="sud">{{ $t('packaging.keg') }}</option>
          </BaseSelect>

          <BaseSelect 
            v-model="form.volume" 
            :label="$t('admin.barcode.volume')" 
            class="half"
            required
          >
            <option value="0.33">0.33 l</option>
            <option value="0.50">0.50 l</option>
            <option value="0.75">0.75 l</option>
            <option value="1.00">1.00 l</option>
            <option value="1.50">1.50 l</option>
            <option value="5.00">5.00 l (Soudek)</option>
          </BaseSelect>
        </div>

        <div class="form-row" style="margin-top: 1rem;">
          <BaseButton type="button" variant="secondary" class="half justify-center" @click="$emit('close')">
            {{ $t('buttons.cancel') }}
          </BaseButton>
          <BaseButton type="submit" variant="add" class="half justify-center">
            {{ isEditing ? $t('buttons.save') : $t('buttons.add') }}
          </BaseButton>
        </div>
      </form>
    </template>
  </BaseModal>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { BarcodeIcon } from 'lucide-vue-next'
import BaseModal from '../BaseModal.vue'
import BaseInput from '../BaseInput.vue'
import BaseSelect from '../BaseSelect.vue'
import BaseButton from '../BaseButton.vue'

const props = defineProps({
  show: Boolean,
  isEditing: Boolean,
  form: Object,
  breweries: Array,
  beers: Array
})

const emit = defineEmits(['close', 'submit'])

// Pomocný stav pro filtrování piv podle pivovaru
const selectedBreweryId = ref('')

// Pokud se modál otevře (nebo se změní props.form), nastavíme správný pivovar
watch(() => props.show, (isShown) => {
  if (isShown) {
    if (props.form.beer_id) {
      const beer = props.beers.find(b => b.id == props.form.beer_id)
      selectedBreweryId.value = beer ? beer.brewery_id : ''
    } else {
      selectedBreweryId.value = ''
    }
  }
})

// Pokud uživatel ručně změní pivovar v selectu, resetujeme vybrané pivo (pokud staré pivo nepatří pod nový pivovar)
watch(selectedBreweryId, (newBreweryId) => {
  if (!props.show) return
  const currentBeer = props.beers.find(b => b.id == props.form.beer_id)
  if (currentBeer && currentBeer.brewery_id != newBreweryId) {
    props.form.beer_id = ''
  }
})

// Vypočtená vlastnost: Piva pouze pro vybraný pivovar, seřazená podle abecedy
const filteredBeers = computed(() => {
  if (!selectedBreweryId.value) return []
  return props.beers
    .filter(b => b.brewery_id == selectedBreweryId.value)
    .sort((a, b) => (a.name || '').localeCompare(b.name || '', 'cs'))
})

const handleSubmit = () => {
  emit('submit')
}
</script>

<style scoped>
/* Standardizované styly pro modály */
.modal-title { display: flex; align-items: center; gap: 0.5rem; margin: 0; color: var(--text-main); font-size: 1.5rem; }
.title-icon { color: var(--blue); }
.modal-form { display: flex; flex-direction: column; gap: 1.5rem; }
.form-row { display: flex; gap: 1rem; }
.half { flex: 1; }
.justify-center { justify-content: center; }

@media (max-width: 600px) {
  .form-row { flex-direction: column; gap: 1.5rem; }
}
</style>