<template>
  <div class="base-date-picker" ref="pickerRef">
    <label v-if="label" class="base-label">{{ label }}</label>
    
    <div class="input-wrapper" @click="toggleCalendar">
      <CalendarIcon class="input-icon" :size="18" />
      <input 
        type="text" 
        readonly 
        :value="formattedDate" 
        class="base-input custom-input" 
        placeholder="Vyberte datum"
      />
    </div>

    <transition name="fade-popover">
      <div v-if="isOpen" class="calendar-popover">
        <div class="calendar-header">
          <button type="button" @click.stop="prevMonth" class="cal-btn"><ChevronLeftIcon :size="18" /></button>
          <strong>{{ monthNames[currentMonth] }} {{ currentYear }}</strong>
          <button type="button" @click.stop="nextMonth" class="cal-btn"><ChevronRightIcon :size="18" /></button>
        </div>
        
        <div class="calendar-grid">
          <div v-for="day in weekDays" :key="day" class="cal-day-name">{{ day }}</div>
          
          <div 
            v-for="(day, index) in calendarDays" 
            :key="index" 
            class="cal-day"
            :class="{ 
              'is-empty': !day, 
              'is-selected': isSelected(day),
              'is-today': isToday(day)
            }"
            @click.stop="selectDate(day)"
          >
            {{ day || '' }}
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { CalendarIcon, ChevronLeftIcon, ChevronRightIcon } from 'lucide-vue-next'

const props = defineProps({
  modelValue: String, // Očekává formát YYYY-MM-DD
  label: String
})
const emit = defineEmits(['update:modelValue'])

const isOpen = ref(false)
const pickerRef = ref(null)

const monthNames = ['Leden', 'Únor', 'Březen', 'Duben', 'Květen', 'Červen', 'Červenec', 'Srpen', 'Září', 'Říjen', 'Listopad', 'Prosinec']
const weekDays = ['Po', 'Út', 'St', 'Čt', 'Pá', 'So', 'Ne']

// Stav kalendáře
const currentMonth = ref(new Date().getMonth())
const currentYear = ref(new Date().getFullYear())

// Pokud se modelValue změní zvenčí, aktualizujeme měsíc
watch(() => props.modelValue, (newVal) => {
  if (newVal) {
    const [y, m] = newVal.split('-')
    currentYear.value = parseInt(y)
    currentMonth.value = parseInt(m) - 1
  }
}, { immediate: true })

// Hezké formátování pro zobrazení
const formattedDate = computed(() => {
  if (!props.modelValue) return ''
  const [y, m, d] = props.modelValue.split('-')
  return `${parseInt(d)}. ${parseInt(m)}. ${y}`
})

// Generování mřížky kalendáře
const calendarDays = computed(() => {
  const days = []
  // Zjistíme, kterým dnem začíná měsíc (0 = Neděle, posuneme na Pondělí)
  let firstDay = new Date(currentYear.value, currentMonth.value, 1).getDay()
  const emptySlots = firstDay === 0 ? 6 : firstDay - 1
  
  const daysInMonth = new Date(currentYear.value, currentMonth.value + 1, 0).getDate()

  // Prázdná místa na začátku
  for (let i = 0; i < emptySlots; i++) days.push(null)
  // Samotné dny
  for (let i = 1; i <= daysInMonth; i++) days.push(i)
  
  return days
})

const toggleCalendar = () => { isOpen.value = !isOpen.value }

const nextMonth = () => {
  if (currentMonth.value === 11) { currentMonth.value = 0; currentYear.value++ } 
  else { currentMonth.value++ }
}

const prevMonth = () => {
  if (currentMonth.value === 0) { currentMonth.value = 11; currentYear.value-- } 
  else { currentMonth.value-- }
}

const selectDate = (day) => {
  if (!day) return
  const m = String(currentMonth.value + 1).padStart(2, '0')
  const d = String(day).padStart(2, '0')
  emit('update:modelValue', `${currentYear.value}-${m}-${d}`)
  isOpen.value = false
}

// Zvýraznění dnů
const isSelected = (day) => {
  if (!day || !props.modelValue) return false
  const m = String(currentMonth.value + 1).padStart(2, '0')
  const d = String(day).padStart(2, '0')
  return `${currentYear.value}-${m}-${d}` === props.modelValue
}

const isToday = (day) => {
  if (!day) return false
  const today = new Date()
  return day === today.getDate() && currentMonth.value === today.getMonth() && currentYear.value === today.getFullYear()
}

// Zavření při kliknutí jinam
const handleClickOutside = (event) => {
  if (pickerRef.value && !pickerRef.value.contains(event.target)) {
    isOpen.value = false
  }
}
onMounted(() => document.addEventListener('click', handleClickOutside))
onUnmounted(() => document.removeEventListener('click', handleClickOutside))
</script>

<style scoped>
.base-date-picker { position: relative; display: flex; flex-direction: column; gap: 0.5rem; width: 100%; text-align: left; }
.base-label { font-size: 0.9rem; font-weight: 600; color: #475569; }

.input-wrapper { position: relative; cursor: pointer; }
.input-icon { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; pointer-events: none; }
.base-input { width: 100%; padding: 0.75rem 1rem 0.75rem 2.5rem; border: 1px solid var(--border); border-radius: 10px; background: white; font-size: 0.95rem; font-family: inherit; color: var(--text-main); cursor: pointer; outline: none; transition: border-color 0.2s; }
.input-wrapper:hover .base-input { border-color: var(--primary); }

.calendar-popover { position: absolute; top: calc(100% + 0.5rem); left: 0; width: 280px; background: white; border: 1px solid var(--border); border-radius: 12px; box-shadow: var(--shadow-md); padding: 1rem; z-index: 100; }
.calendar-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; color: #1e293b; }
.cal-btn { background: none; border: none; padding: 0.25rem; cursor: pointer; border-radius: 6px; display: flex; align-items: center; color: #64748b; transition: all 0.2s; }
.cal-btn:hover { background: #f1f5f9; color: #1e293b; }

.calendar-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 0.25rem; text-align: center; }
.cal-day-name { font-size: 0.75rem; font-weight: 700; color: #94a3b8; margin-bottom: 0.25rem; }
.cal-day { padding: 0.4rem 0; font-size: 0.9rem; font-weight: 500; color: #334155; border-radius: 6px; cursor: pointer; transition: all 0.2s; }
.cal-day:not(.is-empty):hover { background: #f1f5f9; }
.cal-day.is-empty { cursor: default; }

.cal-day.is-today { color: var(--primary-hover); font-weight: 800; background: #fef9c3; }
.cal-day.is-selected { background: var(--primary); color: #1e293b; font-weight: 800; }

.fade-popover-enter-active, .fade-popover-leave-active { transition: opacity 0.2s, transform 0.2s; }
.fade-popover-enter-from, .fade-popover-leave-to { opacity: 0; transform: translateY(-10px); }

@media (max-width: 600px) {
  .calendar-popover { width: 100%; }
}
</style>