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
          <div class="packaging-tag">{{ record.packaging }}</div>
        </div>
        
        <div class="consumption-meta">
          <div class="meta-item">
            <span class="label">Objem:</span>
            <strong class="text-truncate">{{ record.volume }} l</strong>
          </div>

          <div class="meta-item quantity-item">
            <span class="label">Počet:</span>
            <strong>{{ record.quantity }}x</strong>
          </div>
          
          <div class="meta-item price-item" v-if="Number(record.is_free) === 1 || record.price">
            <span class="label">Cena:</span>
            <div class="price-val">
              <template v-if="Number(record.is_free) === 1">
                <span class="free-text">Zdarma</span>
                <small class="unit">Neplatil jsem</small>
              </template>
              <template v-else>
                <template v-if="record.currency && record.currency !== 'CZK'">
                  <span class="price-total">{{ record.original_price * record.quantity }} {{ record.currency }}</span>
                  <small class="unit">({{ Math.round(record.price * record.quantity) }} Kč celkem)</small>
                </template>
                <template v-else>
                  <span class="price-total">{{ record.price * record.quantity }} Kč</span>
                  <small class="unit">({{ record.price }} Kč/ks)</small>
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
              <span class="text-truncate">{{ record.location_name }}</span>
            </div>
            <div class="info-row date">
              <CircleHelpIcon :size="12" class="icon-shrink" /> 
              <span class="text-truncate">{{ formatDate(record.consumed_at) }}</span>
            </div>
          </div>

          <div class="inline-actions">
            <BaseTooltip text="Upravit" position="top">
              <button class="btn-edit is-icon-only sm" @click="$emit('edit', record)">
                <PencilIcon :size="14" />
              </button>
            </BaseTooltip>
            <BaseTooltip text="Smazat" position="top">
              <button class="btn-danger is-icon-only sm" @click="$emit('delete', record.id)">
                <Trash2Icon :size="14" />
              </button>
            </BaseTooltip>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { PencilIcon, Trash2Icon, MapPinIcon, CircleHelpIcon, FactoryIcon } from 'lucide-vue-next'
import BaseTooltip from './BaseTooltip.vue'

defineProps({ history: { type: Array, required: true } })
defineEmits(['edit', 'delete'])

const formatDate = (dateStr) => {
  if (!dateStr) return ''
  const date = new Date(dateStr)
  return date.toLocaleString('cs-CZ', { 
    day: '2-digit', 
    month: '2-digit', 
    year: 'numeric'
  })
}
</script>

<style scoped>
.history-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(min(100%, 320px), 1fr));
  gap: 1.25rem;
  width: 100%;
}

.history-card {
  background: var(--bg-app);
  border: 1px solid var(--border);
  border-radius: 12px;
  display: flex;
  flex-direction: column;
  transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
  min-width: 0;
  width: 100%;
}

.history-card:hover {
  transform: translateY(-3px);
  box-shadow: var(--shadow);
  border-color: var(--primary);
}

.card-content {
  padding: 1.25rem;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  min-width: 0;
  width: 100%;
}

.beer-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 0.5rem;
  min-height: 42px;
  min-width: 0;
  width: 100%;
}

.beer-title {
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
  flex: 1 1 0px;
  min-width: 0;
  overflow: hidden;
}

.brewery-info {
  display: flex;
  align-items: center;
  gap: 0.3rem;
  font-size: 0.8rem;
  color: var(--text-muted);
  min-width: 0;
  max-width: 100%;
}

.packaging-tag {
  background: var(--bg-panel);
  border: 1px solid var(--border);
  padding: 2px 8px;
  border-radius: 4px;
  font-size: 0.65rem;
  font-weight: 800;
  text-transform: uppercase;
  color: var(--text-muted);
  white-space: nowrap;
  flex-shrink: 0;
}

.consumption-meta {
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) minmax(0, 1fr);
  align-items: baseline;
  gap: 0.5rem;
  width: 100%;
}

.meta-item {
  display: flex;
  flex-direction: column;
  gap: 0.1rem;
  min-width: 0;
}

.meta-item .label {
  font-size: 0.7rem;
  text-transform: uppercase;
  color: var(--text-muted);
  font-weight: 700;
}

.quantity-item {
  text-align: center;
}

.price-item {
  text-align: right;
}

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
  max-width: 100%;
}

.info-row.date {
  color: var(--text-muted);
  font-size: 0.75rem;
}

.inline-actions {
  display: flex;
  gap: 0.5rem;
  flex-shrink: 0;
}

.is-icon-only.sm {
  width: 32px;
  height: 32px;
  padding: 0;
  border-radius: 8px;
}

.text-truncate {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  display: block;
  max-width: 100%;
  min-width: 0;
}

strong.text-truncate {
  font-size: 1.05rem;
  color: var(--text-main);
  line-height: 1.2;
}

.icon-shrink {
  flex-shrink: 0;
}

@media (max-width: 600px) {
  .history-grid {
    grid-template-columns: minmax(0, 1fr);
  }
}
</style>