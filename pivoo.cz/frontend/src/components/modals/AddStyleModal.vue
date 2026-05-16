<template>
  <BaseModal :show="show" @close="$emit('close')" customStyle="max-width: 650px; overflow: hidden;">
    <template #header>
      <BackgroundWatermark 
        :icon="isEditing ? PencilIcon : PlusCircleIcon" 
        :size="180" 
        :is-modal="true" 
      />
      <h2 class="modal-title" style="position: relative; z-index: 1;">
        <component :is="isEditing ? PencilIcon : PlusCircleIcon" class="title-icon" :size="26" />
        {{ isEditing ? $t('admin.edit_style') : $t('admin.add_style') }}
      </h2>
    </template>
    <template #body>
      <form @submit.prevent="$emit('submit')" class="modal-form" style="position: relative; z-index: 1;">
        <BaseInput v-model="form.name" :label="$t('admin.style_name')" required />
        <BaseButton type="submit" variant="add" style="padding: 1rem;">
          <template #icon><SaveIcon :size="18" /></template>
          {{ $t('admin.save_style') }}
        </BaseButton>
      </form>
    </template>
  </BaseModal>
</template>

<script setup>
import { PlusCircleIcon, PencilIcon, SaveIcon } from 'lucide-vue-next'
import BaseModal from '../BaseModal.vue'
import BackgroundWatermark from '../BackgroundWatermark.vue'
import BaseInput from '../BaseInput.vue'
import BaseButton from '../BaseButton.vue'

defineProps({
  show: Boolean,
  isEditing: Boolean,
  form: Object
})
defineEmits(['close', 'submit'])
</script>

<style scoped>
.modal-title { display: flex; align-items: center; gap: 0.5rem; margin: 0; color: var(--text-main); font-size: 1.5rem; transition: color 0.3s ease; }
.title-icon { color: var(--blue); }
.modal-form { display: flex; flex-direction: column; gap: 1.5rem; }
</style>