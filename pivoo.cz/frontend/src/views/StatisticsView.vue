<template>
  <div class="statistics-page">
    <div class="view-header">
      <div class="header-actions">
        <BaseSwitch v-model="scope" :options="scopeOptions" />
        <BasePeriodSelector v-model="periodSelection" class="period-picker-right" />
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
            :items="statsData.beers.map(item => ({ 
              id: item.id, 
              type: 'beer', 
              name: item.name, 
              sub: item.brewery, 
              count: item.count 
            }))"
            :emptyText="$t('statistics.empty_data')"
            :clickable="true"
            @itemClick="openDetailModal"
          />

          <StatsRankingCard 
            :title="$t('statistics.fav_breweries')"
            :icon="FactoryIcon"
            :items="statsData.breweries.map(item => ({ 
              id: item.id,
              type: 'brewery',
              name: item.name, 
              sub: $t('statistics.tasted_types', { count: item.beer_types }), 
              count: item.count 
            }))"
            :emptyText="$t('statistics.empty_data')"
            :clickable="true"
            @itemClick="openDetailModal"
          />

          <StatsRankingCard 
            :title="$t('statistics.fav_locations')"
            :icon="MapPinIcon"
            :items="statsData.locations.map(item => ({ 
              id: item.id,
              type: 'location',
              name: translateDynamic(item.name), 
              sub: (item.city ? translateDynamic(item.city) + ' • ' : '') + $t('statistics.visits', { count: item.visits }), 
              count: item.count 
            }))"
            :emptyText="$t('statistics.empty_data')"
            :clickable="true"
            @itemClick="openDetailModal"
          />

          <StatsRankingCard 
            :title="$t('statistics.top_rated_beers')"
            :icon="StarIcon"
            :items="statsData.top_rated_beers?.map(item => ({ 
              id: item.id,
              type: 'beer',
              name: item.name, 
              sub: item.brewery + ' (' + item.ratings_count + 'x)', 
              count: item.avg_rating 
            })) || []"
            :isRating="true"
            :clickable="true"
            @itemClick="openDetailModal"
            :emptyText="$t('statistics.empty_data')"
          />

          <StatsRankingCard 
            :title="$t('statistics.top_rated_locations')"
            :icon="StarIcon"
            :items="statsData.top_rated_locations?.map(item => ({ 
              id: item.id,
              type: 'location',
              name: translateDynamic(item.name), 
              sub: (item.city ? translateDynamic(item.city) + ' • ' : '') + '(' + item.ratings_count + 'x)', 
              count: item.avg_rating 
            })) || []"
            :isRating="true"
            :clickable="true"
            @itemClick="openDetailModal"
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

    <DetailModal 
      :show="isDetailModalVisible" 
      :item="detailModalItem"
      :type="detailModalType"
      :reviews="detailModalReviews"
      @close="isDetailModalVisible = false"
    />
  </div>
</template>

<script setup>
import { 
  BeerIcon, FactoryIcon, MapPinIcon, 
  CalendarDaysIcon, TrophyIcon, 
  CoinsIcon, ShapesIcon, StarIcon
} from 'lucide-vue-next'
import BaseLoader from '../components/BaseLoader.vue'
import BaseSwitch from '../components/BaseSwitch.vue'
import BasePeriodSelector from '../components/BasePeriodSelector.vue'
import StatsBoard from '../components/StatsBoard.vue'

// Import komponent
import StatsRankingCard from '../components/StatsRankingCard.vue'
import StatsChartCard from '../components/StatsChartCard.vue'
import StatsStylesCard from '../components/StatsStylesCard.vue'
import StatsDiversityCard from '../components/StatsDiversityCard.vue'
import StatsEconomyCard from '../components/StatsEconomyCard.vue'
import DetailModal from '../components/modals/DetailModal.vue'

// Import nového composable
import { useStatistics } from '../composables/useStatistics'

const {
  isLoading,
  scope,
  scopeOptions,
  periodSelection,
  statsData,
  isDetailModalVisible,
  detailModalItem,
  detailModalType,
  detailModalReviews,
  openDetailModal,
  userCurrency,
  avgPrice,
  minPrice,
  maxPrice,
  isLoadingRate,
  translateDynamic,
  translateStyle,
  collectorPercent,
  dayActivity,
  monthActivity,
  monthDaysActivity
} = useStatistics()
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

.header-actions { 
  display: flex; 
  justify-content: space-between; 
  align-items: center; 
  gap: 1.5rem; 
  flex-wrap: wrap;
}

.period-picker-right {
  display: flex !important;
  align-items: center;
  gap: 1rem;
}

.period-picker-right :deep(> *) {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.period-picker-right :deep(> *:first-child),
.period-picker-right :deep(.mode-wrapper) {
  order: 2 !important;
}

.period-picker-right :deep(> *:nth-child(2)),
.period-picker-right :deep(> *:not(.mode-wrapper)) {
  order: 1 !important;
}

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
  .period-picker-right { flex-direction: column !important; align-items: stretch; }
  .period-picker-right :deep(> *) { order: initial !important; }
  .stats-grid-detailed { grid-template-columns: 1fr; }
  .panel-card { padding: 1rem; }
}
</style>

<style>
.statistics-page .mode-wrapper {
  width: 240px !important;
}

@media (max-width: 600px) {
  .statistics-page .mode-wrapper {
    width: 100% !important;
  }
}
</style>
