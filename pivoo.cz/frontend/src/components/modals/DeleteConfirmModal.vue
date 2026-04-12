<template>
  <BaseModal :show="show" @close="$emit('close')">
    <template #header><h2 class="modal-title" style="color: #ef4444;">⚠️ Opravdu smazat?</h2></template>
    <template #body>
      <p style="margin-bottom: 2rem; color: #4b5563; font-size: 1.1rem; text-align: center;">Tento zápis piva bude nenávratně odstraněn.</p>
      <div style="display: flex; gap: 1rem;">
         <BaseButton variant="secondary" style="flex: 1" @click="$emit('close')">Zrušit</BaseButton>
         <BaseButton variant="danger" style="flex: 1" @click="$emit('confirm')">Ano, smazat</BaseButton>
      </div>
    </template>
  </BaseModal>
</template>

<script setup>
import BaseModal from '../BaseModal.vue'
import BaseButton from '../BaseButton.vue'

defineProps({ show: Boolean })
defineEmits(['close', 'confirm'])
</script>

<style scoped>
.modal-title { margin: 0; color: #1f2937; font-size: 1.5rem; }
</style>