<template>
  <BaseModal :show="show" @close="$emit('close')" customStyle="max-width: 650px;">
    <template #header>
      <h2 class="modal-title">
        <BeerIcon class="title-icon" :size="26" /> 
        {{ isEditing ? 'Upravit pivo' : 'Nové pivo' }}
      </h2>
    </template>
    <template #body>
      <form @submit.prevent="$emit('submit')" class="add-form">
        
        <div v-if="form.is_magic" class="magic-banner">
          <SparklesIcon :size="20" class="magic-icon" />
          <span>Údaje byly předvyplněny umělou inteligencí. Prosím, zkontrolujte je před uložením.</span>
        </div>

        <div class="form-section">
          <h3 class="section-label">Základní informace</h3>
          <BaseInput v-model="form.name" label="Název piva *" required />
          
          <div class="form-row">
            <!-- ZMĚNA: Napojení na catalogStore.allBreweries a přidání searchable -->
            <BaseSelect v-model="form.brewery_id" label="Pivovar *" required class="half" searchable>
              <option disabled value="">-- Vyber pivovar --</option>
              <option v-for="brewery in catalogStore.allBreweries" :key="brewery.id" :value="brewery.id">{{ brewery.name }}</option>
            </BaseSelect>

            <!-- ZMĚNA: Napojení na catalogStore.styles -->
            <BaseSelect v-model="form.style_id" label="Styl *" required class="half" searchable>
              <option disabled value="">-- Vyber styl --</option>
              <option v-for="style in catalogStore.styles" :key="style.id" :value="style.id">{{ style.name }}</option>
            </BaseSelect>
          </div>
        </div>

        <div class="form-section">
          <h3 class="section-label">Technické parametry</h3>
          <div class="form-row quad">
            <BaseInput v-model="form.epm" type="number" step="0.1" label="EPM (°)" placeholder="např. 12" />
            <BaseInput v-model="form.abv" type="number" step="0.1" label="Alkohol (%)" placeholder="4.5" />
            <BaseInput v-model="form.ibu" type="number" step="1" label="Hořkost (IBU)" placeholder="35" />
            <BaseInput v-model="form.ebc" type="number" step="1" label="Barva (EBC)" placeholder="12" />
          </div>
          
          <BaseSelect v-model="form.fermentation" label="Způsob kvašení">
            <option value="">-- Neuvedeno --</option>
            <option value="spodní">Spodní (ležáky)</option>
            <option value="svrchní">Svrchní (ale, stout, pšenice)</option>
            <option value="spontánní">Spontánní (lambic, wild)</option>
            <option value="smíšené">Smíšené</option>
          </BaseSelect>
        </div>

        <div class="form-section">
          <h3 class="section-label">Složení a charakteristika</h3>
          <BaseInput v-model="form.hops" label="Použité chmely" placeholder="např. Citra, Mosaic, ŽPČ" />
          <BaseInput v-model="form.malts" label="Použité slady" placeholder="např. Plzeňský, Karamelový" />
          <BaseInput v-model="form.tags" label="Chuťový profil / Tagy" placeholder="např. Citrusy, Borovice, Čokoláda" />
        </div>

        <div class="form-section">
          <h3 class="section-label">Vlastnosti piva</h3>
          <div class="checkbox-row">
            <label class="custom-checkbox">
              <input type="checkbox" v-model="form.is_unfiltered" />
              <span class="checkbox-box"></span>
              Nefiltrované
            </label>
            <label class="custom-checkbox">
              <input type="checkbox" v-model="form.is_unpasteurized" />
              <span class="checkbox-box"></span>
              Nepasterizované
            </label>
          </div>
        </div>

        <BaseButton type="submit" variant="add" style="margin-top: 1.5rem; width: 100%;">
          {{ isEditing ? 'Uložit změny' : 'Uložit pivo do katalogu' }}
        </BaseButton>
      </form>
    </template>
  </BaseModal>
</template>

<script setup>
import { BeerIcon, SparklesIcon } from 'lucide-vue-next'
import BaseModal from '../BaseModal.vue'
import BaseInput from '../BaseInput.vue'
import BaseButton from '../BaseButton.vue'
import BaseSelect from '../BaseSelect.vue'

// PŘIDÁNO: Načtení katalogu přímo z modálu
import { useCatalogStore } from '../../stores/catalog'
const catalogStore = useCatalogStore()

defineProps({
  show: Boolean,
  isEditing: Boolean,
  form: Object
})
defineEmits(['close', 'submit'])
</script>

<style scoped>
.modal-title { display: flex; align-items: center; gap: 0.5rem; margin: 0; color: var(--text-main); font-size: 1.5rem; transition: color 0.5s ease; }
.title-icon { color: var(--blue); }
.add-form { display: flex; flex-direction: column; gap: 1.5rem; }
.form-section { display: flex; flex-direction: column; gap: 1rem; padding: 1rem; background-color: var(--bg-app); border-radius: 12px; border: 1px solid var(--border); transition: background-color 0.5s ease, border-color 0.5s ease; }
.section-label { font-size: 0.8rem; font-weight: 800; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.05em; margin-bottom: 0.25rem; }
.form-row { display: flex; gap: 1rem; }
.half { flex: 1; }
.quad > * { flex: 1; }
.checkbox-row { display: flex; gap: 2rem; padding: 0.25rem 0; }
.custom-checkbox { display: flex; align-items: center; gap: 0.75rem; cursor: pointer; font-weight: 600; color: var(--text-main); font-size: 0.95rem; user-select: none; }
.custom-checkbox input { display: none; }
.checkbox-box { width: 22px; height: 22px; border: 2px solid var(--border); border-radius: 6px; background: var(--bg-panel); position: relative; transition: all 0.2s ease; }
.custom-checkbox:hover .checkbox-box { border-color: var(--primary); }
.custom-checkbox input:checked + .checkbox-box { background-color: var(--primary); border-color: var(--primary); }
.custom-checkbox input:checked + .checkbox-box::after { content: ""; position: absolute; left: 6px; top: 2px; width: 6px; height: 11px; border: solid #1e293b; border-width: 0 2.5px 2.5px 0; transform: rotate(45deg); }

.magic-banner {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  background-color: rgba(139, 92, 246, 0.1);
  border: 1px solid rgba(139, 92, 246, 0.3);
  color: #8b5cf6;
  padding: 0.75rem 1rem;
  border-radius: 8px;
  font-size: 0.9rem;
  font-weight: 600;
  margin-bottom: 0.5rem;
}
.magic-icon { flex-shrink: 0; }

@media (max-width: 600px) {
  .form-row { flex-direction: column; }
  .quad { display: grid; grid-template-columns: 1fr 1fr; }
  .checkbox-row { flex-direction: column; gap: 1rem; }
}
</style>