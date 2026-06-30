<template>
  <div class="profile-view">
    <div class="view-header">
      <h1 class="section-title">{{ $t('nav.profile') }}</h1>
    </div>

    <div class="profile-tabs-wrapper">
      <BaseSwitch 
        v-model="activeTab"
        :options="[
          { value: 'settings', label: $t('views.profile.tab_settings'), icon: SettingsIcon },
          { value: 'history', label: $t('views.profile.tab_history'), icon: HistoryIcon }
        ]"
      />
    </div>

    <div class="profile-content">
      <ProfileSettingsTab v-show="activeTab === 'settings'" />
      <ProfileHistoryTab v-show="activeTab === 'history'" />
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { storeToRefs } from 'pinia'
import { SettingsIcon, HistoryIcon } from 'lucide-vue-next'

import BaseSwitch from '../components/BaseSwitch.vue'
import ProfileSettingsTab from '../components/profile/ProfileSettingsTab.vue'
import ProfileHistoryTab from '../components/profile/ProfileHistoryTab.vue'

import { useCatalogStore } from '../stores/catalog'

const route = useRoute()
const catalogStore = useCatalogStore()
const { allBeers } = storeToRefs(catalogStore)

const activeTab = ref('settings')

onMounted(() => {
  // Inicializace dat, pokud uživatel přijde na stránku napřímo
  if (allBeers.value.length === 0) {
    catalogStore.fetchAllData()
  }

  // Přepnutí na historii, pokud je to specifikováno v URL (např. z Dashboardu)
  if (route.query.tab === 'history') {
    activeTab.value = 'history'
  }
})
</script>

<style scoped>
.profile-view { 
  flex: 1; 
  display: flex; 
  flex-direction: column; 
}

.view-header { 
  margin-bottom: 1.5rem; 
}

.profile-tabs-wrapper { 
  margin-bottom: 2rem; 
  display: flex; 
  justify-content: flex-start; 
}

.profile-content { 
  display: flex; 
  flex-direction: column; 
}
</style>
