<template>
  <div class="admin-sync-data">
    <div class="flex justify-between items-center mb-6">
      <div>
        <h1 class="text-2xl font-bold font-heading">{{ $t('admin.sync_data_title') }}</h1>
        <p class="text-secondary text-sm mt-1">{{ $t('admin.sync_data_subtitle') }}</p>
      </div>
    </div>

    <!-- User Selector -->
    <div class="mb-6">
      <label class="block text-sm font-medium text-[var(--text-secondary)] mb-2">{{ $t('admin.select_user') }}</label>
      <select v-model="selectedUserId" class="form-select max-w-md" @change="loadData">
        <option :value="null" disabled>{{ $t('admin.select_user') }}...</option>
        <option v-for="user in users" :key="user.id" :value="user.id">
          {{ user.email }}
          <template v-if="user.first_name || user.last_name"> — {{ user.first_name }} {{ user.last_name }}</template>
        </option>
      </select>
    </div>

    <!-- Error State -->
    <div v-if="error" class="bg-red-500/10 border border-red-500/20 text-red-500 p-4 rounded-xl mb-6">
      {{ error }}
    </div>

    <!-- Tabs -->
    <div v-if="selectedUserId" class="mb-6">
      <div class="flex gap-2 border-b border-[var(--border-subtle)] pb-2">
        <button
          v-for="tab in tabs"
          :key="tab.type"
          class="tab-btn"
          :class="{ active: activeTab === tab.type }"
          @click="switchTab(tab.type)"
        >
          {{ tab.label }}
        </button>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center p-12">
      <div class="animate-spin h-8 w-8 border-4 border-[var(--primary)] border-t-transparent rounded-full"></div>
    </div>

    <!-- Data Table -->
    <div v-else-if="selectedUserId && records.length > 0" class="table-card mt-4 mb-6">
      <div class="table-wrapper">
        <table class="admin-table">
          <thead>
            <tr>
              <th v-for="col in activeColumns" :key="col.key">{{ col.label }}</th>
              <th class="text-right">{{ $t('common.edit') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="record in records" :key="record.id" class="hover:bg-white/5 transition-colors group">
              <td v-for="col in activeColumns" :key="col.key" class="text-sm">
                <template v-if="col.type === 'boolean'">
                  <span :class="record[col.key] ? 'badge badge-green' : 'badge badge-gray'">
                    {{ record[col.key] ? 'Yes' : 'No' }}
                  </span>
                </template>
                <template v-else-if="col.type === 'datetime'">
                  {{ formatDate(record[col.key]) }}
                </template>
                <template v-else>
                  {{ record[col.key] ?? '-' }}
                </template>
              </td>
              <td class="text-right">
                <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                  <AppButton variant="secondary" size="sm" @click="openEditModal(record)">{{ $t('common.edit') }}</AppButton>
                  <AppButton variant="secondary" size="sm" @click="confirmDelete(record)" class="text-red-400 hover:text-red-300 border-red-500/30 hover:bg-red-500/10">
                    {{ $t('common.delete') }}
                  </AppButton>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else-if="selectedUserId && !loading" class="p-12 text-center bg-elevated border border-subtle rounded-xl">
      <p class="text-secondary">{{ $t('admin.no_sync_data') }}</p>
    </div>

    <!-- Delete Confirmation Modal -->
    <AppModal :isOpen="!!deletingRecord" :title="$t('admin.delete_sync_record')" @close="deletingRecord = null">
      <p class="text-[var(--text-secondary)] mb-6">{{ $t('admin.delete_sync_confirm') }}</p>
      <div class="flex gap-3 justify-end mt-4">
        <AppButton variant="secondary" @click="deletingRecord = null">{{ $t('common.cancel') }}</AppButton>
        <AppButton variant="danger" @click="executeDelete">{{ $t('common.delete') }}</AppButton>
      </div>
    </AppModal>

    <!-- Edit Modal -->
    <AppModal :isOpen="editModal.isOpen" :title="$t('admin.edit_sync_record')" @close="closeEditModal">
      <div v-if="editModal.error" class="bg-red-500/10 border border-red-500/20 text-red-500 p-3 rounded-lg mb-4 text-sm">
        {{ editModal.error }}
      </div>
      <div v-for="field in editableFields" :key="field.key" class="form-group mb-4">
        <label class="form-label">{{ field.label }}</label>
        <template v-if="field.type === 'boolean'">
          <select v-model="editModal.data[field.key]" class="form-select">
            <option :value="true">Yes</option>
            <option :value="false">No</option>
          </select>
        </template>
        <template v-else-if="field.type === 'textarea'">
          <textarea v-model="editModal.data[field.key]" class="form-input" rows="3"></textarea>
        </template>
        <template v-else-if="field.type === 'number'">
          <AppInput v-model.number="editModal.data[field.key]" type="number" />
        </template>
        <template v-else>
          <AppInput v-model="editModal.data[field.key]" :type="field.inputType || 'text'" />
        </template>
      </div>
      <div class="flex gap-4 justify-end mt-6">
        <AppButton variant="secondary" @click="closeEditModal">{{ $t('common.cancel') }}</AppButton>
        <AppButton @click="submitEdit" :loading="editModal.loading">{{ $t('common.save') }}</AppButton>
      </div>
    </AppModal>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { adminApi } from '@/api/admin'
import AppButton from '@/components/ui/AppButton.vue'
import AppInput from '@/components/ui/AppInput.vue'
import AppModal from '@/components/ui/AppModal.vue'

const { t } = useI18n()

const users = ref([])
const selectedUserId = ref(null)
const activeTab = ref('weapons')
const records = ref([])
const loading = ref(false)
const error = ref('')
const deletingRecord = ref(null)
const editModal = ref({ isOpen: false, data: {}, error: '', loading: false })

const tabs = computed(() => [
  { type: 'weapons', label: t('admin.tab_weapons') },
  { type: 'ammo', label: t('admin.tab_ammo') },
  { type: 'competitions', label: t('admin.tab_competitions') },
  { type: 'competition_reminders', label: t('admin.tab_reminders') },
  { type: 'ammo_transactions', label: t('admin.tab_transactions') },
])

const columnDefs = {
  weapons: [
    { key: 'name', label: 'Name' },
    { key: 'caliber', label: 'Caliber' },
    { key: 'is_favorite', label: 'Favorite', type: 'boolean' },
    { key: 'is_archived', label: 'Archived', type: 'boolean' },
    { key: 'modified_at', label: t('admin.sync_modified_at'), type: 'datetime' },
  ],
  ammo: [
    { key: 'brand', label: 'Brand' },
    { key: 'name', label: 'Name' },
    { key: 'caliber', label: 'Caliber' },
    { key: 'grain_weight', label: 'Grain' },
    { key: 'current_stock', label: 'Stock' },
    { key: 'is_archived', label: 'Archived', type: 'boolean' },
    { key: 'modified_at', label: t('admin.sync_modified_at'), type: 'datetime' },
  ],
  competitions: [
    { key: 'name', label: 'Name' },
    { key: 'date', label: 'Date' },
    { key: 'location', label: 'Location' },
    { key: 'status', label: 'Status' },
    { key: 'modified_at', label: t('admin.sync_modified_at'), type: 'datetime' },
  ],
  competition_reminders: [
    { key: 'competition_uuid', label: 'Competition UUID' },
    { key: 'reminder_date', label: 'Date' },
    { key: 'type', label: 'Type' },
    { key: 'modified_at', label: t('admin.sync_modified_at'), type: 'datetime' },
  ],
  ammo_transactions: [
    { key: 'ammo_uuid', label: 'Ammo UUID' },
    { key: 'type', label: 'Type' },
    { key: 'quantity', label: 'Qty' },
    { key: 'modified_at', label: t('admin.sync_modified_at'), type: 'datetime' },
  ],
}

const fieldDefs = {
  weapons: [
    { key: 'name', label: 'Name' },
    { key: 'caliber', label: 'Caliber' },
    { key: 'notes', label: 'Notes', type: 'textarea' },
    { key: 'is_favorite', label: 'Favorite', type: 'boolean' },
    { key: 'is_archived', label: 'Archived', type: 'boolean' },
  ],
  ammo: [
    { key: 'brand', label: 'Brand' },
    { key: 'name', label: 'Name' },
    { key: 'caliber', label: 'Caliber' },
    { key: 'grain_weight', label: 'Grain Weight', type: 'number' },
    { key: 'current_stock', label: 'Current Stock', type: 'number' },
    { key: 'notes', label: 'Notes', type: 'textarea' },
    { key: 'is_archived', label: 'Archived', type: 'boolean' },
  ],
  competitions: [
    { key: 'name', label: 'Name' },
    { key: 'date', label: 'Date', inputType: 'date' },
    { key: 'end_date', label: 'End Date', inputType: 'date' },
    { key: 'location', label: 'Location' },
    { key: 'discipline_id', label: 'Discipline ID', type: 'number' },
    { key: 'status', label: 'Status' },
    { key: 'notes', label: 'Notes', type: 'textarea' },
  ],
  competition_reminders: [
    { key: 'reminder_date', label: 'Reminder Date', inputType: 'datetime-local' },
    { key: 'type', label: 'Type' },
  ],
  ammo_transactions: [
    { key: 'type', label: 'Type' },
    { key: 'quantity', label: 'Quantity', type: 'number' },
    { key: 'notes', label: 'Notes', type: 'textarea' },
  ],
}

const activeColumns = computed(() => columnDefs[activeTab.value] || [])
const editableFields = computed(() => fieldDefs[activeTab.value] || [])

const formatDate = (val) => {
  if (!val) return '-'
  return new Date(val).toLocaleString()
}

const loadUsers = async () => {
  try {
    const res = await adminApi.getUsers()
    if (res.success) {
      users.value = res.users
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to load users'
  }
}

const loadData = async () => {
  if (!selectedUserId.value) return
  loading.value = true
  error.value = ''
  try {
    const res = await adminApi.getSyncData(selectedUserId.value, activeTab.value)
    if (res.success) {
      records.value = res.records
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to load sync data'
    records.value = []
  } finally {
    loading.value = false
  }
}

const switchTab = (type) => {
  activeTab.value = type
  loadData()
}

const openEditModal = (record) => {
  const data = {}
  for (const field of editableFields.value) {
    data[field.key] = record[field.key]
  }
  data.type = activeTab.value
  editModal.value = { isOpen: true, data, error: '', loading: false, recordId: record.id }
}

const closeEditModal = () => {
  editModal.value.isOpen = false
}

const submitEdit = async () => {
  editModal.value.error = ''
  editModal.value.loading = true
  try {
    await adminApi.updateSyncData(editModal.value.recordId, editModal.value.data)
    closeEditModal()
    await loadData()
  } catch (err) {
    editModal.value.error = err.response?.data?.message || 'Update failed'
  } finally {
    editModal.value.loading = false
  }
}

const confirmDelete = (record) => {
  deletingRecord.value = record
}

const executeDelete = async () => {
  if (!deletingRecord.value) return
  try {
    await adminApi.deleteSyncData(deletingRecord.value.id, activeTab.value)
    await loadData()
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to delete record'
  } finally {
    deletingRecord.value = null
  }
}

onMounted(() => {
  loadUsers()
})
</script>

<style scoped>
.tab-btn {
  padding: 8px 16px;
  color: var(--text-secondary);
  font-weight: 500;
  border-radius: 8px;
  transition: all 0.2s;
  white-space: nowrap;
  font-size: 0.9rem;
  border: none;
  background: none;
  cursor: pointer;
}

.tab-btn:hover {
  background: rgba(255, 255, 255, 0.05);
  color: var(--text-primary);
}

.tab-btn.active {
  background: var(--bg-surface);
  color: var(--primary);
  border: 1px solid var(--border-subtle);
}
</style>
