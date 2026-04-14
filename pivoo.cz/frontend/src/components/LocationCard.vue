<template>
  <div class="card location-card">
    <div class="card-body">
      <div class="card-main-info">
        <div class="icon-wrapper location-bg">
          <MapPinIcon :size="24" color="#0369a1" />
        </div>
        <div class="text-content">
          <div class="title-row">
            <h3 class="card-title">{{ location.name }}</h3>
            <span class="type-badge">{{ formatType(location.type) }}</span>
          </div>
          
          <p class="card-subtitle">
            <img v-if="location.country_code" :src="`https://flagcdn.com/w20/${location.country_code}.png`" class="flag-icon" :title="location.country" alt="flag" />
            <span v-else class="flag" :title="location.country">🌍</span>
            {{ formatLocation(location) }}
          </p>

          <div class="card-meta" v-if="location.address">
            <div class="meta-item">
              <MapPinIcon :size="12" /> {{ location.address }}
            </div>
          </div>
          
          <div v-if="location.avg_rating" class="card-rating">
            <StarIcon :size="14" fill="#0ea5e9" color="#0ea5e9" />
            <span class="rating-value">{{ Number(location.avg_rating).toFixed(1) }}</span>
            <span class="count" v-if="location.total_visits">
              ({{ location.total_visits }}x návštěva)
            </span>
          </div>
        </div>
      </div>
    </div>
    
    <div class="card-footer">
      <BaseButton variant="secondary" @click="$emit('showDetail', location)" class="full-width-btn">
        <template #icon><InfoIcon :size="16" /></template>
        Detail podniku
      </BaseButton>
    </div>
  </div>
</template>

<script setup>
import { MapPinIcon, StarIcon, InfoIcon } from 'lucide-vue-next'
import BaseButton from './BaseButton.vue'

defineProps({
  location: Object
})
defineEmits(['showDetail'])

const formatLocation = (location) => {
  let loc = location.city || '';
  if (location.country && location.country !== 'Česká republika') {
    loc += loc ? ', ' + location.country : location.country;
  }
  return loc || 'Lokalita neznámá';
}

const formatType = (type) => {
  const types = {
    'hospoda': 'Hospoda',
    'pivoteka': 'Pivotéka',
    'obchod': 'Obchod',
    'jine': 'Jiné'
  }
  return types[type] || type;
}
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
.location-bg { background: #e0f2fe; }

.text-content { display: flex; flex-direction: column; gap: 0.35rem; overflow: hidden; flex: 1; }

.title-row { display: flex; justify-content: space-between; align-items: center; gap: 0.5rem; }
.card-title { margin: 0; font-size: 1.1rem; font-weight: 700; color: var(--text-main); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; transition: color 0.5s ease; }

.type-badge {
  background: var(--bg-app); border: 1px solid var(--border); padding: 2px 8px; border-radius: 4px; font-size: 0.65rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase;
}

.card-subtitle { margin: 0; font-size: 0.85rem; color: var(--text-muted); font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; transition: color 0.5s ease; }

.flag-icon { width: 20px; height: auto; vertical-align: middle; margin-right: 0.3rem; border-radius: 2px; box-shadow: 0 0 1px rgba(0,0,0,0.3); }
.flag { margin-right: 0.2rem; font-size: 1rem; vertical-align: middle; }

.card-meta { display: flex; flex-wrap: wrap; gap: 0.75rem; margin-top: 0.1rem; }
.meta-item { display: flex; align-items: center; gap: 4px; font-size: 0.75rem; font-weight: 600; color: var(--text-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

.card-rating { display: flex; align-items: center; gap: 4px; margin-top: 0.5rem; }
.rating-value { font-size: 0.9rem; font-weight: 800; color: #0369a1; }
.count { font-size: 0.75rem; color: var(--text-muted); margin-left: 4px; transition: color 0.5s ease; }

.card-footer { padding: 0 1.25rem 1.25rem; }
.full-width-btn { width: 100%; justify-content: center; background-color: var(--bg-app); border: 1px solid var(--border); color: var(--text-main); }
.full-width-btn:hover { background-color: var(--border); }
</style>