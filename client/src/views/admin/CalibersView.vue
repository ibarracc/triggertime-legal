<template>
  <div class="admin-calibers">
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold font-heading">{{ $t('admin.calibers_title') }}</h1>
      <AppButton @click="openCreateModal" size="sm">{{ $t('admin.add_caliber') }}</AppButton>
    </div>

    <!-- Error State -->
    <div v-if="error" class="bg-red-500/10 border border-red-500/20 text-red-500 p-4 rounded-xl mb-6">
      {{ error }}
    </div>

    <!-- Filter -->
    <div class="mb-4">
      <select v-model="categoryFilter" class="form-select" style="max-width: 220px;">
        <option value="">{{ $t('admin.all_categories') }}</option>
        <option value="pistol">{{ $t('admin.category_pistol') }}</option>
        <option value="rifle">{{ $t('admin.category_rifle') }}</option>
        <option value="rimfire">{{ $t('admin.category_rimfire') }}</option>
        <option value="shotshell">{{ $t('admin.category_shotshell') }}</option>
      </select>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center p-12">
      <div class="animate-spin h-8 w-8 border-4 border-[var(--primary)] border-t-transparent rounded-full"></div>
    </div>

    <!-- Calibers Table -->
    <div v-else class="table-card mt-6 mb-6">
      <div class="table-wrapper">
        <table class="admin-table">
          <thead>
            <tr>
              <th>{{ $t('admin.caliber_name') }}</th>
              <th>{{ $t('admin.weapon_category') }}</th>
              <th>{{ $t('admin.standard') }}</th>
              <th>{{ $t('admin.user_table_status') }}</th>
              <th class="text-right">{{ $t('admin.user_table_actions') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="caliber in filteredCalibers" :key="caliber.id" class="hover:bg-white/5 transition-colors group">
              <td>
                <div class="font-medium text-sm text-primary">{{ caliber.name }}</div>
              </td>
              <td>
                <span class="badge"
                  :class="{
                    'badge-blue': caliber.weapon_category === 'pistol',
                    'badge-green': caliber.weapon_category === 'rifle',
                    'badge-purple': caliber.weapon_category === 'rimfire',
                    'badge-gray': caliber.weapon_category === 'shotshell'
                  }">
                  {{ caliber.weapon_category }}
                </span>
              </td>
              <td>
                <span class="badge" :class="caliber.standard === 'saami' ? 'badge-blue' : 'badge-gray'">
                  {{ caliber.standard.toUpperCase() }}
                </span>
              </td>
              <td>
                <span class="badge" :class="caliber.is_active ? 'badge-green' : 'badge-red'">
                  {{ caliber.is_active ? $t('dashboard.active') : $t('dashboard.inactive') }}
                </span>
              </td>
              <td class="text-right">
                <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                  <AppButton variant="secondary" size="sm" @click="openEditModal(caliber)">
                    {{ $t('common.edit') }}
                  </AppButton>
                  <AppButton variant="secondary" size="sm" @click="confirmDelete(caliber)" class="text-red-400 hover:text-red-300 border-red-500/30 hover:bg-red-500/10">
                    {{ $t('common.delete') }}
                  </AppButton>
                </div>
              </td>
            </tr>
            <tr v-if="filteredCalibers.length === 0">
              <td colspan="5" class="p-8 text-center text-[var(--text-secondary)]">{{ $t('admin.no_calibers') }}</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="p-4 text-sm text-[var(--text-secondary)]">
        {{ $t('admin.showing_count', { count: filteredCalibers.length, total: calibers.length }) }}
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <AppModal :isOpen="!!deletingCaliber" :title="$t('admin.delete_caliber')" @close="deletingCaliber = null">
      <p class="text-[var(--text-secondary)] mb-6">{{ $t('common.confirm_delete') }} <strong>{{ deletingCaliber?.name }}</strong>?</p>
      <div class="flex gap-4 justify-end mt-6">
        <AppButton variant="secondary" @click="deletingCaliber = null">{{ $t('common.cancel') }}</AppButton>
        <AppButton variant="danger" @click="executeDelete">{{ $t('common.delete') }}</AppButton>
      </div>
    </AppModal>

    <!-- Create/Edit Modal -->
    <AppModal :isOpen="modalState.isOpen" :title="modalState.isEdit ? $t('admin.edit_caliber') : $t('admin.add_caliber')" @close="closeModal">
      <form autocomplete="off" onsubmit="return false;">
        <div class="mb-4">
          <label class="block text-sm font-medium text-[var(--text-secondary)] mb-1">{{ $t('admin.caliber_name') }}</label>
          <AppInput v-model="modalData.name" type="text" placeholder="9mm Luger" :error="modalState.errors?.name?.[0]" />
        </div>

        <div class="form-group mb-4">
          <label class="form-label">{{ $t('admin.weapon_category') }}</label>
          <select v-model="modalData.weapon_category" class="form-select">
            <option value="pistol">{{ $t('admin.category_pistol') }}</option>
            <option value="rifle">{{ $t('admin.category_rifle') }}</option>
            <option value="rimfire">{{ $t('admin.category_rimfire') }}</option>
            <option value="shotshell">{{ $t('admin.category_shotshell') }}</option>
          </select>
        </div>

        <div class="form-group mb-4">
          <label class="form-label">{{ $t('admin.standard') }}</label>
          <select v-model="modalData.standard" class="form-select">
            <option value="saami">SAAMI</option>
            <option value="cip">CIP</option>
          </select>
        </div>

        <div class="form-group mb-4">
          <label class="form-label">{{ $t('admin.sort_order') }}</label>
          <AppInput v-model.number="modalData.sort_order" type="number" placeholder="0" :error="modalState.errors?.sort_order?.[0]" />
        </div>

        <div class="form-group mb-4">
          <label class="inline-flex items-center gap-2">
            <input type="checkbox" v-model="modalData.is_active" class="form-checkbox" />
            <span class="form-label mb-0">{{ $t('dashboard.active') }}</span>
          </label>
        </div>

        <div v-if="modalState.error" class="alert alert-danger">
          {{ modalState.error }}
        </div>

        <div class="flex gap-4 justify-end mt-6">
          <AppButton variant="secondary" @click="closeModal" type="button">{{ $t('common.cancel') }}</AppButton>
          <AppButton @click="submitModal" type="button" :loading="modalState.loading">
            {{ modalState.isEdit ? $t('common.save') : $t('admin.add_caliber') }}
          </AppButton>
        </div>
      </form>
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
const calibers = ref([])
const categoryFilter = ref('')

const deletingCaliber = ref(null)
const modalState = ref({ isOpen: false, isEdit: false, error: '', errors: {}, loading: false })
const modalData = ref({ id: null, name: '', weapon_category: 'pistol', standard: 'saami', is_active: true, sort_order: 0 })

const filteredCalibers = computed(() => {
  if (!categoryFilter.value) return calibers.value
  return calibers.value.filter(c => c.weapon_category === categoryFilter.value)
})

const loadCalibers = async () => {
  loading.value = true
  error.value = ''
  try {
    const response = await adminApi.getCalibers()
    if (response.success) {
      calibers.value = response.calibers
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to load calibers'
  } finally {
    loading.value = false
  }
}

const confirmDelete = (caliber) => {
  deletingCaliber.value = caliber
}

const executeDelete = async () => {
  if (!deletingCaliber.value) return
  try {
    const response = await adminApi.deleteCaliber(deletingCaliber.value.id)
    if (response.success) {
      await loadCalibers()
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to delete caliber'
  } finally {
    deletingCaliber.value = null
  }
}

const openCreateModal = () => {
  modalState.value = { isOpen: true, isEdit: false, error: '', errors: {}, loading: false }
  modalData.value = { id: null, name: '', weapon_category: 'pistol', standard: 'saami', is_active: true, sort_order: 0 }
}

const openEditModal = (caliber) => {
  modalState.value = { isOpen: true, isEdit: true, error: '', errors: {}, loading: false }
  modalData.value = {
    id: caliber.id,
    name: caliber.name,
    weapon_category: caliber.weapon_category,
    standard: caliber.standard,
    is_active: caliber.is_active,
    sort_order: caliber.sort_order,
  }
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
      await adminApi.updateCaliber(modalData.value.id, {
        name: modalData.value.name,
        weapon_category: modalData.value.weapon_category,
        standard: modalData.value.standard,
        is_active: modalData.value.is_active,
        sort_order: modalData.value.sort_order,
      })
    } else {
      await adminApi.createCaliber({
        name: modalData.value.name,
        weapon_category: modalData.value.weapon_category,
        standard: modalData.value.standard,
        is_active: modalData.value.is_active,
        sort_order: modalData.value.sort_order,
      })
    }
    closeModal()
    await loadCalibers()
  } catch (err) {
    modalState.value.error = err.response?.data?.message || 'Action failed.'
    if (err.response?.data?.errors) {
      modalState.value.errors = err.response.data.errors
    }
  } finally {
    modalState.value.loading = false
  }
}

onMounted(() => {
  loadCalibers()
})
</script>
