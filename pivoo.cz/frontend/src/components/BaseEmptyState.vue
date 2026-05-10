<template>
  <div class="empty-state">
    <component 
      :is="icon" 
      v-if="icon" 
      :size="iconSize" 
      color="#cbd5e1" 
      stroke-width="1" 
    />
    <p class="empty-text">{{ text }}</p>
    <slot></slot>
  </div>
</template>

<script setup>
defineProps({
  text: {
    type: String,
    required: true
  },
  icon: {
    type: [Object, Function],
    required: false
  },
  iconSize: {
    type: Number,
    default: 48
  }
})
</script>

<style scoped>
.empty-state { 
  text-align: center; 
  color: var(--text-muted); 
  padding: 2rem 0; 
  display: flex; 
  flex-direction: column; 
  align-items: center; 
  justify-content: center;
  gap: 0.5rem; 
  transition: color 0.3s ease; 
  flex-grow: 1;
}
.empty-text {
  margin: 0;
  font-size: 0.95rem;
}
</style>