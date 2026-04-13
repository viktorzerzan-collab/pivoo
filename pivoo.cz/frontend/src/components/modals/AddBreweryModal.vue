<template>
  <BaseModal :show="show" @close="$emit('close')" customStyle="max-width: 600px;">
    <template #header>
      <h2 class="modal-title">
        <FactoryIcon class="title-icon" :size="26" />
        {{ isEditing ? 'Upravit pivovar' : 'Nový pivovar' }}
      </h2>
    </template>
    <template #body>
      <form @submit.prevent="$emit('submit')" class="add-form">
        
        <div style="margin-bottom: 0.5rem;">
          <BaseFileUpload v-model:file="form.logoFile" label="Logo pivovaru (volitelné)" placeholder="Nahrát logo" />
        </div>

        <BaseInput v-model="form.name" label="Název pivovaru *" required />
        
        <div class="form-row">
          <BaseInput v-model="form.address" label="Ulice" style="flex: 2;" />
          <BaseInput v-model="form.street_number" label="Číslo" style="flex: 1;" />
        </div>
        
        <div class="form-row">
          <BaseInput v-model="form.city" label="Město" style="flex: 2;" />
          <BaseInput v-model="form.zip_code" label="PSČ" style="flex: 1;" />
        </div>
        
        <BaseInput v-model="form.country" label="Země" />

        <div class="form-row">
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
import { FactoryIcon, SaveIcon } from 'lucide-vue-next'
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

<style scoped>
.modal-title { display: flex; align-items: center; gap: 0.5rem; margin: 0; color: var(--text-main); font-size: 1.5rem; transition: color 0.5s ease; }
.title-icon { color: var(--blue); }
.add-form { display: flex; flex-direction: column; gap: 1.25rem; }
.form-row { display: flex; gap: 1rem; }
</style>