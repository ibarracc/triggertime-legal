<template>
  <div class="admin-config-detail">
    <div class="flex items-center justify-between mb-8">
      <div class="flex items-center gap-4">
        <router-link to="/admin/remote-configs" class="text-secondary hover:text-primary transition-colors">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
          </svg>
        </router-link>
        <h1 class="text-2xl font-bold font-heading">Remote Config Detail</h1>
      </div>
      
      <div class="flex gap-2">
        <AppButton v-if="!isComparing" variant="secondary" size="sm" @click="downloadJson">
           <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
           Download JSON
        </AppButton>
      </div>
    </div>

    <div v-if="loading" class="flex justify-center p-12">
      <div class="animate-spin h-8 w-8 border-4 border-[var(--primary)] border-t-transparent rounded-full"></div>
    </div>
    
    <div v-else-if="error" class="bg-red-500/10 border border-red-500/20 text-red-500 p-4 rounded-xl mb-6">
      {{ error }}
    </div>

    <div v-else class="grid grid-cols-1 lg:grid-cols-4 gap-4 lg:gap-5">
      
      <!-- Metadata Sidebar -->
      <div class="lg:col-span-1 space-y-6">
        <div class="glass-card">
          <h2 class="text-lg font-semibold mb-4 text-primary">Metadata</h2>
          
          <div class="space-y-4">
            <div>
              <div class="text-xs text-secondary uppercase tracking-wider mb-1">Instance</div>
              <div class="font-medium truncate" :title="config.instance ? config.instance.name : config.app_instance">
                  {{ config.instance ? config.instance.name : config.app_instance }}
              </div>
            </div>
            
            <div>
              <div class="text-xs text-secondary uppercase tracking-wider mb-1">Version</div>
              <div class="font-medium" v-if="config.version">v{{ config.version.version }}</div>
              <div class="font-medium text-secondary italic" v-else>Global (All Versions)</div>
            </div>
            
            <div class="pt-4 border-t border-[var(--border-subtle)]">
                <div class="text-xs text-secondary mb-1">Created</div>
                <div class="text-sm font-mono text-[10px]">{{ new Date(config.created).toLocaleString() }}</div>
            </div>
            <div>
                <div class="text-xs text-secondary mb-1">Last Modified</div>
                <div class="text-sm font-mono text-[10px]">{{ new Date(config.modified).toLocaleString() }}</div>
            </div>
          </div>
        </div>
        
        <!-- Actions Card -->
        <div class="glass-card mt-8">
          <div class="flex items-center justify-between mb-3">
            <h2 class="text-lg font-semibold text-primary">Update Config</h2>
            <div class="relative">
              <input type="file" ref="fileInputRef" accept=".json" class="hidden" @change="handleFileUpload" />
              <AppButton variant="primary" size="sm" @click="fileInputRef.click()">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                Upload JSON
              </AppButton>
            </div>
          </div>
          <p class="text-[11px] text-secondary leading-relaxed">Upload a new JSON file to replace the current configuration. You'll be able to review differences before saving.</p>
        </div>
      </div>

      <!-- Main Config View -->
      <div class="lg:col-span-3 min-w-0 overflow-hidden">
        
        <!-- View Mode -->
        <div v-if="!isComparing" class="glass-card overflow-hidden h-full">
            <div class="flex justify-between items-center mb-6 border-b border-[var(--border-subtle)] pb-4">
                <h2 class="text-lg font-semibold text-primary">Key/Value Configuration</h2>
            </div>
            
            <table class="config-table">
                <colgroup>
                    <col class="key-col" />
                    <col class="value-col" />
                    <col class="actions-col" />
                </colgroup>
                <thead>
                    <tr>
                        <th>Key</th>
                        <th>Value</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(value, key) in currentConfigData" :key="key">
                        <td class="key-cell">{{ key }}</td>

                        <!-- Edit mode -->
                        <td v-if="editingKey === key" class="edit-mode-cell">
                            <!-- Boolean toggle -->
                            <label v-if="typeof currentConfigData[key] === 'boolean'" class="boolean-toggle">
                                <input type="checkbox" v-model="editValue" @keydown.escape="cancelEdit" />
                                <span class="toggle-label" :class="editValue ? 'text-green-400' : 'text-red-400'">{{ editValue }}</span>
                            </label>
                            <!-- JSON textarea -->
                            <div v-else-if="detectValueType(currentConfigData[key]) === 'json'" class="json-edit-wrapper">
                                <textarea v-model="editValue" class="edit-textarea" rows="6" spellcheck="false" @keydown.escape="cancelEdit"></textarea>
                                <div v-if="editJsonError" class="edit-json-error">{{ editJsonError }}</div>
                            </div>
                            <!-- String/Number input -->
                            <input v-else type="text" v-model="editValue" class="edit-input"
                                @keydown.enter="saveEdit" @keydown.escape="cancelEdit" />
                        </td>

                        <!-- View mode -->
                        <td v-else class="value-cell">
                            <pre v-if="typeof value === 'object'" class="value-pre">{{ JSON.stringify(value, null, 2) }}</pre>
                            <span v-else-if="typeof value === 'boolean'" :class="value ? 'text-green-400 font-bold' : 'text-red-400 font-bold'">{{ value }}</span>
                            <span v-else>{{ value }}</span>
                        </td>

                        <!-- Actions -->
                        <td class="actions-cell">
                            <div v-if="editingKey === key" class="flex gap-1">
                                <button class="action-btn action-btn-save" @click="saveEdit" :disabled="savingKey === key" title="Save">
                                    <svg v-if="savingKey === key" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                    <svg v-else class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                </button>
                                <button class="action-btn" @click="cancelEdit" title="Cancel">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                </button>
                            </div>
                            <div v-else-if="deletingKey === key" class="delete-confirm">
                                <span class="delete-confirm-label">Delete?</span>
                                <button class="delete-confirm-btn delete-confirm-yes" @click="executeDelete" :disabled="savingKey === key">Yes</button>
                                <button class="delete-confirm-btn delete-confirm-no" @click="cancelDelete">No</button>
                            </div>
                            <div v-else class="flex gap-2">
                                <button class="action-btn" @click="startEdit(key)" title="Edit">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                </button>
                                <button class="action-btn action-btn-danger" @click="confirmDelete(key)" title="Delete">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="Object.keys(currentConfigData).length === 0">
                        <td colspan="3" class="empty-cell">No keys defined.</td>
                    </tr>
                </tbody>
            </table>

            <!-- Add Key -->
            <div v-if="!addingKey" class="add-key-trigger">
                <button class="add-key-btn" @click="startAdd">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Add Key
                </button>
            </div>
            <div v-else class="add-key-form">
                <div class="add-key-fields">
                    <div class="add-key-field">
                        <label class="add-key-label">Key Name</label>
                        <input v-model="newKeyName" type="text" class="edit-input" placeholder="key_name"
                            @keydown.escape="cancelAdd" />
                    </div>
                    <div class="add-key-field">
                        <label class="add-key-label">Type</label>
                        <select v-model="newKeyType" class="edit-input">
                            <option value="string">String</option>
                            <option value="number">Number</option>
                            <option value="boolean">Boolean</option>
                            <option value="json">JSON</option>
                        </select>
                    </div>
                    <div class="add-key-field add-key-value-field">
                        <label class="add-key-label">Value</label>
                        <label v-if="newKeyType === 'boolean'" class="boolean-toggle">
                            <input type="checkbox" v-model="newKeyValue" />
                            <span class="toggle-label" :class="newKeyValue ? 'text-green-400' : 'text-red-400'">{{ newKeyValue }}</span>
                        </label>
                        <textarea v-else-if="newKeyType === 'json'" v-model="newKeyValue" class="edit-textarea" rows="4" placeholder="{}" spellcheck="false"></textarea>
                        <input v-else type="text" v-model="newKeyValue" class="edit-input" placeholder="value"
                            @keydown.enter="saveAdd" @keydown.escape="cancelAdd" />
                    </div>
                </div>
                <div v-if="addKeyError" class="edit-json-error mb-2">{{ addKeyError }}</div>
                <div class="flex gap-2 justify-end">
                    <AppButton variant="secondary" size="sm" @click="cancelAdd">Cancel</AppButton>
                    <AppButton size="sm" @click="saveAdd" :loading="savingKey === '__new__'">Add</AppButton>
                </div>
            </div>
        </div>
        
        <!-- Diff / Compare Mode -->
        <div v-else class="glass-card h-full border-primary/50 shadow-glow overflow-hidden">
            <div class="flex justify-between items-center mb-6 border-b border-[var(--border-subtle)] pb-4">
                <div>
                    <h2 class="text-lg font-semibold text-primary">Review Changes</h2>
                    <p class="text-sm text-secondary">Review the differences before confirming update.</p>
                </div>
                <div class="flex gap-4 text-xs font-mono bg-[var(--bg-base)] p-3 rounded-lg border border-[var(--border-subtle)]">
                    <div class="flex items-center gap-2"><span class="w-2.5 h-2.5 rounded-sm" style="background-color: #4ade80;"></span><span class="text-secondary">Added</span></div>
                    <div class="flex items-center gap-2"><span class="w-2.5 h-2.5 rounded-sm" style="background-color: #f87171;"></span><span class="text-secondary">Removed</span></div>
                    <div class="flex items-center gap-2"><span class="w-2.5 h-2.5 rounded-sm" style="background-color: #facc15;"></span><span class="text-secondary">Changed</span></div>
                </div>
            </div>
            
            <table class="diff-table">
                <colgroup>
                    <col class="diff-key-col" />
                    <col class="diff-value-col" />
                    <col class="diff-value-col" />
                </colgroup>
                <thead>
                    <tr>
                        <th>Key</th>
                        <th>Old Value</th>
                        <th>New Value</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="row in diffRows" :key="row.key" :class="row.rowClass">
                        <td class="diff-key-cell">{{ row.key }}</td>
                        <td class="diff-value-cell">
                            <div class="diff-value-inner">
                                <pre v-if="row.oldIsObject" class="value-pre">{{ row.oldValue }}</pre>
                                <span v-else-if="row.oldValue === '—'" class="text-secondary italic">—</span>
                                <span v-else-if="typeof row.oldRaw === 'boolean'" :class="row.oldRaw ? 'text-green-400 font-bold' : 'text-red-400 font-bold'">{{ row.oldValue }}</span>
                                <span v-else>{{ row.oldValue }}</span>
                            </div>
                        </td>
                        <td class="diff-value-cell">
                            <div class="diff-value-inner">
                                <pre v-if="row.newIsObject" class="value-pre">{{ row.newValue }}</pre>
                                <span v-else-if="row.newValue === '—'" class="text-secondary italic">—</span>
                                <span v-else-if="typeof row.newRaw === 'boolean'" :class="row.newRaw ? 'text-green-400 font-bold' : 'text-red-400 font-bold'">{{ row.newValue }}</span>
                                <span v-else>{{ row.newValue }}</span>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="diffRows.length === 0">
                        <td colspan="3" class="empty-cell">No differences found.</td>
                    </tr>
                </tbody>
            </table>
            
            <div class="flex gap-4 justify-end mt-6">
                <AppButton variant="secondary" @click="cancelComparison">Cancel</AppButton>
                <AppButton @click="saveChanges" :loading="saving">Confirm & Save Changes</AppButton>
            </div>
        </div>
        
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { adminApi } from '@/api/admin'
import AppButton from '@/components/ui/AppButton.vue'

