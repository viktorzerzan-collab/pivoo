<template>
  <BaseTooltip :text="translatedName" position="top">
    <span class="country-flag-wrapper">
      <img 
        v-if="code" 
        :src="`https://flagcdn.com/w20/${code.toLowerCase()}.png`" 
        class="flag-icon" 
        :alt="translatedName" 
      />
      <span v-else class="flag-emoji">🌍</span>
    </span>
  </BaseTooltip>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import BaseTooltip from './BaseTooltip.vue'

const props = defineProps({
  code: String,
  name: String
})

const { locale } = useI18n()

// Automaticky přeloží název země podle jejího kódu a jazyka prohlížeče
const translatedName = computed(() => {
  if (!props.code) return props.name || ''
  try {
    const displayNames = new Intl.DisplayNames([locale.value], { type: 'region' })
    return displayNames.of(props.code.toUpperCase())
  } catch (e) {
    return props.name
  }
})
</script>

<style scoped>
.country-flag-wrapper {
  display: inline-flex;
  align-items: center;
}

.flag-icon {
  width: 20px;
  height: auto;
  vertical-align: middle;
  border-radius: 2px;
  box-shadow: 0 0 1px rgba(0,0,0,0.3);
}

.flag-emoji {
  font-size: 1rem;
  vertical-align: middle;
}
</style>