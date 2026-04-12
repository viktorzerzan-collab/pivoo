<template>
  <button :class="['base-button', `btn-${variant}`]" @click="$emit('click', $event)" :type="type">
    <slot></slot> </button>
</template>

<script setup>
defineProps({
  variant: { type: String, default: 'primary' }, // 'primary', 'secondary', 'danger', 'submit', 'logout'
  type: { type: String, default: 'button' }
})
defineEmits(['click'])
</script>

<style scoped>
.base-button { border: none; cursor: pointer; transition: 0.2s; font-family: inherit; display: inline-flex; align-items: center; justify-content: center; }
.btn-primary { background-color: #eab308; color: white; padding: 1rem 2.5rem; border-radius: 99px; font-weight: bold; font-size: 1.2rem; box-shadow: 0 4px 6px -1px rgba(234, 179, 8, 0.4); }
.btn-primary:hover { background-color: #ca8a04; transform: scale(1.03); }
.btn-secondary { background-color: white; color: #1f2937; padding: 1rem 2rem; border-radius: 99px; font-weight: bold; font-size: 1.1rem; border: 2px solid #d1d5db; }
.btn-secondary:hover { border-color: #9ca3af; transform: scale(1.03); background-color: #f9fafb; }
.btn-submit { background-color: #10b981; color: white; padding: 1rem; border-radius: 6px; font-weight: bold; font-size: 1.1rem; width: 100%; margin-top: 1rem; }
.btn-submit:hover { opacity: 0.9; }
.btn-logout { background-color: #ef4444; color: white; padding: 0.5rem 1rem; border-radius: 6px; font-weight: bold; }
.btn-logout:hover { background-color: #dc2626; }
.btn-danger { background-color: #ef4444; color: white; padding: 1rem; border-radius: 6px; font-weight: bold; font-size: 1.1rem; }
.btn-danger:hover { background-color: #dc2626; }
</style>