const route = useRoute()
const router = useRouter()
const configId = route.params.id

const loading = ref(true)
const saving = ref(false)
const error = ref('')
const config = ref({})
const currentConfigData = ref({})

const isComparing = ref(false)
const uploadedConfigData = ref(null)

const fileInputRef = ref(null)

// Inline editing state
const editingKey = ref(null)
const editValue = ref('')
const editJsonError = ref('')
const deletingKey = ref(null)
const savingKey = ref(null)
const addingKey = ref(false)
const newKeyName = ref('')
const newKeyType = ref('string')
const newKeyValue = ref('')
const addKeyError = ref('')

watch(newKeyType, () => {
    newKeyValue.value = newKeyType.value === 'boolean' ? false : ''
    addKeyError.value = ''
})

const detectValueType = (value) => {
    if (typeof value === 'boolean') return 'boolean'
    if (typeof value === 'number') return 'number'
    if (typeof value === 'object' && value !== null) return 'json'
    return 'string'
}

const parseValueByType = (rawInput, type) => {
    switch (type) {
        case 'boolean': return rawInput
        case 'number': {
            const n = Number(rawInput)
            if (Number.isNaN(n)) throw new Error('Invalid number')
            return n
        }
        case 'json': return JSON.parse(rawInput)
        default: return String(rawInput)
    }
}

