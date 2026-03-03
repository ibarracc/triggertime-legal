<template>
  <div class="devices-view">
    <div class="header-section devices-header mb-8 flex justify-between items-end">
      <div>
        <h1 class="mb-2">{{ $t('devices.title') }}</h1>
        <p class="text-secondary m-0">{{ $t('devices.subtitle') }}</p>
      </div>
      <div class="device-count text-right">
        <div class="text-2xl font-heading font-bold">{{ devices.length }}</div>
        <div class="text-xs text-secondary uppercase tracking-wide">{{ $t('nav.devices') }}</div>
      </div>
    </div>

    <div class="devices-grid">
      <AppCard v-for="device in devices" :key="device.id" class="device-card min-w-0">
        <div class="flex items-start justify-between mb-4 gap-4">
          <div class="device-info flex gap-4 flex-1 min-w-0">
            <div class="device-icon shrink-0">📱</div>
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2 mb-1 min-w-0">
                <template v-if="device.custom_name">
                  <h3 class="font-heading text-lg m-0 cursor-pointer hover:text-primary transition-colors truncate flex-1 min-w-0 block w-full" @click="openEditModal(device)" :title="device.custom_name">
                    {{ device.custom_name }}
                  </h3>
                </template>
                <template v-else>
                  <h3 class="font-heading text-sm m-0 text-primary cursor-pointer hover:underline whitespace-nowrap truncate flex-1 min-w-0 block w-full" @click="openEditModal(device)">
                    Add custom name
                  </h3>
                </template>
              </div>
              <div class="text-sm text-secondary truncate">{{ device.hardware_model || 'Unknown Model' }}</div>
            </div>
          </div>
          <AppBadge variant="success" class="shrink-0">{{ $t('dashboard.active') }}</AppBadge>
        </div>
        
        <div class="device-meta text-xs text-secondary mt-4 mb-6 pt-4 border-t border-subtle">
          <div v-if="device.first_activation_date">Added: {{ new Date(device.first_activation_date).toLocaleDateString(locale) }}</div>
          <div class="mt-1 font-mono" style="word-break: break-all;">ID: {{ device.device_uuid }}</div>
        </div>
        
        <AppButton 
          variant="danger" 
          size="sm" 
          class="w-full mt-4" 
          @click="confirmUnlink(device)"
          :loading="unlinkingId === device.device_uuid"
        >
          {{ $t('devices.unlink') }}
        </AppButton>
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

      <!-- Unlink Confirmation Modal -->
      <AppModal :is-open="showUnlinkModal" @close="showUnlinkModal = false" :title="$t('devices.unlink')" size="sm">
        <div class="p-6">
          <div class="mb-6 flex gap-4">
            <div class="text-danger mt-1">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
            </div>
            <div>
              <p class="mt-0 mb-2 font-medium">{{ $t('devices.unlink_confirm') }}</p>
            </div>
          </div>
          <div class="flex justify-end gap-3">
            <AppButton variant="secondary" @click="showUnlinkModal = false">{{ $t('common.cancel') }}</AppButton>
            <AppButton variant="danger" @click="executeUnlink" :loading="isUnlinking">{{ $t('devices.unlink') }}</AppButton>
          </div>
        </div>
      </AppModal>

      <!-- Link Device Block -->
      <div class="empty-device-slot">
        <div class="empty-icon text-secondary mb-3">⨁</div>
        <div v-if="!showLinkModal" @click="showLinkModal = true" style="cursor: pointer">
            <div class="text-secondary font-medium">{{ $t('devices.link_new') }}</div>
            <div class="text-xs text-secondary mt-2 opacity-60">{{ $t('devices.token_help') }}</div>
        </div>
        <div v-else class="link-form w-full flex flex-col gap-3 px-4">
            <input 
                v-model="linkToken" 
                class="token-input" 
                :placeholder="$t('devices.token_placeholder')" 
                maxlength="6"
                @keyup.enter="handleLinkDevice"
            />
            <div v-if="linkError" class="text-xs text-danger">{{ linkError }}</div>
            <div class="flex gap-2">
                <AppButton size="sm" class="flex-1" @click="handleLinkDevice" :loading="isLinking">{{ $t('devices.link_button') }}</AppButton>
                <AppButton size="sm" variant="secondary" @click="showLinkModal = false">✕</AppButton>
            </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { devicesApi } from '@/api/devices'
