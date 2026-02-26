<template>
  <button :class="['btn', variantClass, sizeClass, { disabled: disabled || loading }]" :disabled="disabled || loading">
    <span v-if="loading" class="spinner"></span>
    <slot v-else></slot>
  </button>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  variant: {
    type: String,
    default: 'primary', // 'primary', 'secondary', 'danger', 'ghost'
  },
  size: {
    type: String,
    default: 'md', // 'sm', 'md', 'lg'
  },
  loading: {
    type: Boolean,
    default: false,
  },
  disabled: {
    type: Boolean,
    default: false,
  }
})

const variantClass = computed(() => `btn-${props.variant}`)
const sizeClass = computed(() => `btn-${props.size}`)
</script>

<style scoped>
.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 12px;
  font-family: var(--font-body);
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  border: 1px solid transparent;
}

/* Sizes */
.btn-sm { padding: 8px 16px; font-size: 0.875rem; }
.btn-md { padding: 12px 24px; font-size: 1rem; }
.btn-lg { padding: 16px 32px; font-size: 1.125rem; }

/* Variants */
.btn-primary {
  background: var(--primary);
  color: #0A0A0F;
  box-shadow: var(--shadow-glow);
}
.btn-primary:hover:not(:disabled) {
  background: var(--primary-hover);
  box-shadow: var(--shadow-glow-hover);
  transform: translateY(-2px);
}

.btn-secondary {
  background: var(--bg-elevated);
  color: var(--text-primary);
  border-color: var(--border-subtle);
}
.btn-secondary:hover:not(:disabled) {
  border-color: rgba(255, 255, 255, 0.3);
  background: rgba(255, 255, 255, 0.05);
}

.btn-danger {
  background: rgba(255, 77, 77, 0.1);
  color: var(--danger);
  border-color: rgba(255, 77, 77, 0.2);
}
.btn-danger:hover:not(:disabled) {
  background: var(--danger);
  color: #fff;
}

.btn-ghost {
  background: transparent;
  color: var(--text-secondary);
}
.btn-ghost:hover:not(:disabled) {
  color: var(--text-primary);
  background: var(--bg-elevated);
}

/* States */
.disabled {
  opacity: 0.5;
  cursor: not-allowed;
  transform: none !important;
  box-shadow: none !important;
}

/* Loader */
.spinner {
  width: 20px;
  height: 20px;
  border: 3px solid rgba(255,255,255,0.3);
  border-radius: 50%;
  border-top-color: currentColor;
  animation: spin 1s ease-in-out infinite;
}
@keyframes spin {
  to { transform: rotate(360deg); }
}
.btn-primary .spinner {
  border-color: rgba(0,0,0,0.1);
  border-top-color: #000;
}
</style>