const loadConfig = async () => {
    loading.value = true
    error.value = ''
    try {
        const response = await adminApi.getRemoteConfig(configId)
        if (response.success) {
            config.value = response.config
            
            // Parse JSON String
            if (response.config.config_data) {
                try {
                    currentConfigData.value = JSON.parse(response.config.config_data)
                } catch (e) {
                    currentConfigData.value = { error: "Failed to parse JSON" }
                }
            } else {
                currentConfigData.value = {}
            }
        }
    } catch (err) {
        error.value = err.response?.data?.message || 'Failed to load config detail'
    } finally {
        loading.value = false
    }
}

const downloadJson = () => {
  const dataStr = JSON.stringify(currentConfigData.value, null, 2)
  const dataUri = 'data:application/json;charset=utf-8,'+ encodeURIComponent(dataStr)
  
  const exportFileDefaultName = `config_${config.value.instance?.name || 'export'}.json`
  
  const linkElement = document.createElement('a')
  linkElement.setAttribute('href', dataUri)
  linkElement.setAttribute('download', exportFileDefaultName)
  linkElement.click()
}

const handleFileUpload = (event) => {
    const file = event.target.files[0]
    if (!file) return
    
    const reader = new FileReader()
    reader.onload = (e) => {
        try {
            const jsonStr = e.target.result
            uploadedConfigData.value = JSON.parse(jsonStr)
            isComparing.value = true
        } catch (err) {
            error.value = 'Invalid JSON file selected.'
            setTimeout(() => { error.value = '' }, 3000)
        } finally {
            // Reset file input so same file can be uploaded again if canceled
            fileInputRef.value.value = ''
        }
    }
    reader.readAsText(file)
}

