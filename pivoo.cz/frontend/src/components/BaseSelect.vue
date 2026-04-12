<template>
  <div class="input-group">
    <label v-if="label">{{ label }}</label>
    <div class="select-wrapper">
      <select 
        :value="modelValue" 
        @change="$emit('update:modelValue', $event.target.value)"
        v-bind="$attrs"
        class="base-select"
      >
        <slot></slot>
      </select>
    </div>
  </div>
</template>

<script setup>
defineProps({
  label: String,
  modelValue: [String, Number]
})

defineEmits(['update:modelValue'])
</script>

<style scoped>
.input-group { 
  display: flex; 
  flex-direction: column; 
  gap: 0.4rem; 
  width: 100%; 
}

label { 
  font-weight: 600; 
  color: #334155; 
  font-size: 0.9rem; 
}

.select-wrapper {
  position: relative;
  width: 100%;
}

/* Samotný vzhled se i nadále tahá z globálního styles.css přes třídu .base-select,
   takže tu nemusíme duplikovat barvy a stíny. */
</style>