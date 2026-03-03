<template>
  <div class="admin-configs">
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold font-heading">Remote Configs</h1>
      <AppButton @click="openCreateModal" size="sm">Add Config</AppButton>
    </div>

    <!-- Error State -->
    <div v-if="error" class="bg-red-500/10 border border-red-500/20 text-red-500 p-4 rounded-xl mb-6">
      {{ error }}
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center p-12">
      <div class="animate-spin h-8 w-8 border-4 border-[var(--primary)] border-t-transparent rounded-full"></div>
    </div>

    <!-- Configs Table -->
    <div v-else class="table-card mt-6 mb-6">
      <div class="table-wrapper">
        <table class="admin-table">
          <thead>
            <tr>
              <th>App Instance</th>
              <th>Version</th>
              <th>Keys</th>
              <th>Last Modified</th>
              <th class="text-right">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="config in configs" :key="config.id" class="hover:bg-white/5 transition-colors group">
              <td>
                <div class="font-medium text-sm text-primary">
                  {{ config.instance ? config.instance.name : config.app_instance }}
                </div>
              </td>
              <td>
                <div v-if="config.version" class="text-secondary">
                  v{{ config.version.version }}
                </div>
                <div v-else class="text-secondary italic">Global (All Versions)</div>
              </td>
              <td>
                <div class="text-secondary">
                   {{ getKeyCount(config.config_data) }} keys (+ legacy flags)
                </div>
              </td>
              <td>
                <div class="text-secondary text-sm">
                  {{ new Date(config.modified).toLocaleDateString() }}
                </div>
              </td>
              <td class="p-4 text-right">
                <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                  <router-link :to="`/admin/remote-configs/${config.id}`">
                    <AppButton variant="secondary" size="sm">View / Edit</AppButton>
                  </router-link>
                  <AppButton variant="secondary" size="sm" @click="confirmDelete(config)" class="text-red-400 hover:text-red-300 border-red-500/30 hover:bg-red-500/10">
                    <svg class="w-4 h-4 mr-2 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    Delete
                  </AppButton>
                </div>
              </td>
            </tr>
            <tr v-if="configs.length === 0">
              <td colspan="5" class="p-8 text-center text-secondary">No remote configs found.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <AppModal :isOpen="!!deletingConfig" title="Delete Config" @close="deletingConfig = null">
      <p class="text-[var(--text-secondary)] mb-6">Are you sure you want to delete this remote config payload?</p>
      <div class="flex gap-3 justify-end mt-4">
        <AppButton variant="secondary" @click="deletingConfig = null">Cancel</AppButton>
        <button @click="executeDelete" class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg font-medium transition-colors">
          Confirm Delete
        </button>
      </div>
    </AppModal>

    <!-- Create Modal -->
    <AppModal :isOpen="modalState.isOpen" title="Create Remote Config" @close="closeModal">
      <div v-if="modalState.error" class="alert alert-danger">
        {{ modalState.error }}
      </div>
      
      <div class="form-group">
        <label class="form-label">Instance</label>
        <select v-model="modalData.instance_id" class="form-select text-black" @change="onInstanceChange">
          <option :value="null">-- Select Instance --</option>
          <option v-for="inst in instances" :key="inst.id" :value="inst.id">{{ inst.name }}</option>
        </select>
        <div v-if="modalState.errors?.instance_id?.[0]" class="text-red-500 text-xs mt-1">{{ modalState.errors.instance_id[0] }}</div>
      </div>

      <div class="form-group mt-4">
        <label class="form-label">Version (Optional)</label>
        <select v-model="modalData.version_id" class="form-select text-black" :disabled="!modalData.instance_id">
          <option :value="null">Global (All versions for this instance)</option>
          <option v-for="ver in availableVersions" :key="ver.id" :value="ver.id">v{{ ver.version }}</option>
        </select>
        <div v-if="modalState.errors?.version_id?.[0]" class="text-red-500 text-xs mt-1">{{ modalState.errors.version_id[0] }}</div>
      </div>
      
      <div class="form-group mt-4">
        <label class="form-label">Initial JSON Payload</label>
        <p class="text-xs text-secondary mb-2">Upload a .json file to populate initial config values</p>
        <input type="file" accept=".json" @change="handleFileUpload" class="form-input text-secondary" />
      </div>
      
      <div class="flex gap-3 justify-end mt-6">
        <AppButton variant="secondary" @click="closeModal">Cancel</AppButton>
        <AppButton @click="submitModal" :loading="modalState.loading">Create Config</AppButton>
      </div>
    </AppModal>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { adminApi } from '@/api/admin'
