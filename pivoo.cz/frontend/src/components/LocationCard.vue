<template>
  <div class="card location-card" :class="{ 'is-fav': location.is_favorite }">
    <div class="card-body">
      <div class="card-main-info">
        <div class="icon-wrapper location-bg">
          <MapPinIcon :size="24" color="var(--primary)" />
        </div>
        <div class="text-content">
          <div class="title-row">
            <h3 class="card-title">{{ location.name }}</h3>
            <div class="action-wrap">
              <FavoriteButton 
                :is-favorite="location.is_favorite" 
                @toggle="toggleFav" 
              />
            </div>
          </div>
          
          <p class="card-subtitle">
            <CountryFlag 
              :code="location.country_code" 
              :name="location.country" 
            />
            {{ formatLocation(location) }}
          </p>

          <div class="card-meta" v-if="location.address">
            <div class="meta-item">
              <MapPinIcon :size="12" /> {{ location.address }}
            </div>
          </div>

          <div class="card-meta" style="margin-top: 0.5rem;" @click.stop>
            <OpeningHoursDisplay :openingHours="location.opening_hours" />
          </div>

          <div class="card-meta distance-meta" @click.stop>
            <div class="meta-item">
              <DistanceDisplay :lat="location.lat" :lng="location.lng" />
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
import FavoriteButton from './FavoriteButton.vue'
import CountryFlag from './CountryFlag.vue'
import DistanceDisplay from './DistanceDisplay.vue'
import { useCatalogStore } from '../stores/catalog'
import { useAuthStore } from '../stores/auth'
import OpeningHoursDisplay from './OpeningHoursDisplay.vue'

const props = defineProps({ location: Object })
defineEmits(['showDetail'])
const catalogStore = useCatalogStore()
const authStore = useAuthStore()

const toggleFav = () => { catalogStore.toggleFavorite(props.location.id, 'location') }
const formatLocation = (location) => {
  let loc = location.city || '';
  if (location.country && location.country !== 'Česká republika') { loc += loc ? ', ' + location.country : location.country; }
  return loc || 'Lokalita neznámá';
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
  transition: transform 0.2s, box-shadow 0.2s, border-color 0.2s; 
  height: 100%; 
  position: relative; 
  z-index: 1;
}

.card.is-fav { border-color: var(--primary); box-shadow: 0 0 0 1px var(--primary); }

.card:hover { 
  transform: translateY(-3px); 
  box-shadow: var(--shadow-md); 
  border-color: var(--primary); 
  background-color: var(--card-hover-bg); 
  z-index: 10; 
}

.card-body { padding: 1.25rem; flex-grow: 1; }
.card-main-info { display: flex; gap: 1rem; align-items: flex-start; }

.icon-wrapper { padding: 0.75rem; border-radius: 10px; display: flex; align-items: center; justify-content: center; }
.location-bg { background: #1e293b; }

.text-content { display: flex; flex-direction: column; gap: 0.35rem; min-width: 0; flex: 1; }

.title-row { display: flex; justify-content: space-between; align-items: flex-start; gap: 0.5rem; }
.card-title { margin: 0; font-size: 1.1rem; font-weight: 700; color: var(--text-main); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; flex: 1; }
.action-wrap { display: flex; align-items: center; gap: 0.5rem; }
.card-subtitle { margin: 0; font-size: 0.85rem; color: var(--text-muted); font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.card-meta { display: flex; flex-wrap: wrap; gap: 0.75rem; margin-top: 0.1rem; }
.meta-item { display: flex; align-items: center; gap: 4px; font-size: 0.75rem; font-weight: 600; color: var(--text-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

.distance-meta { margin-top: 0.3rem !important; }

.card-rating { display: flex; align-items: center; gap: 4px; margin-top: 0.5rem; }
.rating-value { font-size: 0.9rem; font-weight: 800; color: #0369a1; }
.count { font-size: 0.75rem; color: var(--text-muted); margin-left: 4px; }
.card-footer { padding: 0 1.25rem 1.25rem; }
.full-width-btn { width: 100%; justify-content: center; background-color: var(--bg-app); border: 1px solid var(--border); color: var(--text-main); }
.full-width-btn:hover { background-color: var(--border); }
</style>