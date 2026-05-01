<template>
  <div class="glass-card" :class="{ 'card-hover': hoverable }">
    <div v-if="$slots.header" class="card-header">
      <slot name="header"></slot>
    </div>
    <div class="card-body" :class="{ 'no-padding': noPadding }">
      <slot></slot>
    </div>
    <div v-if="$slots.footer" class="card-footer">
      <slot name="footer"></slot>
    </div>
  </div>
</template>

<script setup>
defineProps({
  hoverable: {
    type: Boolean,
    default: false
  },
  noPadding: {
    type: Boolean,
    default: false
  }
})
</script>

<style scoped>
.glass-card {
  background: var(--bg-surface);
  border: 1px solid var(--border-subtle);
  border-radius: 12px;
  box-shadow: var(--shadow-card);
  transition: border-color 0.2s ease, box-shadow 0.2s ease;
  display: flex;
  flex-direction: column;
}

.card-hover:hover {
  border-color: oklch(0.40 0.01 145 / 0.6);
  box-shadow: var(--shadow-elevated);
}

.card-header {
  padding: 24px 32px;
  border-bottom: 1px solid var(--border-subtle);
  font-family: var(--font-heading);
  font-weight: 600;
  font-size: 1.125rem;
}

.card-body {
  padding: 32px;
  flex: 1;
}

.card-body.no-padding {
  padding: 0;
}

.card-footer {
  padding: 20px 32px;
  border-top: 1px solid var(--border-subtle);
  background: oklch(0 0 0 / 0.2);
  border-bottom-left-radius: 12px;
  border-bottom-right-radius: 12px;
}
</style>
