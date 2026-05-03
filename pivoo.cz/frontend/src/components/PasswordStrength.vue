<template>
  <div class="password-strength" v-if="password || confirm">
    <ul class="req-list">
      <li :class="{ 'is-met': lengthOK }">
        <CheckIcon v-if="lengthOK" :size="16" />
        <XIcon v-else :size="16" />
        <span>{{ $t('password.req_length') }}</span>
      </li>
      <li :class="{ 'is-met': numberOK }">
        <CheckIcon v-if="numberOK" :size="16" />
        <XIcon v-else :size="16" />
        <span>{{ $t('password.req_number') }}</span>
      </li>
      <li :class="{ 'is-met': specialOK }">
        <CheckIcon v-if="specialOK" :size="16" />
        <XIcon v-else :size="16" />
        <span>{{ $t('password.req_special') }}</span>
      </li>
      <li :class="{ 'is-met': matchOK }">
        <CheckIcon v-if="matchOK" :size="16" />
        <XIcon v-else :size="16" />
        <span>{{ $t('password.req_match') }}</span>
      </li>
    </ul>
  </div>
</template>

<script setup>
import { computed, watch } from 'vue'
import { CheckIcon, XIcon } from 'lucide-vue-next'

const props = defineProps({
  password: { type: String, default: '' },
  confirm: { type: String, default: '' }
})

const emit = defineEmits(['validityChange'])

const lengthOK = computed(() => props.password.length >= 8)
const numberOK = computed(() => /[0-9]/.test(props.password))
const specialOK = computed(() => /[^a-zA-Z0-9]/.test(props.password))
const matchOK = computed(() => props.password.length > 0 && props.password === props.confirm)

const isValid = computed(() => lengthOK.value && numberOK.value && specialOK.value && matchOK.value)

// Okamžitě a spolehlivě informujeme rodiče (RegisterView) o stavu hesla
watch(isValid, (newVal) => {
  emit('validityChange', newVal)
}, { immediate: true })
</script>

<style scoped>
.password-strength {
  background: var(--bg-app);
  padding: 0.85rem 1rem;
  border-radius: var(--radius-sm);
  border: 1px solid var(--border);
  margin-top: -0.5rem;
  transition: all 0.3s ease;
}

.req-list {
  list-style: none;
  padding: 0;
  margin: 0;
  display: grid;
  grid-template-columns: 1fr;
  gap: 0.6rem;
}

.req-list li {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.85rem;
  font-weight: 600;
  color: var(--text-muted);
  transition: color 0.3s ease;
}

.req-list li.is-met {
  color: #10b981;
}

.req-list li:not(.is-met) svg {
  color: var(--danger);
}

@media (min-width: 480px) {
  .req-list {
    grid-template-columns: 1fr 1fr;
  }
}
</style>