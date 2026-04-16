<template>
  <div class="card brewery-card" :class="{ 'is-fav': brewery.is_favorite }">
    <div class="card-body">
      <div class="card-main-info">
        
        <div class="icon-wrapper brewery-bg" :class="{'has-logo': brewery.logo}">
          <img v-if="brewery.logo" :src="'https://www.pivoo.cz/backend/uploads/logos/' + brewery.logo" alt="Logo" class="brewery-logo-img" />
          <FactoryIcon v-else :size="24" color="var(--primary)" />
        </div>

        <div class="text-content">
          <div class="title-row">
            <h3 class="card-title">{{ brewery.name }}</h3>
            <button 
              v-if="authStore.user"
              class="fav-btn" 
              :class="{ 'active': brewery.is_favorite }" 
              @click.stop="toggleFav" 
              title="Přidat do oblíbených"
            >
              <StarIcon :size="20" :fill="brewery.is_favorite ? 'var(--primary)' : 'none'" :color="brewery.is_favorite ? 'var(--primary)' : 'var(--text-muted)'" />
            </button>
          </div>
          
          <p class="card-subtitle">
            <img v-if="brewery.country_code" :src="`https://flagcdn.com/w20/${brewery.country_code}.png`" class="flag-icon" :title="brewery.country" alt="flag" />
            <span v-else class="flag" :title="brewery.country">🌍</span>
            {{ formatLocation(brewery) }}
          </p>
          
          <div class="card-meta" v-if="brewery.address">
            <div class="meta-item">
              <MapPinIcon :size="12" /> {{ brewery.address }}
            </div>
          </div>
          
          <div v-if="brewery.avg_rating" class="card-rating">
            <StarIcon :size="14" fill="#f59e0b" color="#f59e0b" />
            <span class="rating-value">{{ Number(brewery.avg_rating).toFixed(1) }}</span>
            <span class="count" v-if="brewery.total_beers_in_catalog">
              ({{ brewery.total_beers_in_catalog }} piv)
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
import { FactoryIcon, StarIcon, InfoIcon, MapPinIcon } from 'lucide-vue-next'
import BaseButton from './BaseButton.vue'
import { useCatalogStore } from '../stores/catalog'
import { useAuthStore } from '../stores/auth'

const props = defineProps({ brewery: Object })
defineEmits(['showDetail'])
const catalogStore = useCatalogStore()
const authStore = useAuthStore()
const toggleFav = () => { catalogStore.toggleFavorite(props.brewery.id, 'brewery') }
const formatLocation = (brewery) => {
  let loc = brewery.city || '';
  if (brewery.country && brewery.country !== 'Česká republika') { loc += loc ? ', ' + brewery.country : brewery.country; }
  return loc || 'Lokalita neznámá';
}
</script>

<style scoped>
.card { background: var(--bg-panel); border: 1px solid var(--border); border-radius: 12px; display: flex; flex-direction: column; box-shadow: var(--shadow-sm); transition: transform 0.2s, box-shadow 0.2s, border-color 0.2s; height: 100%; position: relative; }
.card.is-fav { border-color: var(--primary); box-shadow: 0 0 0 1px var(--primary); }
.card:hover { transform: translateY(-3px); box-shadow: var(--shadow-md); border-color: var(--primary); background-color: var(--card-hover-bg); }
.card-body { padding: 1.25rem; flex-grow: 1; }
.card-main-info { display: flex; gap: 1rem; align-items: flex-start; }

.icon-wrapper { padding: 0.75rem; border-radius: 10px; display: flex; align-items: center; justify-content: center; width: 48px; height: 48px; flex-shrink: 0; }
/* ZMĚNA: Tmavé pozadí pro ikonu */
.brewery-bg { background: #1e293b; }
.icon-wrapper.has-logo { padding: 0; background: transparent; border: 1px solid var(--border); overflow: hidden; }
.brewery-logo-img { width: 100%; height: 100%; object-fit: contain; background: white; }

.text-content { display: flex; flex-direction: column; gap: 0.35rem; overflow: hidden; flex: 1; }
.title-row { display: flex; justify-content: space-between; align-items: flex-start; gap: 0.5rem; }
.card-title { margin: 0; font-size: 1.1rem; font-weight: 700; color: var(--text-main); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; flex: 1; }
.fav-btn { background: none; border: none; padding: 4px; cursor: pointer; color: var(--text-muted); transition: all 0.2s; display: flex; align-items: center; justify-content: center; }
.fav-btn:hover { transform: scale(1.2); color: var(--primary); }
.fav-btn.active { color: var(--primary); }
.card-subtitle { margin: 0; font-size: 0.85rem; color: var(--text-muted); font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.flag-icon { width: 20px; height: auto; vertical-align: middle; margin-right: 0.3rem; border-radius: 2px; }
.card-meta { display: flex; flex-wrap: wrap; gap: 0.75rem; margin-top: 0.1rem; }
.meta-item { display: flex; align-items: center; gap: 4px; font-size: 0.75rem; font-weight: 600; color: var(--text-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.card-rating { display: flex; align-items: center; gap: 4px; margin-top: 0.5rem; }
.rating-value { font-size: 0.9rem; font-weight: 800; color: #d97706; }
.count { font-size: 0.75rem; color: var(--text-muted); margin-left: 4px; }
.card-footer { padding: 0 1.25rem 1.25rem; }
.full-width-btn { width: 100%; justify-content: center; background-color: var(--bg-app); border: 1px solid var(--border); color: var(--text-main); }
.full-width-btn:hover { background-color: var(--border); }
</style>