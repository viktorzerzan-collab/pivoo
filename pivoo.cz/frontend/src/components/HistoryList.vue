<template>
  <div class="history-section" v-if="history && history.length > 0">
    <h3 class="history-title">Záchranná brzda (Poslední zápisy)</h3>
    <div class="history-list">
      <div v-for="record in history" :key="record.id" class="history-item">
        <div class="history-info">
          <strong>{{ record.quantity }}x {{ record.beer_name }}</strong> ({{ record.volume }}l) 
          <span class="history-location">v {{ record.location_name }}</span>
        </div>
        <div class="history-actions">
          <span class="history-price" v-if="record.price">{{ record.price * record.quantity }} Kč</span>
          <button @click="$emit('edit', record)" class="btn-delete" title="Upravit zápis" style="opacity: 0.8;">✏️</button>
          <button @click="$emit('delete', record.id)" class="btn-delete" title="Smazat">🗑️</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  history: { type: Array, required: true }
})
defineEmits(['edit', 'delete'])
</script>

<style scoped>
.history-section { background: white; border-radius: 12px; padding: 1.5rem; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); border: 1px solid #e5e7eb; margin-bottom: 2rem; }
.history-title { margin-top: 0; color: #ef4444; font-size: 1.1rem; margin-bottom: 1rem; border-bottom: 1px solid #e5e7eb; padding-bottom: 0.5rem; }
.history-list { display: flex; flex-direction: column; gap: 0.5rem; }
.history-item { display: flex; justify-content: space-between; align-items: center; padding: 0.5rem 0; border-bottom: 1px dashed #e5e7eb; }
.history-info { color: #374151; font-size: 0.95rem; }
.history-location { color: #6b7280; font-size: 0.85rem; }
.history-actions { display: flex; align-items: center; gap: 1rem; }
.history-price { font-weight: bold; color: #10b981; }
.btn-delete { background: none; border: none; cursor: pointer; font-size: 1.2rem; opacity: 0.6; transition: opacity 0.2s, transform 0.2s; padding: 0; }
.btn-delete:hover { opacity: 1; transform: scale(1.1); }
@media (max-width: 600px) {
  .history-item { flex-direction: column; align-items: flex-start; gap: 0.5rem; }
  .history-actions { width: 100%; justify-content: space-between; }
}
</style>