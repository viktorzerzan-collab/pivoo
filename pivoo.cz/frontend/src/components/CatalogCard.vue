<template>
  <div class="card catalog-card" :class="cardClasses">
    
    <BackgroundWatermark :type="type" :logo="item.logo" :size="140" />

    <div class="card-body">
      <div class="card-main-info">
        
        <div class="text-content">
          <div class="title-row">
            <h3 class="card-title">{{ item.name }}</h3>
            <div class="action-wrap">
              <FavoriteButton :is-favorite="item.is_favorite" @toggle="toggleFav" />
              <WishlistButton :is-wishlist="item.is_wishlist" @toggle="toggleWishlist" />
            </div>
          </div>
          
          <div v-if="type === 'beer'" class="subtitle-line">
            <CountryFlag :code="item.brewery_country_code" :name="item.brewery_country" />
            <span class="subtitle-text">{{ item.brewery_name }}</span>
          </div>
          <p v-else class="card-subtitle">
            <CountryFlag :code="item.country_code" :name="item.country" />
            {{ formattedLocation }}
          </p>

          <div v-if="type === 'beer'" class="card-meta">
            <BaseTooltip v-if="item.epm" text="Stupňovitost (EPM)" position="top">
              <div class="meta-item"><ActivityIcon :size="12" /> {{ item.epm }}°</div>
            </BaseTooltip>
            <BaseTooltip v-if="item.abv" text="Alkohol" position="top">
              <div class="meta-item"><PercentIcon :size="12" /> {{ item.abv }}%</div>
            </BaseTooltip>
            <BaseTooltip v-if="item.ibu" text="Hořkost" position="top">
              <div class="meta-item"><ThermometerIcon :size="12" /> {{ item.ibu }} IBU</div>
            </BaseTooltip>
            <BaseTooltip v-if="item.ebc" text="Barva" position="top">
              <div class="meta-item"><PipetteIcon :size="12" /> {{ item.ebc }} EBC</div>
            </BaseTooltip>
          </div>

          <template v-else>
            <div class="card-meta" v-if="type === 'location' && item.address">
              <div class="meta-item"><MapPinIcon :size="12" /> {{ item.address }}</div>
            </div>
            <div class="card-meta" style="margin-top: 0.5rem;" @click.stop>
              <OpeningHoursDisplay :openingHours="item.opening_hours" />
            </div>
            <div class="card-meta distance-meta" @click.stop>
              <div class="meta-item">
                <DistanceDisplay :lat="item.lat" :lng="item.lng" />
              </div>
            </div>
          </template>

          <div v-if="type === 'beer'" class="tags-row">
             <span class="tag-badge">{{ translateStyle(item.style) }}</span>
             <span v-if="item.fermentation" class="tag-badge">{{ translateFermentation(item.fermentation) }}</span>
             <span v-if="item.is_unfiltered" class="tag-badge">{{ $t('cards.unfiltered') }}</span>
             <span v-if="item.is_unpasteurized" class="tag-badge">{{ $t('cards.unpasteurized') }}</span>
          </div>

          <div v-if="item.avg_rating" class="card-rating">
            <StarIcon :size="14" :fill="starColor" :color="starColor" />
            <span class="rating-value" :style="{ color: starColor }">{{ Number(item.avg_rating).toFixed(1) }}</span>
            <span class="count" v-if="ratingCount">
              ({{ ratingCount }}{{ ratingCountLabel }})
            </span>
          </div>
        </div>
      </div>
    </div>
    
    <div class="card-footer">
      <BaseButton variant="secondary" @click="$emit('showDetail', item)" class="full-width-btn">
        <template #icon><InfoIcon :size="16" /></template>
        {{ detailButtonLabel }}
      </BaseButton>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useCatalogStore } from '../stores/catalog'
import { 
  MapPinIcon, StarIcon, InfoIcon, ThermometerIcon, PercentIcon, ActivityIcon, PipetteIcon 
} from 'lucide-vue-next'

import BackgroundWatermark from './BackgroundWatermark.vue' // Nový import
import BaseButton from './BaseButton.vue'
import FavoriteButton from './FavoriteButton.vue'
import WishlistButton from './WishlistButton.vue'
import CountryFlag from './CountryFlag.vue'
import BaseTooltip from './BaseTooltip.vue'
import DistanceDisplay from './DistanceDisplay.vue'
import OpeningHoursDisplay from './OpeningHoursDisplay.vue'

const props = defineProps({
  item: { type: Object, required: true },
  type: { type: String, required: true, validator: (val) => ['beer', 'brewery', 'location'].includes(val) }
})

defineEmits(['showDetail'])

const catalogStore = useCatalogStore()
const { t, te } = useI18n()

// --- AKCE ---
const toggleFav = () => { catalogStore.toggleFavorite(props.item.id, props.type) }
const toggleWishlist = () => { catalogStore.toggleWishlist(props.item.id, props.type) }

// --- COMPUTED VLASTNOSTI PRO DYNAMICKÉ VYKRESLENÍ ---
const cardClasses = computed(() => {
  return {
    'is-fav': props.item.is_favorite,
    'special-beer': props.type === 'beer' && (props.item.is_unfiltered || props.item.is_unpasteurized)
  }
})

const starColor = computed(() => props.type === 'location' ? '#0ea5e9' : '#f59e0b')

