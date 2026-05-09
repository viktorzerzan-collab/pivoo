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
          
          <div class="panel-card stats-card">
            <div class="panel-header">
              <h3><BeerIcon :size="20" class="panel-icon" /> {{ $t('statistics.fav_beers') }}</h3>
            </div>
            <div class="ranking-list" v-if="statsData.beers.length > 0">
              <div v-for="(item, index) in statsData.beers" :key="index" class="ranking-item">
                <div class="rank-number">{{ index + 1 }}</div>
                <div class="item-info">
                  <div class="item-name"><strong>{{ item.name }}</strong></div>
                  <div class="item-sub">{{ item.brewery }}</div>
                </div>
                <div class="item-count">{{ item.count }}x</div>
              </div>
            </div>
            <div v-else class="empty-stats">{{ $t('statistics.empty_data') }}</div>
          </div>

          <div class="panel-card stats-card">
            <div class="panel-header">
              <h3><FactoryIcon :size="20" class="panel-icon" /> {{ $t('statistics.fav_breweries') }}</h3>
            </div>
            <div class="ranking-list" v-if="statsData.breweries.length > 0">
              <div v-for="(item, index) in statsData.breweries" :key="index" class="ranking-item">
                <div class="rank-number">{{ index + 1 }}</div>
                <div class="item-info">
                  <div class="item-name"><strong>{{ item.name }}</strong></div>
                  <div class="item-sub">{{ $t('statistics.tasted_types', { count: item.beer_types }) }}</div>
                </div>
                <div class="item-count">{{ item.count }}x</div>
              </div>
            </div>
            <div v-else class="empty-stats">{{ $t('statistics.empty_data') }}</div>
          </div>

          <div class="panel-card stats-card">
            <div class="panel-header">
              <h3><MapPinIcon :size="20" class="panel-icon" /> {{ $t('statistics.fav_locations') }}</h3>
            </div>
            <div class="ranking-list" v-if="statsData.locations.length > 0">
              <div v-for="(item, index) in statsData.locations" :key="index" class="ranking-item">
                <div class="rank-number">{{ index + 1 }}</div>
                <div class="item-info">
                  <div class="item-name"><strong>{{ translateDynamic(item.name) }}</strong></div>
                  <div class="item-sub">
                    <span v-if="item.city">{{ translateDynamic(item.city) }} • </span>
                    {{ $t('statistics.visits', { count: item.visits }) }}
                  </div>
                </div>
                <div class="item-count">{{ item.count }}x</div>
              </div>
            </div>
            <div v-else class="empty-stats">{{ $t('statistics.empty_data') }}</div>
          </div>

          <div class="panel-card stats-card styles-card">
            <div class="panel-header">
              <h3><ShapesIcon :size="20" class="panel-icon" /> {{ $t('statistics.style_distribution') }}</h3>
            </div>
            <div class="styles-list" v-if="statsData.styles.length > 0">
              <div v-for="style in statsData.styles" :key="style.name" class="style-row">
                <div class="style-info">
                  <span class="style-name">{{ translateStyle(style.name) }}</span>
                  <span class="style-count">{{ style.count }}x</span>
                </div>
                <div class="style-bar-bg">
                  <div class="style-bar-fill" :style="{ width: (style.count / (statsData.styles[0]?.count || 1) * 100) + '%' }"></div>
                </div>
              </div>
            </div>
            <div v-else class="empty-stats">{{ $t('statistics.empty_styles') }}</div>
          </div>

          <div class="panel-card stats-card collector-card">
            <div class="panel-header">
              <h3><TrophyIcon :size="20" class="panel-icon" /> {{ $t('statistics.diversity') }}</h3>
            </div>
            <div class="collector-stats" v-if="statsData.collector">
              <div class="collector-main">
                <div class="collector-val">{{ statsData.collector.unique_count }}</div>
                <div class="collector-label">{{ $t('statistics.unique_beers', { total: statsData.collector.total_count }) }}</div>
              </div>
              <div class="collector-progress">
                <div class="progress-bar">
                  <div class="progress-fill" :style="{ width: collectorPercent + '%' }"></div>
                </div>
                <div class="progress-text">{{ $t('statistics.diversity_percent', { percent: collectorPercent }) }}</div>
              </div>
            </div>
          </div>

          <div class="panel-card stats-card price-card">
            <div class="panel-header">
              <h3><CoinsIcon :size="20" class="panel-icon" /> {{ $t('statistics.economy') }}</h3>
            </div>
            <div class="ranking-list" v-if="statsData.prices">
              
              <div class="ranking-item highlight">
                <div class="item-info">
                  <div class="item-name"><strong>{{ $t('statistics.avg_price') }}</strong></div>
                  <div class="item-sub" v-if="statsData.prices.avg_price > 0">{{ $t('statistics.scope_me') }}</div>
                </div>
                <div class="item-count">
                  <template v-if="isLoadingRate">...</template>
                  <template v-else>{{ avgPrice }} {{ userCurrency }}</template>
                </div>
              </div>

              <div class="ranking-item">
                <div class="item-info">
                  <div class="item-name"><strong>{{ $t('statistics.max_price') }}</strong></div>
                  <div class="item-sub" v-if="statsData.price_details?.max_beer">{{ statsData.price_details.max_beer }}</div>
                </div>
                <div class="item-count">
                  <template v-if="isLoadingRate">...</template>
                  <template v-else>{{ maxPrice }} {{ userCurrency }}</template>
                </div>
              </div>

              <div class="ranking-item">
                <div class="item-info">
                  <div class="item-name"><strong>{{ $t('statistics.min_price') }}</strong></div>
                  <div class="item-sub" v-if="statsData.price_details?.min_beer">{{ statsData.price_details.min_beer }}</div>
                </div>
                <div class="item-count">
                  <template v-if="isLoadingRate">...</template>
                  <template v-else>{{ minPrice }} {{ userCurrency }}</template>
                </div>
              </div>

            </div>
          </div>

          <div class="panel-card stats-card full-width-card">
            <div class="panel-header">
              <h3><CalendarDaysIcon :size="20" class="panel-icon" /> {{ $t('statistics.weekly_rhythm') }}</h3>
            </div>
            <div class="chart-container" v-if="dayActivity.length > 0">
              <div v-for="day in dayActivity" :key="day.label" class="chart-column-wrapper">
                <div class="column-value">{{ day.count > 0 ? day.count + 'x' : '' }}</div>
                <div class="chart-column">
                  <div class="column-fill" :style="{ '--percent': day.percent + '%' }"></div>
                </div>
                <div class="column-label" :class="{ 'weekend': day.isWeekend }">{{ day.label }}</div>
              </div>
            </div>
            <div v-else class="empty-stats">{{ $t('statistics.empty_week') }}</div>
          </div>

          <div class="panel-card stats-card full-width-card" v-if="periodSelection.mode === 'month'">
            <div class="panel-header">
              <h3><CalendarDaysIcon :size="20" class="panel-icon" /> {{ $t('statistics.monthly_rhythm') }}</h3>
            </div>
            <div class="chart-container" v-if="monthDaysActivity.length > 0">
              <div v-for="day in monthDaysActivity" :key="day.label" class="chart-column-wrapper">
                <div class="column-value">{{ day.count > 0 ? day.count + 'x' : '' }}</div>
                <div class="chart-column">
                  <div class="column-fill" :style="{ '--percent': day.percent + '%' }"></div>
                </div>
                <div class="column-label">{{ day.label }}</div>
              </div>
            </div>
            <div v-else class="empty-stats">{{ $t('statistics.empty_month') }}</div>
          </div>

          <div class="panel-card stats-card full-width-card" v-if="periodSelection.mode === 'year'">
            <div class="panel-header">
              <h3><CalendarDaysIcon :size="20" class="panel-icon" /> {{ $t('statistics.yearly_rhythm') }}</h3>
            </div>
            <div class="chart-container" v-if="monthActivity.length > 0">
              <div v-for="month in monthActivity" :key="month.label" class="chart-column-wrapper">
                <div class="column-value">{{ month.count > 0 ? month.count + 'x' : '' }}</div>
                <div class="chart-column">
                  <div class="column-fill" :style="{ '--percent': month.percent + '%' }"></div>
                </div>
                <div class="column-label">{{ month.label }}</div>
              </div>
            </div>
            <div v-else class="empty-stats">{{ $t('statistics.empty_year') }}</div>
          </div>

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

