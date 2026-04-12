<template>
  <BaseModal :show="show" @close="$emit('close')">
    <template #header><h2 class="modal-title">🍺 Nové pivo do katalogu</h2></template>
    <template #body>
      <form @submit.prevent="$emit('submit')" class="checkin-form">
        
        <BaseInput v-model="form.name" label="Název piva" required />
        
        <BaseSelect v-model="form.brewery_id" label="Pivovar" required>
          <option disabled value="">-- Vyber pivovar --</option>
          <option v-for="brewery in breweries" :key="brewery.id" :value="brewery.id">
            {{ brewery.name }}
          </option>
        </BaseSelect>

        <BaseSelect v-model="form.style" label="Pivní styl" required>
          <option disabled value="">-- Vyber styl --</option>
          <option v-for="style in styles" :key="style.id" :value="style.name">
            {{ style.name }}
          </option>
        </BaseSelect>

        <div class="form-row">
          <BaseInput class="half" v-model="form.epm" type="number" step="0.1" label="Stupňovitost (EPM)" />
          <BaseInput class="half" v-model="form.abv" type="number" step="0.1" label="Alkohol (ABV %)" />
        </div>
        
        <BaseButton type="submit" variant="add" style="margin-top: 1rem; width: 100%;">
          Uložit do katalogu
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
  breweries: Array, 
  styles: Array, 
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