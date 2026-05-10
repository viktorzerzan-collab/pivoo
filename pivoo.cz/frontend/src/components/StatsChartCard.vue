<template>
  <div class="panel-card stats-card full-width-card">
    <div class="panel-header">
      <h3>
        <component :is="icon" :size="20" class="panel-icon" /> 
        {{ title }}
      </h3>
    </div>
    <div class="chart-container" v-if="data && data.length > 0">
      <div v-for="item in data" :key="item.label" class="chart-column-wrapper">
        <div class="column-value">{{ item.count > 0 ? item.count + 'x' : '' }}</div>
        <div class="chart-column">
          <div class="column-fill" :style="{ '--percent': item.percent + '%' }"></div>
        </div>
        <div class="column-label" :class="{ 'weekend': item.isWeekend }">{{ item.label }}</div>
      </div>
    </div>
    <div v-else class="empty-stats">{{ emptyText }}</div>
  </div>
</template>

<script setup>
defineProps({
  title: { type: String, required: true },
  icon: { type: [Object, Function], required: true },
  data: { type: Array, required: true }, // [{ label, count, percent, isWeekend }]
  emptyText: { type: String, required: true }
})
</script>

<style scoped>
.panel-card { background: var(--bg-panel); border-radius: var(--radius-md); border: 1px solid var(--border); padding: 1.5rem; transition: background-color 0.3s ease, border-color 0.3s ease; }
.panel-card:hover { border-color: var(--primary); }
.full-width-card { grid-column: 1 / -1; }
.panel-header { border-bottom: 1px solid var(--border); padding-bottom: 1rem; margin-bottom: 1.5rem; transition: border-color 0.3s ease; }
.panel-header h3 { margin: 0; display: flex; align-items: center; gap: 0.5rem; font-size: 1.25rem; color: var(--text-main); transition: color 0.3s ease; }
.panel-icon { color: var(--primary); }
.chart-container { display: flex; justify-content: space-between; align-items: flex-end; height: 180px; padding: 1rem 0.5rem; gap: 0.25rem; overflow: hidden; }
.chart-column-wrapper { flex: 1; display: flex; flex-direction: column; align-items: center; gap: 0.5rem; height: 100%; min-width: 0; }
.chart-column { width: 100%; flex: 1; background: var(--bg-app); border-radius: 6px; display: flex; align-items: flex-end; overflow: hidden; transition: background-color 0.3s ease; }
.column-fill { width: 100%; height: var(--percent, 0%); background: var(--primary); border-radius: 4px; transition: height 0.8s ease; }
.column-value { font-size: 0.75rem; font-weight: 800; color: var(--text-main); transition: color 0.3s ease; min-height: 1rem;}
.column-label { font-size: 0.85rem; font-weight: 700; color: var(--text-muted); transition: color 0.3s ease; }
.column-label.weekend { color: var(--orange); }
.empty-stats { padding: 3rem 1rem; text-align: center; color: var(--text-muted); font-style: italic; transition: color 0.3s ease; }

@media (max-width: 800px) {
  .panel-card { padding: 1rem; }
  .chart-container { flex-direction: column; align-items: stretch; height: auto; padding: 0.5rem 0; gap: 0.6rem; }
  .chart-column-wrapper { flex-direction: row; height: auto; gap: 0.75rem; align-items: center; }
  .chart-column { height: 8px; flex: 1; width: auto; align-items: stretch; background: var(--bg-app); border-radius: 4px; order: 2; }
  .column-fill { height: 100%; width: var(--percent, 0%); background: var(--primary); }
  .column-label { order: 1; width: 32px; text-align: left; font-size: 0.8rem; }
  .column-value { order: 3; width: 35px; text-align: right; font-size: 0.75rem; }
}
</style>