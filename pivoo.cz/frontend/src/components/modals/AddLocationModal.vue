<template>
  <BaseModal :show="show" @close="$emit('close')" customStyle="max-width: 600px;">
    <template #header><h2 style="margin: 0; font-size: 1.5rem; color: #1e293b;">📍 Nový podnik</h2></template>
    <template #body>
      <form @submit.prevent="$emit('submit')" style="display: flex; flex-direction: column; gap: 1.25rem;">
        
        <BaseInput v-model="form.name" label="Název *" required />

        <BaseSelect v-model="form.type" label="Typ místa *" required>
          <option value="hospoda">Hospoda / Restaurace (Veřejné)</option>
          <option value="jinde">Ostatní (Doma, chata, venku...)</option>
        </BaseSelect>

        <div style="display: flex; gap: 1rem;">
          <BaseInput v-model="form.address" label="Ulice" style="flex: 2;" />
          <BaseInput v-model="form.street_number" label="Č. p." style="flex: 1;" />
        </div>
        
        <div style="display: flex; gap: 1rem;">
          <BaseInput v-model="form.city" label="Město" style="flex: 2;" />
          <BaseInput v-model="form.zip_code" label="PSČ" style="flex: 1;" />
        </div>
        
        <BaseInput v-model="form.country" label="Země" />

        <BaseInput v-model="form.opening_hours" label="Otevírací doba" />

        <div style="display: flex; gap: 1rem;">
          <BaseInput v-model="form.email" type="email" label="E-mail" style="flex: 1;" />
          <BaseInput v-model="form.phone" label="Telefon" style="flex: 1;" />
        </div>

        <BaseInput v-model="form.website" type="url" label="Web" />

        <BaseButton type="submit" variant="add" style="margin-top: 0.5rem; width: 100%;">
          <template #icon><SaveIcon :size="18" /></template>Uložit podnik
        </BaseButton>

      </form>
    </template>
  </BaseModal>
</template>

<script setup>
import { SaveIcon } from 'lucide-vue-next'
import BaseModal from '../BaseModal.vue'
import BaseInput from '../BaseInput.vue'
import BaseSelect from '../BaseSelect.vue'
import BaseButton from '../BaseButton.vue'

defineProps({
  show: Boolean,
  form: Object
})
defineEmits(['close', 'submit'])
</script>