<template>
  <BaseModal :show="show" @close="$emit('close')">
    <template #header>
      <h2 class="modal-title">
        <UserCogIcon class="title-icon" :size="26" />
        Upravit uživatele
      </h2>
    </template>
    <template #body>
      <div class="avatar-moderation-section">
        <h3 class="section-subtitle">Profilová fotka</h3>
        
        <div v-if="form.avatar" class="avatar-present">
          <div class="avatar-preview">
            <img :src="'https://www.pivoo.cz/backend/uploads/avatars/' + form.avatar" alt="Avatar uživatele" />
          </div>
          <BaseButton type="button" variant="danger" @click="$emit('remove-avatar', form.id)">
            <template #icon><Trash2Icon :size="16" /></template>
            Smazat fotku
          </BaseButton>
        </div>
        
        <div v-else class="avatar-empty">
          <div class="avatar-placeholder"><UserIcon :size="32" color="var(--text-muted)" /></div>
          <p>Uživatel nemá nahranou žádnou fotku.</p>
        </div>
      </div>

      <hr class="ui-divider" />

      <form @submit.prevent="$emit('submit')" class="add-form">
        <h3 class="section-subtitle">Osobní údaje a role</h3>
        <div class="form-row">
          <BaseInput v-model="form.first_name" label="Křestní jméno *" required class="half" />
          <BaseInput v-model="form.last_name" label="Příjmení *" required class="half" />
        </div>
        
        <BaseInput v-model="form.username" label="Přezdívka (Username) *" required />
        <BaseInput v-model="form.email" type="email" label="E-mail *" required />
        
        <div class="role-section">
          <BaseSelect v-model="form.role" label="Role uživatele *" :disabled="isCurrentUser" required>
            <option value="user">Běžný pivař (user)</option>
            <option value="admin">Administrátor (admin)</option>
          </BaseSelect>
          
          <p v-if="isCurrentUser" class="self-edit-warning">
            Nemůžete změnit roli sami sobě, abyste se neodřízli z administrace.
          </p>
        </div>

        <BaseButton type="submit" variant="edit" style="margin-top: 1rem; width: 100%;">
          Uložit změny
        </BaseButton>
      </form>
    </template>
  </BaseModal>
</template>

<script setup>
import { UserCogIcon, Trash2Icon, UserIcon } from 'lucide-vue-next'
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
</script>

<style scoped>
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