<template>
  <BaseModal :show="show" @close="$emit('close')" customStyle="max-width: 550px;">
    <template #header>
      <div class="detail-header">
        <div class="icon-box" :class="type">
          <BeerIcon v-if="type === 'beer'" :size="32" />
          <FactoryIcon v-else-if="type === 'brewery'" :size="32" />
          <MapPinIcon v-else :size="32" />
        </div>
        <div>
          <h2 class="modal-title">{{ item?.name }}</h2>
          <p class="modal-subtitle">
            <template v-if="type === 'beer'">{{ item?.brewery_name }} • {{ item?.style || 'Bez stylu' }}</template>
            <template v-else>{{ item?.city }}{{ item?.address ? ', ' + item.address : '' }}</template>
          </p>
        </div>
      </div>
    </template>

    <template #body>
      <div class="detail-content">
        <div class="rating-section">
          <span class="label">
            {{ type === 'beer' ? 'Celkové hodnocení piva' : type === 'brewery' ? 'Průměrné hodnocení produkce' : 'Hodnocení obsluhy a péče' }}
          </span>
          <div class="rating-display">
            <template v-if="item?.avg_rating && item.avg_rating > 0">
              <div class="stars">
                <StarIcon v-for="n in 5" :key="n" :size="20" 
                  :fill="n <= Math.round(item.avg_rating) ? '#f59e0b' : 'none'"
                  :color="n <= Math.round(item.avg_rating) ? '#f59e0b' : '#cbd5e1'" 
                />
              </div>
              <span class="rating-number">{{ Number(item.avg_rating).toFixed(1) }}</span>
            </template>
            <span v-else class="no-rating">Zatím nehodnoceno</span>
          </div>
        </div>

        <hr class="ui-divider" />

        <div v-if="type === 'beer'" class="beer-specifics">
          <div class="stats-grid">
            <div class="stat-mini">
              <ActivityIcon :size="18" class="icon-muted" />
              <div class="stat-text"><span>Stupňovitost</span><strong>{{ item.epm ? item.epm + '°' : '—' }}</strong></div>
            </div>
            <div class="stat-mini">
              <PercentIcon :size="18" class="icon-muted" />
              <div class="stat-text"><span>Alkohol</span><strong>{{ item.abv ? item.abv + '%' : '—' }}</strong></div>
            </div>
          </div>

          <div class="reviews-section">
            <h3 class="section-subtitle"><MessageSquareIcon :size="18" /> Poslední ochutnávky</h3>
            <div v-if="reviews && reviews.length > 0" class="reviews-list">
              <div v-for="rev in reviews" :key="rev.id" class="review-bubble">
                <div class="rev-head">
                  <strong>{{ rev.username || 'Pivař' }}</strong>
                  <div class="mini-stars">
                    <StarIcon v-for="n in 5" :key="n" :size="12" :fill="n <= rev.rating_beer ? '#ca8a04' : 'none'" :color="n <= rev.rating_beer ? '#ca8a04' : '#cbd5e1'" />
                  </div>
                </div>
                <p v-if="rev.note" class="rev-note">"{{ rev.note }}"</p>
              </div>
            </div>
            <div v-else class="empty-reviews">Tohle pivo zatím nikdo nekomentoval.</div>
          </div>
        </div>

        <div v-else class="contact-list">
          <div v-if="item?.opening_hours" class="info-item">
            <ClockIcon :size="18" class="icon-muted" />
            <div class="info-text"><strong>Otevírací doba:</strong><br><small>{{ item.opening_hours }}</small></div>
          </div>
          <div v-if="item?.phone" class="info-item">
            <PhoneIcon :size="18" class="icon-muted" />
            <a :href="'tel:' + item.phone" class="link">{{ item.phone }}</a>
          </div>
          <div v-if="item?.email" class="info-item">
            <MailIcon :size="18" class="icon-muted" />
            <a :href="'mailto:' + item.email" class="link">{{ item.email }}</a>
          </div>
          <div v-if="item?.website" class="info-item">
            <GlobeIcon :size="18" class="icon-muted" />
            <a :href="item.website" target="_blank" class="link">Webové stránky</a>
          </div>
        </div>
      </div>
    </template>
  </BaseModal>
</template>

<script setup>
import { 
  BeerIcon, FactoryIcon, MapPinIcon, StarIcon, ActivityIcon, 
  PercentIcon, MessageSquareIcon, PhoneIcon, MailIcon, GlobeIcon, ClockIcon 
} from 'lucide-vue-next'
import BaseModal from '../BaseModal.vue'

defineProps({
  show: Boolean,
  item: Object,
  type: String, // 'beer', 'brewery', 'location'
  reviews: Array // Pouze pro piva
})
defineEmits(['close'])
</script>

<style scoped>
.detail-header { display: flex; align-items: center; gap: 1rem; }
.icon-box { padding: 0.8rem; border-radius: 12px; }
.icon-box.beer { background: #fef9c3; color: #ca8a04; }
.icon-box.brewery { background: #ffedd5; color: #b45309; }
.icon-box.location { background: #e0f2fe; color: #0369a1; }
.modal-title { margin: 0; font-size: 1.5rem; font-weight: 800; color: #1e293b; }
.modal-subtitle { margin: 0; color: #64748b; font-size: 0.95rem; }

.rating-section { text-align: center; padding: 1.25rem; background: #f8fafc; border-radius: 12px; margin: 1rem 0; border: 1px solid #f1f5f9; }
.label { font-size: 0.7rem; font-weight: 700; text-transform: uppercase; color: #94a3b8; letter-spacing: 0.05em; }
.rating-display { display: flex; align-items: center; justify-content: center; gap: 0.5rem; margin-top: 0.4rem; }
.rating-number { font-size: 1.5rem; font-weight: 800; color: #1e293b; }
.stars { display: flex; gap: 2px; }

.ui-divider { border: 0; border-top: 1px solid #f1f5f9; margin: 1.25rem 0; }

.stats-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
.stat-mini { display: flex; align-items: center; gap: 0.75rem; background: #f8fafc; padding: 0.75rem; border-radius: 10px; }
.stat-text { display: flex; flex-direction: column; font-size: 0.85rem; }
.stat-text span { color: #64748b; font-size: 0.7rem; text-transform: uppercase; font-weight: 700; }

.section-subtitle { display: flex; align-items: center; gap: 0.5rem; font-size: 1rem; color: #334155; margin: 1.5rem 0 1rem; }
.reviews-list { display: flex; flex-direction: column; gap: 0.75rem; max-height: 250px; overflow-y: auto; }
.review-bubble { background: white; border: 1px solid #e2e8f0; padding: 0.8rem; border-radius: 10px; }
.rev-head { display: flex; justify-content: space-between; font-size: 0.85rem; }
.rev-note { margin: 0.3rem 0 0; font-style: italic; color: #475569; font-size: 0.9rem; }
.empty-reviews { text-align: center; color: #94a3b8; font-size: 0.9rem; padding: 1rem; }

.contact-list { display: flex; flex-direction: column; gap: 1rem; }
.info-item { display: flex; align-items: flex-start; gap: 0.75rem; font-size: 0.95rem; }
.link { color: var(--primary); text-decoration: none; font-weight: 600; }
.link:hover { text-decoration: underline; }
.icon-muted { color: #cbd5e1; flex-shrink: 0; }
</style>