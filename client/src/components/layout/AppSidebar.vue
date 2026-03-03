<template>
  <div class="sidebar-wrapper" :class="{ 'is-open': isOpen }">
    <div class="sidebar-backdrop" @click="$emit('close')"></div>
    <aside class="app-sidebar">
    <div class="sidebar-header">
      <router-link to="/dashboard" class="logo flex items-center gap-2">
        <img src="/triggertime.png" alt="TriggerTime Logo" class="logo-img">
        <span class="logo-text">Trigger<span class="highlight">Time</span></span>
      </router-link>
    </div>

    <nav class="sidebar-nav">
      <router-link to="/dashboard" class="nav-item" exact-active-class="active">
        {{ $t('nav.dashboard') }}
      </router-link>
      <router-link to="/dashboard/subscription" class="nav-item" active-class="active">
        {{ $t('nav.subscription') }}
      </router-link>
      <router-link to="/dashboard/devices" class="nav-item" active-class="active">
        {{ $t('nav.devices') }}
      </router-link>
      <router-link to="/dashboard/profile" class="nav-item" active-class="active">
        {{ $t('nav.profile') }}
      </router-link>
      <div v-if="auth.isAdmin || auth.isClubAdmin" class="admin-section mt-8">
        <span class="section-title">{{ auth.isClubAdmin ? $t('common.club_admin') : 'Admin' }}</span>
        <router-link to="/admin" class="nav-item" active-class="active">
          {{ $t('nav.admin_panel') }}
        </router-link>
      </div>
    </nav>

    <div class="sidebar-footer">
      <!-- Impersonation Alert -->
      <div v-if="isImpersonating" class="impersonation-alert mb-4 shadow-lg border border-amber-500/30">
        <div class="flex items-center gap-1.5 mb-2">
           <svg class="w-8 h-8 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
             <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
           </svg>
           <span class="impersonation-label uppercase tracking-wider">{{ $t('admin.impersonate') }}</span>
        </div>
        <div class="impersonation-user-info mb-3 font-medium leading-tight break-all">
          {{ auth.user?.email }} <span class="opacity-60">({{ auth.user?.role }})</span>
        </div>
        <AppButton variant="secondary" @click="endImpersonation" class="w-full end-impersonation-btn">
          {{ $t('common.cancel') }} {{ $t('common.session') }}
        </AppButton>
      </div>

      <AppButton variant="secondary" @click="handleLogout" class="w-full">
        {{ $t('common.logout') }}
      </AppButton>

      <div class="sidebar-copyright mt-4 text-center">
        <p>&copy; {{ new Date().getFullYear() }} TriggerTime</p>
      </div>
    </div>
  </aside>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useRouter, useRoute } from 'vue-router'
import AppButton from '@/components/ui/AppButton.vue'

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['close'])

const auth = useAuthStore()
const router = useRouter()
const route = useRoute()

// Close sidebar on route change
watch(() => route.path, () => {
  emit('close')
})

const isImpersonating = ref(!!localStorage.getItem('tt_token_stash'))

const endImpersonation = () => {
  const stashedToken = localStorage.getItem('tt_token_stash')
  if (stashedToken) {
    // 1. Clear everything first
    localStorage.removeItem('tt_token')
    localStorage.removeItem('tt_token_stash')

    // 2. Set the restored token
    localStorage.setItem('tt_token', stashedToken)

    // 3. Force a hard reload to ensure all stores and state are completely reset
    window.location.href = '/admin/users'
  }
}

const handleLogout = () => {
  auth.logout()
  router.push('/login')
}
</script>

<style scoped>
.app-sidebar {
  width: 280px;
  background: var(--bg-surface);
  border-right: 1px solid var(--border-subtle);
  display: flex;
  flex-direction: column;
  height: 100vh;
  position: sticky;
  top: 0;
}

.sidebar-header {
  padding: 32px 24px;
}

.logo-text {
  font-family: var(--font-heading);
  font-size: 1.5rem;
  font-weight: 800;
  letter-spacing: -0.5px;
}

.logo-img {
  width: 50px;
  height: 50px;
  border-radius: 12px;
  object-fit: cover;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

.highlight {
  color: var(--primary);
}

.sidebar-nav {
  display: flex;
  flex-direction: column;
  padding: 0 16px;
  gap: 8px;
  flex: 1;
}

.nav-item {
  padding: 12px 16px;
  border-radius: 12px;
  color: var(--text-secondary);
  font-weight: 500;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
}

.nav-item:hover {
  color: var(--text-primary);
  background: rgba(255, 255, 255, 0.05);
}

.nav-item.active {
  background: rgba(193, 255, 114, 0.1);
  color: var(--primary);
  font-weight: 600;
}

.admin-section {
  padding-top: 16px;
  border-top: 1px solid var(--border-subtle);
}

.section-title {
  display: block;
  padding: 0 16px 8px;
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 1px;
  color: var(--text-secondary);
  font-weight: 600;
}

.sidebar-footer {
  padding: 24px;
  border-top: 1px solid var(--border-subtle);
}

.impersonation-alert {
  padding: 12px;
  background: rgba(245, 158, 11, 0.1);
  border: 1px solid rgba(245, 158, 11, 0.2);
  border-radius: 12px;
}

.impersonation-label {
  font-size: 15px;
  font-weight: 700;
  color: #F59E0B;
  margin-left: 5px;
}

.impersonation-user-info {
  font-size: 12px;
  color: white
}

.text-amber-500 {
  color: #F59E0B;
}

.end-impersonation-btn {
  font-size: 0.75rem;
  padding: 8px;
  background: rgba(245, 158, 11, 0.1) !important;
  border-color: rgba(245, 158, 11, 0.2) !important;
  color: #F59E0B !important;
}

.end-impersonation-btn:hover {
  background: rgba(245, 158, 11, 0.2) !important;
}

.sidebar-copyright {
  color: var(--text-secondary);
  font-size: 0.75rem;
  opacity: 0.6;
}

.w-full { width: 100%; }

@media (max-width: 768px) {
  .sidebar-wrapper {
    position: fixed;
    inset: 0;
    z-index: 50;
    pointer-events: none;
  }

  .sidebar-wrapper.is-open {
    pointer-events: auto;
  }

  .sidebar-backdrop {
    position: absolute;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    opacity: 0;
    transition: opacity 0.3s ease;
  }

  .sidebar-wrapper.is-open .sidebar-backdrop {
    opacity: 1;
  }

  .app-sidebar {
    position: absolute;
    top: 0;
    left: 0;
    height: 100vh;
    transform: translateX(-100%);
    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  }

  .sidebar-wrapper.is-open .app-sidebar {
    transform: translateX(0);
  }
}
</style>
