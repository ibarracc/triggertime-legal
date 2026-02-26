<template>
  <div class="dashboard-layout">
    <div class="mobile-topbar">
      <div class="flex items-center gap-3">
        <button class="menu-btn" @click="sidebarOpen = true">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="3" y1="12" x2="21" y2="12"></line>
            <line x1="3" y1="6" x2="21" y2="6"></line>
            <line x1="3" y1="18" x2="21" y2="18"></line>
          </svg>
        </button>
        <span class="font-heading font-bold text-xl" style="color: var(--text-primary); font-family: var(--font-heading); font-size: 1.5rem; font-weight: 800; letter-spacing: -0.5px;">Trigger<span style="color: var(--primary);">Time</span></span>
      </div>
    </div>

    <AppSidebar :is-open="sidebarOpen" @close="sidebarOpen = false" />
    <main class="dashboard-main">
      <div class="dashboard-content">
        <router-view />
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import AppSidebar from '@/components/layout/AppSidebar.vue'

const sidebarOpen = ref(false)
</script>

<style scoped>
.dashboard-layout {
  display: flex;
  min-height: 100vh;
  background-color: var(--bg-base);
}

.dashboard-main {
  flex: 1;
  overflow-y: auto;
  padding: 48px;
}

.dashboard-content {
  max-width: 1000px;
  margin: 0 auto;
}

.mobile-topbar {
  display: none;
  height: 60px;
  background: var(--bg-surface);
  border-bottom: 1px solid var(--border-subtle);
  padding: 0 16px;
  align-items: center;
  position: sticky;
  top: 0;
  z-index: 40;
}

.menu-btn {
  background: transparent;
  border: none;
  color: var(--text-primary);
  cursor: pointer;
  padding: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
}

@media (max-width: 768px) {
  .dashboard-layout {
    flex-direction: column;
  }

  .dashboard-main {
    padding: 16px;
  }

  .mobile-topbar {
    display: flex;
  }
}
</style>
