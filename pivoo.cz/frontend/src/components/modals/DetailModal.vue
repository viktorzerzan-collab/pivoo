<template>
  <BaseModal :show="show" @close="$emit('close')" customStyle="max-width: 600px; overflow: hidden;">
    <template #header>
      
      <BackgroundWatermark :type="type" :logo="item?.logo" :size="180" :is-modal="true" />

      <div class="detail-header">
        <div class="header-text">
          <div class="title-with-badges">
            <h2 class="modal-title">{{ item?.name }}</h2>
            
            <div v-if="type === 'beer' && (item?.is_unfiltered || item?.is_unpasteurized)" class="property-badges">
              <span v-if="item.is_unfiltered" class="prop-badge">{{ $t('cards.unfiltered') }}</span>
              <span v-if="item.is_unpasteurized" class="prop-badge">{{ $t('cards.unpasteurized') }}</span>
            </div>

            <div v-if="type === 'location' && item?.type" class="property-badges">
              <span class="prop-badge loc-type">{{ formatLocationType(item.type) }}</span>
            </div>
          </div>
          
          <p class="modal-subtitle">
            <template v-if="type === 'beer'">
              <CountryFlag 
                :code="item?.brewery_country_code" 
                :name="item?.brewery_country" 
              />
              {{ item?.brewery_name }} • {{ translateStyle(item?.style) }}
            </template>
            
            <template v-else>
              <CountryFlag 
                :code="item?.country_code" 
                :name="item?.country" 
              />
              {{ translateDynamic(item?.city) || $t('modals.detail.unknown_city') }}{{ getCountryName(item?.country_code, item?.country) ? ', ' + getCountryName(item?.country_code, item?.country) : '' }}
            </template>
          </p>
        </div>
      </div>
    </template>

    <template #body>
      <div class="detail-content">
        <div class="rating-section">
          <span class="label">
            {{ type === 'beer' ? $t('modals.detail.rating_beer') : type === 'brewery' ? $t('modals.detail.rating_brewery') : $t('modals.detail.rating_care') }}
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
            <span v-else class="no-rating">{{ $t('modals.detail.unrated') }}</span>
          </div>
        </div>

        <hr class="ui-divider" />

        <div v-if="type === 'beer'" class="beer-detailed-info">
          
          <div class="stats-grid-complex">
            <div class="stat-box">
              <ActivityIcon :size="18" />
              <div class="stat-val">
                <small>{{ $t('modals.detail.epm') }}</small>
                <strong>{{ item.epm ? item.epm + '°' : '—' }}</strong>
              </div>
            </div>
            <div class="stat-box">
              <PercentIcon :size="18" />
              <div class="stat-val">
                <small>{{ $t('modals.detail.abv') }}</small>
                <strong>{{ item.abv ? item.abv + '%' : '—' }}</strong>
              </div>
            </div>
            <div class="stat-box">
              <ThermometerIcon :size="18" />
              <div class="stat-val">
                <small>{{ $t('modals.detail.ibu') }}</small>
                <strong>{{ item.ibu ? item.ibu + ' IBU' : '—' }}</strong>
              </div>
            </div>
            <div class="stat-box">
              <PipetteIcon :size="18" />
              <div class="stat-val">
                <small>{{ $t('modals.detail.ebc') }}</small>
                <strong>{{ item.ebc ? item.ebc + ' EBC' : '—' }}</strong>
              </div>
            </div>
          </div>

          <div class="info-sections-stack">
            <div v-if="item.fermentation" class="info-row">
              <FlaskConicalIcon :size="18" class="row-icon" />
              <div class="row-content">
                <strong>{{ $t('modals.detail.fermentation') }}</strong> <span>{{ translateFermentation(item.fermentation) }}</span>
              </div>
            </div>

            <div v-if="item.hops" class="info-row">
              <SproutIcon :size="18" class="row-icon" />
              <div class="row-content">
                <strong>{{ $t('modals.detail.hops') }}</strong> <span class="tags-list">{{ item.hops }}</span>
              </div>
            </div>

            <div v-if="item.malts" class="info-row">
              <WheatIcon :size="18" class="row-icon" />
              <div class="row-content">
                <strong>{{ $t('modals.detail.malts') }}</strong> <span class="tags-list">{{ item.malts }}</span>
              </div>
            </div>

            <div v-if="item.tags" class="info-row flavor-row">
              <TagIcon :size="18" class="row-icon" />
              <div class="row-content">
                <strong>{{ $t('modals.detail.profile') }}</strong>
                <div class="flavor-tags">
                  <span v-for="tag in item.tags.split(',')" :key="tag" class="f-tag">{{ tag.trim() }}</span>
                </div>
              </div>
            </div>
          </div>

          <div class="reviews-section">
            <h3 class="section-subtitle"><MessageSquareIcon :size="18" /> {{ $t('modals.detail.recent_tastings') }}</h3>
            <div v-if="reviews && reviews.length > 0" class="reviews-list">
              <div v-for="rev in reviews" :key="rev.id" class="review-bubble">
                <div class="rev-head">
                  <strong>{{ rev.user_name || 'Pivař' }}</strong>
                  <div class="mini-stars">
                    <StarIcon v-for="n in 5" :key="n" :size="12" :fill="n <= rev.rating_beer ? '#ca8a04' : 'none'" :color="n <= rev.rating_beer ? '#ca8a04' : '#cbd5e1'" />
                  </div>
                </div>
                <p v-if="rev.note" class="rev-note">"{{ rev.note }}"</p>
                <small class="rev-date">{{ translateDynamic(rev.location_name) }} • {{ new Date(rev.consumed_at).toLocaleDateString() }}</small>
              </div>
            </div>
            <div v-else class="empty-reviews">{{ $t('modals.detail.no_comments') }}</div>
          </div>
        </div>

        <div v-else class="contact-list">
          
          <div class="info-item" v-if="item?.address || item?.city || item?.zip_code">
            <MapPinIcon :size="18" class="icon-muted" />
            <div class="info-text">
              <strong>{{ $t('modals.detail.address') }}</strong><br>
              <span v-if="item.address">{{ item.address }}<br></span>
              <span>{{ item.zip_code ? item.zip_code + ' ' : '' }}{{ translateDynamic(item.city) || $t('modals.detail.unknown_city') }}</span>
              <span v-if="item.country"><br>{{ getCountryName(item.country_code, item.country) }}</span>
            </div>
          </div>

          <div v-if="item?.lat && item?.lng" class="info-item" style="margin-top: 0.5rem;">
            <MapIcon :size="18" class="icon-muted" />
            <div class="info-text">
              <strong>{{ $t('modals.detail.navigation') }}</strong><br>
              <a :href="`https://www.google.com/maps?q=${item.lat},${item.lng}`" 
                 target="_blank" 
                 class="link" style="display: inline-flex; align-items: center; gap: 0.3rem;">
                 {{ $t('modals.detail.open_map') }} <ExternalLinkIcon :size="14" />
              </a>
            </div>
          </div>

          <div v-if="item?.opening_hours" class="info-item">
            <ClockIcon :size="18" class="icon-muted" />
            <div class="info-text">
              <strong>{{ $t('modals.detail.opening_hours') }}</strong><br>
              <OpeningHoursDisplay :openingHours="item.opening_hours" />
            </div>
          </div>
          
          <div v-if="item?.phone" class="info-item">
            <PhoneIcon :size="18" class="icon-muted" />
            <div class="info-text">
              <strong>{{ $t('modals.detail.phone') }}</strong><br>
              <a :href="'tel:' + item.phone" class="link">{{ item.phone }}</a>
            </div>
          </div>
          
          <div v-if="item?.email" class="info-item">
            <MailIcon :size="18" class="icon-muted" />
            <div class="info-text">
              <strong>{{ $t('modals.detail.email') }}</strong><br>
              <a :href="'mailto:' + item.email" class="link">{{ item.email }}</a>
            </div>
          </div>
          
          <div v-if="item?.website" class="info-item">
            <GlobeIcon :size="18" class="icon-muted" />
            <div class="info-text">
              <strong>{{ $t('modals.detail.website') }}</strong><br>
              <a :href="item.website" target="_blank" class="link" rel="noopener noreferrer">{{ item.website }}</a>
            </div>
          </div>

        </div>
      </div>
    </template>
  </BaseModal>
