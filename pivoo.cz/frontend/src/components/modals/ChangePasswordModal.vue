<template>
  <BaseModal :show="show" @close="handleClose">
    <template #header>
      <h2 class="modal-title">
        <KeyIcon class="title-icon" :size="26" />
        {{ $t('modals.change_password.title') }}
      </h2>
    </template>
    <template #body>
      <form @submit.prevent="handleSubmit" class="add-form">
        <p class="user-info-banner">
          {{ $t('modals.change_password.banner') }} <strong>{{ user?.username }}</strong>
        </p>

        <BaseInput 
          v-model="form.password" 
          type="password" 
          :label="$t('modals.change_password.new_password')" 
          :placeholder="$t('modals.change_password.new_password_placeholder')" 
          required 
        />
        
        <BaseInput 
          v-model="form.password_confirm" 
          type="password" 
          :label="$t('modals.change_password.confirm_password')" 
          :placeholder="$t('modals.change_password.confirm_password_placeholder')" 
          required 
        />

        <div v-if="error" class="modal-error-text">
          {{ error }}
        </div>

        <BaseButton type="submit" variant="edit" style="margin-top: 1rem; width: 100%;">
          {{ $t('modals.change_password.save') }}
        </BaseButton>
      </form>
    </template>
  </BaseModal>
</template>

<script setup>
import { ref, watch } from 'vue'
import { KeyIcon } from 'lucide-vue-next'
import { useI18n } from 'vue-i18n'
import BaseModal from '../BaseModal.vue'
import BaseInput from '../BaseInput.vue'
import BaseButton from '../BaseButton.vue'

const props = defineProps({
  show: Boolean,
  user: Object
})

const emit = defineEmits(['close', 'submit'])
const { t } = useI18n()

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
    error.value = t('modals.change_password.error_length')
    return
  }
  
  if (form.value.password !== form.value.password_confirm) {
    error.value = t('modals.change_password.error_match')
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