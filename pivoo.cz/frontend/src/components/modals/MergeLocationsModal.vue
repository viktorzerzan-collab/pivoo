<template>
  <BaseModal :show="show" @close="$emit('close')" customStyle="max-width: 650px; overflow: hidden;">
    <template #header>
      <BackgroundWatermark 
        :icon="GitMergeIcon" 
        :size="180" 
        :is-modal="true" 
      />
      <h2 class="modal-title" style="position: relative; z-index: 1;">
        <GitMergeIcon class="title-icon" :size="26" />
        {{ $t('admin.merge_title') }}
      </h2>
    </template>
    <template #body>
      <form @submit.prevent="$emit('submit')" class="modal-form" style="position: relative; z-index: 1;">
        <p class="merge-desc">{{ $t('admin.merge_desc', { source: form.source?.name }) }}</p>
        <BaseSelect v-model="form.target_id" :label="$t('admin.merge_target')" :placeholder="$t('admin.merge_target_placeholder')" searchable required>
          <option disabled value="">{{ $t('admin.merge_target_placeholder') }}</option>
          <option v-for="loc in targetOptions" :key="loc.id" :value="loc.id">{{ loc.name }} ({{ loc.city || $t('admin.merge_no_city') }})</option>
        </BaseSelect>
        <BaseButton type="submit" variant="primary" style="width: 100%; padding: 1rem; font-weight: 700; background-color: #8b5cf6; color: white;">
          {{ $t('admin.merge_submit') }}
        </BaseButton>
      </form>
    </template>
  </BaseModal>
</template>

<script setup>
import { GitMergeIcon } from 'lucide-vue-next'
import BaseModal from '../BaseModal.vue'
import BackgroundWatermark from '../BackgroundWatermark.vue'
import BaseSelect from '../BaseSelect.vue'
import BaseButton from '../BaseButton.vue'

defineProps({
  show: Boolean,
  form: Object,
  targetOptions: Array
})
defineEmits(['close', 'submit'])
</script>

<style scoped>
.modal-title { display: flex; align-items: center; gap: 0.5rem; margin: 0; color: var(--text-main); font-size: 1.5rem; transition: color 0.3s ease; }
.title-icon { color: var(--blue); }
.modal-form { display: flex; flex-direction: column; gap: 1.5rem; }
.merge-desc { margin: 0; color: var(--text-muted); line-height: 1.4; }
</style>