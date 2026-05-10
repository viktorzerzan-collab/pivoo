<template>
  <div class="panel-card stats-card styles-card">
    <div class="panel-header">
      <h3>
        <component :is="icon" :size="20" class="panel-icon" /> 
        {{ title }}
      </h3>
    </div>
    <div class="styles-list" v-if="styles && styles.length > 0">
      <div v-for="style in styles" :key="style.label" class="style-row">
        <div class="style-info">
          <span class="style-name">{{ style.label }}</span>
          <span class="style-count">{{ style.count }}x</span>
        </div>
        <div class="style-bar-bg">
          <div class="style-bar-fill" :style="{ width: style.percent + '%' }"></div>
        </div>
      </div>
    </div>
    <div v-else class="empty-stats">{{ emptyText }}</div>
  </div>
</template>

<script setup>
defineProps({
  title: { type: String, required: true },
  icon: { type: [Object, Function], required: true },
  styles: { type: Array, required: true }, // [{ label, count, percent }]
  emptyText: { type: String, required: true }
})
</script>

<style scoped>
.panel-card { background: var(--bg-panel); border-radius: var(--radius-md); border: 1px solid var(--border); padding: 1.5rem; transition: background-color 0.3s ease, border-color 0.3s ease; height: 100%; display: flex; flex-direction: column; }
.panel-card:hover { border-color: var(--primary); }
.panel-header { border-bottom: 1px solid var(--border); padding-bottom: 1rem; margin-bottom: 1.5rem; transition: border-color 0.3s ease; }
.panel-header h3 { margin: 0; display: flex; align-items: center; gap: 0.5rem; font-size: 1.25rem; color: var(--text-main); transition: color 0.3s ease; }
.panel-icon { color: var(--primary); }
.styles-list { display: flex; flex-direction: column; gap: 1rem; max-height: 290px; overflow-y: auto; padding-right: 0.5rem; flex-grow: 1;}
.styles-list::-webkit-scrollbar { width: 6px; }
.styles-list::-webkit-scrollbar-thumb { background-color: var(--border); border-radius: var(--radius-sm); }
.styles-list::-webkit-scrollbar-thumb:hover { background-color: var(--primary); }
.style-info { display: flex; justify-content: space-between; font-weight: 700; font-size: 0.9rem; margin-bottom: 0.3rem; }
.style-count { color: var(--primary); }
.style-bar-bg { height: 8px; background: var(--bg-app); border-radius: 4px; overflow: hidden; transition: background-color 0.3s ease; }
.style-bar-fill { height: 100%; background: var(--primary); border-radius: 4px; transition: width 1s ease; }
.empty-stats { padding: 3rem 1rem; text-align: center; color: var(--text-muted); font-style: italic; transition: color 0.3s ease; flex-grow: 1; display: flex; align-items: center; justify-content: center; }

@media (max-width: 800px) {
  .panel-card { padding: 1rem; }
}
</style>