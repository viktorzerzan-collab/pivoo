<template>
  <BaseModal :show="show" @close="$emit('close')">
    <template #header>
      <h2 class="modal-title">
        <BeerIcon class="title-icon" :size="26" /> 
        {{ isEditing ? 'Upravit pivo' : 'Nové pivo' }}
      </h2>
    </template>
    <template #body>
      <form @submit.prevent="$emit('submit')" class="add-form">
        <BaseInput v-model="form.name" label="Název piva *" required />
        
        <BaseSelect v-model="form.brewery_id" label="Pivovar *" required>
          <option disabled value="">-- Vyber pivovar --</option>
          <option v-for="brewery in breweries" :key="brewery.id" :value="brewery.id">{{ brewery.name }}</option>
        </BaseSelect>

        <BaseSelect v-model="form.style" label="Styl (např. Ležák, IPA)">
          <option value="">-- Bez stylu --</option>
          <option v-for="style in styles" :key="style.id" :value="style.name">{{ style.name }}</option>
        </BaseSelect>

        <div class="form-row">
          <BaseInput v-model="form.epm" type="number" step="0.1" label="EPM (°)" class="half" />
          <BaseInput v-model="form.abv" type="number" step="0.1" label="Alkohol (%)" class="half" />
        </div>

        <BaseButton type="submit" variant="add" style="margin-top: 1rem;">
          Uložit pivo
        </BaseButton>
      </form>
    </template>
  </BaseModal>
</template>

<script setup>
import { BeerIcon } from 'lucide-vue-next'
import BaseModal from '../BaseModal.vue'
import BaseInput from '../BaseInput.vue'
import BaseButton from '../BaseButton.vue'
import BaseSelect from '../BaseSelect.vue'

defineProps({
  show: Boolean,
  isEditing: Boolean,
  breweries: Array,
  styles: Array,
  form: Object
})
defineEmits(['close', 'submit'])
</script>

<style scoped>
.modal-title { display: flex; align-items: center; gap: 0.5rem; margin: 0; color: var(--text-main); font-size: 1.5rem; transition: color 0.5s ease; }
.title-icon { color: var(--blue); }
.add-form { display: flex; flex-direction: column; gap: 1rem; }
.form-row { display: flex; gap: 1rem; }
.half { flex: 1; }
</style>