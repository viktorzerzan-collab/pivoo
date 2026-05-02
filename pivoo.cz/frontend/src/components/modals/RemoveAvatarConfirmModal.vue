<template>
  <BaseModal :show="show" @close="$emit('close')">
    <template #header>
      <h2 class="modal-title danger-text">
        <UserMinusIcon :size="26" />
        {{ isCurrentUser ? 'Smazat vlastní fotku?' : 'Smazat profilovou fotku?' }}
      </h2>
    </template>
    <template #body>
      <p class="modal-desc" v-if="isCurrentUser">
        Opravdu si chcete smazat svou profilovou fotku? Tuto akci nelze vrátit zpět.
      </p>
      <p class="modal-desc" v-else>
        Opravdu chcete uživateli <strong>{{ user?.username }}</strong> smazat jeho profilovou fotku? Tato akce je nevratná.
      </p>
      <div class="button-group">
         <BaseButton variant="secondary" style="flex: 1" @click="$emit('close')">Zrušit</BaseButton>
         <BaseButton variant="danger" style="flex: 1" @click="$emit('confirm', user?.id)">
            Ano, smazat fotku
         </BaseButton>
      </div>
    </template>
  </BaseModal>
</template>

<script setup>
import { UserMinusIcon } from 'lucide-vue-next'
import BaseModal from '../BaseModal.vue'
import BaseButton from '../BaseButton.vue'

defineProps({
  show: Boolean,
  user: Object,
  isCurrentUser: { type: Boolean, default: false } 
})

defineEmits(['close', 'confirm'])
</script>

<style scoped>
.modal-title { 
  display: flex; 
  align-items: center; 
  gap: 0.5rem; 
  margin: 0; 
  font-size: 1.5rem; 
}
.danger-text { color: var(--danger); }

.modal-desc { 
  margin-bottom: 2rem; 
  color: var(--text-muted); 
  font-size: 1.05rem; 
  text-align: center; 
  transition: color 0.3s ease; 
  line-height: 1.5;
}
.modal-desc strong { color: var(--text-main); }

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