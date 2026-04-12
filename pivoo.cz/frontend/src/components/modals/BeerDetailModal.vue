<template>
  <BaseModal :show="show" @close="$emit('close')">
    <template #header>
      <h2 class="modal-title" v-if="beer" style="color: #ca8a04;">🍺 {{ beer.name }}</h2>
    </template>
    <template #body v-if="beer">
      <div class="beer-detail-info">
        <p><strong>Uvařil:</strong> {{ beer.brewery_name }}</p>
        <p v-if="beer.style"><strong>Styl:</strong> {{ beer.style }}</p>
        <div class="big-rating-box" v-if="beer.total_checkins > 0">
          <div class="big-star">★</div><div class="big-number">{{ beer.avg_rating || '?' }}</div><div class="big-text">Průměrné skóre z {{ beer.total_checkins }} zápisů</div>
        </div>
      </div>
      <h3 class="reviews-title">Zápisy a poznámky:</h3>
      <div v-if="reviews.length > 0" class="reviews-list">
         <div v-for="(review, index) in reviews" :key="index" class="review-item">
           <div class="review-header">
             <div class="review-stars"><span v-for="s in 5" :key="s" class="small-star" :class="{'is-active': s <= review.rating_beer}">★</span></div>
             <div class="review-meta"><strong style="color: #374151;">👤 {{ review.user_name }}</strong> v 📍 {{ review.location_name }}</div>
           </div>
           <div v-if="review.note" class="review-note">"{{ review.note }}"</div>
         </div>
      </div>
      <div v-else class="empty-reviews">Zatím nejsou k tomuto pivu žádné detailní zápisy.</div>
    </template>
  </BaseModal>
</template>

<script setup>
import BaseModal from '../BaseModal.vue'

defineProps({ show: Boolean, beer: Object, reviews: Array })
defineEmits(['close'])
</script>

<style scoped>
.modal-title { margin: 0; color: #1f2937; font-size: 1.5rem; }
.beer-detail-info { font-size: 1.1rem; color: #4b5563; margin-bottom: 1.5rem; text-align: center; }
.beer-detail-info p { margin: 0.25rem 0; }
.big-rating-box { background-color: #fef3c7; border-radius: 12px; padding: 1.5rem; text-align: center; margin-top: 1rem; color: #92400e; border: 1px solid #fde68a; }
.big-star { font-size: 3rem; line-height: 1; margin-bottom: 0.5rem; color: #eab308; }
.big-number { font-size: 3.5rem; font-weight: bold; line-height: 1; }
.big-text { font-size: 0.95rem; margin-top: 0.5rem; opacity: 0.9; }
.reviews-title { margin-top: 2rem; border-bottom: 1px solid #e5e7eb; padding-bottom: 0.5rem; font-size: 1.1rem; color: #1f2937; }
.reviews-list { display: flex; flex-direction: column; gap: 0.75rem; margin-top: 1rem;}
.review-item { padding: 1rem; background: #f9fafb; border-radius: 8px; border: 1px solid #e5e7eb; }
.review-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.5rem; }
.review-stars { display: flex; gap: 0.1rem; }
.small-star { color: #d1d5db; font-size: 1.1rem; }
.small-star.is-active { color: #eab308; }
.review-meta { color: #6b7280; font-size: 0.95rem; text-align: right; }
.review-note { color: #374151; font-style: italic; margin-top: 0.5rem; font-size: 0.95rem; border-left: 3px solid #eab308; padding-left: 0.5rem; }
.empty-reviews { color: #9ca3af; font-style: italic; margin-top: 1rem; text-align: center; padding: 2rem 0; }
@media (max-width: 600px) {
  .review-header { flex-direction: column; align-items: flex-start; gap: 0.5rem; }
  .review-meta { text-align: left; }
}
</style>