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
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.badge-success {
  background: rgba(74, 222, 128, 0.15);
  color: var(--success);
  border: 1px solid rgba(74, 222, 128, 0.3);
}

.badge-warning {
  background: rgba(251, 191, 36, 0.15);
  color: var(--warning);
  border: 1px solid rgba(251, 191, 36, 0.3);
}

.badge-danger {
  background: rgba(255, 77, 77, 0.15);
  color: var(--danger);
  border: 1px solid rgba(255, 77, 77, 0.3);
}

.badge-primary {
  background: rgba(193, 255, 114, 0.15);
  color: var(--primary);
  border: 1px solid rgba(193, 255, 114, 0.3);
}

.badge-neutral {
  background: var(--bg-elevated);
  color: var(--text-secondary);
  border: 1px solid var(--border-subtle);
}
</style>
