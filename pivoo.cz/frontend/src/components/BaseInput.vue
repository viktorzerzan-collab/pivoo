<template>
  <div class="input-group">
    <label v-if="label">{{ label }}</label>
    <div class="input-wrapper">
      <input 
        :type="inputType" 
        :value="modelValue" 
        @input="$emit('update:modelValue', $event.target.value)"
        v-bind="$attrs"
        class="base-input"
      >
      <button 
        v-if="type === 'password'" 
        type="button" 
        class="eye-btn" 
        @click="togglePassword"
      >
        <EyeIcon v-if="inputType === 'password'" :size="20" />
        <EyeOffIcon v-else :size="20" />
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { EyeIcon, EyeOffIcon } from 'lucide-vue-next'

const props = defineProps({
  label: String,
  modelValue: [String, Number],
  type: { type: String, default: 'text' }
})

const inputType = ref(props.type)

const togglePassword = () => {
  inputType.value = inputType.value === 'password' ? 'text' : 'password'
}
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

.input-wrapper { 
  position: relative; 
  display: flex; 
  align-items: center; 
}

.base-input { 
  width: 100%; 
  padding: 0.75rem 1rem; 
  border-radius: 8px; 
  border: 1px solid var(--border, #cbd5e1); /* Zde byla chyba s chybějící proměnnou */
  background-color: var(--white, #ffffff);
  color: var(--text-main, #0f172a);
  font-size: 1rem; 
  outline: none; 
  transition: all 0.2s ease; 
  box-sizing: border-box;
  box-shadow: var(--shadow-sm, 0 1px 2px 0 rgba(0,0,0,0.05));
}

.base-input::placeholder {
  color: #94a3b8;
}

.base-input:focus { 
  border-color: var(--primary, #eab308); 
  box-shadow: 0 0 0 3px rgba(234, 179, 8, 0.15); 
}

.eye-btn {
  position: absolute; 
  right: 12px; 
  background: none; 
  border: none; 
  color: #94a3b8; 
  cursor: pointer; 
  display: flex; 
  align-items: center;
  padding: 0;
}

.eye-btn:hover { 
  color: var(--text-main, #0f172a); 
}
</style>