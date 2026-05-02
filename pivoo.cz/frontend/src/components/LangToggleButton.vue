<template>
  <BaseTooltip :text="tooltipText" position="bottom">
    <button 
      class="lang-toggle-btn" 
      @click="toggleLanguage" 
    >
      {{ currentLocale.toUpperCase() }}
    </button>
  </BaseTooltip>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import BaseTooltip from './BaseTooltip.vue'

const { locale } = useI18n()
const currentLocale = ref(locale.value)

const langs = ['cs', 'en', 'de', 'pl']

const tooltipText = computed(() => {
  const tooltips = {
    'cs': 'Přepnout jazyk (EN)',
    'en': 'Switch language (DE)',
    'de': 'Sprache wechseln (PL)',
    'pl': 'Zmień język (CS)'
  }
  return tooltips[currentLocale.value] || 'Change language'
})

const toggleLanguage = () => {
  const currentIndex = langs.indexOf(currentLocale.value)
  const nextLang = langs[(currentIndex + 1) % langs.length]
  locale.value = nextLang
  currentLocale.value = nextLang
  localStorage.setItem('pivoo_lang', nextLang)
}
</script>

<style scoped>
.lang-toggle-btn {
  background: rgba(255, 255, 255, 0.1);
  border: none;
  cursor: pointer;
  width: 38px;
  height: 38px;
  padding: 0;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
  color: var(--primary);
  font-weight: 700;
  font-size: 0.85rem;
}

.lang-toggle-btn:hover {
  background: rgba(255, 255, 255, 0.2);
}
</style>