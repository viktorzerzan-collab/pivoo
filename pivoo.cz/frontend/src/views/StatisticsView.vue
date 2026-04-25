<template>
  <div class="statistics-page">
    <div class="view-header">
      <div class="header-actions">
        <div class="scope-toggle">
          <button :class="{ active: scope === 'me' }" @click="scope = 'me'">Moje</button>
          <button :class="{ active: scope === 'global' }" @click="scope = 'global'">Globální</button>
        </div>

        <div class="filter-wrapper">
          <BaseSelect v-model="period" :searchable="false">
            <option value="month">Tento měsíc</option>
            <option value="year">Tento rok</option>
            <option value="all">Celková historie</option>
          </BaseSelect>
        </div>
      </div>
    </div>

    <div class="stats-container">
      <BaseLoader :show="isLoading" />

      <template v-if="!isLoading">
        <div class="stats-grid-detailed">
          
          <div class="panel-card stats-card">
            <div class="panel-header">
              <h3><BeerIcon :size="20" class="panel-icon" /> Nejoblíbenější piva</h3>
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
            <div v-else class="empty-stats">Žádná data k zobrazení.</div>
          </div>

          <div class="panel-card stats-card">
            <div class="panel-header">
              <h3><FactoryIcon :size="20" class="panel-icon" /> Nejoblíbenější pivovary</h3>
            </div>
            <div class="ranking-list" v-if="statsData.breweries.length > 0">
              <div v-for="(item, index) in statsData.breweries" :key="index" class="ranking-item">
                <div class="rank-number">{{ index + 1 }}</div>
                <div class="item-info">
                  <div class="item-name"><strong>{{ item.name }}</strong></div>
                  <div class="item-sub">Ochutnáno {{ item.beer_types }} druhů</div>
                </div>
                <div class="item-count">{{ item.count }}x</div>
              </div>
            </div>
            <div v-else class="empty-stats">Žádná data k zobrazení.</div>
          </div>

          <div class="panel-card stats-card">
            <div class="panel-header">
              <h3><MapPinIcon :size="20" class="panel-icon" /> Nejoblíbenější místa</h3>
            </div>
            <div class="ranking-list" v-if="statsData.locations.length > 0">
              <div v-for="(item, index) in statsData.locations" :key="index" class="ranking-item">
                <div class="rank-number">{{ index + 1 }}</div>
                <div class="item-info">
                  <div class="item-name"><strong>{{ item.name }}</strong></div>
                  <div class="item-sub">
                    <span v-if="item.city">{{ item.city }} • </span>
                    {{ item.visits }}x návštěva
                  </div>
                </div>
                <div class="item-count">{{ item.count }}x</div>
              </div>
            </div>
            <div v-else class="empty-stats">Žádná data k zobrazení.</div>
          </div>

          <div class="panel-card stats-card styles-card">
            <div class="panel-header">
              <h3><ShapesIcon :size="20" class="panel-icon" /> Rozložení pivních stylů</h3>
            </div>
            <div class="styles-list" v-if="statsData.styles.length > 0">
              <div v-for="style in statsData.styles" :key="style.name" class="style-row">
                <div class="style-info">
                  <span class="style-name">{{ style.name }}</span>
                  <span class="style-count">{{ style.count }}x</span>
                </div>
                <div class="style-bar-bg">
                  <div class="style-bar-fill" :style="{ width: (style.count / (statsData.styles[0]?.count || 1) * 100) + '%' }"></div>
                </div>
              </div>
            </div>
            <div v-else class="empty-stats">Žádné styly k zobrazení.</div>
          </div>

          <div class="panel-card stats-card collector-card">
            <div class="panel-header">
              <h3><TrophyIcon :size="20" class="panel-icon" /> Pivní rozmanitost</h3>
            </div>
            <div class="collector-stats" v-if="statsData.collector">
              <div class="collector-main">
                <div class="collector-val">{{ statsData.collector.unique_count }}</div>
                <div class="collector-label">unikátních piv ze {{ statsData.collector.total_count }} celkem</div>
              </div>
              <div class="collector-progress">
                <div class="progress-bar">
                  <div class="progress-fill" :style="{ width: collectorPercent + '%' }"></div>
                </div>
                <div class="progress-text">Diverzita: {{ collectorPercent }} %</div>
              </div>
            </div>
          </div>

          <div class="panel-card stats-card price-card">
            <div class="panel-header">
              <h3><CoinsIcon :size="20" class="panel-icon" /> Pivní ekonomika</h3>
            </div>
            <div class="price-stats-grid" v-if="statsData.prices">
              <div class="price-box avg">
                <span class="p-label">Průměrná cena</span>
                <span class="p-val">{{ Math.round(statsData.prices.avg_price) || 0 }} Kč</span>
              </div>
              <div class="price-box min">
                <span class="p-label">Nejlevnější</span>
                <span class="p-val">{{ Math.round(statsData.prices.min_price) || 0 }} Kč</span>
              </div>
              <div class="price-box max">
                <span class="p-label">Nejdražší</span>
                <span class="p-val">{{ Math.round(statsData.prices.max_price) || 0 }} Kč</span>
              </div>
            </div>
          </div>

          <div class="panel-card stats-card full-width-card">
            <div class="panel-header">
              <h3><CalendarDaysIcon :size="20" class="panel-icon" /> Týdenní pivní biorytmus</h3>
            </div>
            <div class="chart-container" v-if="dayActivity.length > 0">
              <div v-for="day in dayActivity" :key="day.label" class="chart-column-wrapper">
                <div class="column-value">{{ day.count }}x</div>
                <div class="chart-column">
                  <div class="column-fill" :style="{ height: day.percent + '%' }"></div>
                </div>
                <div class="column-label" :class="{ 'weekend': day.isWeekend }">{{ day.label }}</div>
              </div>
            </div>
            <div v-else class="empty-stats">Nedostatek dat pro analýzu týdne.</div>
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
import { apiFetch } from '../api'
import BaseLoader from '../components/BaseLoader.vue'
import BaseSelect from '../components/BaseSelect.vue'

