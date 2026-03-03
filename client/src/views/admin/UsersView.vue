<template>
  <div class="admin-users">
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold font-heading">{{ $t('admin.users_title') }}</h1>
      <AppButton @click="openCreateModal" size="sm">{{ $t('admin.add_user') }}</AppButton>
    </div>

    <!-- Error State -->
    <div v-if="error" class="bg-red-500/10 border border-red-500/20 text-red-500 p-4 rounded-xl mb-6">
      {{ error }}
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center p-12">
      <div class="animate-spin h-8 w-8 border-4 border-[var(--primary)] border-t-transparent rounded-full"></div>
    </div>

    <!-- Users Table -->
    <div v-else class="table-card mt-6 mb-6">
      <div class="table-wrapper">
        <table class="admin-table">
          <thead>
            <tr>
              <th>{{ $t('admin.user_table_user') }}</th>
              <th>{{ $t('admin.user_table_role') }}</th>
              <th>{{ $t('admin.user_table_status') }}</th>
              <th class="text-right">{{ $t('admin.user_table_actions') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="user in users" :key="user.id" class="hover:bg-white/5 transition-colors group">
              <td>
                <div class="font-medium text-sm text-primary">
                  <span v-if="user.first_name || user.last_name">{{ user.first_name }} {{ user.last_name }}</span>
                  <span v-else class="text-secondary italic">{{ $t('common.no_data') }}</span>
                </div>
                <div class="text-sm">{{ user.email }}</div>
                <div class="text-xs text-[var(--text-secondary)]">{{ user.id }}</div>
              </td>
              <td>
                <span class="badge"
                  :class="{
                    'badge-purple': user.role === 'admin',
                    'badge-blue': user.role === 'club_admin',
                    'badge-gray': user.role === 'user'
                  }">
                  {{ user.role }}
                </span>
              </td>
              <td>
                <span v-if="user.deleted_at" class="badge badge-red">{{ $t('common.delete') }}</span>
                <span v-else class="badge badge-green">{{ $t('dashboard.active') }}</span>
              </td>
              <td class="text-right">
                <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                  <AppButton variant="secondary" size="sm" @click="impersonateUser(user)">
                    {{ $t('admin.impersonate') }}
                  </AppButton>
                  <AppButton variant="secondary" size="sm" @click="openPasswordModal(user)">
                    {{ $t('admin.change_password') }}
                  </AppButton>
                  <AppButton variant="secondary" size="sm" @click="openEditModal(user)">
                    {{ $t('common.edit') }}
                  </AppButton>
                  <AppButton v-if="!user.deleted_at" variant="secondary" size="sm" @click="confirmDelete(user)" class="text-red-400 hover:text-red-300 border-red-500/30 hover:bg-red-500/10">
                    <svg class="w-4 h-4 mr-2 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    {{ $t('common.delete') }}
                  </AppButton>
                </div>
              </td>
            </tr>
            <tr v-if="users.length === 0">
              <td colspan="4" class="p-8 text-center text-[var(--text-secondary)]">{{ $t('admin.no_users') }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <AppModal :isOpen="!!deletingUser" :title="$t('admin.delete_user')" @close="deletingUser = null">
      <p class="text-[var(--text-secondary)] mb-6">{{ $t('admin.delete_confirm') }} <strong>{{ deletingUser?.email }}</strong>? They will no longer be able to log in or access the dashboard.</p>

      <div class="flex gap-4 justify-end mt-6">
        <AppButton variant="secondary" @click="deletingUser = null">{{ $t('common.cancel') }}</AppButton>
        <button @click="executeDelete" class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg font-medium transition-colors">
          {{ $t('common.delete') }}
        </button>
      </div>
    </AppModal>

    <!-- Edit/Create Modal -->
    <AppModal :isOpen="modalState.isOpen" :title="modalState.isEdit ? $t('admin.edit_user') : $t('admin.create_user')" @close="closeModal">
      <form autocomplete="off" onsubmit="return false;">
        <!-- Fake hidden fields to trick password managers -->
        <input style="display:none" type="text" name="fakeusernameremembered"/>
        <input style="display:none" type="password" name="fakepasswordremembered"/>

        <div class="mb-4">
            <label class="block text-sm font-medium text-[var(--text-secondary)] mb-1">{{ $t('common.email') }}</label>
            <AppInput v-model="modalData.email" type="text" placeholder="user@example.com" :disabled="modalState.isEdit" autocomplete="new-password" name="prevent_autofill_email" id="prevent_autofill_email" :error="modalState.errors?.email?.[0]" />
        </div>

      <div class="form-group mb-4">
          <label class="form-label">{{ $t('admin.user_table_role') }}</label>
          <select v-model="modalData.role" class="form-select" autocomplete="off" name="prevent_autofill_role">
              <option value="user">User</option>
              <option value="club_admin">Club Admin</option>
              <option value="admin">Super Admin</option>
          </select>
      </div>

      <!-- Show Instance Dropdown only if role is club_admin -->
      <div v-if="modalData.role === 'club_admin'" class="form-group mb-8">
          <label class="form-label">{{ $t('admin.assign_instance') }}</label>
          <select v-model="modalData.instance_id" class="form-select" autocomplete="off" name="prevent_autofill_instance">
              <option :value="null">{{ $t('common.no_data') }}</option>
              <option v-for="inst in instances" :key="inst.id" :value="inst.id">
                {{ inst.name }}
              </option>
          </select>
          <p class="text-xs text-[var(--text-secondary)] mt-3 mb-2">
            {{ $t('admin.instance_help') }}
          </p>
      </div>

        <div class="mb-6 mt-8 pt-8 border-t border-[var(--border-subtle)]">
            <h4 class="text-md font-semibold mb-4">{{ modalState.isEdit ? $t('admin.change_password') : $t('common.password') }}</h4>
            <label class="block text-sm font-medium text-[var(--text-secondary)] mb-1">
                {{ modalState.isEdit ? $t('profile.new_password') : $t('common.password') }}
            </label>
            <AppInput v-model="modalData.password" type="password" placeholder="••••••••" autocomplete="new-password" name="prevent_autofill_password" id="prevent_autofill_password" :error="modalState.errors?.password?.[0]" />
        </div>

      <div v-if="modalState.error" class="alert alert-danger">
        {{ modalState.error }}
      </div>

        <div class="flex gap-4 justify-end mt-6">
          <AppButton variant="secondary" @click="closeModal" type="button">{{ $t('common.cancel') }}</AppButton>
          <AppButton @click="submitModal" type="button" :loading="modalState.loading">{{ modalState.isEdit ? $t('common.save') : $t('admin.create_user') }}</AppButton>
        </div>
      </form>
    </AppModal>

    <!-- Change Password Modal -->
    <AppModal :isOpen="passwordModalState.isOpen" :title="$t('admin.change_password')" @close="closePasswordModal">
      <form autocomplete="off" onsubmit="return false;">
        <!-- Fake hidden fields -->
        <input style="display:none" type="text" name="fakeusernameremembered"/>
        <input style="display:none" type="password" name="fakepasswordremembered"/>

        <div class="form-group mb-6">
            <label class="form-label">{{ $t('profile.new_password') }}</label>
            <AppInput v-model="passwordModalData.password" type="password" placeholder="••••••••" autocomplete="new-password" name="prevent_autofill_pwd2" id="prevent_autofill_pwd2" :error="passwordModalState.errors?.password?.[0]" />
        </div>
      <div v-if="passwordModalState.error" class="alert alert-danger">
        {{ passwordModalState.error }}
      </div>
      <div v-if="passwordModalState.success" class="alert alert-success">
        {{ $t('common.success') }}
      </div>
        <div class="flex gap-4 justify-end mt-6">
          <AppButton variant="secondary" @click="closePasswordModal" type="button">{{ $t('common.cancel') }}</AppButton>
          <AppButton @click="submitPasswordModal" type="button" :disabled="!passwordModalData.password" :loading="passwordModalState.loading">{{ $t('common.save') }}</AppButton>
        </div>
      </form>
    </AppModal>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { adminApi } from '@/api/admin'
import { useAuthStore } from '@/stores/auth'
import { useI18n } from 'vue-i18n'
import AppButton from '@/components/ui/AppButton.vue'
import AppInput from '@/components/ui/AppInput.vue'
import AppModal from '@/components/ui/AppModal.vue'

const auth = useAuthStore()
const { t } = useI18n()
const loading = ref(true)
const error = ref('')
const users = ref([])
const instances = ref([])

const deletingUser = ref(null)

const modalState = ref({ isOpen: false, isEdit: false, error: '', errors: {}, loading: false })
const modalData = ref({ id: null, email: '', role: 'user', password: '', instance_id: null })

const passwordModalState = ref({ isOpen: false, error: '', success: '', errors: {}, loading: false })
const passwordModalData = ref({ id: null, password: '' })

const loadUsers = async () => {
  loading.value = true
  error.value = ''
  try {
    const [userRes, instRes] = await Promise.all([
      adminApi.getUsers(),
      adminApi.getInstances()
    ])
    if (userRes.success) {
      users.value = userRes.users
    }
    if (instRes.success) {
      instances.value = instRes.instances
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to load data'
  } finally {
    loading.value = false
  }
}

const confirmDelete = (user) => {
  deletingUser.value = user
}

const executeDelete = async () => {
  if (!deletingUser.value) return

  try {
    const response = await adminApi.deleteUser(deletingUser.value.id)
    if (response.success) {
      await loadUsers()
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to delete user'
  } finally {
    deletingUser.value = null
  }
}

const impersonateUser = async (user) => {
  try {
    const response = await adminApi.impersonateUser(user.id)
    if (response.success) {
      // Temporarily stash old token only if not already stashing (prevents losing original admin token)
      if (!localStorage.getItem('tt_token_stash')) {
        localStorage.setItem('tt_token_stash', localStorage.getItem('tt_token'))
      }

      // Force set new auth token
      localStorage.setItem('tt_token', response.token)
      auth.setAuthData(response.token, response.user, response.user.subscriptions?.[0])

      // Redirect to dashboard
      window.location.href = '/dashboard'
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to impersonate user'
  }
}

const openEditModal = (user) => {
    modalState.value = { isOpen: true, isEdit: true, error: '', errors: {}, loading: false }
    
    // Find if user is assigned as a club admin to any instance
    let assignedInstanceId = null
    const assignedInstance = instances.value.find(inst => inst.club_admin_id === user.id)
    if (assignedInstance) {
        assignedInstanceId = assignedInstance.id
    }

    modalData.value = { id: user.id, email: user.email, role: user.role, password: '', instance_id: assignedInstanceId }
}

const openCreateModal = () => {
    modalState.value = { isOpen: true, isEdit: false, error: '', errors: {}, loading: false }
    modalData.value = { id: null, email: '', role: 'user', password: '', instance_id: null }
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
            const payload = { role: modalData.value.role, instance_id: modalData.value.instance_id }
            if (modalData.value.password) payload.password = modalData.value.password
            await adminApi.updateUser(modalData.value.id, payload)
        } else {
            await adminApi.createUser({
                email: modalData.value.email,
                password: modalData.value.password,
                role: modalData.value.role,
                instance_id: modalData.value.instance_id
            })
        }
        closeModal()
        await loadUsers()
    } catch (err) {
        modalState.value.error = err.response?.data?.message || 'Action failed. Please verify the form inputs.'
        if (err.response?.data?.errors) {
            modalState.value.errors = err.response.data.errors
        }
    } finally {
        modalState.value.loading = false
    }
}

const openPasswordModal = (user) => {
    passwordModalState.value = { isOpen: true, error: '', success: '', errors: {}, loading: false }
    passwordModalData.value = { id: user.id, password: '' }
}

const closePasswordModal = () => {
    passwordModalState.value.isOpen = false
}

const submitPasswordModal = async () => {
    passwordModalState.value.error = ''
    passwordModalState.value.success = ''
    passwordModalState.value.errors = {}
    passwordModalState.value.loading = true
    try {
        await adminApi.updateUser(passwordModalData.value.id, { password: passwordModalData.value.password })
        passwordModalState.value.success = 'Password successfully updated.'
        passwordModalData.value.password = ''
    } catch (err) {
        passwordModalState.value.error = err.response?.data?.message || 'Password change failed'
        if (err.response?.data?.errors) {
            passwordModalState.value.errors = err.response.data.errors
        }
    } finally {
        passwordModalState.value.loading = false
    }
}

onMounted(() => {
  loadUsers()
})
</script>
