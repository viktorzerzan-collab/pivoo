<template>
  <div class="history-grid" v-if="history && history.length > 0">
    <div v-for="record in history" :key="record.id" class="history-card">
      <div class="card-content">
        <div class="beer-header">
          <div class="beer-title">
            <strong>{{ record.quantity }}x {{ record.beer_name }}</strong>
            <span class="brewery-info">
              <FactoryIcon :size="12" /> {{ record.brewery_name }}
            </span>
          </div>
          <div class="packaging-tag">{{ record.packaging }}</div>
        </div>
        
        <div class="consumption-meta">
          <div class="meta-item">
            <span class="label">Objem:</span>
            <strong>{{ (record.volume * record.quantity).toFixed(1) }} l</strong>
          </div>
          <div class="meta-item price-item" v-if="record.price">
            <span class="label">Cena:</span>
            <div class="price-val">
              <span class="price-total">{{ record.price * record.quantity }} Kč</span>
              <small class="unit">({{ record.price }} Kč/ks)</small>
            </div>
          </div>
        </div>

        <hr class="card-divider" />

        <div class="footer-layout">
          <div class="card-footer-info">
            <div class="info-row">
              <MapPinIcon :size="12" /> <span>{{ record.location_name }}</span>
            </div>
            <div class="info-row date">
              <ClockIcon :size="12" /> <span>{{ formatDate(record.consumed_at) }}</span>
            </div>
          </div>

          <div class="inline-actions">
            <button class="btn-edit is-icon-only sm" @click="$emit('edit', record)" title="Upravit">
              <PencilIcon :size="14" />
            </button>
            <button class="btn-danger is-icon-only sm" @click="$emit('delete', record.id)" title="Smazat">
              <Trash2Icon :size="14" />
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { PencilIcon, Trash2Icon, MapPinIcon, ClockIcon, FactoryIcon } from 'lucide-vue-next'
defineProps({ history: { type: Array, required: true } })
defineEmits(['edit', 'delete'])

const formatDate = (dateStr) => {
  if (!dateStr) return ''
  const date = new Date(dateStr)
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
/* Mřížka pro karty */
.history-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 1.25rem;
}

/* Styl samotné karty */
.history-card {
  background: var(--bg-app);
  border: 1px solid var(--border);
  border-radius: 12px;
  display: flex;
  flex-direction: column;
  transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
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
}

/* Záhlaví karty */
.beer-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 0.5rem;
}

.beer-title {
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
}

.beer-title strong {
  font-size: 1.05rem;
  color: var(--text-main);
  line-height: 1.2;
}

.brewery-info {
  display: flex;
  align-items: center;
  gap: 0.3rem;
  font-size: 0.8rem;
  color: var(--text-muted);
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
}

/* Parametry konzumace */
.consumption-meta {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
}

.meta-item {
  display: flex;
  flex-direction: column;
  gap: 0.1rem;
}

.meta-item .label {
  font-size: 0.7rem;
  text-transform: uppercase;
  color: var(--text-muted);
  font-weight: 700;
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

.unit {
  font-size: 0.65rem;
  color: var(--text-muted);
}

.card-divider {
  border: 0;
  border-top: 1px solid var(--border);
  margin: 0.25rem 0;
}

/* Spodní layout s tlačítky inline */
.footer-layout {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  gap: 1rem;
}

.card-footer-info {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
  flex: 1;
}

.info-row {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  font-size: 0.85rem;
  color: var(--text-main);
}

.info-row.date {
  color: var(--text-muted);
  font-size: 0.75rem;
}

.inline-actions {
  display: flex;
  gap: 0.5rem;
}

/* Menší verze tlačítek pro inline zobrazení */
.is-icon-only.sm {
  width: 32px;
  height: 32px;
  padding: 0;
  border-radius: 8px;
}

@media (max-width: 600px) {
  .history-grid {
    grid-template-columns: 1fr;
  }
}
</style>