<template>
  <div class="background-watermark" :class="{ 'is-logo': type === 'brewery' && logo, 'is-modal': isModal }">
    <template v-if="type === 'brewery' && logo">
      <img :src="'https://www.pivoo.cz/backend/uploads/logos/' + logo" alt="Logo background" class="watermark-logo-img" />
    </template>
    <template v-else>
      <component :is="displayIcon" :size="size" :color="displayColor" />
    </template>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { BeerIcon, FactoryIcon, MapPinIcon } from 'lucide-vue-next'

const props = defineProps({
  type: { 
    type: String, 
    default: null,
    validator: (val) => [null, 'beer', 'brewery', 'location'].includes(val)
  },
  icon: {
    type: [Object, Function],
    default: null
  },
  color: {
    type: String,
    default: null
  },
  logo: { 
    type: String, 
    default: null 
  },
  size: { 
    type: Number, 
    default: 140 
  },
  isModal: { 
    type: Boolean, 
    default: false 
  }
})

const displayIcon = computed(() => {
  if (props.icon) return props.icon
  if (props.type === 'beer') return BeerIcon
  if (props.type === 'brewery') return FactoryIcon
  if (props.type === 'location') return MapPinIcon
  return null
})

const displayColor = computed(() => {
  if (props.color) return props.color
  return 'var(--primary)'
})
</script>

<style scoped>
/* Základní styly vodoznaku (vyladěné pro lepší viditelnost ve světlém režimu) */
.background-watermark {
  position: absolute;
  right: -15px;
  top: 10px;
  opacity: 0.12;
  pointer-events: none;
  z-index: 0;
  transform: rotate(15deg);
  transition: all 0.4s ease;
  display: flex;
  align-items: center;
  justify-content: center;
}

.background-watermark.is-logo {
  right: -10px;
  top: -10px;
  width: 150px;
  height: 150px;
  transform: rotate(-10deg);
  opacity: 0.15;
}

.watermark-logo-img {
  width: 100%;
  height: 100%;
  object-fit: contain;
  filter: grayscale(1);
}

/* Modifikátory specifické pro modálové okno */
.background-watermark.is-modal {
  right: -20px;
  top: -20px;
}

.background-watermark.is-modal.is-logo {
  width: 220px;
  height: 220px;
}

/* Nastavení pro tmavý režim */
:global(.dark) .background-watermark,
:global([data-theme="dark"]) .background-watermark {
  opacity: 0.04;
}

:global(.dark) .background-watermark.is-logo,
:global([data-theme="dark"]) .background-watermark.is-logo {
  opacity: 0.06;
}
</style>