<template>
  <div class="statistics-page">
    <div class="view-header">
      <div class="header-actions">
        <BaseSwitch v-model="scope" :options="scopeOptions" />
        <BasePeriodSelector v-model="periodSelection" />
      </div>
    </div>

    <div class="stats-container">
      <BaseLoader :show="isLoading" />

      <template v-if="!isLoading">
        <div class="panel-card overview-card mb-4" v-if="statsData.overview">
          <div class="panel-header">
            <h3>
              <BeerIcon class="panel-icon" :size="20" /> 
              {{ scope === 'me' ? $t('statistics.me_and_beer_period') : $t('statistics.global_and_beer_period') }}
            </h3>
          </div>
          <StatsBoard :stats="statsData.overview" />
        </div>

        <div class="stats-grid-detailed">
          
          <StatsRankingCard 
            :title="$t('statistics.fav_beers')"
            :icon="BeerIcon"
            :items="statsData.beers.map(item => ({ name: item.name, sub: item.brewery, count: item.count }))"
            :emptyText="$t('statistics.empty_data')"
          />

          <StatsRankingCard 
            :title="$t('statistics.fav_breweries')"
            :icon="FactoryIcon"
            :items="statsData.breweries.map(item => ({ 
              name: item.name, 
              sub: $t('statistics.tasted_types', { count: item.beer_types }), 
              count: item.count 
            }))"
            :emptyText="$t('statistics.empty_data')"
          />

          <StatsRankingCard 
            :title="$t('statistics.fav_locations')"
            :icon="MapPinIcon"
            :items="statsData.locations.map(item => ({ 
              name: translateDynamic(item.name), 
              sub: (item.city ? translateDynamic(item.city) + ' • ' : '') + $t('statistics.visits', { count: item.visits }), 
              count: item.count 
            }))"
            :emptyText="$t('statistics.empty_data')"
          />

          <StatsStylesCard 
            :title="$t('statistics.style_distribution')"
            :icon="ShapesIcon"
            :styles="statsData.styles.map(s => ({ 
              label: translateStyle(s.name), 
              count: s.count, 
              percent: (s.count / (statsData.styles[0]?.count || 1) * 100) 
            }))"
            :emptyText="$t('statistics.empty_styles')"
          />

          <StatsDiversityCard 
            :title="$t('statistics.diversity')"
            :icon="TrophyIcon"
            :uniqueCount="statsData.collector.unique_count"
            :totalCount="statsData.collector.total_count"
            :percent="collectorPercent"
            :subText="$t('statistics.unique_beers', { total: statsData.collector.total_count })"
            :percentText="$t('statistics.diversity_percent', { percent: collectorPercent })"
            :emptyText="$t('statistics.empty_data')"
          />

          <StatsEconomyCard 
            :title="$t('statistics.economy')"
            :icon="CoinsIcon"
            :avgTitle="$t('statistics.avg_price')"
            :maxTitle="$t('statistics.max_price')"
            :minTitle="$t('statistics.min_price')"
            :avgSubText="$t('statistics.scope_me')"
            :avgPrice="avgPrice"
            :maxPrice="maxPrice"
            :minPrice="minPrice"
            :maxBeer="statsData.price_details?.max_beer"
            :minBeer="statsData.price_details?.min_beer"
            :currency="userCurrency"
            :isLoadingRate="isLoadingRate"
            :emptyText="$t('statistics.empty_data')"
          />

          <StatsChartCard 
            :title="$t('statistics.weekly_rhythm')"
            :icon="CalendarDaysIcon"
            :data="dayActivity"
            :emptyText="$t('statistics.empty_week')"
          />

          <StatsChartCard 
            v-if="periodSelection.mode === 'month'"
            :title="$t('statistics.monthly_rhythm')"
            :icon="CalendarDaysIcon"
            :data="monthDaysActivity"
            :emptyText="$t('statistics.empty_month')"
          />

          <StatsChartCard 
            v-if="periodSelection.mode === 'year'"
            :title="$t('statistics.yearly_rhythm')"
            :icon="CalendarDaysIcon"
            :data="monthActivity"
            :emptyText="$t('statistics.empty_year')"
          />

        </div>
      </template>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch, computed } from 'vue'
