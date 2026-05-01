<template>
  <div class="dashboard-home">
    <div v-if="verificationSuccess" class="success-banner">
        {{ $t('auth.verify_email_success') }}
    </div>

    <div class="header-section">
      <h1>{{ $t('dashboard.welcome', { name: auth.user?.first_name || $t('dashboard.shooter_fallback') }) }}</h1>
      <p class="text-secondary">{{ $t('dashboard.dashboard_subtitle') }}</p>
    </div>

    <!-- Recent Sessions — the hero of the dashboard -->
    <section class="recent-sessions">
      <div class="section-header">
        <h2>{{ $t('nav.sessions') }}</h2>
        <router-link v-if="sessions.length" to="/dashboard/sessions" class="view-all-link text-primary">
          {{ $t('sessions.view_all') }} →
        </router-link>
      </div>

      <div v-if="isLoadingSessions" class="loading-row">
        <div class="spinner-sm"></div>
        <span class="text-secondary">{{ $t('common.loading') }}</span>
      </div>

      <div v-else-if="sessions.length === 0" class="empty-sessions">
        <svg class="empty-icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg>
        <p class="text-secondary" v-html="$t('sessions.empty')"></p>
      </div>

      <div v-else class="sessions-list">
        <router-link
          v-for="session in sessions.slice(0, 5)"
          :key="session.uuid"
          :to="{ name: 'session-detail', params: { uuid: session.uuid } }"
          class="session-row"
        >
          <div class="session-info">
            <span class="session-discipline">{{ session.discipline_name || $t('common.no_data') }}</span>
            <span class="session-date text-secondary">{{ formatDate(session.date) }}</span>
          </div>
          <div class="session-score">
            <span class="score-value">{{ session.total_score ?? '—' }}</span>
            <AppBadge v-if="session.type" :variant="session.type === 'competition' ? 'warning' : 'neutral'" class="session-type-badge">
              {{ session.type }}
            </AppBadge>
          </div>
        </router-link>
      </div>
    </section>

    <!-- Secondary cards: Subscription + Devices -->
    <div class="secondary-grid">
      <!-- Subscription -->
      <div class="info-card">
        <div class="info-card-header">
          <span>{{ $t('nav.subscription') }}</span>
          <AppBadge :variant="auth.isProPlus ? 'success' : 'neutral'">
            {{ auth.isProPlus ? $t('dashboard.pro_plan') : $t('dashboard.free_plan') }}
          </AppBadge>
        </div>

        <div v-if="auth.isProPlus" class="info-card-body">
          <p v-if="!auth.subscription?.cancel_at_period_end" class="text-secondary">{{ $t('dashboard.subscription_status') }}: {{ $t('dashboard.active') }}</p>
          <div v-else class="cancel-banner">
            <span>⚠️</span>
            <span>{{ $t('subscription.ending_soon', { date: auth.subscription?.current_period_end ? formatDate(auth.subscription.current_period_end) : '...' }) }}</span>
          </div>
          <router-link to="/dashboard/subscription" class="btn btn-secondary info-card-action">{{ $t('dashboard.manage_subscription') }}</router-link>
        </div>

        <div v-else class="info-card-body">
          <p class="text-secondary">{{ $t('subscription.subtitle') }}</p>
          <div class="upgrade-price">$4.99 <span class="text-secondary upgrade-period">/ {{ $t('subscription.per_month') }}</span></div>
          <router-link to="/dashboard/subscription" class="btn btn-primary info-card-action">{{ $t('dashboard.upgrade_pro') }}</router-link>
        </div>
      </div>

      <!-- Devices -->
      <div class="info-card">
        <div class="info-card-header">
          <span>{{ $t('nav.devices') }}</span>
          <span v-if="devices.length" class="text-secondary device-count">{{ devices.length }}</span>
        </div>

        <div class="info-card-body">
          <div v-if="devices.length === 0" class="text-secondary empty-hint-sm">
            {{ $t('dashboard.no_devices') }}
          </div>

          <div v-else class="devices-compact">
            <div v-for="device in devices" :key="device.id" class="device-row" @click="openEditModal(device)">
              <span class="device-icon">📱</span>
              <div class="device-info">
                <span class="device-name">{{ device.custom_name || device.hardware_model || $t('common.no_data') }}</span>
              </div>
            </div>
          </div>

          <router-link to="/dashboard/devices" class="btn btn-secondary info-card-action">{{ $t('dashboard.manage_devices') }}</router-link>
        </div>
      </div>
    </div>

    <!-- Edit Device Name Modal -->
    <AppModal :is-open="showEditModal" @close="showEditModal = false" :title="$t('devices.edit_device')" size="sm">
      <div class="modal-form">
        <form @submit.prevent="handleEditDevice">
          <AppInput
            v-model="editingDeviceName"
            :label="$t('devices.custom_name_label')"
            placeholder="My Phone"
            required
          />
          <div class="modal-actions">
            <AppButton variant="secondary" @click="showEditModal = false">{{ $t('common.cancel') }}</AppButton>
            <AppButton type="submit" :loading="isEditing">{{ $t('common.save') }}</AppButton>
          </div>
        </form>
      </div>
    </AppModal>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { devicesApi } from '@/api/devices'
import { sessionsApi } from '@/api/sessions'
import { useI18n } from 'vue-i18n'
import AppBadge from '@/components/ui/AppBadge.vue'
import AppModal from '@/components/ui/AppModal.vue'
import AppInput from '@/components/ui/AppInput.vue'
import AppButton from '@/components/ui/AppButton.vue'

const route = useRoute()
const auth = useAuthStore()
const { locale } = useI18n()
const devices = ref([])
const sessions = ref([])
const isLoadingSessions = ref(true)
const verificationSuccess = ref(route.query.verified === '1')

