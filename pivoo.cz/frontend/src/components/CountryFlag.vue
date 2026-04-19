<template>
  <span class="country-flag-wrapper">
    <img 
      v-if="code" 
      :src="`https://flagcdn.com/w20/${code.toLowerCase()}.png`" 
      class="flag-icon" 
      :title="name" 
      :alt="name" 
    />
    <span v-else class="flag-emoji" :title="name">🌍</span>
  </span>
</template>

<script setup>
defineProps({
  code: String,
  name: String
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