const isLoading = ref(true)
const period = ref('month')
const scope = ref('me')
const statsData = ref({
  beers: [], breweries: [], locations: [], days: [],
  collector: { unique_count: 0, total_count: 0 },
  styles: [], prices: { avg_price: 0, min_price: 0, max_price: 0 }
})

const collectorPercent = computed(() => {
  if (!statsData.value.collector || statsData.value.collector.total_count == 0) return 0
  return Math.round((statsData.value.collector.unique_count / statsData.value.collector.total_count) * 100)
})

const dayActivity = computed(() => {
  const dayNames = ['Po', 'Út', 'St', 'Čt', 'Pá', 'So', 'Ne']
  if (!statsData.value.days || statsData.value.days.length === 0) return []
  const maxVal = Math.max(...statsData.value.days.map(d => parseInt(d.count)))
  return dayNames.map((name, index) => {
    const dbDay = statsData.value.days.find(d => parseInt(d.day_index) === index)
    const count = dbDay ? parseInt(dbDay.count) : 0
    return { label: name, count: count, percent: maxVal > 0 ? (count / maxVal) * 100 : 0, isWeekend: index >= 5 }
  })
})

const fetchDetailedStats = async () => {
  isLoading.value = true
  try {
    const res = await apiFetch(`/detailed_stats.php?period=${period.value}&scope=${scope.value}`)
    if (res.status === 'success') {
      statsData.value = res.data
    }
  } catch (error) {
    console.error("Chyba při načítání statistik:", error)
  } finally {
    isLoading.value = false
  }
}

watch([period, scope], () => fetchDetailedStats())
onMounted(() => fetchDetailedStats())
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
  transition: background-color 0.5s ease;
}

