<template>
  <div class="beer-card" @click="$emit('showDetail', beer)">
    <div class="beer-header">
      <div class="beer-icon">🍺</div>
      <div class="beer-title">
        <h3>{{ beer.name }}</h3>
        <span class="brewery">{{ beer.brewery_name }}</span>
      </div>
    </div>
    
    <div class="beer-stats">
      <div class="stat" v-if="beer.epm"><strong>{{ beer.epm }}°</strong> EPM</div>
      <div class="stat" v-if="beer.abv"><strong>{{ beer.abv }}%</strong> ALK</div>
      <div class="stat" v-if="beer.style">{{ beer.style }}</div>
    </div>

    <div class="beer-rating" v-if="beer.total_checkins > 0">
      <span class="star">★</span> 
      <strong v-if="beer.avg_rating">{{ beer.avg_rating }}</strong>
      <strong v-else>-</strong>
      <span class="reviews-count">({{ beer.total_checkins }}x zapsáno)</span>
    </div>
    <div class="beer-rating empty" v-else>
      Zatím bez hodnocení
    </div>
  </div>
</template>

<script setup>
defineProps({ beer: { type: Object, required: true } })
// Umožníme kartičce vysílat signál, že se na ni kliklo
defineEmits(['showDetail'])
</script>

<style scoped>
.beer-card {
  background: white; border-radius: 12px; padding: 1.5rem;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); border: 1px solid #e5e7eb;
  transition: transform 0.2s, box-shadow 0.2s;
  cursor: pointer; /* Změní kurzor myši na ručičku! */
}
.beer-card:hover { transform: translateY(-4px); box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); border-color: #eab308; }
.beer-header { display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem; }
.beer-icon { font-size: 2.5rem; }
.beer-title h3 { margin: 0; color: #1f2937; font-size: 1.25rem; }
.brewery { color: #6b7280; font-size: 0.875rem; }
.beer-stats { display: flex; gap: 1rem; flex-wrap: wrap; padding-top: 1rem; border-top: 1px solid #f3f4f6; }
.stat { background-color: #fef3c7; color: #92400e; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.875rem; }

/* Styly pro hodnocení */
.beer-rating { margin-top: 1rem; padding-top: 1rem; border-top: 1px dashed #e5e7eb; font-size: 1.1rem; color: #1f2937; display: flex; align-items: center; gap: 0.25rem; }
.star { color: #eab308; font-size: 1.25rem; }
.reviews-count { color: #9ca3af; font-size: 0.85rem; margin-left: 0.5rem; font-weight: normal; }
.empty { color: #9ca3af; font-style: italic; font-size: 0.9rem; }
</style>