</template>

<script setup>
import { 
  StarIcon, ActivityIcon, PercentIcon, MessageSquareIcon, 
  PhoneIcon, MailIcon, GlobeIcon, ClockIcon, ThermometerIcon, 
  PipetteIcon, SproutIcon, WheatIcon, FlaskConicalIcon, TagIcon, 
  MapIcon, ExternalLinkIcon, MapPinIcon 
} from 'lucide-vue-next'
import { useI18n } from 'vue-i18n'
import BaseModal from '../BaseModal.vue'
import BackgroundWatermark from '../BackgroundWatermark.vue' // Nový import
import OpeningHoursDisplay from '../OpeningHoursDisplay.vue'
import CountryFlag from '../CountryFlag.vue'

defineProps({
  show: Boolean,
  item: Object,
  type: String,
  reviews: Array
})

defineEmits(['close'])

const { t, te, locale } = useI18n()

const translateDynamic = (val) => {
  if (!val) return val
  const key = `dynamic.locations.${val}`
  return te(key) ? t(key) : val
}

const formatLocationType = (type) => {
  if (!type) return ''
  const key = `dynamic.location_types.${type}`
  return te(key) ? t(key) : type
}

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

const getCountryName = (code, fallback) => {
  if (!code) return fallback;
  try { return new Intl.DisplayNames([locale.value], { type: 'region' }).of(code.toUpperCase()); }
  catch (e) { return fallback; }
}
</script>

