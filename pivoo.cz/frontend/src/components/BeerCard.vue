<template>
  <div class="card beer-card">
    <div class="card-body">
      <div class="card-main-info">
        <div class="icon-wrapper beer-bg">
          <BeerIcon :size="24" color="#ca8a04" />
        </div>
        <div class="text-content">
          <h3 class="card-title">{{ beer.name }}</h3>
          <p class="card-subtitle">{{ beer.brewery_name }} • {{ beer.style || 'Bez stylu' }}</p>
          
          <div v-if="beer.avg_rating" class="card-rating">
            <StarIcon :size="14" fill="#f59e0b" color="#f59e0b" />
            <span class="rating-value">{{ Number(beer.avg_rating).toFixed(1) }}</span>
            <span class="count" v-if="beer.total_checkins">
              ({{ beer.total_checkins }}x v deníčku)
            </span>
          </div>
        </div>
      </div>
    </div>
    
    <div class="card-footer">
      <BaseButton variant="secondary" @click="$emit('showDetail', beer)" class="full-width-btn">
        <template #icon><InfoIcon :size="16" /></template>
        Detail piva
      </BaseButton>
    </div>
  </div>
</template>

<script setup>
import { BeerIcon, StarIcon, InfoIcon } from 'lucide-vue-next'
import BaseButton from './BaseButton.vue'

defineProps({
  beer: Object
})
defineEmits(['showDetail'])
</script>

<style scoped>
.card {
  background: var(--bg-panel);
  border: 1px solid var(--border);
  border-radius: 12px;
  display: flex;
  flex-direction: column;
  box-shadow: var(--shadow-sm);
  transition: transform 0.2s, box-shadow 0.2s, border-color 0.2s, background-color 0.5s ease;
  height: 100%;
}

.card:hover {
  transform: translateY(-3px);
  box-shadow: var(--shadow-md);
  border-color: var(--primary);
  background-color: var(--card-hover-bg);
}

.card-body { padding: 1.25rem; flex-grow: 1; }
.card-main-info { display: flex; gap: 1rem; align-items: flex-start; }

.icon-wrapper {
  padding: 0.75rem;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
}
.beer-bg { background: #fef9c3; }

.text-content { display: flex; flex-direction: column; gap: 0.25rem; overflow: hidden; }

.card-title { margin: 0; font-size: 1.1rem; font-weight: 700; color: var(--text-main); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; transition: color 0.5s ease; }
.card-subtitle { margin: 0; font-size: 0.85rem; color: var(--text-muted); font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; transition: color 0.5s ease; }

.card-rating { display: flex; align-items: center; gap: 4px; margin-top: 0.5rem; }
.rating-value { font-size: 0.9rem; font-weight: 800; color: #d97706; }
.count { font-size: 0.75rem; color: var(--text-muted); margin-left: 4px; transition: color 0.5s ease; }

.card-footer { padding: 0 1.25rem 1.25rem; }
.full-width-btn { width: 100%; justify-content: center; background-color: var(--bg-app); border: 1px solid var(--border); color: var(--text-main); }
.full-width-btn:hover { background-color: var(--border); }
</style>