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
  border-radius: 8px;
  font-family: var(--font-body);
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s ease, border-color 0.2s ease, color 0.2s ease;
  border: 1px solid transparent;
}

/* Sizes */
.btn-sm { padding: 8px 16px; font-size: 0.875rem; }
.btn-md { padding: 12px 24px; font-size: 1rem; }
.btn-lg { padding: 16px 32px; font-size: 1.125rem; }

/* Variants */
.btn-primary {
  background: var(--primary);
  color: oklch(0.14 0.01 145);
}
.btn-primary:hover:not(:disabled) {
  background: var(--primary-hover);
}

.btn-secondary {
  background: var(--bg-elevated);
  color: var(--text-primary);
  border-color: var(--border-subtle);
}
.btn-secondary:hover:not(:disabled) {
  border-color: oklch(0.40 0.01 145 / 0.6);
  background: var(--bg-elevated);
}

.btn-danger {
  background: oklch(0.55 0.22 27 / 0.1);
  color: var(--danger);
  border-color: oklch(0.55 0.22 27 / 0.2);
}
.btn-danger:hover:not(:disabled) {
  background: var(--danger);
  color: oklch(0.95 0.005 145);
}

.btn-ghost {
  background: transparent;
  color: var(--text-secondary);
}
.btn-ghost:hover:not(:disabled) {
  color: var(--text-primary);
  background: var(--bg-elevated);
}

/* Focus */
.btn:focus-visible {
  outline: 2px solid var(--primary);
  outline-offset: 2px;
}

/* States */
.disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Loader */
.spinner {
  width: 20px;
  height: 20px;
  border: 3px solid oklch(0.93 0.005 145 / 0.3);
  border-radius: 50%;
  border-top-color: currentColor;
  animation: spin 1s ease-in-out infinite;
}
@keyframes spin {
  to { transform: rotate(360deg); }
}
.btn-primary .spinner {
  border-color: oklch(0.14 0.01 145 / 0.2);
  border-top-color: oklch(0.14 0.01 145);
}
</style>
