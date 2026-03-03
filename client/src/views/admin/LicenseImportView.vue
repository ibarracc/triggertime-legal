<template>
  <div class="admin-license-import">
    <div class="flex justify-between items-center mb-6">
      <div class="flex items-center gap-4">
        <AppButton variant="ghost" size="sm" @click="$router.push('/admin/licenses')">
          <svg class="icon mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
          </svg>
          Back
        </AppButton>
        <h1 class="text-2xl font-bold font-heading">Import Licenses</h1>
      </div>
    </div>

    <div class="max-w-2xl mx-auto">
      <AppCard>
        <div class="import-section">
          <h3 class="import-section__title">1. Select Target Instance</h3>
          <p class="import-section__desc">Choose the application instance where these licenses will be loaded.</p>

          <select
            v-model="selectedInstanceId"
            class="import-select"
            required
          >
            <option value="" disabled>-- Select an Instance --</option>
            <option v-for="inst in instances" :key="inst.id" :value="inst.id">
              {{ inst.name }}
            </option>
          </select>
        </div>

        <div class="import-section">
          <h3 class="import-section__title">2. Upload CSV File</h3>
          <p class="import-section__desc">Upload a CSV containing <code>email</code> and <code>name</code> columns.</p>

          <div
            class="upload-zone"
            :class="{ 'upload-zone--has-file': selectedFile }"
            @dragover.prevent
            @drop.prevent="handleDrop"
          >
            <input
              type="file"
              ref="fileInput"
              style="display: none;"
              accept=".csv"
              @change="handleFileSelect"
            >

            <div v-if="!selectedFile">
              <svg class="upload-zone__icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
              </svg>
              <p class="upload-zone__text">Drag & drop your CSV file here, or</p>
              <AppButton variant="secondary" size="sm" @click="$refs.fileInput.click()">Browse Files</AppButton>
            </div>

            <div v-else class="upload-zone__file">
              <svg class="upload-zone__icon upload-zone__icon--success" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <div class="font-medium">{{ selectedFile.name }}</div>
              <div class="upload-zone__filesize">{{ (selectedFile.size / 1024).toFixed(1) }} KB</div>
              <button @click="clearFile" class="upload-zone__change">Select different file</button>
            </div>
          </div>
        </div>

        <div v-if="error" class="import-alert import-alert--error">
          {{ error }}
        </div>

        <div v-if="success" class="import-alert import-alert--success">
          <span>{{ success }}</span>
          <AppButton size="sm" @click="$router.push('/admin/licenses')">View Licenses</AppButton>
        </div>

        <div class="import-actions">
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

const handleDrop = (e) => {
  const file = e.dataTransfer.files[0]
  if (file && file.name.endsWith('.csv')) {
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

<style scoped>
.import-section {
  margin-bottom: 2rem;
}

.import-section__title {
  font-size: 1rem;
  font-weight: 600;
  margin-bottom: 0.375rem;
  color: var(--text-primary);
}

.import-section__desc {
  font-size: 0.8125rem;
  color: var(--text-secondary);
  margin-bottom: 1rem;
}

.import-section__desc code {
  background: rgba(255, 255, 255, 0.08);
  padding: 0.125rem 0.375rem;
  border-radius: 4px;
  font-size: 0.75rem;
  color: var(--primary);
  border: 1px solid rgba(255, 255, 255, 0.06);
}

.import-select {
  width: 100%;
  background-color: var(--bg-elevated);
  border: 1px solid var(--border-subtle);
  color: var(--text-primary);
  font-size: 0.875rem;
  border-radius: 12px;
  padding: 0.75rem 1rem;
  transition: border-color 0.2s;
  appearance: none;
  -webkit-appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='none' stroke='%23888' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M4 6l4 4 4-4'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 0.75rem center;
  cursor: pointer;
}

.import-select:focus {
  outline: none;
  border-color: var(--primary);
  box-shadow: 0 0 0 1px rgba(193, 255, 114, 0.3);
}

.import-select option {
  background: var(--bg-elevated);
  color: var(--text-primary);
}

.upload-zone {
  border: 2px dashed var(--border-subtle);
  border-radius: 12px;
  padding: 2.5rem 1.5rem;
  text-align: center;
  transition: all 0.2s ease;
  background: rgba(255, 255, 255, 0.02);
  cursor: pointer;
}

.upload-zone:hover {
  border-color: rgba(193, 255, 114, 0.3);
  background: rgba(193, 255, 114, 0.03);
}

.upload-zone--has-file {
  border-color: rgba(193, 255, 114, 0.4);
  background: rgba(193, 255, 114, 0.04);
  border-style: solid;
}

.upload-zone__icon {
  width: 2.5rem;
  height: 2.5rem;
  margin: 0 auto 1rem;
  color: var(--text-secondary);
  opacity: 0.6;
}

.upload-zone__icon--success {
  color: var(--primary);
  opacity: 1;
}

.upload-zone__text {
  font-size: 0.8125rem;
  color: var(--text-secondary);
  margin-bottom: 0.75rem;
}

.upload-zone__file {
  display: flex;
  flex-direction: column;
  align-items: center;
}

.upload-zone__filesize {
  font-size: 0.75rem;
  color: var(--text-secondary);
  margin-top: 0.25rem;
}

.upload-zone__change {
  font-size: 0.8125rem;
  color: var(--primary);
  background: none;
  border: none;
  cursor: pointer;
  margin-top: 0.75rem;
  opacity: 0.8;
  transition: opacity 0.2s;
}

.upload-zone__change:hover {
  opacity: 1;
  text-decoration: underline;
}

.import-alert {
  padding: 0.875rem 1rem;
  border-radius: 12px;
  margin-bottom: 1.5rem;
  font-size: 0.8125rem;
}

.import-alert--error {
  background: rgba(239, 68, 68, 0.1);
  border: 1px solid rgba(239, 68, 68, 0.2);
  color: #f87171;
}

.import-alert--success {
  background: rgba(34, 197, 94, 0.1);
  border: 1px solid rgba(34, 197, 94, 0.2);
  color: #4ade80;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.import-actions {
  border-top: 1px solid var(--border-subtle);
  padding-top: 1.5rem;
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  margin-top: 0.5rem;
}
</style>