.header-actions { display: flex; justify-content: space-between; align-items: center; gap: 1.5rem; }
.scope-toggle { display: flex; background: var(--bg-panel); padding: 4px; border-radius: 10px; border: 1px solid var(--border); }
.scope-toggle button { padding: 0.5rem 1.5rem; border-radius: 7px; border: none; background: none; color: var(--text-muted); font-weight: 700; cursor: pointer; transition: all 0.2s; }
.scope-toggle button.active { background: var(--primary); color: #1e293b; box-shadow: var(--shadow-sm); }
.filter-wrapper { width: 220px; }

.stats-grid-detailed { 
  display: grid; 
  grid-template-columns: repeat(auto-fill, minmax(min(100%, 350px), 1fr)); 
  gap: 2rem; 
}

.full-width-card { grid-column: 1 / -1; }
.stats-card { background: var(--bg-panel); border-radius: 16px; border: 1px solid var(--border); padding: 1.5rem; transition: border-color 0.2s; }
.stats-card:hover { border-color: var(--primary); }
.panel-header { border-bottom: 1px solid var(--border); padding-bottom: 1rem; margin-bottom: 1.25rem; }
.panel-header h3 { margin: 0; display: flex; align-items: center; gap: 0.5rem; font-size: 1.25rem; color: var(--text-main); }
.panel-icon { color: var(--primary); }

.ranking-list { display: flex; flex-direction: column; gap: 0.75rem; }
.ranking-item { display: flex; align-items: center; gap: 1rem; padding: 0.8rem 1rem; background: var(--bg-app); border: 1px solid var(--border); border-radius: 12px; transition: all 0.2s; min-width: 0; }
.ranking-item:hover { transform: translateX(5px); border-color: var(--primary); }
.rank-number { width: 28px; height: 28px; background: var(--primary); color: #1e293b; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 0.85rem; flex-shrink: 0; }

.item-info { flex-grow: 1; min-width: 0; display: flex; flex-direction: column; gap: 0.1rem; }
.item-name { color: var(--text-main); font-size: 0.95rem; word-break: break-word; line-height: 1.3; }
.item-sub { color: var(--text-muted); font-size: 0.8rem; word-break: break-word; line-height: 1.3; }

.item-count { font-weight: 800; color: var(--primary); font-size: 1.1rem; background: rgba(250, 204, 21, 0.1); padding: 0.25rem 0.75rem; border-radius: 8px; flex-shrink: 0; }

/* ZMĚNA: Přidán max-height, overflow a vlastní scrollbar */
.styles-list { 
  display: flex; 
  flex-direction: column; 
  gap: 1rem; 
  max-height: 290px; 
  overflow-y: auto; 
  padding-right: 0.5rem;
}
.styles-list::-webkit-scrollbar { width: 6px; }
.styles-list::-webkit-scrollbar-thumb { background-color: var(--border); border-radius: 10px; }
.styles-list::-webkit-scrollbar-thumb:hover { background-color: var(--primary); }

.style-info { display: flex; justify-content: space-between; font-weight: 700; font-size: 0.9rem; margin-bottom: 0.3rem; }
.style-count { color: var(--primary); }
.style-bar-bg { height: 8px; background: var(--bg-app); border-radius: 4px; overflow: hidden; }
.style-bar-fill { height: 100%; background: var(--primary); border-radius: 4px; transition: width 1s ease; }

.collector-stats { display: flex; flex-direction: column; gap: 1.25rem; text-align: center; padding: 0.5rem 0; }
.collector-val { font-size: 3rem; font-weight: 900; color: var(--primary); line-height: 1; }
.collector-label { font-size: 0.9rem; color: var(--text-muted); font-weight: 600; }
.progress-bar { height: 10px; background: var(--bg-app); border-radius: 5px; overflow: hidden; margin-bottom: 0.4rem; }
.progress-fill { height: 100%; background: var(--primary); border-radius: 5px; transition: width 1s ease; }
.progress-text { font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; }

.price-stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; padding: 0.5rem 0; }
.price-box { display: flex; flex-direction: column; align-items: center; padding: 1rem 0.5rem; background: var(--bg-app); border-radius: 12px; border: 1px solid var(--border); min-width: 0; }
.p-label { font-size: 0.7rem; text-transform: uppercase; color: var(--text-muted); font-weight: 700; margin-bottom: 0.5rem; text-align: center; line-height: 1.2; display: flex; align-items: center; justify-content: center; width: 100%; }
.p-val { font-size: 1.05rem; font-weight: 800; color: var(--text-main); white-space: nowrap; }
.price-box.avg { border-color: var(--primary); background: rgba(250, 204, 21, 0.05); }

.chart-container { display: flex; justify-content: space-between; align-items: flex-end; height: 180px; padding: 1rem 0.5rem; gap: 0.5rem; overflow: hidden; }
.chart-column-wrapper { flex: 1; display: flex; flex-direction: column; align-items: center; gap: 0.5rem; height: 100%; min-width: 0; }
.chart-column { width: 100%; flex: 1; background: var(--bg-app); border-radius: 6px; position: relative; display: flex; align-items: flex-end; overflow: hidden; }
.column-fill { width: 100%; background: var(--primary); border-radius: 4px; transition: height 0.8s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
.column-value { font-size: 0.75rem; font-weight: 800; color: var(--text-main); }
.column-label { font-size: 0.85rem; font-weight: 700; color: var(--text-muted); }
.column-label.weekend { color: var(--orange); }

.empty-stats { padding: 3rem 1rem; text-align: center; color: var(--text-muted); font-style: italic; }

@media (max-width: 800px) {
  .header-actions { flex-direction: column; align-items: stretch; }
  .scope-toggle button { flex: 1; }
  .filter-wrapper { width: 100%; }
  
  .stats-grid-detailed { grid-template-columns: 1fr; }
  .chart-container { height: 140px; padding: 0.5rem 0; gap: 0.25rem; }
  .price-stats-grid { grid-template-columns: 1fr; }
  
  .stats-card { padding: 1rem; }
  .ranking-item { padding: 0.6rem 0.75rem; gap: 0.75rem; }
  .price-box { padding: 0.75rem; }
}
</style>