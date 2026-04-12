<template>
  <BaseModal :show="show" @close="$emit('close')">
    <template #header><h2 class="modal-title">✏️ Upravit záznam</h2></template>
    <template #body>
      <form @submit.prevent="$emit('submit')" class="checkin-form">
        
        <BaseSelect v-model="form.beer_id" label="Které pivo jsi pil?" required>
          <option disabled value="">-- Vyber pivo z katalogu --</option>
          <option v-for="beer in beers" :key="beer.id" :value="beer.id">
            {{ beer.name }} ({{ beer.brewery_name }})
          </option>
        </BaseSelect>

        <BaseSelect v-model="form.location_id" label="Kde to bylo?" required>
          <option disabled value="">-- Vyber lokaci --</option>
          <option v-for="loc in locations" :key="loc.id" :value="loc.id">
            {{ loc.name }}
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
          <BaseSelect class="half" v-model="form.rating_beer" label="Hodnocení piva">
            <option value="0">Nehodnoceno</option>
            <option value="1">⭐</option>
            <option value="2">⭐⭐</option>
            <option value="3">⭐⭐⭐</option>
            <option value="4">⭐⭐⭐⭐</option>
            <option value="5">⭐⭐⭐⭐⭐</option>
          </BaseSelect>

          <BaseSelect class="half" v-model="form.rating_care" label="Obsluha a péče">
            <option value="0">Nehodnoceno</option>
            <option value="1">⭐</option>
            <option value="2">⭐⭐</option>
            <option value="3">⭐⭐⭐</option>
            <option value="4">⭐⭐⭐⭐</option>
            <option value="5">⭐⭐⭐⭐⭐</option>
          </BaseSelect>
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
import BaseModal from '../BaseModal.vue'
import BaseInput from '../BaseInput.vue'
import BaseButton from '../BaseButton.vue'
import BaseSelect from '../BaseSelect.vue'

defineProps({ 
  show: Boolean, 
  beers: Array, 
  locations: Array, 
  form: Object 
})
defineEmits(['close', 'submit'])
</script>

<style scoped>
.modal-title { margin: 0; color: #1e293b; font-size: 1.5rem; }
.checkin-form { display: flex; flex-direction: column; gap: 1.25rem; }
.form-row { display: flex; gap: 1rem; }
.half { flex: 1; }

@media (max-width: 600px) { 
  .form-row { flex-direction: column; gap: 1.25rem; } 
}
</style>