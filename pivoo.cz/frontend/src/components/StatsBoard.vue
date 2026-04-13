<template>
  <div class="stats-grid" v-if="stats">
    
    <div class="stat-card">
      <div class="stat-icon"><BeerIcon :size="32" color="var(--primary)" /></div>
      <div class="stat-value">{{ stats.count }}</div>
      <div class="stat-label">Vypitých kousků</div>
    </div>
    
    <div class="stat-card">
      <div class="stat-icon"><DropletIcon :size="32" color="#3b82f6" /></div>
      <div class="stat-value">{{ stats.liters }} l</div>
      <div class="stat-label">Celkový objem</div>
    </div>
    
    <div class="stat-card">
      <div class="stat-icon"><CoinsIcon :size="32" color="#10b981" /></div>
      <div class="stat-value">{{ stats.spent }} Kč</div>
      <div class="stat-label">Utraceno</div>
    </div>
    
    <div class="stat-card">
      <div class="stat-icon"><CrownIcon :size="32" color="#f59e0b" /></div>
      <div class="stat-value fav-beer">{{ stats.favorite }}</div>
      <div class="stat-label">Nejoblíbenější</div>
    </div>

  </div>
</template>

<script setup>
import { BeerIcon, DropletIcon, CoinsIcon, CrownIcon } from 'lucide-vue-next'

defineProps({
  stats: { type: Object, required: false }
})
</script>

<style scoped>
.stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.5rem; margin-bottom: 2rem; }
.stat-card { 
  background: var(--bg-panel); 
  padding: 1.5rem; 
  border-radius: 12px; 
  box-shadow: var(--shadow-sm); 
  text-align: center; 
  border: 1px solid var(--border); 
  transition: background-color 0.5s ease, border-color 0.5s ease; 
}
.stat-icon { 
  display: flex; 
  align-items: center; 
  justify-content: center; 
  margin-bottom: 0.75rem; 
}
.stat-value { 
  font-size: 1.75rem; 
  font-weight: bold; 
  color: var(--text-main); 
  margin-bottom: 0.25rem; 
  transition: color 0.5s ease; 
}
.stat-value.fav-beer { font-size: 1.2rem; } 
.stat-label { 
  color: var(--text-muted); 
  font-size: 0.9rem; 
  font-weight: 500; 
  text-transform: uppercase; 
  letter-spacing: 0.05em; 
  transition: color 0.5s ease; 
}
@media (max-width: 600px) {
  .stats-grid { grid-template-columns: repeat(2, 1fr); gap: 1rem; }
  .stat-card { padding: 1rem; }
  .stat-value { font-size: 1.4rem; }
}
</style>