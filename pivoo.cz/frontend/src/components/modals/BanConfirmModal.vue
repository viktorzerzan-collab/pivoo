<template>
  <BaseModal :show="show" @close="$emit('close')">
    <template #header>
      <h2 class="modal-title" :class="isBanning ? 'danger-text' : 'success-text'">
        <BanIcon v-if="isBanning" :size="26" />
        <UnlockIcon v-else :size="26" />
        {{ isBanning ? $t('modals.ban_confirm.title_ban') : $t('modals.ban_confirm.title_unban') }}
      </h2>
    </template>
    <template #body>
      <p class="modal-desc">
        {{ isBanning ? $t('modals.ban_confirm.desc_ban_1') : $t('modals.ban_confirm.desc_unban_1') }} <strong>{{ user?.username }}</strong>.<br><br>
        <span v-if="isBanning">{{ $t('modals.ban_confirm.desc_ban_2') }}</span>
        <span v-else>{{ $t('modals.ban_confirm.desc_unban_2') }}</span>
      </p>
      <div class="button-group">
         <BaseButton variant="secondary" style="flex: 1" @click="$emit('close')">{{ $t('buttons.cancel') }}</BaseButton>
         <BaseButton :variant="isBanning ? 'danger' : 'primary'" style="flex: 1" @click="$emit('confirm', user)">
            {{ isBanning ? $t('modals.ban_confirm.btn_ban') : $t('modals.ban_confirm.btn_unban') }}
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