<template>
  <div class="stats-grid" v-if="stats">
    <div class="stat-card"><div class="stat-icon">🍺</div><div class="stat-value">{{ stats.count }}</div><div class="stat-label">Vypitých kousků</div></div>
    <div class="stat-card"><div class="stat-icon">🛢️</div><div class="stat-value">{{ stats.liters }} l</div><div class="stat-label">Celkový objem</div></div>
    <div class="stat-card"><div class="stat-icon">💸</div><div class="stat-value">{{ stats.spent }} Kč</div><div class="stat-label">Utraceno</div></div>
    <div class="stat-card"><div class="stat-icon">👑</div><div class="stat-value fav-beer">{{ stats.favorite }}</div><div class="stat-label">Nejoblíbenější</div></div>
  </div>
</template>

<script setup>
defineProps({
  stats: { type: Object, required: false }
})
</script>

<style scoped>
.stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.5rem; margin-bottom: 2rem; }
.stat-card { background: white; padding: 1.5rem; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); text-align: center; border: 1px solid #e5e7eb; }
.stat-icon { font-size: 2rem; margin-bottom: 0.5rem; }
.stat-value { font-size: 1.75rem; font-weight: bold; color: #1f2937; margin-bottom: 0.25rem; }
.stat-value.fav-beer { font-size: 1.2rem; } 
.stat-label { color: #6b7280; font-size: 0.9rem; font-weight: 500; text-transform: uppercase; letter-spacing: 0.05em; }
@media (max-width: 600px) {
  .stats-grid { grid-template-columns: repeat(2, 1fr); gap: 1rem; }
  .stat-card { padding: 1rem; }
  .stat-value { font-size: 1.4rem; }
}
</style>