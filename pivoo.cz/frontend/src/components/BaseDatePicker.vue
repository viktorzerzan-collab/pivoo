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
        :placeholder="$t('datepicker.placeholder')"
      />
    </div>

    <transition name="fade-popover">
      <div v-if="isOpen" class="calendar-popover" :class="{ 'align-right': align === 'right' }" @click.stop>
        <div class="calendar-header">
          <button type="button" @click="prevMonth" class="cal-btn"><ChevronLeftIcon :size="18" /></button>
          
          <div class="date-selectors">
            <div class="custom-select-mini" ref="monthDropdownRef">
              <button type="button" class="select-trigger-mini" @click="toggleMonthDropdown">
                {{ monthNames[currentMonth] }}
                <ChevronDownIcon :size="14" :class="{ 'rotated': isMonthOpen }" />
              </button>
              <transition name="dropdown-slide">
                <ul v-if="isMonthOpen" class="options-menu-mini">
                  <li v-for="(name, index) in monthNames" :key="index" 
                      @click="selectMonth(index)" 
                      :class="{ 'is-selected': currentMonth === index }">
                    {{ name }}
                  </li>
                </ul>
              </transition>
            </div>

            <div class="custom-select-mini" ref="yearDropdownRef">
              <button type="button" class="select-trigger-mini" @click="toggleYearDropdown">
                {{ currentYear }}
                <ChevronDownIcon :size="14" :class="{ 'rotated': isYearOpen }" />
              </button>
              <transition name="dropdown-slide">
                <ul v-if="isYearOpen" class="options-menu-mini">
                  <li v-for="year in yearOptions" :key="year" 
                      @click="selectYear(year)" 
                      :class="{ 'is-selected': currentYear === year }">
                    {{ year }}
                  </li>
                </ul>
              </transition>
            </div>
          </div>

          <button type="button" @click="nextMonth" class="cal-btn"><ChevronRightIcon :size="18" /></button>
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
import { CalendarIcon, ChevronLeftIcon, ChevronRightIcon, ChevronDownIcon } from 'lucide-vue-next'
import { useI18n } from 'vue-i18n'

const props = defineProps({
  modelValue: String,
  label: String,
  align: {
    type: String,
    default: 'left'
  }
})
const emit = defineEmits(['update:modelValue'])

const { t, tm } = useI18n()

const isOpen = ref(false)
const isMonthOpen = ref(false)
const isYearOpen = ref(false)
const pickerRef = ref(null)

const monthNames = computed(() => tm('datepicker.months'))
const weekDays = computed(() => tm('datepicker.days'))

const currentMonth = ref(new Date().getMonth())
const currentYear = ref(new Date().getFullYear())

const yearOptions = computed(() => {
  const currentY = new Date().getFullYear()
  const years = []
  for (let i = currentY - 100; i <= currentY; i++) {
    years.push(i)
  }
  return years.reverse() 
})

watch(() => props.modelValue, (newVal) => {
  if (newVal) {
    const [y, m] = newVal.split('-')
    currentYear.value = parseInt(y)
    currentMonth.value = parseInt(m) - 1
  }
}, { immediate: true })

const formattedDate = computed(() => {
  if (!props.modelValue) return ''
  const [y, m, d] = props.modelValue.split('-')
  return `${parseInt(d)}. ${parseInt(m)}. ${y}`
})

const calendarDays = computed(() => {
  const days = []
  let firstDay = new Date(currentYear.value, currentMonth.value, 1).getDay()
  const emptySlots = firstDay === 0 ? 6 : firstDay - 1
  const daysInMonth = new Date(currentYear.value, currentMonth.value + 1, 0).getDate()
  for (let i = 0; i < emptySlots; i++) days.push(null)
  for (let i = 1; i <= daysInMonth; i++) days.push(i)
  return days
})

const toggleCalendar = () => { isOpen.value = !isOpen.value }
const toggleMonthDropdown = () => { 
  isYearOpen.value = false
  isMonthOpen.value = !isMonthOpen.value 
}
const toggleYearDropdown = () => { 
  isMonthOpen.value = false
  isYearOpen.value = !isYearOpen.value 
}

const selectMonth = (index) => {
  currentMonth.value = index
  isMonthOpen.value = false
}

const selectYear = (year) => {
  currentYear.value = year
  isYearOpen.value = false
}

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

const handleClickOutside = (event) => {
  if (pickerRef.value && !pickerRef.value.contains(event.target)) {
    isOpen.value = false
    isMonthOpen.value = false
    isYearOpen.value = false
  }
}

onMounted(() => document.addEventListener('click', handleClickOutside))
onUnmounted(() => document.removeEventListener('click', handleClickOutside))
</script>