.full-width-card { grid-column: 1 / -1; }

.ranking-list { display: flex; flex-direction: column; gap: 0.75rem; }
.ranking-item { display: flex; align-items: center; gap: 1rem; padding: 0.8rem 1rem; background: var(--bg-app); border: 1px solid var(--border); border-radius: var(--radius-md); transition: all 0.2s; min-width: 0; }
.ranking-item:hover { border-color: var(--primary); }
.ranking-item.highlight { border-color: var(--primary); background: rgba(250, 204, 21, 0.05); }

.rank-number { width: 28px; height: 28px; background: var(--primary); color: #1e293b; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 0.85rem; flex-shrink: 0; }

.item-info { flex-grow: 1; min-width: 0; display: flex; flex-direction: column; gap: 0.1rem; }
.item-name { color: var(--text-main); font-size: 0.95rem; word-break: break-word; line-height: 1.3; transition: color 0.3s ease; }
.item-sub { color: var(--text-muted); font-size: 0.8rem; word-break: break-word; line-height: 1.3; transition: color 0.3s ease; }

.item-count { font-weight: 800; color: var(--primary); font-size: 1.1rem; background: rgba(250, 204, 21, 0.1); padding: 0.25rem 0.75rem; border-radius: var(--radius-sm); flex-shrink: 0; }

.styles-list { 
  display: flex; 
  flex-direction: column; 
  gap: 1rem; 
  max-height: 290px; 
  overflow-y: auto; 
  padding-right: 0.5rem;
}
.styles-list::-webkit-scrollbar { width: 6px; }
.styles-list::-webkit-scrollbar-thumb { background-color: var(--border); border-radius: var(--radius-sm); }
.styles-list::-webkit-scrollbar-thumb:hover { background-color: var(--primary); }

.style-info { display: flex; justify-content: space-between; font-weight: 700; font-size: 0.9rem; margin-bottom: 0.3rem; }
.style-count { color: var(--primary); }
.style-bar-bg { height: 8px; background: var(--bg-app); border-radius: 4px; overflow: hidden; transition: background-color 0.3s ease; }
.style-bar-fill { height: 100%; background: var(--primary); border-radius: 4px; transition: width 1s ease; }

.collector-stats { display: flex; flex-direction: column; gap: 1.25rem; text-align: center; padding: 0.5rem 0; }
.collector-val { font-size: 3rem; font-weight: 900; color: var(--primary); line-height: 1; }
.collector-label { font-size: 0.9rem; color: var(--text-muted); font-weight: 600; transition: color 0.3s ease; }
.progress-bar { height: 10px; background: var(--bg-app); border-radius: 5px; overflow: hidden; margin-bottom: 0.4rem; transition: background-color 0.3s ease; }
.progress-fill { height: 100%; background: var(--primary); border-radius: 5px; transition: width 1s ease; }
.progress-text { font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; transition: color 0.3s ease; }

.chart-container { 
  display: flex; 
  justify-content: space-between; 
  align-items: flex-end; 
  height: 180px; 
  padding: 1rem 0.5rem; 
  gap: 0.25rem; 
  overflow: hidden; 
}
.chart-column-wrapper { 
  flex: 1; 
  display: flex; 
  flex-direction: column; 
  align-items: center; 
  gap: 0.5rem; 
  height: 100%; 
  min-width: 0; 
}
.chart-column { 
  width: 100%; 
  flex: 1; 
  background: var(--bg-app); 
  border-radius: 6px; 
  display: flex; 
  align-items: flex-end; 
  overflow: hidden; 
  transition: background-color 0.3s ease; 
}
.column-fill { 
  width: 100%; 
  height: var(--percent, 0%); 
  background: var(--primary); 
  border-radius: 4px; 
  transition: height 0.8s ease; 
}
.column-value { font-size: 0.75rem; font-weight: 800; color: var(--text-main); transition: color 0.3s ease; min-height: 1rem;}
.column-label { font-size: 0.85rem; font-weight: 700; color: var(--text-muted); transition: color 0.3s ease; }
.column-label.weekend { color: var(--orange); }

.empty-stats { padding: 3rem 1rem; text-align: center; color: var(--text-muted); font-style: italic; transition: color 0.3s ease; }

@media (max-width: 800px) {
  .header-actions { flex-direction: column; align-items: stretch; }
  
  .stats-grid-detailed { grid-template-columns: 1fr; }
  .panel-card { padding: 1rem; }
  .ranking-item { padding: 0.6rem 0.75rem; gap: 0.75rem; }

  .chart-container { 
    flex-direction: column; 
    align-items: stretch; 
    height: auto; 
    padding: 0.5rem 0; 
    gap: 0.6rem; 
  }
  .chart-column-wrapper { 
    flex-direction: row; 
    height: auto; 
    gap: 0.75rem; 
    align-items: center;
  }
  .chart-column { 
    height: 8px; 
    flex: 1; 
    width: auto; 
    align-items: stretch; 
    background: var(--bg-app);
    border-radius: 4px;
    order: 2;
  }
  .column-fill { 
    height: 100%; 
    width: var(--percent, 0%); 
    background: var(--primary);
  }
  .column-label { 
    order: 1; 
    width: 32px; 
    text-align: left; 
    font-size: 0.8rem;
  }
  .column-value { 
    order: 3; 
    width: 35px; 
    text-align: right; 
    font-size: 0.75rem;
  }
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