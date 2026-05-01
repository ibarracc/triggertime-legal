<template>
  <span :class="['badge', variantClass]">
    <slot></slot>
  </span>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  variant: {
    type: String,
    default: 'neutral' // 'success', 'warning', 'danger', 'neutral', 'primary'
  }
})

const variantClass = computed(() => `badge-${props.variant}`)
</script>

<style scoped>
.badge {
  display: inline-flex;
  align-items: center;
  padding: 4px 10px;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.badge-success {
  background: oklch(0.60 0.15 145 / 0.15);
  color: var(--success);
  border: 1px solid oklch(0.60 0.15 145 / 0.3);
}

.badge-warning {
  background: oklch(0.80 0.15 85 / 0.15);
  color: var(--warning);
  border: 1px solid oklch(0.80 0.15 85 / 0.3);
}

.badge-danger {
  background: oklch(0.55 0.22 27 / 0.15);
  color: var(--danger);
  border: 1px solid oklch(0.55 0.22 27 / 0.3);
}

.badge-primary {
  background: var(--primary-a15);
  color: var(--primary);
  border: 1px solid var(--primary-a30);
}

.badge-neutral {
  background: var(--bg-elevated);
  color: var(--text-secondary);
  border: 1px solid var(--border-subtle);
}
</style>
