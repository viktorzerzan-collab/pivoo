<template>
  <BaseModal :show="show" @close="$emit('close')">
    <template #header>
      <h2 class="modal-title danger-text">
        <AlertTriangleIcon :size="26" />
        Opravdu smazat?
      </h2>
    </template>
    <template #body>
      <p class="modal-desc">Tento zápis bude nenávratně odstraněn.</p>
      <div class="button-group">
         <BaseButton variant="secondary" style="flex: 1" @click="$emit('close')">Zrušit</BaseButton>
         <BaseButton variant="danger" style="flex: 1" @click="$emit('confirm')">Ano, smazat</BaseButton>
      </div>
    </template>
  </BaseModal>
</template>

<script setup>
import { AlertTriangleIcon } from 'lucide-vue-next'
import BaseModal from '../BaseModal.vue'
import BaseButton from '../BaseButton.vue'

defineProps({ show: Boolean })
defineEmits(['close', 'confirm'])
</script>

<style scoped>
.modal-title { display: flex; align-items: center; gap: 0.5rem; margin: 0; font-size: 1.5rem; }
.danger-text { color: var(--danger); }
.modal-desc { margin-bottom: 2rem; color: var(--text-muted); font-size: 1.1rem; text-align: center; transition: color 0.5s ease; }
.button-group { display: flex; gap: 1rem; }

/* Definice pro sekundární tlačítko v rámci potvrzovacího dialogu, pokud BaseButton.vue nemá variant="secondary" */
:deep(.secondary) {
  background-color: var(--bg-app);
  color: var(--text-main);
  border: 1px solid var(--border);
}
:deep(.secondary:hover) {
  background-color: var(--border);
}
</style>