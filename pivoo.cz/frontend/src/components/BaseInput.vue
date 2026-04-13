<template>
  <div class="base-input-group">
    <label v-if="label" class="base-label">{{ label }}</label>
    
    <div class="input-wrapper">
      <input 
        :type="currentType" 
        :value="modelValue" 
        @input="$emit('update:modelValue', $event.target.value)"
        class="base-input"
        :class="{ 'has-icon': type === 'password' }"
        v-bind="$attrs"
      />
      
      <button 
        v-if="type === 'password'" 
        type="button" 
        class="toggle-password" 
        @click="toggleVisibility"
        aria-label="Zobrazit/Skrýt heslo"
        tabindex="-1"
      >
        <EyeIcon v-if="currentType === 'password'" :size="18" />
        <EyeOffIcon v-else :size="18" />
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import { EyeIcon, EyeOffIcon } from 'lucide-vue-next'

const props = defineProps({
  modelValue: [String, Number],
  label: String,
  type: { type: String, default: 'text' }
})

const emit = defineEmits(['update:modelValue'])

// Udržujeme si lokální stav pro typ inputu, abychom ho mohli dynamicky měnit
const currentType = ref(props.type)

// Pokud by se typ změnil zvenčí (např. přes props), zareagujeme
watch(() => props.type, (newType) => {
  currentType.value = newType
})

// Přepínání mezi 'password' a 'text'
const toggleVisibility = () => {
  currentType.value = currentType.value === 'password' ? 'text' : 'password'
}
</script>

<style scoped>
.base-input-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  width: 100%;
  text-align: left;
}

.base-label {
  font-size: 0.9rem;
  font-weight: 600;
  color: var(--text-main);
  transition: color 0.5s ease;
}

.input-wrapper {
  position: relative;
  width: 100%;
}

.base-input {
  width: 100%;
  padding: 0.75rem 1rem;
  border: 1px solid var(--border);
  border-radius: 10px;
  background: var(--bg-panel);
  color: var(--text-main);
  font-size: 0.95rem;
  font-family: inherit;
  transition: all 0.3s ease;
  outline: none;
}

/* --- OPRAVA AUTOFILLU V PROHLÍŽEČÍCH --- */
/* WebKit prohlížeče (Chrome, Safari, Edge) si vynucují vlastní barvu pro autofill. */
/* Překryjeme ji pomocí velkého vnitřního stínu a vynutíme naši barvu textu. */
.base-input:-webkit-autofill,
.base-input:-webkit-autofill:hover, 
.base-input:-webkit-autofill:focus, 
.base-input:-webkit-autofill:active {
  -webkit-box-shadow: 0 0 0 30px var(--bg-panel) inset !important;
  -webkit-text-fill-color: var(--text-main) !important;
  transition: background-color 5000s ease-in-out 0s; /* Pojistka proti probliknutí */
}

/* Odsazení textu, aby nepřetékal přes ikonku oka */
.base-input.has-icon {
  padding-right: 2.75rem;
}

.base-input:focus {
  border-color: var(--primary);
  box-shadow: 0 0 0 3px rgba(250, 204, 21, 0.2);
}

/* Styl pro naši ikonku (resetování globálních vlastností tlačítek z App.vue) */
.toggle-password {
  position: absolute;
  right: 0.5rem;
  top: 50%;
  transform: translateY(-50%);
  background: transparent !important;
  border: none !important;
  color: var(--text-muted) !important;
  cursor: pointer;
  padding: 0.25rem !important;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: none !important;
  transition: color 0.3s ease;
}

.toggle-password:hover {
  color: var(--text-main) !important;
}
</style>