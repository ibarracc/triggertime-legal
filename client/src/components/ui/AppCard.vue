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
  background: rgba(30, 30, 35, 0.6);
  border: 1px solid var(--border-subtle);
  border-radius: 16px;
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  transform: translateZ(0); /* Fixes WebKit disappearing element bug */
  will-change: transform;
  box-shadow: var(--shadow-card);
  transition: all 0.3s ease;
  display: flex;
  flex-direction: column;
}

.card-hover:hover {
  border-color: rgba(255, 255, 255, 0.15);
  transform: translateY(-6px) translateZ(0);
  box-shadow: 0 15px 35px rgba(0, 0, 0, 0.4);
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
  background: rgba(0, 0, 0, 0.2);
  border-bottom-left-radius: 16px;
  border-bottom-right-radius: 16px;
}
</style>