<style scoped>
/* Zajištění, aby texty v modálu byly vždy nad vodoznakem */
.detail-header, .detail-content { 
  position: relative; 
  z-index: 1; 
}

/* Hlavička detailu */
.detail-header { display: flex; align-items: center; }
.header-text { flex: 1; }
.title-with-badges { display: flex; flex-direction: column; gap: 0.4rem; }
.modal-title { margin: 0; font-size: 1.6rem; font-weight: 800; color: var(--text-main); line-height: 1.1; }

.property-badges { display: flex; gap: 0.5rem; }
.prop-badge { background: var(--primary); color: #1e293b; font-size: 0.65rem; font-weight: 800; text-transform: uppercase; padding: 2px 8px; border-radius: 4px; }
.prop-badge.loc-type { background: var(--bg-app); border: 1px solid var(--border); color: var(--text-muted); }

.modal-subtitle { margin: 0.35rem 0 0; color: var(--text-muted); font-size: 1rem; font-weight: 500; display: flex; align-items: center; gap: 0.4rem; }

.rating-section { text-align: center; padding: 1.25rem; background: var(--bg-app); border-radius: var(--radius-md); margin: 1rem 0; border: 1px solid var(--border); }
.label { font-size: 0.7rem; font-weight: 700; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.05em; }
.rating-display { display: flex; align-items: center; justify-content: center; gap: 0.5rem; margin-top: 0.4rem; }
.rating-number { font-size: 1.75rem; font-weight: 800; color: var(--text-main); }
.stars { display: flex; gap: 2px; }

.ui-divider { border: 0; border-top: 1px solid var(--border); margin: 1.5rem 0; }

.stats-grid-complex { display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.75rem; margin-bottom: 1.5rem; }
.stat-box { background: var(--bg-app); border: 1px solid var(--border); padding: 0.75rem 0.5rem; border-radius: var(--radius-md); display: flex; flex-direction: column; align-items: center; gap: 0.4rem; text-align: center; color: var(--text-muted); }
.stat-val { display: flex; flex-direction: column; line-height: 1.2; }
.stat-val small { font-size: 0.6rem; font-weight: 800; text-transform: uppercase; }
.stat-val strong { font-size: 0.95rem; color: var(--text-main); }

.info-sections-stack { display: flex; flex-direction: column; gap: 0.75rem; }
.info-row { display: flex; align-items: flex-start; gap: 0.75rem; background: var(--bg-app); padding: 0.75rem 1rem; border-radius: var(--radius-md); border: 1px solid var(--border); font-size: 0.95rem; }
.row-icon { color: var(--text-muted); margin-top: 2px; }
.row-content strong { color: var(--text-main); margin-right: 0.4rem; }
.tags-list { color: var(--text-muted); font-style: italic; }

.flavor-row { flex-direction: column; gap: 0.5rem; }
.flavor-tags { display: flex; flex-wrap: wrap; gap: 0.4rem; margin-top: 0.25rem; }
.f-tag { background: var(--bg-panel); border: 1px solid var(--border); padding: 2px 10px; border-radius: 99px; font-size: 0.8rem; font-weight: 600; color: var(--text-main); }

.section-subtitle { display: flex; align-items: center; gap: 0.5rem; font-size: 1.1rem; color: var(--text-main); margin: 2rem 0 1rem; }
.reviews-list { display: flex; flex-direction: column; gap: 1rem; max-height: 300px; overflow-y: auto; padding-right: 0.5rem; }
.review-bubble { background: var(--bg-app); border: 1px solid var(--border); padding: 1rem; border-radius: var(--radius-md); }
.rev-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.25rem; }
.rev-note { margin: 0.5rem 0; font-style: italic; color: var(--text-main); font-size: 0.95rem; line-height: 1.4; }
.rev-date { font-size: 0.75rem; color: var(--text-muted); }
.empty-reviews { text-align: center; color: var(--text-muted); font-size: 0.9rem; padding: 2rem; border: 1px dashed var(--border); border-radius: var(--radius-md); }

.contact-list { display: flex; flex-direction: column; gap: 1.25rem; }
.info-item { display: flex; align-items: flex-start; gap: 0.85rem; font-size: 0.95rem; color: var(--text-main); background: var(--bg-app); padding: 1rem; border-radius: var(--radius-md); border: 1px solid var(--border); }
.info-text { line-height: 1.4; }
.info-text strong { color: var(--text-main); display: inline-block; margin-bottom: 0.2rem; }
.link { color: var(--primary); text-decoration: none; font-weight: 600; display: inline-block; margin-top: 0.2rem; }
.link:hover { text-decoration: underline; }
.icon-muted { color: var(--text-muted); flex-shrink: 0; margin-top: 2px; }

@media (max-width: 500px) {
  .stats-grid-complex { grid-template-columns: 1fr 1fr; }
}
</style>