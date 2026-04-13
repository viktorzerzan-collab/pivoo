<template>
  <div class="star-rating">
    <span 
      v-for="star in 5" 
      :key="star" 
      @click="toggleRating(star)" 
      class="star" 
      :class="{ 'is-active': star <= modelValue }"
    >★</span>
  </div>
</template>

<script setup>
const props = defineProps({
  modelValue: { type: Number, default: 0 }
})
const emit = defineEmits(['update:modelValue'])

const toggleRating = (star) => {
  // Pokud uživatel klikne na stejnou hvězdičku, vynulujeme hodnocení (zrušení výběru)
  if (props.modelValue === star) {
    emit('update:modelValue', 0)
  } else {
    emit('update:modelValue', star)
  }
}
</script>

<style scoped>
.star-rating { 
  display: flex; 
  gap: 0.25rem; 
  font-size: 2.2rem; 
  cursor: pointer; 
  color: #e2e8f0; 
  user-select: none; 
}
.star { 
  transition: color 0.2s, transform 0.1s; 
  text-shadow: 0 1px 2px rgba(0,0,0,0.05); 
}
.star:hover { 
  transform: scale(1.15); 
}
.star.is-active { 
  color: #facc15; /* Krásná zlatá/žlutá barva z Tailwindu */
}

@media (max-width: 600px) {
  .star-rating { 
    font-size: 2.5rem; 
    justify-content: space-between; 
    width: 100%; 
  }
}
</style>