<template>
  <div class="input-group">
    <label v-if="label" :for="id" class="label">{{ label }}</label>
    <div class="input-wrapper">
      <input
        :id="id"
        :type="inputType"
        :value="modelValue"
        @input="$emit('update:modelValue', $event.target.value)"
        :placeholder="placeholder"
        :required="required"
        :disabled="disabled"
        :autocomplete="autocomplete"
        :name="name"
        class="input"
        :class="{ 'has-error': error, 'has-icon': $slots.icon, 'has-trailing-icon': isPassword }"
      />
      <div v-if="$slots.icon" class="icon-slot">
        <slot name="icon"></slot>
      </div>
      
      <button 
        v-if="isPassword" 
        type="button"
        class="trailing-icon-btn" 
        @click="togglePasswordVisibility"
        aria-label="Toggle password visibility"
      >
        <svg v-if="!showPassword" class="password-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/><circle cx="12" cy="12" r="3"/></svg>
        <svg v-else class="password-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.733 5.076a10.744 10.744 0 0 1 11.205 6.575 1 1 0 0 1 0 .696 10.747 10.747 0 0 1-1.444 2.49"/><path d="M14.084 14.158a3 3 0 0 1-4.242-4.242"/><path d="M17.479 17.499a10.75 10.75 0 0 1-15.417-5.151 1 1 0 0 1 0-.696 10.75 10.75 0 0 1 4.446-5.143"/><path d="m2 2 20 20"/></svg>
      </button>
    </div>
    <span v-if="error" class="error-text">{{ error }}</span>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
  modelValue: [String, Number],
  label: String,
  type: { type: String, default: 'text' },
  placeholder: String,
  id: { type: String, default: () => `input-${Math.random().toString(36).substr(2, 9)}` },
  required: Boolean,
  disabled: Boolean,
  error: String,
  autocomplete: String,
  name: String
})

defineEmits(['update:modelValue'])

const showPassword = ref(false)
const isPassword = computed(() => props.type === 'password')
const inputType = computed(() => isPassword.value && showPassword.value ? 'text' : props.type)

const togglePasswordVisibility = () => {
  showPassword.value = !showPassword.value
}
</script>

<style scoped>
.input-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin-bottom: 16px;
}

.label {
  font-size: 0.875rem;
  font-weight: 500;
  color: var(--text-secondary);
}

.input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.input {
  width: 100%;
  padding: 14px 16px;
  background: var(--bg-elevated);
  border: 1px solid var(--border-subtle);
  border-radius: 12px;
  color: var(--text-primary);
  font-family: var(--font-body);
  font-size: 1rem;
  transition: all 0.2s ease;
  outline: none;
}

.input.has-icon {
  padding-left: 44px;
}

/* Adjust padding if there's a trailing icon */
.input.has-trailing-icon {
  padding-right: 44px;
}

.input:focus {
  border-color: var(--primary);
  box-shadow: 0 0 0 2px rgba(193, 255, 114, 0.1);
}

.input:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.input.has-error {
  border-color: var(--danger);
}

.icon-slot {
  position: absolute;
  left: 14px;
  color: var(--text-secondary);
  display: flex;
}

.trailing-icon-btn {
  position: absolute;
  right: 14px;
  background: none;
  border: none;
  padding: 0;
  color: var(--text-secondary);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: color 0.2s ease;
}

.trailing-icon-btn:hover {
  color: var(--primary);
}

.password-icon {
  width: 20px;
  height: 20px;
}

.error-text {
  font-size: 0.75rem;
  color: var(--danger);
  margin-top: -4px;
}
</style>
