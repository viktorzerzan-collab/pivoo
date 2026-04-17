<template>
  <div class="opening-hours-input">
    <label v-if="label" class="input-label">{{ label }}</label>
    
    <div class="days-container">
      <div v-for="day in days" :key="day.id" class="day-row">
        <div class="day-name">
          <span class="day-label">{{ day.name }}</span>
        </div>
        
        <div class="day-controls">
          <label class="closed-checkbox">
            <input 
              type="checkbox" 
              v-model="localValue[day.id].closed"
              @change="emitUpdate"
            >
            <span class="checkbox-text">Zavřeno</span>
          </label>

          <div v-if="!localValue[day.id].closed" class="time-inputs">
            <input 
              type="time" 
              v-model="localValue[day.id].from"
              class="time-input"
              @change="emitUpdate"
            >
            <span class="time-separator">-</span>
            <input 
              type="time" 
              v-model="localValue[day.id].to"
              class="time-input"
              @change="emitUpdate"
            >
          </div>
          <div v-else class="time-inputs closed-placeholder">
            <span>---</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'

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

// Výchozí prázdná struktura
const getEmptySchedule = () => {
  const schedule = {}
  days.forEach(day => {
    schedule[day.id] = { closed: false, from: '', to: '' }
  })
  return schedule
}

const localValue = ref(getEmptySchedule())

// Při načtení se pokusíme rozparsovat existující hodnotu (pokud upravujeme existující záznam)
onMounted(() => {
  parseIncomingValue()
})

watch(() => props.modelValue, (newVal) => {
  // Zabráníme zbytečnému přepisování, pokud změna vychází odsud
  if (JSON.stringify(localValue.value) !== newVal) {
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

    // Sloučíme s prázdným rozvrhem, aby nám nechyběly dny, kdyby byl JSON neúplný
    const schedule = getEmptySchedule()
    for (const key in parsed) {
      if (schedule[key]) {
        schedule[key] = { ...schedule[key], ...parsed[key] }
      }
    }
    localValue.value = schedule
  } catch (e) {
    console.error("Chyba při parsování otevírací doby:", e)
    localValue.value = getEmptySchedule()
  }
}

const emitUpdate = () => {
  // Emitujeme jako JSON string, aby se to rovnou dalo uložit do DB
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
  font-weight: 500;
  color: var(--text-main);
  margin-bottom: 0.25rem;
}

.days-container {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  background: var(--bg-panel);
  border: 1px solid var(--border);
  border-radius: 8px;
  padding: 1rem;
}

.day-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-bottom: 0.5rem;
  border-bottom: 1px solid var(--border);
}

.day-row:last-child {
  padding-bottom: 0;
  border-bottom: none;
}

.day-name {
  flex: 0 0 80px;
}

.day-label {
  font-weight: 500;
  color: var(--text-main);
  font-size: 0.9rem;
}

.day-controls {
  display: flex;
  align-items: center;
  gap: 1rem;
  flex: 1;
  justify-content: flex-end;
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

.time-inputs {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  width: 170px; /* Pevná šířka pro zarovnání pod sebe */
  justify-content: center;
}

.closed-placeholder {
  color: var(--text-muted);
  font-weight: 500;
}

.time-input {
  width: 75px;
  padding: 0.3rem;
  border: 1px solid var(--border);
  border-radius: 4px;
  background: var(--bg-body);
  color: var(--text-main);
  font-size: 0.85rem;
  font-family: inherit;
}

.time-input:focus {
  outline: none;
  border-color: var(--primary);
}

.time-separator {
  color: var(--text-muted);
  font-weight: bold;
}

@media (max-width: 480px) {
  .day-row {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.5rem;
  }
  .day-controls {
    width: 100%;
    justify-content: space-between;
  }
}
</style>