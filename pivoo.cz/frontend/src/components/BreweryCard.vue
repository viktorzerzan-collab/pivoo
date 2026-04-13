<template>
  <div class="card brewery-card">
    <div class="card-body">
      <div class="card-main-info">
        
        <div class="icon-wrapper brewery-bg" :class="{'has-logo': brewery.logo}">
          <img v-if="brewery.logo" :src="'https://www.pivoo.cz/backend/uploads/logos/' + brewery.logo" alt="Logo" class="brewery-logo-img" />
          <FactoryIcon v-else :size="24" color="#b45309" />
        </div>

        <div class="text-content">
          <h3 class="card-title">{{ brewery.name }}</h3>
          <p class="card-subtitle">{{ brewery.city || brewery.country || 'Lokalita neznámá' }}</p>
          
          <div v-if="brewery.avg_rating" class="card-rating">
            <StarIcon :size="14" fill="#f59e0b" color="#f59e0b" />
            <span class="rating-value">{{ Number(brewery.avg_rating).toFixed(1) }}</span>
            <span class="count" v-if="brewery.total_beers_in_catalog">
              ({{ brewery.total_beers_in_catalog }} piva)
            </span>
          </div>
        </div>
      </div>
    </div>
    
    <div class="card-footer">
      <BaseButton variant="secondary" @click="$emit('showDetail', brewery)" class="full-width-btn">
        <template #icon><InfoIcon :size="16" /></template>
        Detail pivovaru
      </BaseButton>
    </div>
  </div>
</template>

<script setup>
import { FactoryIcon, StarIcon, InfoIcon } from 'lucide-vue-next'
import BaseButton from './BaseButton.vue'

defineProps({
  brewery: Object
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
  width: 48px;
  height: 48px;
  flex-shrink: 0;
}
.brewery-bg { background: #ffedd5; }
.icon-wrapper.has-logo { padding: 0; background: transparent; border: 1px solid var(--border); overflow: hidden; }
.brewery-logo-img { width: 100%; height: 100%; object-fit: contain; }

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