<template>
  <div class="admin-page">
    <div class="admin-header">
      <BaseSwitch v-model="activeTab" :options="tabs" />
    </div>

    <div class="admin-layout">
      <BasePanel class="admin-section">
        <div class="section-header">
          <div class="header-info">
            <FilterInput 
              v-model="searchQuery" 
              :placeholder="$t('admin.search_placeholder', { section: getTabLabel(activeTab).toLowerCase() })" 
              class="admin-search"
            />
          </div>
          <BaseButton 
            v-if="activeTab !== 'users' && activeTab !== 'pending'" 
            variant="add" 
            @click="triggerAddModal" 
            class="mobile-full-width"
          >
            <template #icon><PlusCircleIcon :size="20" /></template>
            {{ $t('admin.add_item', { item: currentLabelSingle }) }}
          </BaseButton>
        </div>

        <AdminUsersTable 
          v-if="activeTab === 'users'" 
          :search-query="searchQuery" 
        />
        
        <AdminPendingTable 
          v-else-if="activeTab === 'pending'" 
          :search-query="searchQuery" 
        />
        
        <AdminBarcodesTable 
          v-else-if="activeTab === 'barcodes'" 
          ref="barcodesTableRef"
          :search-query="searchQuery" 
        />
        
        <AdminCatalogTable 
          v-else 
          ref="catalogTableRef"
          :active-tab="activeTab" 
          :search-query="searchQuery" 
        />

      </BasePanel>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { 
  PlusCircleIcon, UserIcon, ListChecksIcon, BeerIcon, 
  FactoryIcon, MapPinIcon, HopIcon, BarcodeIcon 
} from 'lucide-vue-next'

import BaseSwitch from '../components/BaseSwitch.vue'
import BasePanel from '../components/BasePanel.vue'
import FilterInput from '../components/FilterInput.vue'
import BaseButton from '../components/BaseButton.vue'

// Import nově vytvořených komponent
import AdminUsersTable from '../components/admin/AdminUsersTable.vue'
import AdminPendingTable from '../components/admin/AdminPendingTable.vue'
import AdminBarcodesTable from '../components/admin/AdminBarcodesTable.vue'
import AdminCatalogTable from '../components/admin/AdminCatalogTable.vue'

const { t } = useI18n()

const activeTab = ref('users')
const searchQuery = ref('')

// Odkazy na vnořené komponenty, abychom z hlavičky mohli zavolat jejich funkce
const catalogTableRef = ref(null)
const barcodesTableRef = ref(null)

// Reset vyhledávání při změně tabu
watch(activeTab, () => { 
  searchQuery.value = '' 
})

// Definice záložek
const tabs = computed(() => [
  { value: 'users', label: t('admin.tabs.users'), icon: UserIcon },
  { value: 'pending', label: t('admin.tabs.pending'), icon: ListChecksIcon },
  { value: 'beers', label: t('admin.tabs.beers'), icon: BeerIcon },
  { value: 'breweries', label: t('admin.tabs.breweries'), icon: FactoryIcon },
  { value: 'locations', label: t('admin.tabs.locations'), icon: MapPinIcon },
  { value: 'styles', label: t('admin.tabs.styles'), icon: HopIcon },
  { value: 'barcodes', label: t('admin.tabs.barcodes'), icon: BarcodeIcon }
])

const getTabLabel = (val) => tabs.value.find(t => t.value === val)?.label || ''

const currentLabelSingle = computed(() => {
  if (activeTab.value === 'pending') return ''
  const key = {
    users: 'user', beers: 'beer', breweries: 'brewery', 
    locations: 'location', styles: 'style', barcodes: 'barcode'
  }[activeTab.value]
  return t(`admin.items.${key}`)
})

// Funkce, která propíše kliknutí na tlačítko "Přidat" do příslušné podřízené tabulky
const triggerAddModal = () => {
  if (activeTab.value === 'barcodes') {
    // Zavolá openAddModal v AdminBarcodesTable (pokud jste ji tam přidali)
    if (barcodesTableRef.value && barcodesTableRef.value.openAddModal) {
      barcodesTableRef.value.openAddModal()
    }
  } else {
    // Zavolá openAddModal v AdminCatalogTable
    if (catalogTableRef.value && catalogTableRef.value.openAddModal) {
      catalogTableRef.value.openAddModal()
    }
  }
}
</script>

<style scoped>
.admin-page { flex: 1; display: flex; flex-direction: column; }
.admin-header { margin-bottom: 2rem; overflow-x: auto; }
.admin-layout { position: relative; flex: 1; min-height: 400px; display: flex; flex-direction: column; }
.admin-section { display: flex; flex-direction: column; flex: 1; }
.section-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 2.5rem; gap: 2rem; }
.header-info { flex: 1; }
.admin-search { max-width: 380px; }

@media (max-width: 768px) {
  .section-header { flex-direction: column; align-items: stretch; gap: 1rem; }
  .admin-search { max-width: none; }
  .mobile-full-width { order: -1; padding: 1rem; width: 100%; justify-content: center; }
}
</style>
