<template>
  <div class="history-grid" v-if="history && history.length > 0">
    <div v-for="record in history" :key="record.id" class="history-card">
      <div class="card-content">
        <div class="beer-header">
          <div class="beer-title">
            <strong class="text-truncate">{{ record.beer_name }}</strong>
            <span class="brewery-info">
              <FactoryIcon :size="12" class="icon-shrink" /> 
              <span class="text-truncate">{{ record.brewery_name }}</span>
            </span>
          </div>
          <div class="packaging-tag">{{ translatePackaging(record.packaging) }}</div>
        </div>
        
        <div class="consumption-meta">
          <div class="meta-item">
            <span class="label">{{ $t('history.volume') }}</span>
            <strong class="text-truncate">{{ record.volume }} l</strong>
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
                  <span class="price-total">{{ (record.original_price || record.price) * record.quantity }} {{ record.currency || 'CZK' }}</span>
                  <small class="unit" v-if="isLoadingRate">(...)</small>
                  <small class="unit" v-else>({{ Math.round(record.price * record.quantity * exchangeRate) }} {{ userCurrency }} {{ $t('history.total') }})</small>
                </template>
                <template v-else>
                  <span class="price-total">{{ (record.original_price || record.price) * record.quantity }} {{ userCurrency }}</span>
                  <small class="unit">({{ record.original_price || record.price }} {{ userCurrency }}/{{ $t('history.per_piece') }})</small>
                </template>
              </template>
            </div>
          </div>
          <div v-else class="meta-item"></div>
        </div>

        <hr class="card-divider" />

        <div class="footer-layout">
          <div class="card-footer-info">
            <div class="info-row">
              <MapPinIcon :size="12" class="icon-shrink" /> 
              <span class="text-truncate">{{ translateLocation(record.location_name) }}</span>
            </div>
            <div class="info-row date">
              <CalendarDaysIcon :size="12" class="icon-shrink" /> 
              <span class="text-truncate">{{ formatDate(record.consumed_at) }}</span>
            </div>
            <div class="info-row note-row" v-if="record.note">
              <QuoteIcon :size="12" class="icon-shrink" />
              <span class="text-truncate italic">{{ record.note }}</span>
            </div>
          </div>

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
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch, computed } from 'vue'
import { 
  PencilIcon, Trash2Icon, MapPinIcon, 
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
  // OPRAVENO: Hodnoty 2.4. nahrazeny validními řetězci '2-digit'
  return date.toLocaleString('cs-CZ', { 
    day: '2-digit', 
    month: '2-digit', 
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}
</script>

<style scoped>
.history-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(min(100%, 350px), 1fr));
  gap: 1.25rem;
  width: 100%;
}

.history-card {
  background: var(--bg-panel);
  border: 1px solid var(--border);
  border-radius: var(--radius-md);
  display: flex;
  flex-direction: column;
  transition: all 0.3s ease;
  min-width: 0;
  width: 100%;
}

.history-card:hover {
  border-color: var(--primary);
  transform: translateY(-2px);
  box-shadow: var(--shadow-sm);
}

.card-content {
  padding: 1.25rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
  min-width: 0;
  width: 100%;
}

.beer-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 1rem;
  min-width: 0;
}

.beer-title {
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.beer-title strong {
  font-size: 1.15rem;
  color: var(--text-main);
  line-height: 1.2;
}

.brewery-info {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  color: var(--text-muted);
  font-size: 0.85rem;
  margin-top: 0.2rem;
}

.packaging-tag {
  background: var(--bg-app);
  border: 1px solid var(--border);
  padding: 0.25rem 0.6rem;
  border-radius: 99px;
  font-size: 0.75rem;
  font-weight: 700;
  color: var(--text-muted);
  text-transform: uppercase;
  white-space: nowrap;
}

.consumption-meta {
  display: flex;
  gap: 1.5rem;
  background: var(--bg-app);
  padding: 0.85rem;
  border-radius: var(--radius-sm);
  border: 1px solid var(--border);
}

.meta-item {
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
  min-width: 0;
}

.label {
  font-size: 0.7rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: var(--text-muted);
  font-weight: 700;
}

.quantity-item { flex: 0 0 auto; }
.price-item { flex: 1; text-align: right; }

.price-val {
  display: flex;
  flex-direction: column;
  line-height: 1.1;
}

.price-total {
  color: #10b981;
  font-weight: 700;
}

.free-text {
  color: var(--primary);
  font-weight: 800;
  font-size: 1.05rem;
}

.unit {
  font-size: 0.65rem;
  color: var(--text-muted);
}

.card-divider {
  border: 0;
  border-top: 1px solid var(--border);
  margin: 0.25rem 0;
}

.footer-layout {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  gap: 1rem;
  min-width: 0;
  width: 100%;
}

.card-footer-info {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
  flex: 1 1 0px;
  min-width: 0;
  overflow: hidden;
}

.info-row {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  font-size: 0.85rem;
  color: var(--text-main);
  min-width: 0;
}

.text-truncate {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.italic { font-style: italic; }
.icon-shrink { flex-shrink: 0; }

@media (max-width: 600px) {
  .history-grid {
    grid-template-columns: minmax(0, 1fr);
  }
}
</style>