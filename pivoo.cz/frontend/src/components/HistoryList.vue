<template>
  <div class="history-grid" v-if="history && history.length > 0">
    <div v-for="record in history" :key="record.id" class="card history-card">
      
      <div class="background-watermark">
        <BeerIcon :size="140" color="var(--primary)" />
      </div>

      <div class="card-body">
        <div class="card-main-info">
          
          <div class="text-content">
            <div class="title-row">
              <h3 class="card-title text-truncate" :title="record.beer_name">{{ record.beer_name }}</h3>
              <div class="action-wrap">
                <BaseActionGroup>
                  <BaseTooltip :text="$t('buttons.edit')" position="top">
                    <button class="btn-edit" @click="$emit('edit', record)">
                      <PencilIcon :size="16" />
                    </button>
                  </BaseTooltip>
                  <BaseTooltip :text="$t('buttons.delete')" position="top">
                    <button class="btn-danger" @click="$emit('delete', record.id)">
                      <Trash2Icon :size="16" />
                    </button>
                  </BaseTooltip>
                </BaseActionGroup>
              </div>
            </div>
            
            <div class="brewery-line">
              <FactoryIcon :size="12" class="icon-shrink text-muted" /> 
              <span class="brewery-name text-truncate" :title="record.brewery_name">{{ record.brewery_name }}</span>
            </div>

            <div class="tags-row">
              <span class="tag-badge">{{ translatePackaging(record.packaging) }}</span>
            </div>

            <div class="consumption-meta">
              <div class="meta-item quantity-item">
                <span class="label">{{ $t('history.volume') }}</span>
                <strong>{{ record.volume }} l</strong>
              </div>

              <div class="meta-item quantity-item">
                <span class="label">{{ $t('history.quantity') }}</span>
                <strong>{{ record.quantity }}x</strong>
              </div>
              
              <div class="meta-item price-item" v-if="Number(record.is_free) === 1 || record.price">
                <span class="label">{{ $t('history.price') }}</span>
                <div class="price-val">
                  <template v-if="Number(record.is_free) === 1">
                    <span class="free-text">{{ $t('history.free') }}</span>
                    <small class="unit">{{ $t('history.did_not_pay') }}</small>
                  </template>
                  <template v-else>
                    <template v-if="(record.currency || 'CZK') !== userCurrency">
                      <span class="price-total">{{ ((record.original_price || record.price) * record.quantity).toFixed(2) }} {{ record.currency || 'CZK' }}</span>
                      <small class="unit" v-if="isLoadingRate">(...)</small>
                      <small class="unit" v-else>({{ Math.round(record.price * record.quantity * exchangeRate) }} {{ userCurrency }} {{ $t('history.total') }})</small>
                    </template>
                    <template v-else>
                      <span class="price-total">{{ ((record.original_price || record.price) * record.quantity).toFixed(2) }} {{ userCurrency }}</span>
                      <small class="unit">({{ record.original_price || record.price }} {{ userCurrency }}/{{ $t('history.per_piece') }})</small>
                    </template>
                  </template>
                </div>
              </div>
            </div>

            <div class="history-details">
              <div class="info-row">
                <MapPinIcon :size="12" class="icon-shrink text-muted" /> 
                <span class="text-truncate" :title="translateLocation(record.location_name)">{{ translateLocation(record.location_name) }}</span>
              </div>
              <div class="info-row date">
                <CalendarDaysIcon :size="12" class="icon-shrink text-muted" /> 
                <span class="text-truncate">{{ formatDate(record.consumed_at) }}</span>
              </div>
              <div class="info-row note-row" v-if="record.note">
                <QuoteIcon :size="12" class="icon-shrink text-muted" />
                <span class="text-truncate italic" :title="record.note">{{ record.note }}</span>
              </div>
            </div>

          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch, computed } from 'vue'
import { 
  BeerIcon, PencilIcon, Trash2Icon, MapPinIcon, 
  CalendarDaysIcon, FactoryIcon, QuoteIcon 
} from 'lucide-vue-next'
import BaseTooltip from './BaseTooltip.vue'
import BaseActionGroup from './BaseActionGroup.vue'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '../stores/auth'

defineProps({ history: { type: Array, required: true } })
defineEmits(['edit', 'delete'])

const { t, te } = useI18n()
const authStore = useAuthStore()

const userCurrency = computed(() => authStore.defaultCurrency || 'CZK')
const exchangeRate = ref(1.0)
const isLoadingRate = ref(false)

const fetchRate = async () => {
  if (userCurrency.value === 'CZK') {
    exchangeRate.value = 1.0;
    return;
  }
  isLoadingRate.value = true;
  try {
    const res = await fetch(`https://cdn.jsdelivr.net/npm/@fawazahmed0/currency-api@latest/v1/currencies/czk.json`);
    const data = await res.json();
    const rate = data.czk[userCurrency.value.toLowerCase()];
    if (rate) {
      exchangeRate.value = rate;
    }
  } catch (e) {
    console.error("Nepodařilo se načíst kurz pro historii:", e);
  } finally {
    isLoadingRate.value = false;
  }
}

