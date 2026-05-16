<template>
  <BaseModal :show="show" @close="$emit('close')" customStyle="max-width: 600px; overflow: hidden;">
    <template #header>
      <BackgroundWatermark 
        :icon="isEditing ? PencilIcon : PlusCircleIcon" 
        :size="180" 
        :is-modal="true" 
      />

      <h2 class="modal-title" style="position: relative; z-index: 1;">
        <component :is="isEditing ? PencilIcon : PlusCircleIcon" class="title-icon" :size="26" />
        {{ isEditing ? $t('modals.add_brewery.title_edit') : $t('modals.add_brewery.title_add') }}
      </h2>
    </template>
    <template #body>
      <form @submit.prevent="$emit('submit')" class="add-form" style="position: relative; z-index: 1;">
        
        <div v-if="form.is_magic" class="magic-banner">
          <SparklesIcon :size="20" class="magic-icon" />
          <span>{{ $t('modals.add_beer.magic_banner') }}</span>
        </div>

        <div style="margin-bottom: 0.5rem;">
          <BaseFileUpload v-model:file="form.logoFile" :label="$t('modals.add_brewery.logo')" :placeholder="$t('modals.add_brewery.logo_placeholder')" />
        </div>

        <BaseInput v-model="form.name" :label="$t('modals.add_brewery.name')" required />
        
        <BaseMapPicker 
          v-model:lat="form.lat" 
          v-model:lng="form.lng" 
          :label="$t('modals.add_brewery.map_label')" 
          :show="show" 
        />

        <BaseInput v-model="form.address" :label="$t('modals.add_brewery.address')" />
        
        <div class="form-row">
          <BaseInput v-model="form.city" :label="$t('modals.add_brewery.city')" style="flex: 2;" />
          <BaseInput v-model="form.zip_code" :label="$t('modals.add_brewery.zip')" style="flex: 1;" />
        </div>
        
        <BaseSelect v-model="form.country_id" :label="$t('modals.add_brewery.country')">
          <option v-for="c in countries" :key="c.id" :value="c.id">
            {{ c.name_cz }}
          </option>
        </BaseSelect>

        <div class="form-row">
          <BaseInput v-model="form.email" type="email" :label="$t('modals.add_brewery.email')" style="flex: 1;" />
          <BaseInput v-model="form.phone" :label="$t('modals.add_brewery.phone')" style="flex: 1;" />
        </div>

        <BaseInput v-model="form.website" type="url" :label="$t('modals.add_brewery.website')" />

        <OpeningHoursInput v-model="form.opening_hours" :label="$t('opening_hours.label')" />

        <BaseButton type="submit" variant="add" style="margin-top: 0.5rem; width: 100%;">
          <template #icon>
            <component :is="isEditing ? PencilIcon : PlusCircleIcon" :size="18" />
          </template>
          {{ $t('modals.add_brewery.save') }}
        </BaseButton>
      </form>
    </template>
  </BaseModal>
</template>

<script setup>
import { SparklesIcon, PlusCircleIcon, PencilIcon } from 'lucide-vue-next'
import BaseModal from '../BaseModal.vue'
import BackgroundWatermark from '../BackgroundWatermark.vue'
import BaseInput from '../BaseInput.vue'
import BaseButton from '../BaseButton.vue'
import BaseFileUpload from '../BaseFileUpload.vue'
import BaseSelect from '../BaseSelect.vue'
import OpeningHoursInput from '../OpeningHoursInput.vue'
import BaseMapPicker from '../BaseMapPicker.vue'

const props = defineProps({
  show: Boolean,
  isEditing: Boolean,
  form: Object,
  countries: Array
})
const emit = defineEmits(['close', 'submit'])
</script>

<style scoped>
.modal-title { display: flex; align-items: center; gap: 0.5rem; margin: 0; color: var(--text-main); font-size: 1.5rem; transition: color 0.3s ease; }
.title-icon { color: var(--blue); }
.add-form { display: flex; flex-direction: column; gap: 1.25rem; }
.form-row { display: flex; gap: 1rem; }

.magic-banner {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  background-color: rgba(139, 92, 246, 0.1);
  border: 1px solid rgba(139, 92, 246, 0.3);
  color: #8b5cf6;
  padding: 0.75rem 1rem;
  border-radius: var(--radius-sm);
  font-size: 0.9rem;
  font-weight: 600;
  margin-bottom: 0.5rem;
}
.magic-icon { flex-shrink: 0; }
</style>