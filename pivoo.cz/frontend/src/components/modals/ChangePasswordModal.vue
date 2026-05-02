<template>
  <BaseModal :show="show" @close="handleClose">
    <template #header>
      <h2 class="modal-title">
        <KeyIcon class="title-icon" :size="26" />
        Změnit heslo uživatele
      </h2>
    </template>
    <template #body>
      <form @submit.prevent="handleSubmit" class="add-form">
        <p class="user-info-banner">
          Měníte heslo pro uživatele: <strong>{{ user?.username }}</strong>
        </p>

        <BaseInput 
          v-model="form.password" 
          type="password" 
          label="Nové heslo *" 
          placeholder="Zadejte nové heslo (min. 8 znaků)" 
          required 
        />
        
        <BaseInput 
          v-model="form.password_confirm" 
          type="password" 
          label="Potvrzení nového hesla *" 
          placeholder="Zadejte heslo znovu pro kontrolu" 
          required 
        />

        <div v-if="error" class="modal-error-text">
          {{ error }}
        </div>

        <BaseButton type="submit" variant="edit" style="margin-top: 1rem; width: 100%;">
          Nastavit nové heslo
        </BaseButton>
      </form>
    </template>
  </BaseModal>
</template>

<script setup>
import { ref, watch } from 'vue'
import { KeyIcon } from 'lucide-vue-next'
import BaseModal from '../BaseModal.vue'
import BaseInput from '../BaseInput.vue'
import BaseButton from '../BaseButton.vue'

const props = defineProps({
  show: Boolean,
  user: Object
})

const emit = defineEmits(['close', 'submit'])

const form = ref({
  password: '',
  password_confirm: ''
})

const error = ref('')

const handleClose = () => {
  form.value = { password: '', password_confirm: '' }
  error.value = ''
  emit('close')
}

const handleSubmit = () => {
  if (form.value.password.length < 8) {
    error.value = 'Heslo musí mít alespoň 8 znaků.'
    return
  }
  
  if (form.value.password !== form.value.password_confirm) {
    error.value = 'Zadaná hesla se neshodují.'
    return
  }

  error.value = ''
  emit('submit', { 
    user_id: props.user.id, 
    new_password: form.value.password 
  })
  
  form.value = { password: '', password_confirm: '' }
}

watch(() => form.value.password, () => error.value = '')
watch(() => form.value.password_confirm, () => error.value = '')
</script>

<style scoped>
.modal-title { display: flex; align-items: center; gap: 0.5rem; margin: 0; color: var(--text-main); font-size: 1.5rem; transition: color 0.3s ease; }
.title-icon { color: var(--orange); }
.add-form { display: flex; flex-direction: column; gap: 1.25rem; }

.user-info-banner {
  background-color: var(--bg-app);
  padding: 0.75rem;
  border-radius: var(--radius-sm);
  font-size: 0.95rem;
  color: var(--text-main);
  border: 1px solid var(--border);
  margin-bottom: 0.5rem;
}

.modal-error-text {
  color: var(--danger);
  font-size: 0.85rem;
  font-weight: 600;
  text-align: center;
}

@media (max-width: 600px) {
  .add-form { gap: 1rem; }
}
</style>