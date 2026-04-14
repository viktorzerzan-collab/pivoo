<template>
  <BaseModal :show="show" @close="$emit('close')">
    <template #header>
      <h2 class="modal-title">
        <UserCogIcon class="title-icon" :size="26" />
        Upravit uživatele
      </h2>
    </template>
    <template #body>
      <form @submit.prevent="$emit('submit')" class="add-form">
        
        <div class="form-row">
          <BaseInput v-model="form.first_name" label="Křestní jméno *" required class="half" />
          <BaseInput v-model="form.last_name" label="Příjmení *" required class="half" />
        </div>
        
        <BaseInput v-model="form.username" label="Přezdívka (Username) *" required />
        <BaseInput v-model="form.email" type="email" label="E-mail *" required />
        
        <BaseSelect v-model="form.role" label="Role uživatele *" required>
          <option value="user">Běžný pivař (user)</option>
          <option value="admin">Administrátor (admin)</option>
        </BaseSelect>

        <BaseButton type="submit" variant="edit" style="margin-top: 1rem; width: 100%;">
          Uložit změny
        </BaseButton>
        
      </form>
    </template>
  </BaseModal>
</template>

<script setup>
import { UserCogIcon } from 'lucide-vue-next'
import BaseModal from '../BaseModal.vue'
import BaseInput from '../BaseInput.vue'
import BaseButton from '../BaseButton.vue'
import BaseSelect from '../BaseSelect.vue'

defineProps({
  show: Boolean,
  form: Object
})
defineEmits(['close', 'submit'])
</script>

<style scoped>
.modal-title { display: flex; align-items: center; gap: 0.5rem; margin: 0; color: var(--text-main); font-size: 1.5rem; transition: color 0.5s ease; }
.title-icon { color: var(--orange); }
.add-form { display: flex; flex-direction: column; gap: 1.25rem; }
.form-row { display: flex; gap: 1rem; }
.half { flex: 1; }

@media (max-width: 600px) {
  .form-row { flex-direction: column; gap: 1.25rem; }
}
</style>