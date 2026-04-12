<template>
  <div class="input-wrapper">
    <label v-if="label" class="input-label">{{ label }}</label>
    
    <div class="input-container">
      <input
        :type="computedType"
        :value="modelValue"
        @input="$emit('update:modelValue', $event.target.value)"
        :placeholder="placeholder"
        :required="required"
        :min="min"
        :step="step"
        class="base-input"
        :class="{ 'has-icon': type === 'password' }"
      />
      
      <button
        v-if="type === 'password'"
        type="button"
        class="toggle-password"
        @click="isPasswordVisible = !isPasswordVisible"
        title="Zobrazit/skrýt heslo"
      >
        {{ isPasswordVisible ? '🙈' : '👁️' }}
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
  modelValue: { type: [String, Number], default: '' },
  label: { type: String, default: '' },
  type: { type: String, default: 'text' },
  placeholder: { type: String, default: '' },
  required: { type: Boolean, default: false },
  min: { type: [String, Number], default: null },
  step: { type: [String, Number], default: null }
})

defineEmits(['update:modelValue'])

// Stav pro zapamatování, jestli je oko kliknuté
const isPasswordVisible = ref(false)

// Chytrá logika: Pokud to má být heslo a uživatel kliknul na oko, změníme input na běžný 'text'
const computedType = computed(() => {
  if (props.type === 'password') {
    return isPasswordVisible.value ? 'text' : 'password'
  }
  // Pro všechny ostatní typy (čísla, běžný text) to necháme, jak to je
  return props.type
})
</script>

<style scoped>
.input-wrapper { display: flex; flex-direction: column; width: 100%; }
.input-label { font-weight: 600; margin-bottom: 0.4rem; color: #4b5563; font-size: 0.95rem; }

.input-container { position: relative; display: flex; align-items: center; }

.base-input { width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 1rem; box-sizing: border-box; font-family: inherit; transition: border-color 0.2s, box-shadow 0.2s; outline: none; }
.base-input:focus { border-color: #eab308; box-shadow: 0 0 0 3px rgba(234, 179, 8, 0.2); }

/* Pokud tam je ikonka, uděláme vpravo uvnitř políčka mezeru, aby text nepřetékal přes oko */
.base-input.has-icon { padding-right: 3rem; }

.toggle-password { position: absolute; right: 0.5rem; background: none; border: none; font-size: 1.3rem; cursor: pointer; padding: 0.2rem; transition: transform 0.2s; user-select: none; }
.toggle-password:hover { transform: scale(1.15); }
</style>