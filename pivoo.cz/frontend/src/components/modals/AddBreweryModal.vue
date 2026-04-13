<template>
  <BaseModal :show="show" @close="$emit('close')" customStyle="max-width: 600px;">
    <template #header>
      <h2 style="margin: 0; font-size: 1.5rem; color: #1e293b;">
        🏭 {{ isEditing ? 'Upravit pivovar' : 'Nový pivovar' }}
      </h2>
    </template>
    <template #body>
      <form @submit.prevent="$emit('submit')" style="display: flex; flex-direction: column; gap: 1.25rem;">
        
        <div style="margin-bottom: 0.5rem;">
          <BaseFileUpload v-model:file="form.logoFile" label="Logo pivovaru (volitelné)" placeholder="Nahrát logo" />
        </div>

        <BaseInput v-model="form.name" label="Název pivovaru *" required />
        
        <div style="display: flex; gap: 1rem;">
          <BaseInput v-model="form.address" label="Ulice" style="flex: 2;" />
          <BaseInput v-model="form.street_number" label="Číslo" style="flex: 1;" />
        </div>
        
        <div style="display: flex; gap: 1rem;">
          <BaseInput v-model="form.city" label="Město" style="flex: 2;" />
          <BaseInput v-model="form.zip_code" label="PSČ" style="flex: 1;" />
        </div>
        
        <BaseInput v-model="form.country" label="Země" />

        <div style="display: flex; gap: 1rem;">
          <BaseInput v-model="form.email" type="email" label="E-mail" style="flex: 1;" />
          <BaseInput v-model="form.phone" label="Telefon" style="flex: 1;" />
        </div>

        <BaseInput v-model="form.website" type="url" label="Web" />

        <BaseButton type="submit" variant="add" style="margin-top: 0.5rem; width: 100%;">
          <template #icon><SaveIcon :size="18" /></template>Uložit pivovar
        </BaseButton>
      </form>
    </template>
  </BaseModal>
</template>

<script setup>
import { SaveIcon } from 'lucide-vue-next'
import BaseModal from '../BaseModal.vue'
import BaseInput from '../BaseInput.vue'
import BaseButton from '../BaseButton.vue'
import BaseFileUpload from '../BaseFileUpload.vue'

defineProps({
  show: Boolean,
  isEditing: Boolean,
  form: Object
})
defineEmits(['close', 'submit'])
</script>