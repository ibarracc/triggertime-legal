<template>
  <div class="admin-versions">
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold font-heading">App Versions</h1>
      <AppButton @click="openCreateModal" size="sm">Add Version</AppButton>
    </div>

    <!-- Error State -->
    <div v-if="error" class="bg-red-500/10 border border-red-500/20 text-red-500 p-4 rounded-xl mb-6">
      {{ error }}
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center p-12">
      <div class="animate-spin h-8 w-8 border-4 border-[var(--primary)] border-t-transparent rounded-full"></div>
    </div>

    <!-- Versions Table -->
    <div v-else class="table-card mt-6 mb-6">
      <div class="table-wrapper">
        <table class="admin-table">
          <thead>
            <tr>
              <th>Instance</th>
              <th>Version Name</th>
              <th>Status</th>
              <th class="text-right">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="version in versions" :key="version.id" class="hover:bg-white/5 transition-colors group">
              <td>
                <div class="font-medium text-sm text-primary">
                  {{ version.instance ? version.instance.name : 'Unknown Instance' }}
                </div>
              </td>
              <td>
                <div class="font-medium text-secondary">
                  {{ version.version }}
                </div>
              </td>
              <td>
                <span v-if="!version.disabled" class="badge badge-green">Active</span>
                <span v-else class="badge badge-gray">Inactive</span>
              </td>
              <td class="p-4 text-right">
                <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                  <AppButton variant="secondary" size="sm" @click="openEditModal(version)">Edit</AppButton>
                  <AppButton variant="secondary" size="sm" @click="confirmDelete(version)" class="text-red-400 hover:text-red-300 border-red-500/30 hover:bg-red-500/10">
                    <svg class="w-4 h-4 mr-2 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    Delete
                  </AppButton>
                </div>
              </td>
            </tr>
            <tr v-if="versions.length === 0">
              <td colspan="5" class="p-8 text-center text-secondary">No versions found.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <AppModal :isOpen="!!deletingVersion" title="Delete Version" @close="deletingVersion = null">
      <p class="text-[var(--text-secondary)] mb-6">Are you sure you want to delete version <strong>{{ deletingVersion?.version }}</strong>? This might affect remote configs linked to it.</p>
      <div class="flex gap-3 justify-end mt-4">
        <AppButton variant="secondary" @click="deletingVersion = null">Cancel</AppButton>
        <button @click="executeDelete" class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg font-medium transition-colors">
          Confirm Delete
        </button>
      </div>
    </AppModal>

    <!-- Edit/Create Modal -->
    <AppModal :isOpen="modalState.isOpen" :title="modalState.isEdit ? 'Edit Version' : 'Create Version'" @close="closeModal">
      <div v-if="modalState.error" class="alert alert-danger">
        {{ modalState.error }}
      </div>
      
      <div class="form-group">
        <label class="form-label">Instance</label>
        <select v-model="modalData.instance_id" class="form-select text-black">
          <option :value="null">-- Select Instance --</option>
          <option v-for="inst in instances" :key="inst.id" :value="inst.id">{{ inst.name }}</option>
        </select>
        <div v-if="modalState.errors?.instance_id?.[0]" class="text-red-500 text-xs mt-1">{{ modalState.errors.instance_id[0] }}</div>
      </div>

      <div class="form-group mt-4">
        <label class="form-label">Version Name</label>
        <input type="text" v-model="modalData.version" placeholder="1.0.0" class="form-input" />
        <div v-if="modalState.errors?.version?.[0]" class="text-red-500 text-xs mt-1">{{ modalState.errors.version[0] }}</div>
      </div>

      <div class="mb-4 mt-4 flex items-center gap-2">
        <input type="checkbox" id="active" v-model="modalData.active" class="w-4 h-4 rounded bg-[var(--bg-card)] border-[var(--border-subtle)] focus:ring-[var(--primary)] text-[var(--primary)]" />
        <label for="active" class="text-sm font-medium text-[var(--text-primary)]">Active</label>
      </div>
      
      <div class="flex gap-3 justify-end mt-6">
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
import AppModal from '@/components/ui/AppModal.vue'

const loading = ref(true)
const error = ref('')
const versions = ref([])
const instances = ref([])

const deletingVersion = ref(null)
const modalState = ref({ isOpen: false, isEdit: false, error: '', errors: {}, loading: false })
const modalData = ref({ id: null, instance_id: null, version: '', active: true })

const loadData = async () => {
  loading.value = true
  error.value = ''
  try {
    const response = await adminApi.getVersions()
    if (response.success) {
      versions.value = response.versions
    }
    
    const instRes = await adminApi.getInstances()
    if (instRes.success) {
        instances.value = instRes.instances
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to load versions'
  } finally {
    loading.value = false
  }
}

const confirmDelete = (version) => {
  deletingVersion.value = version
}

const executeDelete = async () => {
  if (!deletingVersion.value) return
  try {
    const response = await adminApi.deleteVersion(deletingVersion.value.id)
    if (response.success) {
      await loadData()
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to delete version'
  } finally {
    deletingVersion.value = null
  }
}

const openEditModal = (versionObj) => {
    modalData.value = { 
        id: versionObj.id, 
        instance_id: versionObj.instance_id, 
        version: versionObj.version,
        active: !versionObj.disabled
    }
    modalState.value = { isOpen: true, isEdit: true, error: '', errors: {}, loading: false }
}

const openCreateModal = () => {
    modalData.value = { id: null, instance_id: null, version: '', active: true }
    modalState.value = { isOpen: true, isEdit: false, error: '', errors: {}, loading: false }
}

const closeModal = () => {
    modalState.value.isOpen = false
}

const submitModal = async () => {
    modalState.value.error = ''
    modalState.value.errors = {}
    modalState.value.loading = true
    try {
        const payload = { ...modalData.value }
        // API expects disabled field, not active
        payload.disabled = !payload.active;
        delete payload.active;

        if (modalState.value.isEdit) {
            await adminApi.updateVersion(payload.id, payload)
        } else {
            await adminApi.createVersion(payload)
        }
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
