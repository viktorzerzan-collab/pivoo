<template>
  <div class="stats-grid" v-if="stats">
    <div class="stat-box">
      <div class="stat-icon-wrapper">
        <BeerIcon :size="24" class="stat-icon" />
      </div>
      <div class="stat-info">
        <span class="stat-label">{{ $t('stats.total_beers') }}</span>
        <span class="stat-value">{{ stats.total_beers || 0 }}x</span>
      </div>
    </div>

    <div class="stat-box">
      <div class="stat-icon-wrapper">
        <DropletsIcon :size="24" class="stat-icon" />
      </div>
      <div class="stat-info">
        <span class="stat-label">{{ $t('stats.total_volume') }}</span>
        <span class="stat-value">{{ stats.total_liters || 0 }} l</span>
      </div>
    </div>

    <div class="stat-box">
      <div class="stat-icon-wrapper">
        <CoinsIcon :size="24" class="stat-icon" />
      </div>
      <div class="stat-info">
        <span class="stat-label">{{ $t('stats.total_spent') }}</span>
        <span class="stat-value">
          <template v-if="isLoadingRate">...</template>
          <template v-else>{{ convertedPrice }} {{ userCurrency }}</template>
        </span>
      </div>
    </div>

    <div class="stat-box">
      <div class="stat-icon-wrapper">
        <HeartIcon :size="24" class="stat-icon" />
      </div>
      <div class="stat-info">
        <span class="stat-label">{{ $t('stats.favorite') }}</span>
        <span class="stat-value fav-text">{{ stats.favorite || $t('stats.none') }}</span>
      </div>
    </div>
  </div>
  
  <div v-else class="empty-stats">
    <p>{{ $t('stats.loading') }}</p>
  </div>
</template>

<script setup>
import { computed, ref, watch, onMounted } from 'vue'
import { BeerIcon, DropletsIcon, CoinsIcon, HeartIcon } from 'lucide-vue-next'
import { useAuthStore } from '../stores/auth'

const authStore = useAuthStore()

const props = defineProps({
  stats: {
    type: Object,
    default: null
  }
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

onMounted(() => {
  fetchRate()
})

watch(userCurrency, () => {
  fetchRate()
})

const convertedPrice = computed(() => {
  if (!props.stats?.total_price) return 0;
  const priceInCzk = Number(props.stats.total_price);
  return Math.round(priceInCzk * exchangeRate.value);
})
</script>

<style scoped>
.stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; }

.stat-box { 
  background: var(--bg-app); 
  border: 1px solid var(--border); 
  border-radius: 10px; 
  padding: 1rem; 
  display: flex; 
  align-items: center; 
  gap: 1rem; 
  transition: all 0.3s ease; 
  min-width: 0; 
}

.stat-box:hover { border-color: var(--primary); transform: translateY(-2px); box-shadow: var(--shadow-sm); }

.stat-icon-wrapper { 
  background: rgba(250, 204, 21, 0.15); 
  color: var(--primary); 
  padding: 0.75rem; 
  border-radius: 8px; 
  display: flex; 
  align-items: center; 
  justify-content: center; 
  flex-shrink: 0; 
}

.stat-info { display: flex; flex-direction: column; gap: 0.25rem; overflow: hidden; }

.stat-label { 
  font-size: 0.8rem; 
  color: var(--text-muted); 
  text-transform: uppercase; 
  letter-spacing: 0.5px; 
  font-weight: 600; 
  white-space: nowrap; 
}

.stat-value { 
  font-size: 1.15rem; 
  font-weight: 800; 
  color: var(--text-main); 
  line-height: 1.1; 
  white-space: nowrap; 
  overflow: hidden; 
  text-overflow: ellipsis; 
}

.fav-text { font-size: 0.95rem; }

.empty-stats { 
  padding: 2rem; 
  text-align: center; 
  color: var(--text-muted); 
  font-style: italic; 
  background: var(--bg-app); 
  border-radius: 10px; 
  border: 1px dashed var(--border); 
}

@media (max-width: 1024px) { .stats-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 480px) { .stats-grid { grid-template-columns: 1fr; } }
</style>