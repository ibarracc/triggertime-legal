<template>
  <div class="admin-instances">
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold font-heading">App Instances</h1>
      <AppButton @click="openCreateModal" size="sm">Add Instance</AppButton>
    </div>

    <!-- Error State -->
    <div v-if="error" class="bg-red-500/10 border border-red-500/20 text-red-500 p-4 rounded-xl mb-6">
      {{ error }}
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center p-12">
      <div class="animate-spin h-8 w-8 border-4 border-[var(--primary)] border-t-transparent rounded-full"></div>
    </div>

    <!-- Instances Table -->
    <div v-else class="table-card mt-6 mb-6">
      <div class="table-wrapper">
        <table class="admin-table">
          <thead>
            <tr>
              <th>Name (Identifier)</th>
              <th>Club Admin</th>
              <th>API Gate</th>
              <th>Flags</th>
              <th class="text-right">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="inst in instances" :key="inst.id" class="hover:bg-white/5 transition-colors group">
              <td>
                <div class="font-medium text-sm text-primary">{{ inst.name }}</div>
                <div class="text-xs text-secondary">{{ inst.id }}</div>
              </td>
              <td class="text-xs">
                <div v-if="inst.club_admin" class="font-medium text-sm text-primary">
                  <span v-if="inst.club_admin.first_name || inst.club_admin.last_name">{{ inst.club_admin.first_name }} {{ inst.club_admin.last_name }}</span>
                  <span v-else>{{ inst.club_admin.email }}</span>
                </div>
                <div v-else class="text-secondary italic">Unassigned</div>
              </td>
              <td>
                <span v-if="!inst.is_active" class="badge badge-red">Disabled</span>
                <span v-else class="badge badge-green">Active</span>
              </td>
              <td>
                <span v-if="inst.deleted_at" class="badge badge-red">Deleted</span>
              </td>
              <td class="p-4 text-right">
                <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                  <AppButton variant="secondary" size="sm" @click="openEditModal(inst)">Edit</AppButton>
                  <AppButton v-if="!inst.deleted_at" variant="secondary" size="sm" @click="confirmDelete(inst)" class="text-red-400 hover:text-red-300 border-red-500/30 hover:bg-red-500/10">
                    <svg class="w-4 h-4 mr-2 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    Delete
                  </AppButton>
                </div>
              </td>
            </tr>
            <tr v-if="instances.length === 0">
              <td colspan="5" class="p-8 text-center text-secondary">No instances found.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <AppModal :isOpen="!!deletingInstance" title="Delete Instance" @close="deletingInstance = null">
      <p class="text-[var(--text-secondary)] mb-6">Are you sure you want to soft delete instance <strong>{{ deletingInstance?.name }}</strong>?</p>
      <div class="flex gap-3 justify-end mt-4">
        <AppButton variant="secondary" @click="deletingInstance = null">Cancel</AppButton>
        <button @click="executeDelete" class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg font-medium transition-colors">
          Confirm Delete
        </button>
      </div>
    </AppModal>

    <!-- Edit/Create Modal -->
    <AppModal :isOpen="modalState.isOpen" :title="modalState.isEdit ? 'Edit Instance' : 'Create Instance'" @close="closeModal">
      <div v-if="modalState.error" class="alert alert-danger">
        {{ modalState.error }}
      </div>
      <div class="form-group">
        <label class="form-label">Instance Name</label>
        <AppInput v-model="modalData.name" placeholder="com.example.app" :error="modalState.errors?.name?.[0]" />
      </div>

      <div class="mb-4 flex items-center gap-2">
        <input type="checkbox" id="is_active" v-model="modalData.is_active" class="w-4 h-4 rounded bg-[var(--bg-card)] border-[var(--border-subtle)] focus:ring-[var(--primary)] text-[var(--primary)]" />
        <label for="is_active" class="text-sm font-medium text-[var(--text-primary)]">Active Status</label>
      </div>
      
      <div class="flex gap-3 justify-end mt-4">
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
const instances = ref([])

const deletingInstance = ref(null)
const modalState = ref({ isOpen: false, isEdit: false, error: '', errors: {}, loading: false })
const modalData = ref({ id: null, name: '', is_active: true })

const loadInstances = async () => {
  loading.value = true
  error.value = ''
  try {
    const response = await adminApi.getInstances()
    if (response.success) {
      instances.value = response.instances
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to load instances'
  } finally {
    loading.value = false
  }
}

const confirmDelete = (inst) => {
  deletingInstance.value = inst
}

const executeDelete = async () => {
  if (!deletingInstance.value) return
  try {
    const response = await adminApi.deleteInstance(deletingInstance.value.id)
    if (response.success) {
      await loadInstances()
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to delete instance'
  } finally {
    deletingInstance.value = null
  }
}

const openEditModal = (inst) => {
    modalData.value = { id: inst.id, name: inst.name, is_active: inst.is_active }
    modalState.value = { isOpen: true, isEdit: true, error: '', errors: {}, loading: false }
}

const openCreateModal = () => {
    modalData.value = { id: null, name: '', is_active: true }
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
        if (modalState.value.isEdit) {
            await adminApi.updateInstance(modalData.value.id, { name: modalData.value.name, is_active: modalData.value.is_active })
        } else {
            await adminApi.createInstance({ name: modalData.value.name, is_active: modalData.value.is_active })
        }
        closeModal()
        await loadInstances()
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
  loadInstances()
})
</script>
