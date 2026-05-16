<template>
  <BaseModal :show="show" @close="$emit('close')" customStyle="overflow: hidden;">
    <template #header>
      <div class="background-watermark">
        <UserMinusIcon :size="180" color="var(--danger)" />
      </div>

      <h2 class="modal-title danger-text" style="position: relative; z-index: 1;">
        <UserMinusIcon :size="26" />
        {{ isCurrentUser ? $t('modals.remove_avatar.title_self') : $t('modals.remove_avatar.title_other') }}
      </h2>
    </template>
    <template #body>
      <div style="position: relative; z-index: 1;">
        <p class="modal-desc" v-if="isCurrentUser">
          {{ $t('modals.remove_avatar.desc_self') }}
        </p>
        <p class="modal-desc" v-else>
          {{ $t('modals.remove_avatar.desc_other', { user: user?.username }) }}
        </p>
        <div class="button-group">
           <BaseButton variant="secondary" style="flex: 1" @click="$emit('close')">{{ $t('buttons.cancel') }}</BaseButton>
           <BaseButton variant="danger" style="flex: 1" @click="$emit('confirm', user?.id)">
              {{ $t('modals.remove_avatar.confirm') }}
           </BaseButton>
        </div>
      </div>
    </template>
  </BaseModal>
</template>

<script setup>
import { UserMinusIcon } from 'lucide-vue-next'
import { useI18n } from 'vue-i18n'
import BaseModal from '../BaseModal.vue'
import BaseButton from '../BaseButton.vue'

defineProps({
  show: Boolean,
  user: Object,
  isCurrentUser: { type: Boolean, default: false } 
})

defineEmits(['close', 'confirm'])
const { t } = useI18n()
</script>

<style scoped>
/* Vodoznak na pozadí */
.background-watermark {
  position: absolute;
  right: -20px;
  top: -20px;
  opacity: 0.04;
  pointer-events: none;
  z-index: 0;
  transform: rotate(15deg);
  display: flex;
  align-items: center;
  justify-content: center;
}

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