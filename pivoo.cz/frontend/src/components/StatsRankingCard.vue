<template>
  <div class="panel-card stats-card">
    <div class="panel-header">
      <h3>
        <component :is="icon" :size="20" class="panel-icon" /> 
        {{ title }}
      </h3>
    </div>
    <div class="ranking-list" v-if="items && items.length > 0">
      <div v-for="(item, index) in items" :key="index" class="ranking-item">
        <div class="rank-number">{{ index + 1 }}</div>
        <div class="item-info">
          <div class="item-name"><strong>{{ item.name }}</strong></div>
          <div class="item-sub" v-if="item.sub">{{ item.sub }}</div>
        </div>
        <div class="item-count">{{ item.count }}x</div>
      </div>
    </div>
    <div v-else class="empty-stats">{{ emptyText }}</div>
  </div>
</template>

<script setup>
defineProps({
  title: { type: String, required: true },
  icon: { type: [Object, Function], required: true },
  items: { type: Array, required: true }, // [{ name, sub, count }]
  emptyText: { type: String, required: true }
})
</script>

<style scoped>
.panel-card { background: var(--bg-panel); border-radius: var(--radius-md); border: 1px solid var(--border); padding: 1.5rem; transition: background-color 0.3s ease, border-color 0.3s ease; height: 100%; display: flex; flex-direction: column; }
.panel-card:hover { border-color: var(--primary); }
.panel-header { border-bottom: 1px solid var(--border); padding-bottom: 1rem; margin-bottom: 1.5rem; transition: border-color 0.3s ease; }
.panel-header h3 { margin: 0; display: flex; align-items: center; gap: 0.5rem; font-size: 1.25rem; color: var(--text-main); transition: color 0.3s ease; }
.panel-icon { color: var(--primary); }
.ranking-list { display: flex; flex-direction: column; gap: 0.75rem; flex-grow: 1; }
.ranking-item { display: flex; align-items: center; gap: 1rem; padding: 0.8rem 1rem; background: var(--bg-app); border: 1px solid var(--border); border-radius: var(--radius-md); transition: all 0.2s; min-width: 0; }
.ranking-item:hover { border-color: var(--primary); }
.rank-number { width: 28px; height: 28px; background: var(--primary); color: #1e293b; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 0.85rem; flex-shrink: 0; }
.item-info { flex-grow: 1; min-width: 0; display: flex; flex-direction: column; gap: 0.1rem; }
.item-name { color: var(--text-main); font-size: 0.95rem; word-break: break-word; line-height: 1.3; transition: color 0.3s ease; }
.item-sub { color: var(--text-muted); font-size: 0.8rem; word-break: break-word; line-height: 1.3; transition: color 0.3s ease; }
.item-count { font-weight: 800; color: var(--primary); font-size: 1.1rem; background: rgba(250, 204, 21, 0.1); padding: 0.25rem 0.75rem; border-radius: var(--radius-sm); flex-shrink: 0; }
.empty-stats { padding: 3rem 1rem; text-align: center; color: var(--text-muted); font-style: italic; transition: color 0.3s ease; flex-grow: 1; display: flex; align-items: center; justify-content: center; }

@media (max-width: 800px) {
  .panel-card { padding: 1rem; }
  .ranking-item { padding: 0.6rem 0.75rem; gap: 0.75rem; }
}
</style>