<template>
  <BaseModal :show="show" @close="$emit('close')" customStyle="overflow: hidden;">
    <template #header>
      <BackgroundWatermark 
        :icon="TrashIcon" 
        color="var(--danger)" 
        :size="180" 
        :is-modal="true" 
      />

      <h2 class="modal-title danger-text" style="position: relative; z-index: 1;">
        <TrashIcon :size="26" />
        {{ $t('modals.delete_confirm.title') }}
      </h2>
    </template>
    <template #body>
      <div style="position: relative; z-index: 1;">
        <p class="modal-desc">{{ $t('modals.delete_confirm.desc') }}</p>
        <div class="button-group">
           <BaseButton variant="secondary" style="flex: 1" @click="$emit('close')">{{ $t('modals.delete_confirm.cancel') }}</BaseButton>
           <BaseButton variant="danger" style="flex: 1" @click="$emit('confirm')">
              <template #icon><TrashIcon :size="18" /></template>
              {{ $t('modals.delete_confirm.confirm') }}
           </BaseButton>
        </div>
      </div>
    </template>
  </BaseModal>
</template>

<script setup>
import { TrashIcon } from 'lucide-vue-next'
import BaseModal from '../BaseModal.vue'
import BaseButton from '../BaseButton.vue'
import BackgroundWatermark from '../BackgroundWatermark.vue'

defineProps({ show: Boolean })
defineEmits(['close', 'confirm'])
</script>

<style scoped>
.modal-title { display: flex; align-items: center; gap: 0.5rem; margin: 0; font-size: 1.5rem; }
.danger-text { color: var(--danger); }
.modal-desc { margin-bottom: 2rem; color: var(--text-muted); font-size: 1.1rem; text-align: center; transition: color 0.3s ease; }
.button-group { display: flex; gap: 1rem; }

:deep(.secondary) {
  background-color: var(--bg-app);
  color: var(--text-main);
  border: 1px solid var(--border);
}
:deep(.secondary:hover) {
  background-color: var(--border);
}
</style>