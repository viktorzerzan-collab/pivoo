<template>
  <BaseModal :show="show" @close="$emit('close')">
    <template #header>
      <h2 class="modal-title">
        <MapPinIcon class="title-icon" :size="26" />
        {{ isEditing ? 'Upravit podnik' : 'Nový podnik' }}
      </h2>
    </template>
    <template #body>
      <form @submit.prevent="$emit('submit')" class="add-form">
        <BaseInput v-model="form.name" label="Název podniku *" required />
        
        <BaseSelect v-model="form.type" label="Typ podniku" required>
          <option value="hospoda">Hospoda / Bar</option>
          <option value="jine">Jiné</option>
        </BaseSelect>

        <template v-if="form.type !== 'jine'">
          <BaseInput v-model="form.address" label="Ulice a číslo popisné" />

          <div class="form-row">
            <BaseInput v-model="form.city" label="Město" class="half" />
            <BaseInput v-model="form.zip_code" label="PSČ" class="half" />
          </div>

          <BaseSelect v-model="form.country_id" label="Země">
            <option v-for="c in countries" :key="c.id" :value="c.id">
              {{ c.name_cz }}
            </option>
          </BaseSelect>

          <div class="form-row">
            <BaseInput v-model="form.email" type="email" label="E-mail" class="half" />
            <BaseInput v-model="form.phone" label="Telefon" class="half" />
          </div>

          <BaseInput v-model="form.website" type="url" label="Webové stránky" />
          <BaseInput v-model="form.opening_hours" label="Otevírací doba (volné pole)" placeholder="Např. Po-Pá 16:00-23:00" />
        </template>

        <BaseButton type="submit" variant="add" style="margin-top: 1rem;">
          Uložit podnik
        </BaseButton>
      </form>
    </template>
  </BaseModal>
</template>

<script setup>
import { MapPinIcon } from 'lucide-vue-next'
import BaseModal from '../BaseModal.vue'
import BaseInput from '../BaseInput.vue'
import BaseButton from '../BaseButton.vue'
import BaseSelect from '../BaseSelect.vue'

defineProps({
  show: Boolean,
  isEditing: Boolean,
  form: Object,
  countries: Array
})
defineEmits(['close', 'submit'])
</script>

<style scoped>
.modal-title { display: flex; align-items: center; gap: 0.5rem; margin: 0; color: var(--text-main); font-size: 1.5rem; transition: color 0.5s ease; }
.title-icon { color: var(--blue); }
.add-form { display: flex; flex-direction: column; gap: 1rem; }
.form-row { display: flex; gap: 1rem; }
.half { flex: 1; }
</style>