const detailButtonLabel = computed(() => {
  if (props.type === 'beer') return t('cards.beer_detail')
  if (props.type === 'brewery') return t('cards.brewery_detail')
  return t('cards.location_detail')
})

const ratingCount = computed(() => {
  if (props.type === 'beer') return props.item.total_checkins
  if (props.type === 'brewery') return props.item.total_beers_in_catalog
  return props.item.total_visits
})

const ratingCountLabel = computed(() => {
  if (props.type === 'beer') return t('cards.in_diary')
  if (props.type === 'brewery') return ` ${t('cards.beers_count')}`
  return t('cards.visits_count')
})

const formattedLocation = computed(() => {
  let loc = props.item.city || ''
  
  if (props.type === 'location') {
    const key = `dynamic.locations.${loc}`
    if (te(key)) loc = t(key)
  }

  if (props.item.country && props.item.country !== 'Česká republika') { 
    loc += loc ? ', ' + props.item.country : props.item.country 
  }
  return loc || t('cards.unknown_location')
})

// --- POMOCNÉ METODY PRO PIVO ---
const translateStyle = (val) => {
  if (!val) return t('cards.no_style')
  const key = `dynamic.styles.${val}`
  return te(key) ? t(key) : val
}

const translateFermentation = (val) => {
  if (!val) return ''
  const key = `dynamic.fermentation.${val}`
  return te(key) ? t(key) : `${val} ${t('cards.fermentation_suffix')}`
}
</script>

<style scoped>
.card { 
  background: var(--bg-panel); 
  border: 1px solid var(--border); 
  border-radius: var(--radius-md); 
  display: flex; 
  flex-direction: column; 
  box-shadow: none; 
  transition: border-color 0.2s, background-color 0.3s ease; 
  height: 100%; 
  position: relative; 
  z-index: 1;
  overflow: hidden; /* Důležité pro oříznutí vodoznaku */
}

.card.is-fav { border-color: var(--primary); outline: 1px solid var(--primary); }
.card:hover { border-color: var(--primary); background-color: var(--card-hover-bg); z-index: 10; }

/* Animace a průhlednost při hover na kartu (používáme :deep, protože vodoznak je nyní uvnitř dětské komponenty) */
.card:hover :deep(.background-watermark) {
  opacity: 0.15; /* Výraznější opacity při hoveru (světlý režim) */
  transform: rotate(10deg) scale(1.1);
}

.card:hover :deep(.background-watermark.is-logo) {
  opacity: 0.18;
  transform: rotate(-5deg) scale(1.05);
}

/* Hover stavy pro tmavý režim */
:global(.dark) .card:hover :deep(.background-watermark),
:global([data-theme="dark"]) .card:hover :deep(.background-watermark) {
  opacity: 0.08;
}

:global(.dark) .card:hover :deep(.background-watermark.is-logo),
:global([data-theme="dark"]) .card:hover :deep(.background-watermark.is-logo) {
  opacity: 0.1;
}

.card-body { padding: 1.25rem; flex-grow: 1; position: relative; z-index: 1; }
.card-main-info { display: flex; gap: 1rem; align-items: flex-start; }

.text-content { display: flex; flex-direction: column; gap: 0.35rem; flex: 1; min-width: 0; }

/* Hlavička */
.title-row { display: flex; justify-content: space-between; align-items: flex-start; gap: 0.5rem; }
.card-title { margin: 0; font-size: 1.1rem; font-weight: 800; color: var(--text-main); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; flex: 1; }
.action-wrap { display: flex; align-items: center; gap: 0.25rem; }

/* Podnadpis */
.subtitle-line { display: flex; align-items: center; gap: 0.3rem; margin-top: 0.15rem; min-width: 0; }
.subtitle-text { font-size: 0.9rem; color: var(--text-main); font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; flex: 1; }
.card-subtitle { margin: 0; font-size: 0.85rem; color: var(--text-muted); font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

/* Meta data */
.card-meta { display: flex; flex-wrap: wrap; gap: 0.75rem; margin-top: 0.1rem; }
.meta-item { display: flex; align-items: center; gap: 4px; font-size: 0.75rem; font-weight: 600; color: var(--text-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.distance-meta { margin-top: 0.3rem !important; }

/* Tagy */
.tags-row { display: flex; flex-wrap: wrap; gap: 0.4rem; margin-top: 0.6rem; }
.tag-badge { 
  background: rgba(var(--bg-app-rgb), 0.7); 
  border: 1px solid var(--border); 
  padding: 3px 8px; 
  border-radius: var(--radius-sm); 
  font-size: 0.7rem; 
  font-weight: 700; 
  color: var(--text-muted); 
  transition: all 0.3s ease;
  backdrop-filter: blur(2px);
}

/* Hodnocení */
.card-rating { display: flex; align-items: center; gap: 4px; margin-top: 0.5rem; }
.rating-value { font-size: 0.95rem; font-weight: 800; }
.count { font-size: 0.75rem; color: var(--text-muted); margin-left: 4px; }

/* Patička */
.card-footer { padding: 0 1.25rem 1.25rem; position: relative; z-index: 1; }
.full-width-btn { width: 100%; justify-content: center; background-color: var(--bg-app); border: 1px solid var(--border); color: var(--text-main); }
.full-width-btn:hover { background-color: var(--border); }
</style>