const cancelComparison = () => {
    isComparing.value = false
    uploadedConfigData.value = null
}

const startEdit = (key) => {
    cancelDelete()
    cancelAdd()
    editingKey.value = key
    const value = currentConfigData.value[key]
    const type = detectValueType(value)
    if (type === 'json') {
        editValue.value = JSON.stringify(value, null, 2)
    } else {
        editValue.value = value
    }
    editJsonError.value = ''
}

const cancelEdit = () => {
    editingKey.value = null
    editValue.value = ''
    editJsonError.value = ''
}

const saveEdit = async () => {
    const key = editingKey.value
    if (!key) return
    error.value = ''

    const type = detectValueType(currentConfigData.value[key])
    let parsedValue
    try {
        parsedValue = parseValueByType(editValue.value, type)
    } catch (e) {
        editJsonError.value = type === 'json' ? 'Invalid JSON' : 'Invalid number'
        return
    }

    // Snapshot for rollback
    const previousData = { ...currentConfigData.value }

    // Optimistic update
    currentConfigData.value = { ...currentConfigData.value, [key]: parsedValue }
    savingKey.value = key

    try {
        await adminApi.updateRemoteConfig(configId, { config_data: currentConfigData.value })
        cancelEdit()
    } catch (err) {
        currentConfigData.value = previousData
        error.value = err.response?.data?.message || 'Failed to save changes'
    } finally {
        savingKey.value = null
    }
}

const cancelAdd = () => {
    addingKey.value = false
    newKeyName.value = ''
    newKeyType.value = 'string'
    newKeyValue.value = ''
    addKeyError.value = ''
}

const startAdd = () => {
    cancelEdit()
    cancelDelete()
    addingKey.value = true
    newKeyName.value = ''
    newKeyType.value = 'string'
    newKeyValue.value = ''
    addKeyError.value = ''
}

