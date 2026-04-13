<template>
  <div class="history-list" v-if="history && history.length > 0">
    <div v-for="record in history" :key="record.id" class="history-item">
      <div class="history-info">
        <div class="beer-main">
          <strong>{{ record.quantity }}x {{ record.beer_name }}</strong>
          <span class="volume">({{ record.volume }}l)</span>
        </div>
        <div class="location-info">
          <MapPinIcon :size="12" /> {{ record.location_name }}
        </div>
      </div>
      
      <div class="history-actions">
        <span class="price" v-if="record.price">{{ record.price * record.quantity }} Kč</span>
        <div class="btn-group">
          <button class="btn-edit is-icon-only" @click="$emit('edit', record)" title="Upravit">
            <PencilIcon :size="16" />
          </button>
          <button class="btn-danger is-icon-only" @click="$emit('delete', record.id)" title="Smazat">
            <Trash2Icon :size="16" />
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { PencilIcon, Trash2Icon, MapPinIcon } from 'lucide-vue-next'
defineProps({ history: { type: Array, required: true } })
defineEmits(['edit', 'delete'])
</script>

<style scoped>
.history-list { display: flex; flex-direction: column; }
.history-item { 
  display: flex; justify-content: space-between; align-items: center; 
  padding: 1rem 0; border-bottom: 1px solid var(--border); 
}
.history-item:last-child { border-bottom: none; }

.history-info { display: flex; flex-direction: column; gap: 0.25rem; }
.beer-main { font-size: 1rem; color: var(--text-main); transition: color 0.5s ease; }
.volume { color: var(--text-muted); font-size: 0.85rem; margin-left: 0.4rem; transition: color 0.5s ease; }
.location-info { display: flex; align-items: center; gap: 0.3rem; font-size: 0.8rem; color: var(--text-muted); font-weight: 500; transition: color 0.5s ease; }

.history-actions { display: flex; align-items: center; gap: 1.25rem; }
.price { font-weight: 700; color: #10b981; font-size: 0.95rem; }
.btn-group { display: flex; gap: 0.5rem; }

@media (max-width: 500px) {
  .history-item { flex-direction: column; align-items: flex-start; gap: 1rem; }
  .history-actions { width: 100%; justify-content: space-between; }
}
</style>