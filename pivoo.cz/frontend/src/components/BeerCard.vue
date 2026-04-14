<template>
  <div class="card beer-card" :class="{ 'special-beer': beer.is_unfiltered || beer.is_unpasteurized }">
    <div class="card-body">
      <div class="card-main-info">
        <div class="icon-wrapper beer-bg">
          <BeerIcon :size="24" color="#ca8a04" />
        </div>
        <div class="text-content">
          <div class="title-row">
            <h3 class="card-title">{{ beer.name }}</h3>
            <div class="mini-badges">
              <span v-if="beer.is_unfiltered" class="m-badge" title="Nefiltrované">N</span>
              <span v-if="beer.is_unpasteurized" class="m-badge" title="Nepasterizované">P</span>
            </div>
          </div>
          
          <p class="card-subtitle">
            <img v-if="beer.brewery_country_code" :src="`https://flagcdn.com/w20/${beer.brewery_country_code}.png`" class="flag-icon" :title="beer.brewery_country" alt="flag" />
            <span v-else class="flag" :title="beer.brewery_country">🌍</span>
            {{ beer.brewery_name }} • {{ beer.style || 'Bez stylu' }}
          </p>
          
          <div class="card-meta">
            <div v-if="beer.epm" class="meta-item" title="Stupňovitost (EPM)">
              <ActivityIcon :size="12" /> {{ beer.epm }}°
            </div>
            <div v-if="beer.abv" class="meta-item" title="Alkohol">
              <PercentIcon :size="12" /> {{ beer.abv }}%
            </div>
            <div v-if="beer.ibu" class="meta-item" title="Hořkost">
              <ThermometerIcon :size="12" /> {{ beer.ibu }} IBU
            </div>
            <div v-if="beer.ebc" class="meta-item" title="Barva">
              <PipetteIcon :size="12" /> {{ beer.ebc }} EBC
            </div>
          </div>

          <div class="tags-row" v-if="beer.fermentation">
             <span class="tag-badge">{{ beer.fermentation }} kvašení</span>
          </div>

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
import { BeerIcon, StarIcon, InfoIcon, ThermometerIcon, PercentIcon, ActivityIcon, PipetteIcon } from 'lucide-vue-next'
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
  position: relative;
  overflow: hidden;
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

.text-content { display: flex; flex-direction: column; gap: 0.35rem; overflow: hidden; flex: 1; }

.title-row { display: flex; justify-content: space-between; align-items: center; gap: 0.5rem; }
.card-title { margin: 0; font-size: 1.1rem; font-weight: 700; color: var(--text-main); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; transition: color 0.5s ease; }

.mini-badges { display: flex; gap: 2px; }
.m-badge { 
  font-size: 0.65rem; font-weight: 900; background: var(--border); color: var(--text-muted); 
  width: 16px; height: 16px; display: flex; align-items: center; justify-content: center; 
  border-radius: 4px; cursor: help;
}

.card-subtitle { margin: 0; font-size: 0.85rem; color: var(--text-muted); font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; transition: color 0.5s ease; }

/* Styly pro opravdovou vlaječku */
.flag-icon { width: 20px; height: auto; vertical-align: middle; margin-right: 0.3rem; border-radius: 2px; box-shadow: 0 0 1px rgba(0,0,0,0.3); }
.flag { margin-right: 0.2rem; font-size: 1rem; vertical-align: middle; }

.card-meta { display: flex; flex-wrap: wrap; gap: 0.75rem; margin-top: 0.25rem; }
.meta-item { display: flex; align-items: center; gap: 3px; font-size: 0.75rem; font-weight: 600; color: var(--text-muted); }

.tags-row { display: flex; gap: 0.4rem; margin-top: 0.2rem; }
.tag-badge { background: var(--bg-app); border: 1px solid var(--border); padding: 2px 8px; border-radius: 4px; font-size: 0.7rem; font-weight: 600; color: var(--text-muted); text-transform: lowercase; }

.card-rating { display: flex; align-items: center; gap: 4px; margin-top: 0.5rem; }
.rating-value { font-size: 0.9rem; font-weight: 800; color: #d97706; }
.count { font-size: 0.75rem; color: var(--text-muted); margin-left: 4px; transition: color 0.5s ease; }

.card-footer { padding: 0 1.25rem 1.25rem; }
.full-width-btn { width: 100%; justify-content: center; background-color: var(--bg-app); border: 1px solid var(--border); color: var(--text-main); }
.full-width-btn:hover { background-color: var(--border); }
</style>