const saveAdd = async () => {
    addKeyError.value = ''
    error.value = ''
    const keyName = newKeyName.value.trim()

    if (!keyName) {
        addKeyError.value = 'Key name is required'
        return
    }
    if (keyName in currentConfigData.value) {
        addKeyError.value = 'Key already exists'
        return
    }

    let parsedValue
    try {
        if (newKeyType.value === 'boolean') {
            parsedValue = !!newKeyValue.value
        } else {
            parsedValue = parseValueByType(newKeyValue.value || (newKeyType.value === 'json' ? '{}' : ''), newKeyType.value)
        }
    } catch (e) {
        addKeyError.value = 'Invalid JSON value'
        return
    }

    const previousData = { ...currentConfigData.value }
    currentConfigData.value = { ...currentConfigData.value, [keyName]: parsedValue }
    savingKey.value = '__new__'

    try {
        await adminApi.updateRemoteConfig(configId, { config_data: currentConfigData.value })
        cancelAdd()
    } catch (err) {
        currentConfigData.value = previousData
        error.value = err.response?.data?.message || 'Failed to add key'
    } finally {
        savingKey.value = null
    }
}

const confirmDelete = (key) => {
    cancelEdit()
    cancelAdd()
    deletingKey.value = key
}

const cancelDelete = () => {
    deletingKey.value = null
}

const executeDelete = async () => {
    const key = deletingKey.value
    if (!key) return
    error.value = ''

    const previousData = { ...currentConfigData.value }
    const updated = { ...currentConfigData.value }
    delete updated[key]
    currentConfigData.value = updated
    savingKey.value = key

    try {
        await adminApi.updateRemoteConfig(configId, { config_data: currentConfigData.value })
        cancelDelete()
    } catch (err) {
        currentConfigData.value = previousData
        error.value = err.response?.data?.message || 'Failed to delete key'
    } finally {
        savingKey.value = null
    }
}

const saveChanges = async () => {
    saving.value = true
    error.value = ''
    try {
        const payload = {
            config_data: uploadedConfigData.value
        }
        
        await adminApi.updateRemoteConfig(configId, payload)
        
        // Success
        await loadConfig()
        isComparing.value = false
        uploadedConfigData.value = null
    } catch (err) {
        error.value = err.response?.data?.message || 'Failed to save changes'
    } finally {
        saving.value = false
    }
}

// Formats a value for display in the diff table
const formatValue = (val) => {
    if (val === undefined) return '—'
    if (typeof val === 'object' && val !== null) return JSON.stringify(val, null, 2)
    return String(val)
}

// Three-column diff: Key | Old Value | New Value
const diffRows = computed(() => {
    if (!isComparing.value || !uploadedConfigData.value) return []
    
    const allKeys = new Set([
        ...Object.keys(currentConfigData.value),
        ...Object.keys(uploadedConfigData.value)
    ])
    
    return Array.from(allKeys).sort().map(key => {
        const inOld = key in currentConfigData.value
        const inNew = key in uploadedConfigData.value
        const oldRaw = inOld ? currentConfigData.value[key] : undefined
        const newRaw = inNew ? uploadedConfigData.value[key] : undefined
        const oldVal = formatValue(oldRaw)
        const newVal = formatValue(newRaw)
        
        let rowClass = ''
        if (inOld && inNew) {
            if (JSON.stringify(oldRaw) !== JSON.stringify(newRaw)) rowClass = 'diff-changed'
        } else if (inOld && !inNew) {
            rowClass = 'diff-removed'
        } else {
            rowClass = 'diff-added'
        }
        
        return {
            key,
            oldValue: oldVal,
            newValue: newVal,
            oldRaw,
            newRaw,
            oldIsObject: typeof oldRaw === 'object' && oldRaw !== null,
            newIsObject: typeof newRaw === 'object' && newRaw !== null,
            rowClass
        }
    })
})

onMounted(() => {
    loadConfig()
})
</script>

<style scoped>
/* Fixed-layout table: the value column is allowed to overflow and scroll internally,
   but will NEVER expand the table or card beyond its container width. */
.config-table {
  table-layout: fixed;
  width: 100%;
  border-collapse: collapse;
  min-width: 0;
}

.config-table .key-col {
  width: 200px;
}

.config-table .value-col {
  width: auto;
}

