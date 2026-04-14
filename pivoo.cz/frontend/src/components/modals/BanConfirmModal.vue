<template>
  <BaseModal :show="show" @close="$emit('close')">
    <template #header>
      <h2 class="modal-title" :class="isBanning ? 'danger-text' : 'success-text'">
        <BanIcon v-if="isBanning" :size="26" />
        <UnlockIcon v-else :size="26" />
        {{ isBanning ? 'Opravdu zablokovat?' : 'Opravdu odblokovat?' }}
      </h2>
    </template>
    <template #body>
      <p class="modal-desc">
        Chystáte se {{ isBanning ? 'zablokovat' : 'odblokovat' }} uživatele <strong>{{ user?.username }}</strong>.<br><br>
        <span v-if="isBanning">Uživatel bude okamžitě odhlášen a ztratí přístup do aplikace.</span>
        <span v-else>Uživatel se bude moci znovu normálně přihlásit.</span>
      </p>
      <div class="button-group">
         <BaseButton variant="secondary" style="flex: 1" @click="$emit('close')">Zrušit</BaseButton>
         <BaseButton :variant="isBanning ? 'danger' : 'primary'" style="flex: 1" @click="$emit('confirm', user)">
            {{ isBanning ? 'Ano, zablokovat' : 'Ano, odblokovat' }}
         </BaseButton>
      </div>
    </template>
  </BaseModal>
</template>

<script setup>
import { computed } from 'vue'
import { BanIcon, UnlockIcon } from 'lucide-vue-next'
import BaseModal from '../BaseModal.vue'
import BaseButton from '../BaseButton.vue'

const props = defineProps({
  show: Boolean,
  user: Object
})

defineEmits(['close', 'confirm'])

// Pokud aktuálně uživatel NENÍ zabanovaný, tak akce, kterou chceme provést, je "Banning" (blokování)
const isBanning = computed(() => !props.user?.is_banned)
</script>

<style scoped>
.modal-title { 
  display: flex; 
  align-items: center; 
  gap: 0.5rem; 
  margin: 0; 
  font-size: 1.5rem; 
  transition: color 0.3s ease; 
}
.danger-text { color: var(--danger); }
.success-text { color: #10b981; }

.modal-desc { 
  margin-bottom: 2rem; 
  color: var(--text-muted); 
  font-size: 1.05rem; 
  text-align: center; 
  transition: color 0.5s ease; 
  line-height: 1.5; 
}
.modal-desc strong { color: var(--text-main); }

.button-group { display: flex; gap: 1rem; }

/* Styl pro sekundární tlačítko */
:deep(.secondary) {
  background-color: var(--bg-app);
  color: var(--text-main);
  border: 1px solid var(--border);
}
:deep(.secondary:hover) {
  background-color: var(--border);
}
</style>