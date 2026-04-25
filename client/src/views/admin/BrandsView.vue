<template>
  <div class="admin-brands">
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold font-heading">{{ $t('admin.brands_title') }}</h1>
      <AppButton @click="openCreateModal" size="sm">{{ $t('admin.add_brand') }}</AppButton>
    </div>

    <!-- Error State -->
    <div v-if="error" class="bg-red-500/10 border border-red-500/20 text-red-500 p-4 rounded-xl mb-6">
      {{ error }}
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center p-12">
      <div class="animate-spin h-8 w-8 border-4 border-[var(--primary)] border-t-transparent rounded-full"></div>
    </div>

    <!-- Brands Table -->
    <div v-else class="table-card mt-6 mb-6">
      <div class="table-wrapper">
        <table class="admin-table">
          <thead>
            <tr>
              <th>{{ $t('admin.brand_name') }}</th>
              <th>{{ $t('admin.country') }}</th>
              <th>{{ $t('admin.user_table_status') }}</th>
              <th class="text-right">{{ $t('admin.user_table_actions') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="brand in brands" :key="brand.id" class="hover:bg-white/5 transition-colors group">
              <td>
                <div class="font-medium text-sm text-primary">{{ brand.name }}</div>
              </td>
              <td>
                <span class="text-sm">{{ brand.country || '-' }}</span>
              </td>
              <td>
                <span class="badge" :class="brand.is_active ? 'badge-green' : 'badge-red'">
                  {{ brand.is_active ? $t('dashboard.active') : $t('dashboard.inactive') }}
                </span>
              </td>
              <td class="text-right">
                <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                  <AppButton variant="secondary" size="sm" @click="openEditModal(brand)">
                    {{ $t('common.edit') }}
                  </AppButton>
                  <AppButton variant="secondary" size="sm" @click="confirmDelete(brand)" class="text-red-400 hover:text-red-300 border-red-500/30 hover:bg-red-500/10">
                    {{ $t('common.delete') }}
                  </AppButton>
                </div>
              </td>
            </tr>
            <tr v-if="brands.length === 0">
              <td colspan="4" class="p-8 text-center text-[var(--text-secondary)]">{{ $t('admin.no_brands') }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <AppModal :isOpen="!!deletingBrand" :title="$t('admin.delete_brand')" @close="deletingBrand = null">
      <p class="text-[var(--text-secondary)] mb-6">{{ $t('common.confirm_delete') }} <strong>{{ deletingBrand?.name }}</strong>?</p>
      <div class="flex gap-4 justify-end mt-6">
        <AppButton variant="secondary" @click="deletingBrand = null">{{ $t('common.cancel') }}</AppButton>
        <AppButton variant="danger" @click="executeDelete">{{ $t('common.delete') }}</AppButton>
      </div>
    </AppModal>

    <!-- Create/Edit Modal -->
    <AppModal :isOpen="modalState.isOpen" :title="modalState.isEdit ? $t('admin.edit_brand') : $t('admin.add_brand')" @close="closeModal">
      <form autocomplete="off" onsubmit="return false;">
        <div class="mb-4">
          <label class="block text-sm font-medium text-[var(--text-secondary)] mb-1">{{ $t('admin.brand_name') }}</label>
          <AppInput v-model="modalData.name" type="text" placeholder="Federal" :error="modalState.errors?.name?.[0]" />
        </div>

        <div class="mb-4">
          <label class="block text-sm font-medium text-[var(--text-secondary)] mb-1">{{ $t('admin.country') }}</label>
          <AppInput v-model="modalData.country" type="text" placeholder="USA" :error="modalState.errors?.country?.[0]" />
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
            {{ modalState.isEdit ? $t('common.save') : $t('admin.add_brand') }}
          </AppButton>
        </div>
      </form>
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
const brands = ref([])

const deletingBrand = ref(null)
const modalState = ref({ isOpen: false, isEdit: false, error: '', errors: {}, loading: false })
const modalData = ref({ id: null, name: '', country: '', is_active: true, sort_order: 0 })

const loadBrands = async () => {
  loading.value = true
  error.value = ''
  try {
    const response = await adminApi.getBrands()
    if (response.success) {
      brands.value = response.brands
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to load brands'
  } finally {
    loading.value = false
  }
}

const confirmDelete = (brand) => {
  deletingBrand.value = brand
}

const executeDelete = async () => {
  if (!deletingBrand.value) return
  try {
    const response = await adminApi.deleteBrand(deletingBrand.value.id)
    if (response.success) {
      await loadBrands()
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to delete brand'
  } finally {
    deletingBrand.value = null
  }
}

const openCreateModal = () => {
  modalState.value = { isOpen: true, isEdit: false, error: '', errors: {}, loading: false }
  modalData.value = { id: null, name: '', country: '', is_active: true, sort_order: 0 }
}

const openEditModal = (brand) => {
  modalState.value = { isOpen: true, isEdit: true, error: '', errors: {}, loading: false }
  modalData.value = {
    id: brand.id,
    name: brand.name,
    country: brand.country || '',
    is_active: brand.is_active,
    sort_order: brand.sort_order,
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
    const payload = {
      name: modalData.value.name,
      country: modalData.value.country || null,
      is_active: modalData.value.is_active,
      sort_order: modalData.value.sort_order,
    }
    if (modalState.value.isEdit) {
      await adminApi.updateBrand(modalData.value.id, payload)
    } else {
      await adminApi.createBrand(payload)
    }
    closeModal()
    await loadBrands()
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
  loadBrands()
})
</script>
