<template>
  <div class="opening-hours-display" v-if="parsedHours">
    <div class="status-badge" :class="isOpenNow ? 'open' : 'closed'" @click="toggleDetails">
      <span class="status-dot"></span>
      <span class="status-text">{{ isOpenNow ? 'Otevřeno' : 'Zavřeno' }}</span>
      <span class="today-hours" v-if="todaySchedule && !todaySchedule.closed && isOpenNow">
        (dnes {{ todaySchedule.from }} - {{ todaySchedule.to }})
      </span>
      <ChevronDownIcon :class="['toggle-icon', { 'rotated': showDetails }]" :size="16" />
    </div>

    <transition name="slide-fade">
      <div v-show="showDetails" class="hours-list">
        <div 
          v-for="day in days" 
          :key="day.id" 
          class="hour-row"
          :class="{ 'is-today': day.id === currentDayId }"
        >
          <span class="day-name">{{ day.name }}</span>
          <span class="day-time" v-if="parsedHours[day.id] && !parsedHours[day.id].closed">
            {{ parsedHours[day.id].from }} - {{ parsedHours[day.id].to }}
          </span>
          <span class="day-time closed" v-else>Zavřeno</span>
        </div>
      </div>
    </transition>
  </div>
  <div v-else class="no-hours">
    <span class="text-muted">Otevírací doba není uvedena</span>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { ChevronDownIcon } from 'lucide-vue-next'

const props = defineProps({
  openingHours: {
    type: [String, Object],
    default: null
  }
})

const days = [
  { id: 1, name: 'Pondělí' },
  { id: 2, name: 'Úterý' },
  { id: 3, name: 'Středa' },
  { id: 4, name: 'Čtvrtek' },
  { id: 5, name: 'Pátek' },
  { id: 6, name: 'Sobota' },
  { id: 7, name: 'Neděle' }
]

const showDetails = ref(false)
const currentTime = ref(new Date())
let timer = null

// Aktualizace času každou minutu pro přesný výpočet "Otevřeno/Zavřeno"
onMounted(() => {
  timer = setInterval(() => {
    currentTime.value = new Date()
  }, 60000)
})

onUnmounted(() => {
  if (timer) clearInterval(timer)
})

const toggleDetails = () => {
  showDetails.value = !showDetails.value
}

// Parsování dat z databáze a kontrola, zda JSON obsahuje reálná data
const parsedHours = computed(() => {
  if (!props.openingHours) return null
  try {
    const parsed = typeof props.openingHours === 'string' ? JSON.parse(props.openingHours) : props.openingHours
    const hasData = Object.values(parsed).some(day => !day.closed && day.from && day.to)
    return hasData ? parsed : null
  } catch (e) {
    return null
  }
})

// Převod číslování dnů (neděle v JS je 0, my potřebujeme 7)
const currentDayId = computed(() => {
  const day = currentTime.value.getDay()
  return day === 0 ? 7 : day
})

const todaySchedule = computed(() => {
  if (!parsedHours.value) return null
  return parsedHours.value[currentDayId.value]
})

// Chytrý výpočet otevřeno/zavřeno vč. případů, kdy otvíračka přesahuje půlnoc
const isOpenNow = computed(() => {
  if (!todaySchedule.value || todaySchedule.value.closed) return false
  
  const from = todaySchedule.value.from
  const to = todaySchedule.value.to
  
  if (!from || !to) return false

  const nowHours = currentTime.value.getHours()
  const nowMinutes = currentTime.value.getMinutes()
  const nowMinutesTotal = nowHours * 60 + nowMinutes

  const [fromH, fromM] = from.split(':').map(Number)
  const fromTotal = fromH * 60 + fromM

  const [toH, toM] = to.split(':').map(Number)
  const toTotal = toH * 60 + toM

  // Standardní případ (např. 10:00 - 22:00)
  if (fromTotal <= toTotal) {
    return nowMinutesTotal >= fromTotal && nowMinutesTotal <= toTotal
  } 
  // Případ přes půlnoc (např. 18:00 - 02:00)
  else {
    return nowMinutesTotal >= fromTotal || nowMinutesTotal <= toTotal
  }
})
</script>

<style scoped>
.opening-hours-display {
  display: inline-flex;
  flex-direction: column;
  font-size: 0.9rem;
  position: relative;
}

.no-hours {
  font-size: 0.9rem;
  color: var(--text-muted);
  font-style: italic;
}

.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.4rem 0.75rem;
  border-radius: 20px;
  background: var(--bg-panel);
  border: 1px solid var(--border);
  cursor: pointer;
  user-select: none;
  transition: background-color 0.2s;
}

.status-badge:hover {
  background: var(--bg-body);
}

.status-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
}

.status-badge.open .status-dot {
  background-color: #10b981;
  box-shadow: 0 0 5px rgba(16, 185, 129, 0.5);
}

.status-badge.closed .status-dot {
  background-color: #ef4444;
}

.status-badge.open .status-text {
  color: #10b981;
  font-weight: 600;
}

.status-badge.closed .status-text {
  color: #ef4444;
  font-weight: 600;
}

.today-hours {
  color: var(--text-muted);
  margin-left: 0.2rem;
  font-size: 0.85rem;
}

.toggle-icon {
  color: var(--text-muted);
  transition: transform 0.3s ease;
  margin-left: 0.2rem;
}

.toggle-icon.rotated {
  transform: rotate(180deg);
}

.hours-list {
  position: absolute;
  top: 100%;
  left: 0;
  margin-top: 0.5rem;
  background: var(--bg-panel);
  border: 1px solid var(--border);
  border-radius: 8px;
  padding: 0.75rem;
  min-width: 220px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  z-index: 50;
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
}

.hour-row {
  display: flex;
  justify-content: space-between;
  padding: 0.2rem 0;
  color: var(--text-main);
  border-bottom: 1px solid var(--border);
}
.hour-row:last-child {
  border-bottom: none;
  padding-bottom: 0;
}

.hour-row.is-today {
  font-weight: bold;
  color: var(--primary);
}

.day-name {
  flex: 1;
}

.day-time {
  font-family: monospace;
  font-size: 0.95rem;
}

.day-time.closed {
  font-family: inherit;
  color: var(--text-muted);
  font-size: 0.85rem;
}

.slide-fade-enter-active {
  transition: all 0.2s ease-out;
}
.slide-fade-leave-active {
  transition: all 0.2s cubic-bezier(1, 0.5, 0.8, 1);
}
.slide-fade-enter-from,
.slide-fade-leave-to {
  transform: translateY(-5px);
  opacity: 0;
}
</style>