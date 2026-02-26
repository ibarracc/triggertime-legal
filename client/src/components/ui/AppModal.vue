<template>
  <div v-if="isOpen" class="modal-overlay">
    <div class="modal-card" :class="maxWidthClass">
      
      <!-- Header -->
      <div v-if="$slots.header || title" class="modal-header">
        <h3 v-if="title" class="modal-title">{{ title }}</h3>
        <slot name="header"></slot>
        <button v-if="showClose" @click="close" class="modal-close">
          <svg class="icon-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </button>
      </div>

      <!-- Body -->
      <div class="modal-body">
        <slot></slot>
      </div>

      <!-- Footer -->
      <div v-if="$slots.footer" class="modal-footer">
        <slot name="footer"></slot>
      </div>
      
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  isOpen: {
    type: Boolean,
    required: true
  },
  title: {
    type: String,
    default: ''
  },
  size: {
    type: String,
    default: 'md' // sm, md, lg, xl
  },
  showClose: {
    type: Boolean,
    default: true
  }
})

const emit = defineEmits(['close'])

const close = () => {
  emit('close')
}

const maxWidthClass = computed(() => {
  switch (props.size) {
    case 'sm': return 'max-w-sm'
    case 'md': return 'max-w-md'
    case 'lg': return 'max-w-lg'
    case 'xl': return 'max-w-xl'
    default: return 'max-w-md'
  }
})
</script>
<style scoped>
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  backdrop-filter: blur(4px);
  z-index: 50;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
}

.modal-card {
  background-color: var(--bg-card);
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  border-radius: 1rem;
  padding: 1.5rem;
  width: 100%;
  border: 1px solid var(--border-subtle);
}

.max-w-sm { max-width: 24rem; }
.max-w-md { max-width: 28rem; }
.max-w-lg { max-width: 32rem; }
.max-w-xl { max-width: 36rem; }

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.modal-title {
  font-size: 1.25rem;
  font-weight: 700;
  font-family: var(--font-heading);
  margin: 0;
}

.modal-close {
  background: none;
  border: none;
  color: var(--text-secondary);
  cursor: pointer;
  padding: 0.25rem;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: color 0.2s;
}

.modal-close:hover {
  color: var(--text-primary);
}

.icon-sm {
  width: 1.25rem;
  height: 1.25rem;
}

.modal-body {
  overflow-y: auto;
  flex: 1;
  margin-bottom: 1.5rem;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
}
</style>
