<template>
  <transition name="toast-fade">
    <div v-if="toastStore.show" class="toast-notification" :class="toastStore.type">
      {{ toastStore.message }}
    </div>
  </transition>
</template>

<script setup>
import { useToastStore } from '../stores/toast'

const toastStore = useToastStore()
</script>