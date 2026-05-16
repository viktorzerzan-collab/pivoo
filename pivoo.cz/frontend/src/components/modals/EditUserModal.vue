<template>
  <BaseModal :show="show" @close="$emit('close')" customStyle="overflow: hidden;">
    <template #header>
      <div class="background-watermark">
        <UserCogIcon :size="180" color="var(--primary)" />
      </div>

      <h2 class="modal-title" style="position: relative; z-index: 1;">
        <UserCogIcon class="title-icon" :size="26" />
        {{ $t('modals.edit_user.title') }}
      </h2>
    </template>
    <template #body>
      <div style="position: relative; z-index: 1;">
        <div class="avatar-moderation-section">
          <h3 class="section-subtitle">{{ $t('modals.edit_user.avatar_title') }}</h3>
          
          <div v-if="form.avatar" class="avatar-present">
            <div class="avatar-preview">
              <img :src="'https://www.pivoo.cz/backend/uploads/avatars/' + form.avatar" alt="Avatar uživatele" />
            </div>
            <BaseButton type="button" variant="danger" @click="$emit('remove-avatar', form.id)">
              <template #icon><Trash2Icon :size="16" /></template>
              {{ $t('modals.edit_user.delete_avatar') }}
            </BaseButton>
          </div>
          
          <div v-else class="avatar-empty">
            <div class="avatar-placeholder"><UserIcon :size="32" color="var(--text-muted)" /></div>
            <p>{{ $t('modals.edit_user.no_avatar') }}</p>
          </div>
        </div>

        <hr class="ui-divider" />

        <form @submit.prevent="$emit('submit')" class="add-form">
          <h3 class="section-subtitle">{{ $t('modals.edit_user.personal_info') }}</h3>
          <div class="form-row">
            <BaseInput v-model="form.first_name" :label="$t('modals.edit_user.first_name')" required class="half" />
            <BaseInput v-model="form.last_name" :label="$t('modals.edit_user.last_name')" required class="half" />
          </div>
          
          <BaseInput v-model="form.username" :label="$t('modals.edit_user.username')" required />
          <BaseInput v-model="form.email" type="email" :label="$t('modals.edit_user.email')" required />
          
          <div class="role-section">
            <BaseSelect v-model="form.role" :label="$t('modals.edit_user.role')" :disabled="isCurrentUser" required>
              <option value="user">{{ $t('modals.edit_user.role_user') }}</option>
              <option value="admin">{{ $t('modals.edit_user.role_admin') }}</option>
            </BaseSelect>
            
            <p v-if="isCurrentUser" class="self-edit-warning">
              {{ $t('modals.edit_user.self_edit_warning') }}
            </p>
          </div>

          <BaseButton type="submit" variant="edit" style="margin-top: 1rem; width: 100%;">
            {{ $t('modals.edit_user.save') }}
          </BaseButton>
        </form>
      </div>
    </template>
  </BaseModal>
</template>

<script setup>
import { UserCogIcon, Trash2Icon, UserIcon } from 'lucide-vue-next'
import { useI18n } from 'vue-i18n'
import BaseModal from '../BaseModal.vue'
import BaseInput from '../BaseInput.vue'
import BaseButton from '../BaseButton.vue'
import BaseSelect from '../BaseSelect.vue'

defineProps({
  show: Boolean,
  form: Object,
  isCurrentUser: { type: Boolean, default: false }
})

defineEmits(['close', 'submit', 'remove-avatar'])
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

.modal-title { display: flex; align-items: center; gap: 0.5rem; margin: 0; color: var(--text-main); font-size: 1.5rem; transition: color 0.3s ease; }
.title-icon { color: var(--orange); }

.ui-divider { border: 0; border-top: 1px solid var(--border); margin: 1.5rem 0; }
.section-subtitle { margin: 0 0 1rem 0; font-size: 1.1rem; color: var(--text-main); font-weight: 700; }

.avatar-moderation-section { display: flex; flex-direction: column; }
.avatar-present { display: flex; align-items: center; gap: 1.5rem; background: var(--bg-app); padding: 1rem; border-radius: var(--radius-md); border: 1px solid var(--border); }
.avatar-preview { width: 80px; height: 80px; border-radius: 50%; overflow: hidden; border: 2px solid var(--border); flex-shrink: 0; }
.avatar-preview img { width: 100%; height: 100%; object-fit: cover; }
.avatar-empty { display: flex; align-items: center; gap: 1rem; color: var(--text-muted); font-size: 0.95rem; background: var(--bg-app); padding: 1rem; border-radius: var(--radius-md); border: 1px dashed var(--border); }
.avatar-placeholder { width: 48px; height: 48px; border-radius: 50%; background: var(--bg-panel); display: flex; align-items: center; justify-content: center; border: 1px solid var(--border); }

.add-form { display: flex; flex-direction: column; gap: 1.25rem; }
.form-row { display: flex; gap: 1rem; }
.half { flex: 1; }

.role-section { display: flex; flex-direction: column; gap: 0.4rem; }
.self-edit-warning { margin: 0; font-size: 0.8rem; color: var(--orange); font-weight: 600; }

@media (max-width: 600px) {
  .form-row { flex-direction: column; gap: 1.25rem; }
  .avatar-present { flex-direction: column; text-align: center; gap: 1rem; }
}
</style>