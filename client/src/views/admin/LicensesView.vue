<template>
  <div class="admin-licenses">
    <h1 class="text-2xl font-bold font-heading mb-6">Activation Licenses</h1>
    
    <div class="flex flex-row justify-between items-center gap-4 mb-8">
      <!-- Instance Filter Dropdown -->
      <div class="flex-1 max-w-[300px]">
        <select v-model="selectedInstanceId" class="form-select w-full">
          <option value="">All Instances</option>
          <option v-for="inst in instances" :key="inst.id" :value="inst.id">
            {{ inst.name }}
          </option>
        </select>
      </div>
      
      <div class="flex items-center gap-3">
        <AppButton variant="secondary" @click="downloadCsv" size="sm" :disabled="filteredLicenses.length === 0">
          <svg class="w-4 h-4 mr-2 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
          Download CSV
        </AppButton>
        <AppButton @click="$router.push('/admin/licenses/import')" size="sm">
          <svg class="w-4 h-4 mr-2 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
          Import CSV
        </AppButton>
      </div>
    </div>

    <!-- Error State -->
    <div v-if="error" class="bg-red-500/10 border border-red-500/20 text-red-500 p-4 rounded-xl mb-6">
      {{ error }}
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center p-12">
      <div class="animate-spin h-8 w-8 border-4 border-[var(--primary)] border-t-transparent rounded-full"></div>
    </div>

    <!-- Licenses Table -->
    <div v-else class="table-card mt-6 mb-6">
      <div class="table-wrapper">
        <table class="admin-table">
          <thead>
            <tr>
              <th>License</th>
              <th>Customer</th>
              <th>Instance</th>
              <th>Status</th>
              <th class="text-right">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="lic in filteredLicenses" :key="lic.id" class="hover:bg-white/5 transition-colors group">
              <td>
                <div class="font-mono text-sm tracking-wider text-primary">{{ lic.license_number }}</div>
              </td>
              <td>
                <div class="font-medium">{{ lic.name || 'N/A' }}</div>
                <div class="text-xs text-secondary">{{ lic.email }}</div>
              </td>
              <td>
                <div class="text-sm">{{ lic.instance?.name || 'Unknown' }}</div>
              </td>
              <td>
                <span v-if="lic.deleted_at" class="badge badge-red">Disabled</span>
                <span v-else-if="lic.used" class="badge badge-blue">Claimed</span>
                <span v-else class="badge badge-green">Ready</span>
              </td>
              <td class="text-right">
                <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                  <AppButton variant="secondary" size="sm" @click="toggleAccess(lic)">
                    {{ lic.deleted_at ? 'Enable' : 'Disable' }}
                  </AppButton>
                  <AppButton variant="secondary" size="sm" @click="openEditModal(lic)">
                    Edit
                  </AppButton>
                </div>
              </td>
            </tr>
            <tr v-if="filteredLicenses.length === 0">
              <td colspan="5" class="p-8 text-center text-secondary">
                {{ licenses.length === 0 ? 'No licenses found.' : 'No licenses match this filter.' }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
    
    <!-- Edit Modal -->
    <AppModal :isOpen="!!editingLicense" title="Edit License" @close="editingLicense = null">
      <div v-if="editError" class="alert alert-danger">
        {{ editError }}
      </div>
      <div class="form-group">
          <label class="form-label">Customer Name</label>
          <AppInput v-model="editingData.name" placeholder="John Doe" :error="editErrors?.name?.[0]" />
      </div>
      <div class="form-group mb-6">
          <label class="form-label">Customer Email</label>
          <AppInput v-model="editingData.email" type="email" placeholder="john@example.com" :error="editErrors?.email?.[0]" />
      </div>
      <div class="flex gap-3 justify-end mt-4">
        <AppButton variant="secondary" @click="editingLicense = null">Cancel</AppButton>
        <AppButton @click="submitEdit" :loading="editLoading">Save Changes</AppButton>
      </div>
    </AppModal>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { adminApi } from '@/api/admin'
import AppButton from '@/components/ui/AppButton.vue'
import AppInput from '@/components/ui/AppInput.vue'
import AppModal from '@/components/ui/AppModal.vue'

const loading = ref(true)
const error = ref('')
const licenses = ref([])
const instances = ref([])
const selectedInstanceId = ref('')

const editingLicense = ref(null)
const editingData = ref({ name: '', email: '' })
const editError = ref('')
const editErrors = ref({})
const editLoading = ref(false)

const filteredLicenses = computed(() => {
  if (!selectedInstanceId.value) return licenses.value
  return licenses.value.filter(l => l.instance_id === selectedInstanceId.value)
})

const loadData = async () => {
  loading.value = true
  error.value = ''
  try {
    const [licRes, instRes] = await Promise.all([
        adminApi.getLicenses(),
        adminApi.getInstances()
    ])
    if (licRes.success) licenses.value = licRes.licenses
    if (instRes.success) instances.value = instRes.instances
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to load data'
  } finally {
    loading.value = false
  }
}

const toggleAccess = async (license) => {
  try {
    const response = await adminApi.toggleLicenseAccess(license.id)
    if (response.success) {
      // Opt: update locally instead of full refetch
      license.deleted_at = license.deleted_at ? null : new Date().toISOString()
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to toggle status'
  }
}

const openEditModal = (license) => {
    editError.value = ''
    editErrors.value = {}
    editingLicense.value = license
    editingData.value = { name: license.name || '', email: license.email || '' }
}

const submitEdit = async () => {
    editError.value = ''
    editErrors.value = {}
    editLoading.value = true
    try {
        const response = await adminApi.updateLicense(editingLicense.value.id, editingData.value)
        if (response.success) {
            editingLicense.value.name = editingData.value.name
            editingLicense.value.email = editingData.value.email
            editingLicense.value = null
        }
    } catch (err) {
        editError.value = err.response?.data?.message || 'Update failed'
        if (err.response?.data?.errors) {
            editErrors.value = err.response.data.errors
        }
    } finally {
        editLoading.value = false
    }
}

const downloadCsv = () => {
    if (filteredLicenses.value.length === 0) return

    const headers = ['Email', 'Name', 'License Number', 'Instance Name', 'Status', 'Claimed At']
    const csvRows = [headers.join(',')]

    for (const lic of filteredLicenses.value) {
        const status = lic.deleted_at ? 'Disabled' : (lic.used ? 'Claimed' : 'Ready')
        const row = [
            `"${lic.email || ''}"`,
            `"${lic.name || ''}"`,
            lic.license_number,
            `"${lic.instance?.name || ''}"`,
            status,
            lic.used ? `"${new Date(lic.used).toLocaleString()}"` : ''
        ]
        csvRows.push(row.join(','))
    }

    const csvData = csvRows.join('\n')
    const blob = new Blob([csvData], { type: 'text/csv;charset=utf-8;' })
    const url = URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.setAttribute('href', url)
    link.setAttribute('download', `licenses_export_${new Date().toISOString().split('T')[0]}.csv`)
    link.style.visibility = 'hidden'
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
}

onMounted(() => {
  loadData()
})
</script>