const formatDate = (dateString) => {
  if (!dateString) return ''
  const date = new Date(dateString)
  return date.toLocaleDateString(locale.value, {
    month: 'short',
    day: 'numeric',
    year: 'numeric'
  })
}

onMounted(async () => {
  await auth.fetchUser()

  const [devicesRes, sessionsRes] = await Promise.all([
    devicesApi.getDevices(),
    sessionsApi.getSessions({ sort: 'newest' })
  ])

  if (devicesRes.success) {
    devices.value = devicesRes.devices
  }
  if (sessionsRes.success) {
    sessions.value = sessionsRes.sessions || []
  }
  isLoadingSessions.value = false
})

const showEditModal = ref(false)
const editingDevice = ref(null)
const editingDeviceName = ref('')
const isEditing = ref(false)

const openEditModal = (device) => {
  editingDevice.value = device
  editingDeviceName.value = device.custom_name || ''
  showEditModal.value = true
}

const handleEditDevice = async () => {
  if (!editingDevice.value) return
  isEditing.value = true
  try {
    const res = await devicesApi.updateDevice(editingDevice.value.device_uuid, { custom_name: editingDeviceName.value })
    if (res.success) {
      const response = await devicesApi.getDevices()
      if (response.success) {
        devices.value = response.devices
      }
      showEditModal.value = false
    }
  } finally {
    isEditing.value = false
  }
}
</script>

<style scoped>
.dashboard-home {
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

.header-section h1 {
  margin: 0 0 0.25rem 0;
}

.header-section p {
  margin: 0;
}

/* ─── Recent Sessions ─── */
.recent-sessions {
  background: var(--bg-surface);
  border: 1px solid var(--border-subtle);
  border-radius: 12px;
  overflow: hidden;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid var(--border-subtle);
}

.section-header h2 {
  font-size: 1rem;
  font-weight: 600;
  margin: 0;
}

.view-all-link {
  font-size: 0.875rem;
  font-weight: 500;
  transition: opacity 0.2s ease;
}

.view-all-link:hover {
  opacity: 0.8;
}

.loading-row {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 2rem 1.5rem;
}

.spinner-sm {
  width: 16px;
  height: 16px;
  border: 2px solid var(--border-subtle);
  border-radius: 50%;
  border-top-color: var(--primary);
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.empty-sessions {
  text-align: center;
  padding: 3rem 1.5rem;
}

.empty-icon-svg {
  width: 2.5rem;
  height: 2.5rem;
  color: var(--primary);
  margin-bottom: 0.75rem;
}

.empty-sessions :deep(strong) {
  color: var(--text-primary);
  font-weight: 600;
}

.session-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 1.5rem;
  border-bottom: 1px solid var(--border-subtle);
  transition: background 0.15s ease;
  cursor: pointer;
  text-decoration: none;
  color: inherit;
}

.session-row:last-child {
  border-bottom: none;
}

.session-row:hover {
  background: var(--bg-elevated);
}

.session-info {
  display: flex;
  flex-direction: column;
  gap: 0.125rem;
}

.session-discipline {
  font-weight: 500;
  font-size: 0.95rem;
}

.session-date {
  font-size: 0.8rem;
}

.session-score {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.score-value {
  font-family: var(--font-heading);
  font-weight: 700;
  font-size: 1.25rem;
}

.session-type-badge {
  font-size: 0.65rem;
}

/* ─── Secondary Grid ─── */
.secondary-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 1.5rem;
}

.info-card {
  background: var(--bg-surface);
  border: 1px solid var(--border-subtle);
  border-radius: 12px;
  overflow: hidden;
}

.info-card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid var(--border-subtle);
  font-weight: 600;
  font-size: 1rem;
}

.info-card-body {
  padding: 1.5rem;
}

.info-card-body p {
  margin: 0 0 1rem 0;
}

.info-card-action {
  width: 100%;
  text-align: center;
  margin-top: 1rem;
}

.upgrade-price {
  font-family: var(--font-heading);
  font-size: 2rem;
  font-weight: 700;
  margin-bottom: 0.5rem;
}

.upgrade-period {
  font-size: 0.875rem;
  font-family: var(--font-body);
  font-weight: 400;
}

.device-count {
  font-size: 0.875rem;
}

.devices-compact {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.device-row {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.625rem 0.75rem;
  background: var(--bg-elevated);
  border-radius: 8px;
  cursor: pointer;
  transition: background 0.15s ease;
}

.device-row:hover {
  background: var(--bg-card);
}

.device-icon {
  font-size: 1.25rem;
}

.device-name {
  font-size: 0.9rem;
  font-weight: 500;
}

.empty-hint-sm {
  font-size: 0.9rem;
}

/* ─── Banners ─── */
.success-banner {
  color: var(--success);
  font-size: 0.9375rem;
  text-align: center;
  padding: 0.75rem 1rem;
  background: oklch(0.55 0.15 145 / 0.1);
  border: 1px solid oklch(0.55 0.15 145 / 0.2);
  border-radius: 8px;
}

.cancel-banner {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  padding: 0.875rem 1rem;
  border-radius: 8px;
  background: oklch(0.75 0.15 85 / 0.08);
  border: 1px solid oklch(0.75 0.15 85 / 0.2);
  font-size: 0.875rem;
  line-height: 1.5;
  color: var(--text-secondary);
  margin-bottom: 1rem;
}

/* ─── Modal ─── */
.modal-form {
  padding: 1.5rem;
}

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  margin-top: 1.5rem;
}

/* ─── Utilities ─── */
.text-secondary { color: var(--text-secondary); }
.text-primary { color: var(--primary); }

@media (max-width: 640px) {
  .secondary-grid {
    grid-template-columns: 1fr;
  }
}
</style>