import { 
  BeerIcon, FactoryIcon, MapPinIcon, 
  CalendarDaysIcon, TrophyIcon, 
  CoinsIcon, ShapesIcon
} from 'lucide-vue-next'
import { useI18n } from 'vue-i18n'
import { apiFetch } from '../api'
import { useToastStore } from '../stores/toast'
import { useAuthStore } from '../stores/auth'
import BaseLoader from '../components/BaseLoader.vue'
import BaseSwitch from '../components/BaseSwitch.vue'
import BasePeriodSelector from '../components/BasePeriodSelector.vue'
import StatsBoard from '../components/StatsBoard.vue'

// Import nových komponent
import StatsRankingCard from '../components/StatsRankingCard.vue'
import StatsChartCard from '../components/StatsChartCard.vue'
import StatsStylesCard from '../components/StatsStylesCard.vue'
import StatsDiversityCard from '../components/StatsDiversityCard.vue'
import StatsEconomyCard from '../components/StatsEconomyCard.vue'

const toastStore = useToastStore()
const authStore = useAuthStore()
const { t, tm, te } = useI18n()

const isLoading = ref(true)
const scope = ref('me')

const periodSelection = ref({
  mode: 'month',
  from: null,
  to: null,
  year: new Date().getFullYear(),
  month: new Date().getMonth()
})

const scopeOptions = computed(() => [
  { value: 'me', label: t('statistics.scope_me') },
  { value: 'global', label: t('statistics.scope_global') }
])

const statsData = ref({
  beers: [], breweries: [], locations: [], days: [], months: [], month_days: [],
  collector: { unique_count: 0, total_count: 0 },
  overview: null,
  styles: [], prices: { avg_price: 0, min_price: 0, max_price: 0 },
  price_details: { min_beer: null, max_beer: null }
})

const userCurrency = computed(() => authStore.defaultCurrency || 'CZK')
const exchangeRate = ref(1.0)
const isLoadingRate = ref(false)

const fetchRate = async () => {
  if (userCurrency.value === 'CZK') {
    exchangeRate.value = 1.0;
    return;
  }
  isLoadingRate.value = true;
  try {
    const res = await fetch(`https://cdn.jsdelivr.net/npm/@fawazahmed0/currency-api@latest/v1/currencies/czk.json`);
    const data = await res.json();
    const rate = data.czk[userCurrency.value.toLowerCase()];
    if (rate) {
      exchangeRate.value = rate;
    }
  } catch (e) {
    console.error("Nepodařilo se načíst kurz pro statistiky:", e);
  } finally {
    isLoadingRate.value = false;
  }
}

const avgPrice = computed(() => Math.round((statsData.value.prices?.avg_price || 0) * exchangeRate.value))
const minPrice = computed(() => Math.round((statsData.value.prices?.min_price || 0) * exchangeRate.value))
const maxPrice = computed(() => Math.round((statsData.value.prices?.max_price || 0) * exchangeRate.value))

const translateDynamic = (val) => {
  if (!val) return val
  const key = `dynamic.locations.${val}`
  return te(key) ? t(key) : val
}

const translateStyle = (val) => {
  if (!val) return t('cards.no_style')
  const key = `dynamic.styles.${val}`
  return te(key) ? t(key) : val
}

const collectorPercent = computed(() => {
  if (!statsData.value.collector || statsData.value.collector.total_count == 0) return 0
  return Math.round((statsData.value.collector.unique_count / statsData.value.collector.total_count) * 100)
})

const dayActivity = computed(() => {
  const dayNames = tm('days')
  const labels = [dayNames.monday, dayNames.tuesday, dayNames.wednesday, dayNames.thursday, dayNames.friday, dayNames.saturday, dayNames.sunday].map(d => d.substring(0, 2))
  
  if (!statsData.value.days || statsData.value.days.length === 0) return []
  const maxVal = Math.max(...statsData.value.days.map(d => parseInt(d.count)), 0)
  return labels.map((name, index) => {
    const dbDay = statsData.value.days.find(d => parseInt(d.day_index) === index)
    const count = dbDay ? parseInt(dbDay.count) : 0
    return { label: name, count: count, percent: maxVal > 0 ? (count / maxVal) * 100 : 0, isWeekend: index >= 5 }
  })
})

