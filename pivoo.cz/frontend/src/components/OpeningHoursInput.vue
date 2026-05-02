<template>
  <div class="opening-hours-input">
    <label v-if="label" class="input-label">{{ label }}</label>
    
    <div class="days-container">
      <div v-for="day in days" :key="day.id" class="day-row">
        <div class="day-info-row">
          <div class="day-name">
            <span class="day-label">{{ day.name }}</span>
          </div>
          
          <div class="day-actions">
            <label class="closed-checkbox">
              <input 
                type="checkbox" 
                v-model="localValue[day.id].closed"
                @change="handleClosedChange(day.id)"
              >
              <span class="checkbox-text">Zavřeno</span>
            </label>
            
            <BaseTooltip v-if="!localValue[day.id].closed" text="Přidat pauzu/interval" position="top">
              <button 
                type="button" 
                class="btn-add-interval" 
                @click="addInterval(day.id)"
              >
                + Přidat čas
              </button>
            </BaseTooltip>
          </div>
        </div>

        <div v-if="!localValue[day.id].closed" class="intervals-list">
          <div v-for="(interval, index) in localValue[day.id].intervals" :key="index" class="interval-row">
            <div class="time-inputs">
              <input 
                type="time" 
                v-model="interval.from"
                class="time-input"
                @change="emitUpdate"
              >
              <span class="time-separator">-</span>
              <input 
                type="time" 
                v-model="interval.to"
                class="time-input"
                @change="emitUpdate"
              >
            </div>
            
            <BaseTooltip v-if="localValue[day.id].intervals.length > 1" text="Odebrat interval" position="top">
              <button 
                type="button" 
                class="btn-remove-interval" 
                @click="removeInterval(day.id, index)"
              >
                <XIcon :size="16" />
              </button>
            </BaseTooltip>
          </div>
        </div>
        <div v-else class="closed-placeholder">
          <span>--- Celý den zavřeno ---</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'
import { XIcon } from 'lucide-vue-next'
import BaseTooltip from './BaseTooltip.vue'

const props = defineProps({
  modelValue: {
    type: [String, Object],
    default: null
  },
  label: {
    type: String,
    default: 'Otevírací doba'
  }
})

const emit = defineEmits(['update:modelValue'])

const days = [
  { id: 1, name: 'Pondělí' },
  { id: 2, name: 'Úterý' },
  { id: 3, name: 'Středa' },
  { id: 4, name: 'Čtvrtek' },
  { id: 5, name: 'Pátek' },
  { id: 6, name: 'Sobota' },
  { id: 7, name: 'Neděle' }
]

const getEmptySchedule = () => {
  const schedule = {}
  days.forEach(day => {
    schedule[day.id] = { closed: false, intervals: [{ from: '', to: '' }] }
  })
  return schedule
}

const localValue = ref(getEmptySchedule())

onMounted(() => {
  parseIncomingValue()
})

watch(() => props.modelValue, (newVal) => {
  if (typeof newVal === 'string' && newVal !== JSON.stringify(localValue.value)) {
    parseIncomingValue()
  }
})

const parseIncomingValue = () => {
  if (!props.modelValue) {
    localValue.value = getEmptySchedule()
    return
  }
  
  try {
    const parsed = typeof props.modelValue === 'string' 
      ? JSON.parse(props.modelValue) 
      : props.modelValue

    const schedule = getEmptySchedule()
    for (const key in parsed) {
      if (schedule[key]) {
        if (parsed[key].from !== undefined || parsed[key].to !== undefined) {
          schedule[key].closed = parsed[key].closed
          schedule[key].intervals = [{ from: parsed[key].from || '', to: parsed[key].to || '' }]
        } else {
          schedule[key] = { ...schedule[key], ...parsed[key] }
        }
      }
    }
    localValue.value = schedule
  } catch (e) {
    console.error("Chyba při parsování otevírací doby:", e)
    localValue.value = getEmptySchedule()
  }
}

const handleClosedChange = (dayId) => {
  if (localValue.value[dayId].closed) {
    localValue.value[dayId].intervals = [{ from: '', to: '' }]
  }
  emitUpdate()
}

const addInterval = (dayId) => {
  localValue.value[dayId].intervals.push({ from: '', to: '' })
  emitUpdate()
}

const removeInterval = (dayId, index) => {
  localValue.value[dayId].intervals.splice(index, 1)
  emitUpdate()
}

const emitUpdate = () => {
  emit('update:modelValue', JSON.stringify(localValue.value))
}
</script>

<style scoped>
.opening-hours-input {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  margin-bottom: 1rem;
}

.input-label {
  font-size: 0.9rem;
  font-weight: 600;
  color: var(--text-main);
  margin-bottom: 0.25rem;
}

.days-container {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  background: var(--bg-panel);
  border: 1px solid var(--border);
  border-radius: var(--radius-md);
  padding: 1rem;
}

.day-row {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  padding-bottom: 0.75rem;
  border-bottom: 1px solid var(--border);
}

.day-row:last-child {
  padding-bottom: 0;
  border-bottom: none;
}

.day-info-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.day-label {
  font-weight: 700;
  color: var(--text-main);
  font-size: 0.95rem;
}

.day-actions {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.closed-checkbox {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  cursor: pointer;
  user-select: none;
}

.checkbox-text {
  font-size: 0.85rem;
  color: var(--text-muted);
}

.intervals-list {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
  padding-left: 0.5rem;
}

.interval-row {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.time-inputs {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  background: var(--bg-app);
  padding: 0.25rem 0.5rem;
  border-radius: var(--radius-sm);
  border: 1px solid var(--border);
}

.time-input {
  width: 85px;
  padding: 0.3rem;
  border: none;
  background: transparent;
  color: var(--text-main);
  font-size: 0.9rem;
  font-family: inherit;
  outline: none;
}

.time-separator {
  color: var(--text-muted);
  font-weight: bold;
}

.btn-add-interval {
  background: var(--blue);
  color: white;
  border: none;
  padding: 0.2rem 0.6rem;
  border-radius: var(--radius-sm);
  font-size: 0.75rem;
  font-weight: 700;
  cursor: pointer;
}

.btn-remove-interval {
  background: var(--danger);
  color: white;
  border: none;
  width: 28px;
  height: 28px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  padding: 0;
  transition: background-color 0.2s ease;
}

.btn-remove-interval:hover {
  background: var(--danger-hover);
}

.btn-remove-interval :deep(svg) {
  margin: 0 !important;
}

.closed-placeholder {
  font-size: 0.85rem;
  color: var(--text-muted);
  font-style: italic;
  padding-left: 0.5rem;
}

@media (max-width: 480px) {
  .day-info-row {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.5rem;
  }
  .day-actions {
    width: 100%;
    justify-content: space-between;
  }
}
</style>