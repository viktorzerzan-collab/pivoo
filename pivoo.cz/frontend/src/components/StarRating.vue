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
  color: var(--border); 
  user-select: none; 
  transition: color 0.5s ease;
}
.star { 
  transition: color 0.2s, transform 0.1s; 
  text-shadow: 0 1px 2px rgba(0,0,0,0.05); 
}
.star:hover { 
  transform: scale(1.15); 
}
.star.is-active { 
  color: var(--primary); 
}

@media (max-width: 600px) {
  .star-rating { 
    font-size: 2.5rem; 
    justify-content: space-between; 
    width: 100%; 
  }
}
</style>