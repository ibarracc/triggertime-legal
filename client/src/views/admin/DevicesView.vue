<template>
  <div class="admin-devices">
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold font-heading">Devices Management</h1>
    </div>

    <!-- Error State -->
    <div v-if="error" class="bg-red-500/10 border border-red-500/20 text-red-500 p-4 rounded-xl mb-6">
      {{ error }}
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center p-12">
      <div class="animate-spin h-8 w-8 border-4 border-[var(--primary)] border-t-transparent rounded-full"></div>
    </div>

    <!-- Devices Table -->
    <div v-else class="table-card mt-6 mb-6">
      <div class="table-wrapper">
        <table class="admin-table">
          <thead>
            <tr>
              <th>UUID</th>
              <th>Model</th>
              <th>Owner</th>
              <th>Instance</th>
              <th class="text-right">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="device in devices" :key="device.id" class="hover:bg-white/5 transition-colors group">
              <td>
                <div class="font-mono text-sm tracking-wider text-primary">{{ device.device_uuid }}</div>
                <div class="text-xs text-secondary">{{ device.platform }} v{{ device.os_version }}</div>
              </td>
              <td>
                <div class="font-medium">{{ device.hardware_model }}</div>
                <div class="text-xs text-secondary">{{ device.custom_name || '-' }}</div>
              </td>
              <td class="text-sm">
                <div v-if="device.user" class="font-medium text-primary">
                  <span v-if="device.user.first_name || device.user.last_name">{{ device.user.first_name }} {{ device.user.last_name }}</span>
                  <span v-else>{{ device.user.email }}</span>
                </div>
                <div v-else class="text-secondary">N/A</div>
              </td>
              <td class="text-sm">{{ device.instance?.name || 'Global' }}</td>
              <td class="text-right">
                <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                  <AppButton variant="secondary" size="sm" @click="openEditModal(device)">Edit</AppButton>
                  <AppButton v-if="!device.deleted_at" variant="secondary" size="sm" @click="confirmDelete(device)" class="text-red-400 hover:text-red-300 border-red-500/30 hover:bg-red-500/10">
                    <svg class="w-4 h-4 mr-2 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    Delete
                  </AppButton>
                </div>
              </td>
            </tr>
            <tr v-if="devices.length === 0">
              <td colspan="5" class="p-8 text-center text-secondary">No devices found.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <AppModal :isOpen="!!deletingDevice" title="Delete Device" @close="deletingDevice = null">
      <p class="text-[var(--text-secondary)] mb-6">Are you sure you want to soft delete device <strong>{{ deletingDevice?.device_uuid }}</strong>?</p>
      
      <div class="flex gap-3 justify-end mt-4">
        <AppButton variant="secondary" @click="deletingDevice = null">Cancel</AppButton>
        <button @click="executeDelete" class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg font-medium transition-colors">
          Confirm Delete
        </button>
      </div>
    </AppModal>

    <!-- Edit Modal -->
    <AppModal :isOpen="modalState.isOpen" title="Edit Device" @close="closeModal">
      <div v-if="modalState.error" class="alert alert-danger">
        {{ modalState.error }}
      </div>
      <div class="form-group">
        <label class="form-label">Custom Name</label>
        <AppInput v-model="modalData.custom_name" placeholder="Device Name" :error="modalState.errors?.custom_name?.[0]" />
      </div>
      
      <div class="flex gap-4 justify-end mt-6">
        <AppButton variant="secondary" @click="closeModal">Cancel</AppButton>
        <AppButton @click="submitModal" :loading="modalState.loading">Save Changes</AppButton>
      </div>
    </AppModal>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { adminApi } from '@/api/admin'
import AppButton from '@/components/ui/AppButton.vue'
import AppInput from '@/components/ui/AppInput.vue'
import AppModal from '@/components/ui/AppModal.vue'

const loading = ref(true)
const error = ref('')
const devices = ref([])

const deletingDevice = ref(null)
const modalState = ref({ isOpen: false, error: '', errors: {}, loading: false })
const modalData = ref({ id: null, custom_name: '' })

const loadDevices = async () => {
  loading.value = true
  error.value = ''
  try {
    const response = await adminApi.getDevices()
    if (response.success) {
      devices.value = response.devices
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to load devices'
  } finally {
    loading.value = false
  }
}

const confirmDelete = (device) => {
  deletingDevice.value = device
}

const executeDelete = async () => {
  if (!deletingDevice.value) return
  
  try {
    const response = await adminApi.deleteDevice(deletingDevice.value.id)
    if (response.success) {
      await loadDevices()
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to delete device'
  } finally {
    deletingDevice.value = null
  }
}

const openEditModal = (device) => {
    modalData.value = { id: device.id, custom_name: device.custom_name || '' }
    modalState.value = { isOpen: true, error: '', errors: {}, loading: false }
}

const closeModal = () => {
    modalState.value.isOpen = false
}

const submitModal = async () => {
    modalState.value.error = ''
    modalState.value.errors = {}
    modalState.value.loading = true
    try {
        await adminApi.updateDevice(modalData.value.id, { custom_name: modalData.value.custom_name })
        closeModal()
        await loadDevices()
    } catch (err) {
        modalState.value.error = err.response?.data?.message || 'Update failed'
        if (err.response?.data?.errors) {
            modalState.value.errors = err.response.data.errors
        }
    } finally {
        modalState.value.loading = false
    }
}

onMounted(() => {
  loadDevices()
})
</script>