onMounted(() => {
  fetchRate()
})

watch(userCurrency, () => {
  fetchRate()
})

const packagingMap = {
  'točené': 'modals.checkin.packaging.draft',
  'lahev': 'modals.checkin.packaging.bottle',
  'plechovka': 'modals.checkin.packaging.can',
  'pet': 'modals.checkin.packaging.pet',
  'sud': 'modals.checkin.packaging.keg'
}

const translatePackaging = (val) => packagingMap[val] ? t(packagingMap[val]) : val

const translateLocation = (val) => {
  if (!val) return val
  const key = `dynamic.locations.${val}`
  return te(key) ? t(key) : val
}

const formatDate = (dateStr) => {
  if (!dateStr) return ''
  const date = new Date(dateStr)
  return date.toLocaleDateString('cs-CZ', { 
    day: '2-digit', 
    month: '2-digit', 
    year: 'numeric'
  })
}
</script>

<style scoped>
.history-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(min(100%, 350px), 1fr));
  gap: 1.5rem;
  width: 100%;
}

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
  min-width: 0;
  overflow: hidden;
}
.card:hover { 
  border-color: var(--primary); 
  background-color: var(--card-hover-bg); 
  transform: translateY(-2px);
  box-shadow: var(--shadow-sm);
}

/* Vodoznak sjednocený s CatalogCard */
.background-watermark {
  position: absolute;
  right: -15px;
  top: 10px;
  opacity: 0.04;
  pointer-events: none;
  z-index: 0;
  transform: rotate(15deg);
  transition: all 0.4s ease;
  display: flex;
  align-items: center;
  justify-content: center;
}

.card:hover .background-watermark {
  opacity: 0.07;
  transform: rotate(10deg) scale(1.1);
}

.card-body { padding: 1.25rem; flex-grow: 1; min-width: 0; position: relative; z-index: 1; }
.card-main-info { display: flex; gap: 1rem; align-items: flex-start; }

.text-content { display: flex; flex-direction: column; gap: 0.35rem; flex: 1; min-width: 0; }

.title-row { display: flex; justify-content: space-between; align-items: flex-start; gap: 0.5rem; }
.card-title { margin: 0; font-size: 1.15rem; font-weight: 800; color: var(--text-main); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; flex: 1; line-height: 1.2; }
.action-wrap { display: flex; align-items: center; gap: 0.25rem; flex-shrink: 0; }

.brewery-line { display: flex; align-items: center; gap: 0.35rem; margin-top: 0.15rem; min-width: 0; color: var(--text-muted); font-size: 0.85rem;}
.brewery-name { font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; flex: 1; }

.tags-row { display: flex; flex-wrap: wrap; gap: 0.4rem; margin-top: 0.4rem; }
.tag-badge { 
  background: rgba(var(--bg-app-rgb), 0.7); 
  border: 1px solid var(--border); 
  padding: 3px 8px; 
  border-radius: var(--radius-sm); 
  font-size: 0.7rem; 
  font-weight: 700; 
  color: var(--text-muted); 
  text-transform: uppercase;
  backdrop-filter: blur(2px);
}

.consumption-meta { 
  display: flex; 
  gap: 1.5rem; 
  margin-top: 0.8rem; 
  padding: 0.85rem; 
  background: rgba(var(--bg-app-rgb), 0.8); 
  border-radius: var(--radius-sm); 
  border: 1px solid var(--border); 
  backdrop-filter: blur(2px); 
}
.meta-item { display: flex; flex-direction: column; gap: 0.15rem; min-width: 0; }
.label { font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); font-weight: 700; }

.quantity-item { flex: 0 0 auto; }
.price-item { flex: 1; text-align: right; }

.price-val { display: flex; flex-direction: column; line-height: 1.1; }
.price-total { color: #10b981; font-weight: 700; }
.free-text { color: var(--primary); font-weight: 800; font-size: 1.05rem; }
.unit { font-size: 0.65rem; color: var(--text-muted); }

.history-details { display: flex; flex-direction: column; gap: 0.4rem; margin-top: 0.5rem; }
.info-row { display: flex; align-items: center; gap: 0.4rem; font-size: 0.85rem; color: var(--text-main); min-width: 0; }
.text-truncate { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.italic { font-style: italic; }
.text-muted { color: var(--text-muted); }
.icon-shrink { flex-shrink: 0; }

@media (max-width: 600px) {
  .history-grid {
    grid-template-columns: minmax(0, 1fr);
  }
}
</style>