.config-table th {
  padding: 8px 12px;
  text-align: left;
  font-size: 11px;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: var(--text-secondary);
  font-weight: 600;
  border-bottom: 1px solid var(--border-subtle);
  background: rgba(255,255,255,0.04);
}

.config-table td.key-cell {
  padding: 10px 12px;
  font-family: monospace;
  font-size: 11px;
  color: var(--primary);
  font-weight: 600;
  vertical-align: top;
  border-bottom: 1px solid var(--border-subtle);
  word-break: break-all;
  width: 200px;
}

.config-table td.value-cell {
  padding: 10px 12px;
  font-family: monospace;
  font-size: 11px;
  color: var(--text-secondary);
  border-bottom: 1px solid var(--border-subtle);
  /* Critical: this is what prevents the cell from expanding the table */
  max-width: 0;
  overflow: hidden;
}

/* Inner wrapper enables the scroll without breaking out */
.config-table td.value-cell > *:not(pre) {
  display: block;
  overflow-x: auto;
  white-space: nowrap;
}

.config-table .value-pre {
  background: rgba(0,0,0,0.2);
  padding: 10px;
  border-radius: 6px;
  border: 1px solid rgba(255,255,255,0.05);
  font-size: 11px;
  line-height: 1.6;
  overflow-x: auto;
  white-space: pre;
  max-width: 100%;
}

.config-table .empty-cell {
  padding: 32px;
  text-align: center;
  color: var(--text-secondary);
  font-style: italic;
}

.config-table tr:hover td {
  background: rgba(255, 255, 255, 0.03);
}

.config-table .actions-col {
  width: 140px;
}

.config-table td.actions-cell {
  padding: 10px 8px;
  vertical-align: top;
  border-bottom: 1px solid var(--border-subtle);
  width: 140px;
}

.action-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 28px;
  height: 28px;
  border-radius: 6px;
  border: 1px solid var(--border-subtle);
  background: transparent;
  color: var(--text-secondary);
  cursor: pointer;
  transition: all 0.15s;
}

.action-btn:hover {
  background: rgba(255,255,255,0.08);
  color: var(--primary);
  border-color: var(--primary);
}

.action-btn-danger {
  color: #f87171;
  border-color: rgba(248, 113, 113, 0.3);
}

.action-btn-danger:hover {
  color: #f87171;
  border-color: #f87171;
  background: rgba(248, 113, 113, 0.1);
}

/* ── Diff Table ── */
.diff-table {
  table-layout: fixed;
  width: 100%;
  border-collapse: collapse;
  min-width: 0;
}

.diff-table .diff-key-col {
  width: 200px;
}

.diff-table .diff-value-col {
  width: auto;
}

.diff-table th {
  padding: 8px 12px;
  text-align: left;
  font-size: 11px;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: var(--text-secondary);
  font-weight: 600;
  border-bottom: 1px solid var(--border-subtle);
  background: rgba(255,255,255,0.04);
}

.diff-table td.diff-key-cell {
  padding: 10px 12px;
  font-family: monospace;
  font-size: 11px;
  color: var(--primary);
  font-weight: 600;
  vertical-align: top;
  border-bottom: 1px solid var(--border-subtle);
  word-break: break-all;
  width: 200px;
}

.diff-table td.diff-value-cell {
  padding: 10px 12px;
  font-family: monospace;
  font-size: 11px;
  color: var(--text-secondary);
  border-bottom: 1px solid var(--border-subtle);
  max-width: 0;
  overflow: hidden;
}

.diff-table .diff-value-inner {
  overflow-x: auto;
  white-space: nowrap;
}

.diff-table .diff-value-inner .value-pre {
  background: rgba(0,0,0,0.2);
  padding: 10px;
  border-radius: 6px;
  border: 1px solid rgba(255,255,255,0.05);
  font-size: 11px;
  line-height: 1.6;
  overflow-x: auto;
  white-space: pre;
  max-width: 100%;
}

/* Row status colours */
.diff-table tr.diff-added td {
  background: rgba(34, 197, 94, 0.08);
}
.diff-table tr.diff-added td.diff-key-cell {
  color: #4ade80;
}