import api from '@/api/index'
import AppButton from '@/components/ui/AppButton.vue'
import AppModal from '@/components/ui/AppModal.vue'

const loading = ref(true)
const error = ref('')
const configs = ref([])

const instances = ref([])
const allVersions = ref([])

const deletingConfig = ref(null)
const modalState = ref({ isOpen: false, error: '', errors: {}, loading: false })
const modalData = ref({ instance_id: null, version_id: null, config_data: {} })

const availableVersions = computed(() => {
    if (!modalData.value.instance_id) return []
    return allVersions.value.filter(v => v.instance_id === modalData.value.instance_id)
})

const getKeyCount = (configDataStr) => {
    if (!configDataStr) return 0;
    try {
        const parsed = JSON.parse(configDataStr)
        return Object.keys(parsed).length
    } catch (e) {
        return 0
    }
}

const loadData = async () => {
  loading.value = true
  error.value = ''
  try {
    const response = await adminApi.getRemoteConfigs()
    if (response.success) {
      configs.value = response.configs
    }
    
    const instRes = await adminApi.getInstances()
    if (instRes.success) {
        instances.value = instRes.instances
    }
    
    const verRes = await api.get('/admin/versions')
    if (verRes.success) {
        allVersions.value = verRes.versions
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to load configs'
  } finally {
    loading.value = false
  }
}

const confirmDelete = (config) => {
  deletingConfig.value = config
}

const executeDelete = async () => {
  if (!deletingConfig.value) return
  try {
    const response = await adminApi.deleteRemoteConfig(deletingConfig.value.id)
    if (response.success) {
      await loadData()
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to delete config'
  } finally {
    deletingConfig.value = null
  }
}

const openCreateModal = () => {
    modalData.value = { instance_id: null, version_id: null, config_data: {} }
    modalState.value = { isOpen: true, error: '', errors: {}, loading: false }
}

const onInstanceChange = () => {
    modalData.value.version_id = null
}

const handleFileUpload = (event) => {
    const file = event.target.files[0]
    if (!file) return
    
    const reader = new FileReader()
    reader.onload = (e) => {
        try {
            const json = JSON.parse(e.target.result)
            modalData.value.config_data = json
        } catch (err) {
            modalState.value.error = 'Invalid JSON file selected.'
        }
    }
    reader.readAsText(file)
}

const closeModal = () => {
    modalState.value.isOpen = false
}

const submitModal = async () => {
    modalState.value.error = ''
    modalState.value.errors = {}
    modalState.value.loading = true
    try {
        await adminApi.createRemoteConfig(modalData.value)
        closeModal()
        await loadData()
    } catch (err) {
        modalState.value.error = err.response?.data?.message || 'Action failed'
        if (err.response?.data?.errors) {
            modalState.value.errors = err.response.data.errors
        }
    } finally {
        modalState.value.loading = false
    }
}

onMounted(() => {
  loadData()
})
</script>

<style scoped>
.form-select {
    appearance: none;
    -webkit-appearance: none;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
    background-position: right 0.5rem center;
    background-repeat: no-repeat;
    background-size: 1.5em 1.5em;
    padding-right: 2.5rem;
}
</style>
