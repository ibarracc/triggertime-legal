<template>
  <div class="dashboard-home">
    <div class="header-section mb-8">
      <h1 class="mb-2">{{ $t('dashboard.welcome', { name: auth.user?.first_name || $t('dashboard.shooter_fallback') }) }}</h1>
      <p class="text-secondary">{{ $t('dashboard.dashboard_subtitle') }}</p>
    </div>

    <!-- Free Tier Upsell / Subscription Status -->
    <div class="status-cards-grid mb-8">
      <AppCard hoverable>
        <template #header>
          <div class="flex justify-between items-center">
            <span>{{ $t('nav.subscription') }}</span>
            <AppBadge :variant="auth.isProPlus ? 'success' : 'neutral'">
              {{ auth.isProPlus ? $t('dashboard.pro_plan') : $t('dashboard.free_plan') }}
            </AppBadge>
          </div>
        </template>

        <div v-if="auth.isProPlus" class="pro-status">
          <p v-if="!auth.subscription?.cancel_at_period_end" class="mb-4 text-secondary">{{ $t('dashboard.subscription_status') }}: {{ $t('dashboard.active') }}</p>
          <div v-else class="cancel-banner mb-4">
            <div class="cancel-banner-icon">⚠️</div>
            <div class="cancel-banner-content">
              {{ $t('subscription.ending_soon', { date: auth.subscription?.current_period_end ? formatDate(auth.subscription.current_period_end) : '...' }) }}
            </div>
          </div>
          <div class="metric-value text-primary mb-6">{{ $t('dashboard.pro_plan') }}</div>
          <router-link to="/dashboard/subscription" class="btn btn-secondary w-full">{{ $t('dashboard.manage_subscription') }}</router-link>
        </div>

        <div v-else class="free-status">
          <p class="mb-4 text-secondary">{{ $t('subscription.subtitle') }}</p>
          <div class="metric-value mb-6">$4.99<span class="text-sm text-secondary font-body">/{{ $t('subscription.per_month') }}</span></div>
          <router-link to="/dashboard/subscription" class="btn btn-primary w-full">{{ $t('dashboard.upgrade_pro') }}</router-link>
        </div>
      </AppCard>

      <AppCard hoverable class="min-w-0">
        <template #header>
          <div class="flex justify-between items-center min-w-0">
            <span class="truncate">{{ $t('nav.devices') }}</span>
            <span class="text-sm text-secondary shrink-0">{{ devices.length }}</span>
          </div>
        </template>

        <div v-if="devices.length === 0" class="empty-state text-center py-4 text-secondary">
          {{ $t('dashboard.no_devices') }}
        </div>

        <div v-else class="devices-list flex flex-col gap-3 mb-6">
          <div v-for="device in devices" :key="device.id" class="device-item flex justify-between items-center p-3 bg-elevated rounded-lg border border-subtle min-w-0">
            <div class="flex items-center gap-3 min-w-0">
              <span class="device-icon">📱</span>
              <div class="flex-1 min-w-0">
                <div class="font-medium text-sm min-w-0">
                  <h3 v-if="device.custom_name" class="cursor-pointer hover:text-primary transition-colors truncate block w-full" @click="openEditModal(device)" :title="device.custom_name">
                    {{ device.custom_name }}
                  </h3>
                  <span v-else class="text-primary cursor-pointer hover:underline text-xs whitespace-nowrap truncate block w-full" @click="openEditModal(device)">
                    Add custom name
                  </span>
                </div>
                <div class="text-xs text-secondary truncate">{{ device.hardware_model || $t('common.no_data') }}</div>
              </div>
            </div>
          </div>
        </div>

        <router-link to="/dashboard/devices" class="btn btn-secondary w-full">{{ $t('dashboard.manage_devices') }}</router-link>
      </AppCard>
      <!-- Edit Device Name Modal -->
      <AppModal :is-open="showEditModal" @close="showEditModal = false" :title="$t('devices.edit_device')" size="sm">
        <div class="p-6">
          <form @submit.prevent="handleEditDevice">
            <AppInput
              v-model="editingDeviceName"
              :label="$t('devices.custom_name_label')"
              placeholder="My Phone"
              required
            />
            <div class="flex justify-end gap-3 mt-6">
              <AppButton variant="secondary" @click="showEditModal = false">{{ $t('common.cancel') }}</AppButton>
              <AppButton type="submit" :loading="isEditing">{{ $t('common.save') }}</AppButton>
            </div>
          </form>
        </div>
      </AppModal>

    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { devicesApi } from '@/api/devices'
import { useI18n } from 'vue-i18n'
import AppCard from '@/components/ui/AppCard.vue'
import AppBadge from '@/components/ui/AppBadge.vue'
import AppModal from '@/components/ui/AppModal.vue'
import AppInput from '@/components/ui/AppInput.vue'
import AppButton from '@/components/ui/AppButton.vue'

const auth = useAuthStore()
const { locale } = useI18n()
const devices = ref([])

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
  const response = await devicesApi.getDevices()
  if (response.success) {
    devices.value = response.devices
  }
})

// Editing Device
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
.status-cards-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 24px;
}

@media (min-width: 640px) {
  .status-cards-grid {
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
  }
}

.metric-value {
  font-family: var(--font-heading);
  font-size: 2.5rem;
  font-weight: 700;
  line-height: 1;
}

.device-item {
  background: rgba(255, 255, 255, 0.03);
}

.device-icon {
  font-size: 1.5rem;
}

.font-medium { font-weight: 500; }
.text-xs { font-size: 0.75rem; }
.text-sm { font-size: 0.875rem; }
.w-full { width: 100%; }
.bg-elevated { background-color: var(--bg-elevated); }
.border { border: 1px solid var(--border-subtle); }
.rounded-lg { border-radius: 12px; }
.font-body { font-family: var(--font-body); font-weight: normal; }

.cancel-banner {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 14px 18px;
  border-radius: 10px;
  background: rgba(251, 191, 36, 0.08);
  border: 1px solid rgba(251, 191, 36, 0.25);
  font-size: 0.875rem;
  line-height: 1.5;
  color: var(--text-secondary);
}

.cancel-banner-icon {
  font-size: 1.15rem;
  flex-shrink: 0;
  margin-top: 1px;
}

.cancel-banner-title {
  color: #FBBF24;
  font-weight: 600;
}

.cancel-banner-content {
  word-wrap: break-word;
  overflow-wrap: break-word;
  min-width: 0; /* Ensures flex container text will wrap */
}

.cancel-banner-content strong {
  color: var(--text-primary);
}
</style>