.diff-table tr.diff-removed td {
  background: rgba(239, 68, 68, 0.08);
}
.diff-table tr.diff-removed td.diff-key-cell {
  color: #f87171;
}

.diff-table tr.diff-changed td {
  background: rgba(234, 179, 8, 0.08);
}
.diff-table tr.diff-changed td.diff-key-cell {
  color: #facc15;
}

.diff-table tr:hover td {
  background: rgba(255, 255, 255, 0.03);
}

.diff-table .empty-cell {
  padding: 32px;
  text-align: center;
  color: var(--text-secondary);
  font-style: italic;
}

/* ── Edit Mode ── */
.edit-mode-cell {
  padding: 10px 12px;
  font-family: monospace;
  font-size: 11px;
  color: var(--text-secondary);
  border-bottom: 1px solid var(--border-subtle);
  max-width: 0;
  overflow: visible !important;
}

.edit-input {
  width: 100%;
  padding: 6px 10px;
  font-family: monospace;
  font-size: 11px;
  background: var(--bg-base);
  border: 1px solid var(--border-subtle);
  border-radius: 6px;
  color: var(--text-primary);
  outline: none;
  transition: border-color 0.15s;
}

.edit-input:focus {
  border-color: var(--primary);
  box-shadow: 0 0 0 2px rgba(var(--primary-rgb, 99,102,241), 0.15);
}

.edit-textarea {
  width: 100%;
  padding: 8px 10px;
  font-family: monospace;
  font-size: 11px;
  background: var(--bg-base);
  border: 1px solid var(--border-subtle);
  border-radius: 6px;
  color: var(--text-primary);
  outline: none;
  resize: vertical;
  line-height: 1.6;
  transition: border-color 0.15s;
}

.edit-textarea:focus {
  border-color: var(--primary);
}

.edit-json-error {
  margin-top: 4px;
  font-size: 10px;
  color: #f87171;
}

.boolean-toggle {
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  font-family: monospace;
  font-size: 11px;
  padding: 5px 0;
}

.boolean-toggle input[type="checkbox"] {
  width: 16px;
  height: 16px;
  accent-color: var(--primary);
  cursor: pointer;
}

.action-btn-save:hover {
  color: #4ade80;
  border-color: #4ade80;
}

.delete-confirm {
  display: flex;
  align-items: center;
  gap: 6px;
}

.delete-confirm-label {
  font-size: 11px;
  color: #f87171;
  font-weight: 500;
}

.delete-confirm-btn {
  padding: 2px 8px;
  font-size: 11px;
  font-weight: 600;
  border-radius: 4px;
  border: 1px solid var(--border-subtle);
  background: transparent;
  cursor: pointer;
  transition: all 0.15s;
}

.delete-confirm-yes {
  color: #f87171;
  border-color: rgba(248, 113, 113, 0.3);
}

.delete-confirm-yes:hover {
  background: rgba(248, 113, 113, 0.15);
  border-color: #f87171;
}

.delete-confirm-no {
  color: var(--text-secondary);
}

.delete-confirm-no:hover {
  background: rgba(255, 255, 255, 0.08);
  color: var(--text-primary);
}

/* ── Add Key Form ── */
.add-key-trigger {
  padding: 12px;
  border-top: 1px dashed var(--border-subtle);
}

.add-key-btn {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  font-size: 12px;
  font-weight: 500;
  color: var(--text-secondary);
  background: transparent;
  border: 1px dashed var(--border-subtle);
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.15s;
}

.add-key-btn:hover {
  color: var(--primary);
  border-color: var(--primary);
  background: rgba(255,255,255,0.04);
}

.add-key-form {
  padding: 16px;
  border-top: 1px solid var(--border-subtle);
  background: rgba(255,255,255,0.02);
}

.add-key-fields {
  display: flex;
  gap: 12px;
  margin-bottom: 12px;
  align-items: flex-start;
}

.add-key-field {
  display: flex;
  flex-direction: column;
  gap: 4px;
  min-width: 0;
}

.add-key-value-field {
  flex: 1;
}

.add-key-label {
  font-size: 10px;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: var(--text-secondary);
  font-weight: 600;
}
</style>
