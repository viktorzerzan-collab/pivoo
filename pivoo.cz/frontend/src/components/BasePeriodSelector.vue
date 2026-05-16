<template>
  <div class="base-period-selector">
    <div class="selector-controls">
      
      <div class="mode-wrapper">
        <BaseSelect v-model="mode" :searchable="false">
          <option value="month">{{ $t('statistics.period_month') }}</option>
          <option value="year">{{ $t('statistics.period_year') }}</option>
          <option value="custom">{{ $t('statistics.period_custom') }}</option>
          <option value="all">{{ $t('statistics.period_all') }}</option>
        </BaseSelect>
      </div>

      <div class="nav-wrapper" v-if="mode === 'month'">
        <button class="icon-btn" @click="changeMonth(-1)"><ChevronLeftIcon :size="18" /></button>
        <div class="nav-label">{{ monthName }} {{ currentYear }}</div>
        <button class="icon-btn" @click="changeMonth(1)" :disabled="isFutureMonth"><ChevronRightIcon :size="18" /></button>
      </div>

      <div class="nav-wrapper" v-if="mode === 'year'">
        <button class="icon-btn" @click="changeYear(-1)"><ChevronLeftIcon :size="18" /></button>
        <div class="nav-label">{{ currentYear }}</div>
        <button class="icon-btn" @click="changeYear(1)" :disabled="isFutureYear"><ChevronRightIcon :size="18" /></button>
      </div>

      <div class="custom-wrapper" v-if="mode === 'custom'">
        <div class="date-input-container">
          <div class="date-field-group">
            <span class="field-label">{{ $t('filter.from') }}</span>
            <div class="date-input">
              <BaseDatePicker v-model="customFrom" :placeholder="$t('filter.from')" align="right" />
            </div>
          </div>
          <span class="sep">-</span>
          <div class="date-field-group">
            <span class="field-label">{{ $t('filter.to') }}</span>
            <div class="date-input">
              <BaseDatePicker v-model="customTo" :placeholder="$t('filter.to')" align="right" />
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { ChevronLeftIcon, ChevronRightIcon } from 'lucide-vue-next'
import { useI18n } from 'vue-i18n'
import BaseSelect from './BaseSelect.vue'
import BaseDatePicker from './BaseDatePicker.vue'

const props = defineProps({
  modelValue: {
    type: Object,
    default: () => ({ mode: 'month', from: null, to: null })
  }
})

const emit = defineEmits(['update:modelValue'])
const { tm } = useI18n()

const mode = ref(props.modelValue.mode || 'month')
const currentDate = ref(new Date())
const customFrom = ref('')
const customTo = ref('')

const currentYear = computed(() => currentDate.value.getFullYear())
const currentMonthIndex = computed(() => currentDate.value.getMonth())

const monthName = computed(() => {
  const months = tm('datepicker.months')
  return months[currentMonthIndex.value] || ''
})

const isFutureMonth = computed(() => {
  const now = new Date()
  return currentYear.value > now.getFullYear() || (currentYear.value === now.getFullYear() && currentMonthIndex.value >= now.getMonth())
})

const isFutureYear = computed(() => {
  const now = new Date()
  return currentYear.value >= now.getFullYear()
})

const changeMonth = (delta) => {
  const newDate = new Date(currentDate.value)
  newDate.setMonth(newDate.getMonth() + delta)
  currentDate.value = newDate
}

const changeYear = (delta) => {
  const newDate = new Date(currentDate.value)
  newDate.setFullYear(newDate.getFullYear() + delta)
  currentDate.value = newDate
}

const emitUpdate = () => {
  let from = null
  let to = null

  if (mode.value === 'month') {
    const firstDay = new Date(currentYear.value, currentMonthIndex.value, 1)
    const lastDay = new Date(currentYear.value, currentMonthIndex.value + 1, 0) 
    from = formatDate(firstDay)
    to = formatDate(lastDay)
  } else if (mode.value === 'year') {
    const firstDay = new Date(currentYear.value, 0, 1)
    const lastDay = new Date(currentYear.value, 11, 31)
    from = formatDate(firstDay)
    to = formatDate(lastDay)
  } else if (mode.value === 'custom') {
    from = customFrom.value || null
    to = customTo.value || null
  }

  emit('update:modelValue', { mode: mode.value, from, to, year: currentYear.value, month: currentMonthIndex.value })
}

const formatDate = (date) => {
  const y = date.getFullYear()
  const m = String(date.getMonth() + 1).padStart(2, '0')
  const d = String(date.getDate()).padStart(2, '0')
  return `${y}-${m}-${d}`
}

watch([mode, currentDate, customFrom, customTo], () => emitUpdate(), { deep: true })
onMounted(() => emitUpdate())
</script>

<style scoped>
.base-period-selector {
  display: flex;
  align-items: center;
}

.selector-controls {
  display: flex;
  align-items: center;
  gap: 1.5rem;
  flex-wrap: wrap;
}

.mode-wrapper {
  width: 260px;
}

.nav-wrapper {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  background: var(--bg-app);
  border: 1px solid var(--border);
  border-radius: var(--radius-md);
  padding: 0.25rem;
}

.icon-btn {
  background: transparent;
  border: none;
  color: var(--text-muted);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0.4rem;
  border-radius: var(--radius-sm);
  transition: all 0.2s ease;
}

.icon-btn:hover:not(:disabled) {
  background: var(--bg-panel);
  color: var(--primary);
}

.icon-btn:disabled {
  opacity: 0.3;
  cursor: not-allowed;
}

.nav-label {
  font-weight: 600;
  color: var(--text-main);
  min-width: 120px;
  text-align: center;
  font-size: 0.95rem;
  /* Přidáno flex: 1 pro vycentrování textu a vytlačení šipek do stran */
  flex: 1; 
}

.custom-wrapper {
  display: flex;
  align-items: center;
}

.date-input-container {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.date-field-group {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.field-label {
  font-size: 0.85rem;
  color: var(--text-muted);
  font-weight: 600;
}

.date-input {
  width: 140px;
  position: relative;
}

.sep {
  color: var(--text-muted);
  font-weight: bold;
}

@media (max-width: 900px) {
  .selector-controls {
    flex-direction: column;
    align-items: stretch;
    width: 100%;
  }
  .mode-wrapper {
    width: 100%;
  }
  .nav-wrapper {
    width: 100%; /* Zajišťuje roztáhnutí navigace let/měsíců na celou šířku */
  }
  .custom-wrapper {
    width: 100%; /* Zajišťuje roztáhnutí vlastní volby kalendáře na celou šířku */
  }
  .date-input-container {
    flex-direction: column;
    align-items: stretch;
    width: 100%;
  }
  .date-field-group {
    width: 100%;
  }
  .date-input {
    flex: 1;
  }
  .sep {
    display: none;
  }
}
</style>