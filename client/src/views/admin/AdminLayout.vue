<template>
  <div class="admin-section">
    <!-- Admin Navigation Tabs -->
    <div class="admin-nav mb-8 border-b border-[var(--border-subtle)] pb-4 flex flex-wrap gap-3">
      <router-link to="/admin/dashboard" class="admin-tab" active-class="active">{{ $t('nav.dashboard') }}</router-link>
      <router-link v-if="auth.isAdmin" to="/admin/users" class="admin-tab" active-class="active">{{ $t('nav.users') }}</router-link>
      <router-link to="/admin/licenses" class="admin-tab" active-class="active">{{ $t('admin.licenses_title') }}</router-link>
      <router-link v-if="auth.isAdmin" to="/admin/devices" class="admin-tab" active-class="active">{{ $t('nav.devices') }}</router-link>
      <router-link v-if="auth.isAdmin" to="/admin/instances" class="admin-tab" active-class="active">{{ $t('admin.instances_title') }}</router-link>
      <router-link v-if="auth.isAdmin" to="/admin/versions" class="admin-tab" active-class="active">{{ $t('admin.versions_title') }}</router-link>
      <router-link v-if="auth.isAdmin" to="/admin/subscriptions" class="admin-tab" active-class="active">{{ $t('admin.subscriptions_title') }}</router-link>
      <router-link v-if="auth.isAdmin" to="/admin/remote-configs" class="admin-tab" active-class="active" :class="{'active': $route.path.includes('/admin/remote-configs')}">{{ $t('admin.configs_title') }}</router-link>
    </div>
    
    <!-- Render Child Routes -->
    <router-view></router-view>
  </div>
</template>

<script setup>
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
</script>

<style scoped>
.admin-tab {
  padding: 8px 16px;
  color: var(--text-secondary);
  font-weight: 500;
  border-radius: 8px;
  transition: all 0.2s;
  white-space: nowrap;
  font-size: 0.95rem;
}

.admin-tab:hover {
  background: rgba(255, 255, 255, 0.05);
  color: var(--text-primary);
}

.admin-tab.active {
  background: var(--bg-surface);
  color: var(--primary);
  border: 1px solid var(--border-subtle);
}

@media (max-width: 768px) {
  .admin-tab {
    padding: 6px 12px;
    font-size: 0.85rem;
    flex: 1 1 auto;
    text-align: center;
    border: 1px solid transparent;
    background: rgba(255, 255, 255, 0.03);
  }
}
</style>
