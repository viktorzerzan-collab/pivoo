<template>
  <BaseModal :show="show" @close="$emit('close')">
    <template #header><h2 class="modal-title">📝 Co jsi právě vypil?</h2></template>
    <template #body>
      <form @submit.prevent="$emit('submit')" class="checkin-form">
        <div class="form-group"><label>Jaké pivo?</label><select v-model="form.beer_id" required><option v-for="beer in beers" :key="beer.id" :value="beer.id">{{ beer.name }} ({{ beer.brewery_name }})</option></select></div>
        <div class="form-group"><label>Kde to bylo?</label><select v-model="form.location_id" required><option v-for="loc in locations" :key="loc.id" :value="loc.id">{{ loc.name }}</option></select></div>
        <div class="form-row">
          <div class="form-group half"><label>Objem</label><select v-model="form.volume" required><option value="0.30">Malé (0.3l)</option><option value="0.50">Velké (0.5l)</option><option value="1.00">Tuplák (1.0l)</option></select></div>
          <div class="form-group half"><BaseInput v-model="form.quantity" type="number" min="1" label="Počet kusů" required /></div>
        </div>
        <div class="form-group">
          <BaseInput v-model="form.price" type="number" label="Cena za 1 kus (Kč)" placeholder="Např. 55" />
          <small v-if="form.price && form.quantity > 1" class="price-calc">Celkem: <strong>{{ form.price * form.quantity }} Kč</strong></small>
        </div>
        <div class="form-group"><label>Tvoje hodnocení</label><StarRating v-model="form.rating_beer" /></div>
        <div class="form-group"><label>Poznámka (Volitelné)</label><textarea v-model="form.note" rows="3"></textarea></div>
        <BaseButton type="submit" variant="submit">Uložit do deníčku</BaseButton>
      </form>
    </template>
  </BaseModal>
</template>

<script setup>
import BaseModal from '../BaseModal.vue'
import BaseInput from '../BaseInput.vue'
import StarRating from '../StarRating.vue'
import BaseButton from '../BaseButton.vue'

defineProps({ show: Boolean, beers: Array, locations: Array, form: Object })
defineEmits(['close', 'submit'])
</script>

<style scoped>
.modal-title { margin: 0; color: #1f2937; font-size: 1.5rem; }
.checkin-form { display: flex; flex-direction: column; gap: 1.2rem; }
.form-group label { display: block; font-weight: 600; margin-bottom: 0.4rem; color: #4b5563; font-size: 0.95rem; }
.form-group select, .form-group textarea { width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 1rem; box-sizing: border-box; font-family: inherit; outline: none; transition: 0.2s;}
.form-group textarea { resize: vertical; }
.form-group select:focus, .form-group textarea:focus { border-color: #eab308; box-shadow: 0 0 0 3px rgba(234, 179, 8, 0.2); }
.form-row { display: flex; gap: 1rem; }
.half { flex: 1; }
.price-calc { color: #6b7280; margin-top: 0.25rem; display: block; }
@media (max-width: 600px) { .form-row { flex-direction: column; gap: 1.2rem; } }
</style>