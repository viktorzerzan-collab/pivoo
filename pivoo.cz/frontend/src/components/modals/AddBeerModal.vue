<template>
  <BaseModal :show="show" @close="$emit('close')">
    <template #header><h2 class="modal-title">🍺 Nové pivo do katalogu</h2></template>
    <template #body>
      <form @submit.prevent="$emit('submit')" class="checkin-form">
        <BaseInput v-model="form.name" label="Název piva" required />
        <div class="form-group"><label>Pivovar</label><select v-model="form.brewery_id" required><option v-for="brewery in breweries" :key="brewery.id" :value="brewery.id">{{ brewery.name }}</option></select></div>
        <BaseInput v-model="form.style" label="Styl piva (Volitelné)" />
        <div class="form-row">
          <div class="form-group half"><BaseInput v-model="form.epm" type="number" step="0.1" label="Stupňovitost (EPM)" /></div>
          <div class="form-group half"><BaseInput v-model="form.abv" type="number" step="0.1" label="Alkohol (ABV %)" /></div>
        </div>
        <BaseButton type="submit" variant="submit">Přidat pivo</BaseButton>
      </form>
    </template>
  </BaseModal>
</template>

<script setup>
import BaseModal from '../BaseModal.vue'
import BaseInput from '../BaseInput.vue'
import BaseButton from '../BaseButton.vue'

defineProps({ show: Boolean, breweries: Array, form: Object })
defineEmits(['close', 'submit'])
</script>

<style scoped>
.modal-title { margin: 0; color: #1f2937; font-size: 1.5rem; }
.checkin-form { display: flex; flex-direction: column; gap: 1.2rem; }
.form-group label { display: block; font-weight: 600; margin-bottom: 0.4rem; color: #4b5563; font-size: 0.95rem; }
.form-group select { width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 1rem; box-sizing: border-box; outline: none; transition: 0.2s;}
.form-group select:focus { border-color: #eab308; box-shadow: 0 0 0 3px rgba(234, 179, 8, 0.2); }
.form-row { display: flex; gap: 1rem; }
.half { flex: 1; }
@media (max-width: 600px) { .form-row { flex-direction: column; gap: 1.2rem; } }
</style>