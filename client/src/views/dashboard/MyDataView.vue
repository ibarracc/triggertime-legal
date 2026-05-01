<template>
  <div class="my-data-view">
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold font-heading m-0">{{ $t('my_data.title') }}</h1>
    </div>

    <!-- Error State -->
    <div v-if="error" class="bg-red-500/10 border border-red-500/20 text-red-500 p-4 rounded-xl mb-6">
      {{ error }}
    </div>

    <!-- Tabs -->
    <div class="mb-6">
      <div class="flex gap-2 border-b border-[var(--border-subtle)] pb-2 overflow-x-auto">
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
    <div v-else-if="records.length > 0" class="table-card mt-4 mb-6">
      <div class="table-wrapper">
        <table class="admin-table">
          <thead>
            <tr>
              <th v-for="col in activeColumns" :key="col.key">{{ col.label }}</th>
              <th v-if="editableTypes.includes(activeTab)" class="text-right">{{ $t('common.edit') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="record in records"
              :key="record.id"
              class="hover:bg-white/5 transition-colors group"
              :class="{ 'cursor-pointer': activeTab === 'sessions' }"
              @click="handleRowClick(record)"
            >
              <td v-for="col in activeColumns" :key="col.key" class="text-sm">
                <template v-if="col.type === 'boolean'">
                  <span :class="record[col.key] ? 'badge badge-green' : 'badge badge-gray'">
                    {{ record[col.key] ? $t('common.yes') : $t('common.no') }}
                  </span>
                </template>
                <template v-else-if="col.type === 'datetime'">
                  {{ formatDate(record[col.key]) }}
                </template>
                <template v-else-if="col.type === 'currency'">
                  {{ record[col.key] != null ? `$${Number(record[col.key]).toFixed(2)}` : '-' }}
                </template>
                <template v-else>
                  {{ record[col.key] ?? '-' }}
                </template>
              </td>
              <td v-if="editableTypes.includes(activeTab)" class="text-right">
                <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                  <AppButton variant="secondary" size="sm" @click.stop="openEditModal(record)">{{ $t('common.edit') }}</AppButton>
                  <AppButton variant="secondary" size="sm" @click.stop="confirmDelete(record)" class="text-red-400 hover:text-red-300 border-red-500/30 hover:bg-red-500/10">
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
    <div v-else-if="!loading" class="p-12 text-center bg-elevated border border-subtle rounded-xl">
      <p class="text-secondary">{{ $t('my_data.empty') }}</p>
    </div>

    <!-- Delete Confirmation Modal -->
    <AppModal :isOpen="!!deletingRecord" :title="$t('my_data.delete_record')" @close="deletingRecord = null">
      <p class="text-[var(--text-secondary)] mb-6">{{ $t('my_data.delete_confirm') }}</p>
      <div class="flex gap-3 justify-end mt-4">
        <AppButton variant="secondary" @click="deletingRecord = null">{{ $t('common.cancel') }}</AppButton>
        <AppButton variant="danger" @click="executeDelete">{{ $t('common.delete') }}</AppButton>
      </div>
    </AppModal>

    <!-- Edit Modal -->
    <AppModal :isOpen="editModal.isOpen" :title="$t('my_data.edit_record')" @close="closeEditModal">
      <div v-if="editModal.error" class="bg-red-500/10 border border-red-500/20 text-red-500 p-3 rounded-lg mb-4 text-sm">
        {{ editModal.error }}
      </div>
      <div v-for="field in editFields" :key="field.key" class="form-group mb-4">
        <label class="form-label">{{ field.label }}</label>
        <template v-if="field.type === 'boolean'">
          <select v-model="editModal.data[field.key]" class="form-select">
            <option :value="true">{{ $t('common.yes') }}</option>
            <option :value="false">{{ $t('common.no') }}</option>
          </select>
        </template>
        <template v-else-if="field.type === 'textarea'">
          <textarea v-model="editModal.data[field.key]" class="form-input" rows="3"></textarea>
        </template>
        <template v-else-if="field.type === 'number'">
          <AppInput v-model.number="editModal.data[field.key]" type="number" />
        </template>
        <template v-else-if="field.type === 'date'">
          <AppInput v-model="editModal.data[field.key]" type="date" />
        </template>
        <template v-else>
          <AppInput v-model="editModal.data[field.key]" />
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
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { mydataApi } from '@/api/mydata'
import AppButton from '@/components/ui/AppButton.vue'
import AppInput from '@/components/ui/AppInput.vue'
import AppModal from '@/components/ui/AppModal.vue'

const router = useRouter()
const { t } = useI18n()

const activeTab = ref('weapons')
const records = ref([])
const loading = ref(false)
const error = ref('')
const deletingRecord = ref(null)
const editModal = ref({ isOpen: false, data: {}, error: '', loading: false, recordId: null })

const editableTypes = ['weapons', 'ammo', 'sessions', 'competitions', 'competition_reminders']

const tabs = computed(() => [
  { type: 'weapons', label: t('my_data.tab_weapons') },
  { type: 'ammo', label: t('my_data.tab_ammo') },
  { type: 'sessions', label: t('my_data.tab_sessions') },
  { type: 'competitions', label: t('my_data.tab_competitions') },
  { type: 'competition_reminders', label: t('my_data.tab_reminders') },
  { type: 'ammo_transactions', label: t('my_data.tab_transactions') },
])

const columnDefs = {
  weapons: [
    { key: 'name', label: t('my_data.col_name') },
    { key: 'caliber', label: t('my_data.col_caliber') },
    { key: 'is_favorite', label: t('my_data.col_favorite'), type: 'boolean' },
    { key: 'is_archived', label: t('my_data.col_archived'), type: 'boolean' },
    { key: 'modified_at', label: t('my_data.col_modified'), type: 'datetime' },
  ],
  ammo: [
    { key: 'brand', label: t('my_data.col_brand') },
    { key: 'name', label: t('my_data.col_name') },
    { key: 'caliber', label: t('my_data.col_caliber') },
    { key: 'grain_weight', label: t('my_data.col_grain') },
    { key: 'cost_per_round', label: t('my_data.col_cost_per_round'), type: 'currency' },
    { key: 'current_stock', label: t('my_data.col_stock') },
    { key: 'is_archived', label: t('my_data.col_archived'), type: 'boolean' },
    { key: 'modified_at', label: t('my_data.col_modified'), type: 'datetime' },
  ],
  sessions: [
    { key: 'date', label: t('sessions.date'), type: 'datetime' },
    { key: 'discipline_name', label: t('sessions.discipline') },
    { key: 'type', label: t('sessions.type') },
    { key: 'location', label: t('sessions.location') },
    { key: 'total_score', label: t('sessions.total_score') },
    { key: 'total_x_count', label: t('sessions.total_x_count') },
    { key: 'modified_at', label: t('my_data.col_modified'), type: 'datetime' },
  ],
  competitions: [
    { key: 'name', label: t('my_data.col_name') },
    { key: 'date', label: t('sessions.date') },
    { key: 'location', label: t('sessions.location') },
    { key: 'status', label: t('my_data.col_status') },
    { key: 'modified_at', label: t('my_data.col_modified'), type: 'datetime' },
  ],
  competition_reminders: [
    { key: 'competition_uuid', label: t('my_data.col_competition') },
    { key: 'reminder_date', label: t('sessions.date') },
    { key: 'type', label: t('sessions.type') },
    { key: 'modified_at', label: t('my_data.col_modified'), type: 'datetime' },
  ],
  ammo_transactions: [
    { key: 'ammo_uuid', label: t('my_data.col_ammo') },
    { key: 'type', label: t('sessions.type') },
    { key: 'quantity', label: t('my_data.col_quantity') },
    { key: 'modified_at', label: t('my_data.col_modified'), type: 'datetime' },
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
    { key: 'cost_per_round', label: 'Cost/Round', type: 'number' },
    { key: 'current_stock', label: 'Current Stock', type: 'number' },
    { key: 'notes', label: 'Notes', type: 'textarea' },
    { key: 'is_archived', label: 'Archived', type: 'boolean' },
  ],
  sessions: [
    { key: 'notes', label: 'Notes', type: 'textarea' },
    { key: 'location', label: 'Location' },
  ],
  competitions: [
    { key: 'name', label: 'Name' },
    { key: 'date', label: 'Date', type: 'date' },
    { key: 'end_date', label: 'End Date', type: 'date' },
    { key: 'location', label: 'Location' },
    { key: 'status', label: 'Status' },
    { key: 'notes', label: 'Notes', type: 'textarea' },
  ],
  competition_reminders: [
    { key: 'reminder_offset', label: 'Reminder Offset' },
    { key: 'is_enabled', label: 'Enabled', type: 'boolean' },
  ],
}

const activeColumns = computed(() => columnDefs[activeTab.value] || [])
const editFields = computed(() => fieldDefs[activeTab.value] || [])

const formatDate = (val) => {
  if (!val) return '-'
  return new Date(val).toLocaleString()
}

const loadData = async () => {
  loading.value = true
  error.value = ''
  try {
    const res = await mydataApi.getSyncData(activeTab.value)
    if (res.success) {
      records.value = res.records
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to load data'
    records.value = []
  } finally {
    loading.value = false
  }
}

const switchTab = (type) => {
  activeTab.value = type
  loadData()
}

const handleRowClick = (record) => {
  if (activeTab.value === 'sessions') {
    router.push({ name: 'session-detail', params: { uuid: record.id } })
  }
}

const openEditModal = (record) => {
  const data = {}
  for (const field of editFields.value) {
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
    await mydataApi.updateSyncData(editModal.value.recordId, editModal.value.data)
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
    await mydataApi.deleteSyncData(deletingRecord.value.id, activeTab.value)
    await loadData()
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to delete record'
  } finally {
    deletingRecord.value = null
  }
}

onMounted(loadData)
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
