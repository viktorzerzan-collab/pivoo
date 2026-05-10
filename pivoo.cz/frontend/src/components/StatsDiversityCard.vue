<template>
  <div class="panel-card stats-card collector-card">
    <div class="panel-header">
      <h3>
        <component :is="icon" :size="20" class="panel-icon" /> 
        {{ title }}
      </h3>
    </div>
    <div class="collector-stats" v-if="totalCount > 0">
      <div class="collector-main">
        <div class="collector-val">{{ uniqueCount }}</div>
        <div class="collector-label">{{ subText }}</div>
      </div>
      <div class="collector-progress">
        <div class="progress-bar">
          <div class="progress-fill" :style="{ width: percent + '%' }"></div>
        </div>
        <div class="progress-text">{{ percentText }}</div>
      </div>
    </div>
    <div v-else class="empty-stats">{{ emptyText }}</div>
  </div>
</template>

<script setup>
defineProps({
  title: { type: String, required: true },
  icon: { type: [Object, Function], required: true },
  uniqueCount: { type: Number, default: 0 },
  totalCount: { type: Number, default: 0 },
  percent: { type: Number, default: 0 },
  subText: { type: String, required: true },
  percentText: { type: String, required: true },
  emptyText: { type: String, required: true }
})
</script>

<style scoped>
.panel-card { background: var(--bg-panel); border-radius: var(--radius-md); border: 1px solid var(--border); padding: 1.5rem; transition: background-color 0.3s ease, border-color 0.3s ease; height: 100%; display: flex; flex-direction: column; }
.panel-card:hover { border-color: var(--primary); }
.panel-header { border-bottom: 1px solid var(--border); padding-bottom: 1rem; margin-bottom: 1.5rem; transition: border-color 0.3s ease; }
.panel-header h3 { margin: 0; display: flex; align-items: center; gap: 0.5rem; font-size: 1.25rem; color: var(--text-main); transition: color 0.3s ease; }
.panel-icon { color: var(--primary); }
.collector-stats { display: flex; flex-direction: column; gap: 1.25rem; text-align: center; padding: 0.5rem 0; justify-content: center; flex-grow: 1;}
.collector-val { font-size: 3rem; font-weight: 900; color: var(--primary); line-height: 1; }
.collector-label { font-size: 0.9rem; color: var(--text-muted); font-weight: 600; transition: color 0.3s ease; }
.progress-bar { height: 10px; background: var(--bg-app); border-radius: 5px; overflow: hidden; margin-bottom: 0.4rem; transition: background-color 0.3s ease; }
.progress-fill { height: 100%; background: var(--primary); border-radius: 5px; transition: width 1s ease; }
.progress-text { font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; transition: color 0.3s ease; }
.empty-stats { padding: 3rem 1rem; text-align: center; color: var(--text-muted); font-style: italic; transition: color 0.3s ease; flex-grow: 1; display: flex; align-items: center; justify-content: center; }

@media (max-width: 800px) {
  .panel-card { padding: 1rem; }
}
</style>