<style scoped>
.base-date-picker { position: relative; display: flex; flex-direction: column; gap: 0.5rem; width: 100%; text-align: left; }
.base-label { font-size: 0.9rem; font-weight: 600; color: var(--text-main); transition: color 0.3s ease; }

.input-wrapper { position: relative; cursor: pointer; }
.input-icon { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted); pointer-events: none; transition: color 0.3s ease; }
.base-input { width: 100%; padding: 0.75rem 1rem 0.75rem 2.5rem; border: 1px solid var(--border); border-radius: var(--radius-sm); background-color: var(--bg-panel); font-size: 0.95rem; font-family: inherit; color: var(--text-main); cursor: pointer; outline: none; transition: all 0.3s ease; box-shadow: none; }
.input-wrapper:hover .base-input { border-color: var(--primary); }

.calendar-popover { position: absolute; top: calc(100% + 0.5rem); left: 0; width: 310px; background-color: var(--bg-panel); border: 1px solid var(--border); border-radius: var(--radius-md); box-shadow: var(--shadow-floating); padding: 1rem; z-index: 100; transition: all 0.3s ease; }
.calendar-popover.align-right { left: auto; right: 0; }

.calendar-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; color: var(--text-main); gap: 0.5rem; }

.cal-btn { background: none; border: none; padding: 0.25rem; cursor: pointer; border-radius: var(--radius-sm); display: flex; align-items: center; color: var(--text-muted); transition: all 0.2s; }
.cal-btn:hover { background-color: var(--border); color: var(--text-main); }

.date-selectors { display: flex; gap: 0.4rem; align-items: center; flex: 1; justify-content: center; }
.custom-select-mini { position: relative; }

.select-trigger-mini {
  display: flex; align-items: center; gap: 0.3rem;
  padding: 0.4rem 0.6rem;
  background-color: var(--bg-app);
  color: var(--text-main);
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  font-size: 0.85rem; font-weight: 700;
  cursor: pointer; transition: all 0.2s ease;
  white-space: nowrap;
}
.select-trigger-mini:hover { border-color: var(--primary); }
.select-trigger-mini svg { transition: transform 0.3s ease; color: var(--text-muted); }
.select-trigger-mini svg.rotated { transform: rotate(180deg); color: var(--primary); }

.options-menu-mini {
  position: absolute; top: calc(100% + 5px); left: 50%; transform: translateX(-50%);
  background-color: var(--bg-panel);
  border: 1px solid var(--border);
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-floating);
  z-index: 1001;
  max-height: 200px; overflow-y: auto;
  list-style: none; padding: 0.4rem; margin: 0;
  min-width: 100px;
}

.options-menu-mini li {
  padding: 0.5rem 0.75rem; border-radius: var(--radius-sm);
  cursor: pointer; font-size: 0.85rem; font-weight: 500;
  color: var(--text-main); transition: all 0.2s ease;
  text-align: left;
}
.options-menu-mini li:hover { background-color: var(--card-hover-bg); color: var(--primary); }
.options-menu-mini li.is-selected { background-color: rgba(250, 204, 21, 0.1); color: var(--primary); font-weight: 700; }

.options-menu-mini::-webkit-scrollbar { width: 4px; }
.options-menu-mini::-webkit-scrollbar-thumb { background-color: var(--border); border-radius: var(--radius-sm); }

.dropdown-slide-enter-active, .dropdown-slide-leave-active { transition: opacity 0.2s, transform 0.2s; }
.dropdown-slide-enter-from, .dropdown-slide-leave-to { opacity: 0; transform: translate(-50%, -10px); }

.calendar-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 0.25rem; text-align: center; }
.cal-day-name { font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem; }
.cal-day { padding: 0.4rem 0; font-size: 0.9rem; font-weight: 500; color: var(--text-main); border-radius: var(--radius-sm); cursor: pointer; transition: all 0.2s; }
.cal-day:not(.is-empty):hover { background-color: var(--border); }
.cal-day.is-empty { cursor: default; }

.cal-day.is-today { color: var(--primary-hover); font-weight: 800; background-color: rgba(250, 204, 21, 0.1); }
.cal-day.is-selected { background-color: var(--primary); color: #1e293b; font-weight: 800; }

.fade-popover-enter-active, .fade-popover-leave-active { transition: opacity 0.2s, transform 0.2s; }
.fade-popover-enter-from, .fade-popover-leave-to { opacity: 0; transform: translateY(-10px); }

@media (max-width: 600px) {
  .calendar-popover { width: 100%; left: 0; right: 0; }
}
</style>