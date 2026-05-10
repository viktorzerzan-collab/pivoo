<template>
  <div class="entity-icon" :class="[bgClass, { 'has-image': imageUrl }]">
    <img v-if="imageUrl" :src="imageUrl" :alt="altText" />
    <component v-else :is="icon" :size="size" :color="iconColor" :stroke-width="2" />
  </div>
</template>

<script setup>
defineProps({
  imageUrl: String,
  altText: String,
  icon: [Object, Function],
  bgClass: String,
  size: { type: Number, default: 20 },
  iconColor: { type: String, default: 'var(--primary)' }
})
</script>

<style scoped>
.entity-icon { 
  width: 42px; height: 42px; border-radius: var(--radius-sm); display: flex; 
  align-items: center; justify-content: center; flex-shrink: 0;
  overflow: hidden;
  transition: all 0.3s ease;
  background: #1e293b; /* Tmavé pozadí pro ikony */
}
.entity-icon img { width: 100%; height: 100%; object-fit: cover; }
.entity-icon.has-image { padding: 0; background: transparent; border: 1px solid var(--border); }
</style>