const monthActivity = computed(() => {
  const monthNames = tm('months_short')
  const labels = [
    monthNames.jan, monthNames.feb, monthNames.mar, monthNames.apr, 
    monthNames.may, monthNames.jun, monthNames.jul, monthNames.aug, 
    monthNames.sep, monthNames.oct, monthNames.nov, monthNames.dec
  ]
  
  if (!statsData.value.months || statsData.value.months.length === 0) return []
  const maxVal = Math.max(...statsData.value.months.map(m => parseInt(m.count)), 0)
  return labels.map((name, index) => {
    const dbMonth = statsData.value.months.find(m => parseInt(m.month_index) === (index + 1))
    const count = dbMonth ? parseInt(dbMonth.count) : 0
    return { label: name, count: count, percent: maxVal > 0 ? (count / maxVal) * 100 : 0 }
  })
})

const monthDaysActivity = computed(() => {
  if (periodSelection.value.mode !== 'month' || periodSelection.value.year === null) return []

  const daysInMonth = new Date(periodSelection.value.year, periodSelection.value.month + 1, 0).getDate()
  const labels = Array.from({ length: daysInMonth }, (_, i) => String(i + 1))

  if (!statsData.value.month_days || statsData.value.month_days.length === 0) return []
  const maxVal = Math.max(...statsData.value.month_days.map(d => parseInt(d.count)), 0)

  return labels.map((label, index) => {
    const dayNum = index + 1
    const dbDay = statsData.value.month_days.find(d => parseInt(d.day_index) === dayNum)
    const count = dbDay ? parseInt(dbDay.count) : 0
    return {
      label: label,
      count: count,
      percent: maxVal > 0 ? (count / maxVal) * 100 : 0
    }
  })
})

const fetchDetailedStats = async () => {
  isLoading.value = true
  try {
    const { from, to } = periodSelection.value
    let url = `/detailed_stats.php?scope=${scope.value}`
    
    if (from && to) {
      url += `&date_from=${from}&date_to=${to}`
    }

    const res = await apiFetch(url)
    if (res.status === 'success') {
      statsData.value = res.data
    }
  } catch (error) {
    toastStore.showToast(t('toast.communication_error'), 'toast-error')
  } finally {
    isLoading.value = false
  }
}

watch([periodSelection, scope], () => fetchDetailedStats(), { deep: true })
watch(userCurrency, () => fetchRate())

onMounted(() => {
  fetchRate()
})
</script>

<style scoped>
.statistics-page { 
  flex: 1; 
  display: flex; 
  flex-direction: column; 
  width: 100%;
  max-width: 100vw;
  overflow-x: hidden; 
}

.view-header { 
  background-color: var(--bg-app); 
  padding: 1rem 0; 
  margin-bottom: 1.5rem; 
  transition: background-color 0.3s ease;
  position: relative;
  z-index: 10; 
}

.header-actions { display: flex; justify-content: space-between; align-items: center; gap: 1.5rem; flex-wrap: wrap;}

.panel-card { background: var(--bg-panel); border-radius: var(--radius-md); border: 1px solid var(--border); padding: 1.5rem; transition: background-color 0.3s ease, border-color 0.3s ease; }
.panel-card:hover { border-color: var(--primary); }
.panel-header { border-bottom: 1px solid var(--border); padding-bottom: 1rem; margin-bottom: 1.5rem; transition: border-color 0.3s ease; }
.panel-header h3 { margin: 0; display: flex; align-items: center; gap: 0.5rem; font-size: 1.25rem; color: var(--text-main); transition: color 0.3s ease; }
.panel-icon { color: var(--primary); }

.overview-card {
  margin-bottom: 2rem;
}

.stats-grid-detailed { 
  display: grid; 
  grid-template-columns: repeat(auto-fill, minmax(min(100%, 350px), 1fr)); 
  gap: 2rem; 
}

@media (max-width: 800px) {
  .header-actions { flex-direction: column; align-items: stretch; }
  .stats-grid-detailed { grid-template-columns: 1fr; }
  .panel-card { padding: 1rem; }
}
</style>

<style>
/* Rozšíření selektoru období, aby se texty nelámaly */
.statistics-page .mode-wrapper {
  width: 240px !important;
}

@media (max-width: 600px) {
  .statistics-page .mode-wrapper {
    width: 100% !important;
  }
}
</style>