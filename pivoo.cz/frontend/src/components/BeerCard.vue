<template>
  <div class="card beer-card" :class="{ 'special-beer': beer.is_unfiltered || beer.is_unpasteurized, 'is-fav': beer.is_favorite }">
    <div class="card-body">
      <div class="card-main-info">
        <div class="icon-wrapper beer-bg">
          <BeerIcon :size="24" color="var(--primary)" />
        </div>
        <div class="text-content">
          <div class="title-row">
            <h3 class="card-title">{{ beer.name }}</h3>
            <div class="action-wrap">
              <FavoriteButton 
                :is-favorite="beer.is_favorite" 
                @toggle="toggleFav" 
              />
              <WishlistButton 
                :is-wishlist="beer.is_wishlist" 
                @toggle="toggleWishlist" 
              />
            </div>
          </div>
          
          <div class="brewery-line">
            <CountryFlag 
              :code="beer.brewery_country_code" 
              :name="beer.brewery_country" 
            />
            <span class="brewery-name">{{ beer.brewery_name }}</span>
          </div>

          <div class="card-meta">
            <BaseTooltip v-if="beer.epm" text="Stupňovitost (EPM)" position="top">
              <div class="meta-item">
                <ActivityIcon :size="12" /> {{ beer.epm }}°
              </div>
            </BaseTooltip>

            <BaseTooltip v-if="beer.abv" text="Alkohol" position="top">
              <div class="meta-item">
                <PercentIcon :size="12" /> {{ beer.abv }}%
              </div>
            </BaseTooltip>

            <BaseTooltip v-if="beer.ibu" text="Hořkost" position="top">
              <div class="meta-item">
                <ThermometerIcon :size="12" /> {{ beer.ibu }} IBU
              </div>
            </BaseTooltip>

            <BaseTooltip v-if="beer.ebc" text="Barva" position="top">
              <div class="meta-item">
                <PipetteIcon :size="12" /> {{ beer.ebc }} EBC
              </div>
            </BaseTooltip>
          </div>

          <div class="tags-row">
             <span class="tag-badge">{{ beer.style || 'Bez stylu' }}</span>
             <span v-if="beer.fermentation" class="tag-badge">{{ beer.fermentation }} kvašení</span>
             <span v-if="beer.is_unfiltered" class="tag-badge">Nefiltrované</span>
             <span v-if="beer.is_unpasteurized" class="tag-badge">Nepasterizované</span>
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
import { 
  BeerIcon, StarIcon, InfoIcon, ThermometerIcon, 
  PercentIcon, ActivityIcon, PipetteIcon 
} from 'lucide-vue-next'
import BaseButton from './BaseButton.vue'
import FavoriteButton from './FavoriteButton.vue'
import WishlistButton from './WishlistButton.vue'
import CountryFlag from './CountryFlag.vue'
import BaseTooltip from './BaseTooltip.vue'
import { useCatalogStore } from '../stores/catalog'
import { useAuthStore } from '../stores/auth'

const props = defineProps({ beer: Object })
defineEmits(['showDetail'])
const catalogStore = useCatalogStore()
const authStore = useAuthStore()

const toggleFav = () => { catalogStore.toggleFavorite(props.beer.id, 'beer') }
const toggleWishlist = () => { catalogStore.toggleWishlist(props.beer.id, 'beer') }
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

.icon-wrapper { padding: 0.75rem; border-radius: 10px; display: flex; align-items: center; justify-content: center; transition: background-color 0.3s ease; }
.beer-bg { background: #1e293b; }

/* OPRAVA: Přidáno min-width: 0 */
.text-content { display: flex; flex-direction: column; gap: 0.35rem; flex: 1; min-width: 0; }

.title-row { display: flex; justify-content: space-between; align-items: flex-start; gap: 0.5rem; }
.card-title { margin: 0; font-size: 1.15rem; font-weight: 800; color: var(--text-main); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; flex: 1; }
.action-wrap { display: flex; align-items: center; gap: 0.25rem; }

/* OPRAVA: Pojistka pro dlouhé názvy pivovaru */
.brewery-line { display: flex; align-items: center; gap: 0.3rem; margin-top: 0.15rem; min-width: 0; }
.brewery-name { font-size: 0.9rem; color: var(--text-main); font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; flex: 1; }

.card-meta { display: flex; flex-wrap: wrap; gap: 0.75rem; margin-top: 0.4rem; padding: 0.2rem 0; }
.meta-item { display: flex; align-items: center; gap: 3px; font-size: 0.75rem; font-weight: 600; color: var(--text-muted); }

.tags-row { display: flex; flex-wrap: wrap; gap: 0.4rem; margin-top: 0.6rem; }

.tag-badge { 
  background: var(--bg-app); 
  border: 1px solid var(--border); 
  padding: 3px 8px; 
  border-radius: 6px; 
  font-size: 0.7rem; 
  font-weight: 700; 
  color: var(--text-muted); 
  transition: all 0.3s ease;
}

.card-rating { display: flex; align-items: center; gap: 4px; margin-top: 0.8rem; }
.rating-value { font-size: 0.95rem; font-weight: 800; color: #f59e0b; }
.count { font-size: 0.75rem; color: var(--text-muted); margin-left: 4px; }

.card-footer { padding: 0 1.25rem 1.25rem; }
.full-width-btn { width: 100%; justify-content: center; background-color: var(--bg-app); border: 1px solid var(--border); color: var(--text-main); }
.full-width-btn:hover { background-color: var(--border); }
</style>