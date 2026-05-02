<template>
  <div class="base-input-group">
    <label v-if="label" class="base-label">{{ label }}</label>
    
    <div class="input-wrapper">
      <textarea
        v-if="type === 'textarea'"
        :value="modelValue"
        @input="$emit('update:modelValue', $event.target.value)"
        class="base-input base-textarea"
        v-bind="$attrs"
      ></textarea>

      <template v-else>
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
      </template>
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

const currentType = ref(props.type)

watch(() => props.type, (newType) => {
  currentType.value = newType
})

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
  border-radius: var(--radius-sm);
  background: var(--bg-panel);
  color: var(--text-main);
  font-size: 0.95rem;
  font-family: inherit;
  transition: all 0.3s ease;
  outline: none;
  box-shadow: none;
}

.base-textarea {
  min-height: 100px;
  resize: vertical;
  line-height: 1.5;
}

.base-input:-webkit-autofill,
.base-input:-webkit-autofill:hover, 
.base-input:-webkit-autofill:focus, 
.base-input:-webkit-autofill:active {
  -webkit-box-shadow: 0 0 0 30px var(--bg-panel) inset !important;
  -webkit-text-fill-color: var(--text-main) !important;
  transition: background-color 5000s ease-in-out 0s;
}

.base-input.has-icon {
  padding-right: 2.75rem;
}

.base-input:focus {
  border-color: var(--primary);
  box-shadow: 0 0 0 3px rgba(250, 204, 21, 0.15);
}

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

.base-textarea::-webkit-scrollbar {
  width: 8px;
}
.base-textarea::-webkit-scrollbar-thumb {
  background-color: var(--border);
  border-radius: var(--radius-sm);
}
</style>