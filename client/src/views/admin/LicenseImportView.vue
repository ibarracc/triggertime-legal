<template>
  <div class="admin-license-import">
    <div class="flex justify-between items-center mb-6">
      <div class="flex items-center gap-4">
        <button @click="$router.push('/admin/licenses')" class="btn btn-secondary p-2">
          <svg class="icon mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
          </svg>
          Back
        </button>
        <h1 class="text-2xl font-bold font-heading">Import Licenses</h1>
      </div>
    </div>

    <div class="max-w-2xl mx-auto">
      <AppCard>
        <div class="mb-6">
          <h3 class="text-lg font-semibold mb-2">1. Select Target Instance</h3>
          <p class="text-sm text-[var(--text-secondary)] mb-4">Choose the application instance where these licenses will be loaded.</p>
          
          <select 
            v-model="selectedInstanceId" 
            class="form-select"
            required
          >
            <option value="" disabled>-- Select an Instance --</option>
            <option v-for="inst in instances" :key="inst.id" :value="inst.id">
              {{ inst.name }}
            </option>
          </select>
        </div>

        <div class="mb-8">
          <h3 class="text-lg font-semibold mb-2">2. Upload CSV File</h3>
          <p class="text-sm text-[var(--text-secondary)] mb-4">Upload a CSV containing <code>email</code> and <code>name</code> columns.</p>
          
          <div 
            class="border-2 border-dashed border-[var(--border-subtle)] p-8 text-center transition-colors"
            :style="{ borderRadius: '12px', borderColor: selectedFile ? 'var(--primary)' : 'var(--border-subtle)', backgroundColor: selectedFile ? 'rgba(193, 255, 114, 0.05)' : 'rgba(0,0,0,0.1)' }"
          >
            <input 
              type="file" 
              ref="fileInput" 
              style="display: none;"
              accept=".csv" 
              @change="handleFileSelect"
            >
            
            <div v-if="!selectedFile">
              <svg class="mx-auto icon-lg text-secondary mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
              </svg>
              <AppButton variant="secondary" @click="$refs.fileInput.click()">Browse Files</AppButton>
            </div>
            
            <div v-else class="flex flex-col items-center">
              <svg class="icon-lg text-primary mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <div class="font-medium mb-1">{{ selectedFile.name }}</div>
              <div class="text-xs text-secondary mb-4">{{ (selectedFile.size / 1024).toFixed(1) }} KB</div>
              <button @click="clearFile" class="text-sm">Select different file</button>
            </div>
          </div>
        </div>

        <div v-if="error" class="bg-red-500/10 border border-red-500/20 text-red-500 p-4 rounded-xl mb-6 text-sm">
          {{ error }}
        </div>

        <div v-if="success" class="bg-green-500/10 border border-green-500/20 text-green-400 p-4 rounded-xl mb-6 text-sm flex justify-between items-center">
          <span>{{ success }}</span>
          <AppButton size="sm" @click="$router.push('/admin/licenses')">View Licenses</AppButton>
        </div>

        <div class="border-t pt-6 flex justify-end gap-4 mt-8">
          <AppButton variant="secondary" @click="$router.push('/admin/licenses')">Cancel</AppButton>
          <AppButton 
            @click="submitImport" 
            :disabled="!selectedInstanceId || !selectedFile || loading"
            :loading="loading"
          >
            Import Licenses
          </AppButton>
        </div>
      </AppCard>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { adminApi } from '@/api/admin'
import AppCard from '@/components/ui/AppCard.vue'
import AppButton from '@/components/ui/AppButton.vue'

const router = useRouter()
const fileInput = ref(null)
const instances = ref([])
const selectedInstanceId = ref('')
const selectedFile = ref(null)

const loading = ref(false)
const error = ref('')
const success = ref('')

const fetchInstances = async () => {
  try {
    const res = await adminApi.getInstances()
    if (res.success) {
      instances.value = res.instances
    }
  } catch (err) {
    error.value = 'Failed to load instances'
  }
}

onMounted(() => {
  fetchInstances()
})

const handleFileSelect = (e) => {
  const file = e.target.files[0]
  if (file) {
    selectedFile.value = file
    error.value = ''
    success.value = ''
  }
}

const clearFile = () => {
  selectedFile.value = null
  if (fileInput.value) {
    fileInput.value.value = ''
  }
}

const submitImport = async () => {
  if (!selectedInstanceId.value) {
    error.value = 'Please select a target instance'
    return
  }
  if (!selectedFile.value) {
    error.value = 'Please select a CSV file'
    return
  }

  error.value = ''
  success.value = ''
  loading.value = true

  const formData = new FormData()
  formData.append('file', selectedFile.value)
  formData.append('instance_id', selectedInstanceId.value)

  try {
    const response = await adminApi.importLicenses(formData)
    if (response.success) {
      success.value = response.message || 'Import successful'
      clearFile()
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to upload CSV'
  } finally {
    loading.value = false
  }
}
</script>
