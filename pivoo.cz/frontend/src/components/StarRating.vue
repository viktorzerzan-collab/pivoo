<template>
  <div class="star-rating">
    <span 
      v-for="star in 5" 
      :key="star" 
      @click="$emit('update:modelValue', star)" 
      class="star" 
      :class="{ 'is-active': star <= modelValue }"
    >★</span>
  </div>
</template>

<script setup>
defineProps({
  modelValue: { type: Number, default: 0 }
})
defineEmits(['update:modelValue'])
</script>

<style scoped>
.star-rating { display: flex; gap: 0.5rem; font-size: 2.5rem; cursor: pointer; color: #d1d5db; user-select: none; }
.star { transition: color 0.2s, transform 0.1s; }
.star:hover { transform: scale(1.1); }
.star.is-active { color: #eab308; }
@media (max-width: 600px) {
  .star-rating { font-size: 2.2rem; justify-content: space-between; }
}
</style>