import { useI18n } from 'vue-i18n'
import api from '@/api/index'
import AppCard from '@/components/ui/AppCard.vue'
import AppBadge from '@/components/ui/AppBadge.vue'
import AppButton from '@/components/ui/AppButton.vue'
import AppModal from '@/components/ui/AppModal.vue'
import AppInput from '@/components/ui/AppInput.vue'

const auth = useAuthStore()
const { t, locale } = useI18n()

const devices = ref([])
const unlinkingId = ref(null)
const isLoading = ref(true)

const fetchDevices = async () => {
  isLoading.value = true
  try {
    const response = await devicesApi.getDevices()
    if (response.success) {
      devices.value = response.devices
    }
  } finally {
    isLoading.value = false
  }
}

onMounted(fetchDevices)

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
      await fetchDevices()
      showEditModal.value = false
    }
  } finally {
    isEditing.value = false
  }
}

// Unlinking device
const showUnlinkModal = ref(false)
const deviceToUnlink = ref(null)
const isUnlinking = ref(false)

const confirmUnlink = (device) => {
  deviceToUnlink.value = device
  showUnlinkModal.value = true
}

const executeUnlink = async () => {
  if (!deviceToUnlink.value) return
  
  const deviceUuid = deviceToUnlink.value.device_uuid
  unlinkingId.value = deviceUuid
  isUnlinking.value = true
  
  try {
    const response = await devicesApi.unlinkDevice(deviceUuid)
    if (response.success) {
      devices.value = devices.value.filter(d => d.device_uuid !== deviceUuid)
      showUnlinkModal.value = false
    }
  } finally {
    unlinkingId.value = null
    isUnlinking.value = false
  }
}

// Token verification and linking
const showLinkModal = ref(false)
const linkToken = ref('')
const isLinking = ref(false)
const linkError = ref('')

const handleLinkDevice = async () => {
    isLinking.value = true
    linkError.value = ''
    try {
            // Link the device using the 6-character code
            const linkRes = await devicesApi.linkDevice(linkToken.value)
            if (linkRes.success) {
                await fetchDevices()
                showLinkModal.value = false
                linkToken.value = ''
            }
    } catch (e) {
        linkError.value = e.response?.data?.message || t('common.error')
    } finally {
        isLinking.value = false
    }
}
</script>

<style scoped>
.font-heading { font-family: var(--font-heading); }
.text-2xl { font-size: 1.5rem; }
.text-xs { font-size: 0.75rem; }
.text-sm { font-size: 0.875rem; }
.m-0 { margin: 0; }
.font-bold { font-weight: 700; }
.tracking-wide { letter-spacing: 0.05em; }
.uppercase { text-transform: uppercase; }
.border-t { border-top: 1px solid var(--border-subtle); }
.pt-4 { padding-top: 1rem; }
.font-mono { font-family: monospace; }
.font-medium { font-weight: 500; }
.w-full { width: 100%; }

.text-primary { color: var(--primary); }
.hover-underline:hover { text-decoration: underline; }
.opacity-60 { opacity: 0.6; }

.alert {
  border: 1px solid transparent;
}
.alert.warning {
  background: rgba(251, 191, 36, 0.1);
  border-color: rgba(251, 191, 36, 0.3);
}
.alert.info {
  background: rgba(255, 255, 255, 0.05);
  border-color: var(--border-subtle);
}
.alert-icon { font-size: 1.25rem; }

.devices-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 24px;
}

@media (max-width: 640px) {
  .devices-header {
    flex-direction: column;
    align-items: flex-start !important;
    gap: 16px;
  }
  .device-count {
    text-align: left !important;
  }
}

@media (min-width: 640px) {
  .devices-grid {
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  }
}

.device-icon {
  font-size: 2rem;
  background: var(--bg-base);
  width: 48px;
  height: 48px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid var(--border-subtle);
}

.empty-device-slot {
  border: 1px dashed var(--border-subtle);
  border-radius: 16px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 40px 20px;
  background: rgba(255, 255, 255, 0.02);
  text-align: center;
}

.empty-icon {
  font-size: 2rem;
}

.token-input {
    background: var(--bg-surface);
    border: 1px solid var(--border-subtle);
    border-radius: 8px;
    padding: 8px;
    color: var(--text-primary);
    text-align: center;
    font-weight: 700;
    letter-spacing: 2px;
    width: 100%;
}

.token-input:focus {
    outline: none;
    border-color: var(--primary);
}

.text-danger {
    color: var(--danger);
}

.flex-1 {
    flex: 1;
}

.px-4 {
    padding-left: 1rem;
    padding-right: 1rem;
}
</style>
