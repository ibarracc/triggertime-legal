<template>
  <div class="profile-view">
    <div class="header-section mb-8">
      <h1 class="m-0">{{ $t('profile.title') }}</h1>
    </div>

    <div class="flex flex-col gap-12">
      <!-- Profile Information -->
      <AppCard>
        <template #header>{{ $t('profile.info_title') }}</template>
        <div v-if="profileState.error" class="alert alert-danger mb-4">
          {{ profileState.error }}
        </div>
        <div v-if="profileState.success" class="alert alert-success mb-4">
          {{ profileState.success }}
        </div>
        <form @submit.prevent="updateProfile" class="max-w-md">
          <div class="grid grid-cols-2 gap-4 mb-4">
            <AppInput
              v-model="profileForm.firstName"
              :label="$t('common.first_name')"
              :placeholder="auth.user?.first_name || $t('common.first_name')"
              :error="profileState.errors?.first_name?.[0]"
            />
            <AppInput
              v-model="profileForm.lastName"
              :label="$t('common.last_name')"
              :placeholder="auth.user?.last_name || $t('common.last_name')"
              :error="profileState.errors?.last_name?.[0]"
            />
          </div>

          <AppInput
            v-model="profileForm.language"
            :label="$t('common.language')"
            type="select"
            :options="availableLocales.map(l => ({ value: l.code, label: `${l.flag} ${l.name}` }))"
          />

          <AppInput
            v-model="profileForm.email"
            :label="$t('common.email')"
            type="email"
            disabled
          />
          <AppButton type="submit" :loading="isUpdating" class="mt-4">
            {{ $t('common.save') }}
          </AppButton>
        </form>
      </AppCard>

      <!-- Connected Accounts -->
      <AppCard>
        <template #header>{{ $t('profile.connected_accounts') }}</template>

        <div class="flex flex-col gap-3">
          <!-- Google -->
          <div class="social-row">
            <div class="social-info">
              <svg viewBox="0 0 24 24" width="20" height="20" class="social-icon">
                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z"/>
                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
              </svg>
              <div>
                <div class="font-medium">Google</div>
                <div class="text-xs text-secondary">
                  {{ isProviderConnected('google') ? $t('profile.connected') : $t('profile.not_connected') }}
                </div>
              </div>
            </div>
            <AppButton
              v-if="isProviderConnected('google')"
              variant="danger"
              size="sm"
              :loading="disconnecting === 'google'"
              @click="disconnectProvider('google')"
            >
              {{ $t('profile.disconnect') }}
            </AppButton>
            <AppButton
              v-else
              variant="secondary"
              size="sm"
              :loading="connecting === 'google'"
              @click="connectGoogle"
            >
              {{ $t('profile.connect') }}
            </AppButton>
          </div>

          <!-- Apple -->
          <div class="social-row">
            <div class="social-info">
              <svg viewBox="0 0 24 24" width="20" height="20" class="social-icon">
                <path fill="currentColor" d="M17.05 20.28c-.98.95-2.05.88-3.08.4-1.09-.5-2.08-.48-3.24 0-1.44.62-2.2.44-3.06-.4C2.79 15.25 3.51 7.59 9.05 7.31c1.35.07 2.29.74 3.08.8 1.18-.24 2.31-.93 3.57-.84 1.51.12 2.65.72 3.4 1.8-3.12 1.87-2.38 5.98.48 7.13-.57 1.5-1.31 2.99-2.54 4.09zM12.03 7.25c-.15-2.23 1.66-4.07 3.74-4.25.29 2.58-2.34 4.5-3.74 4.25z"/>
              </svg>
              <div>
                <div class="font-medium">Apple</div>
                <div class="text-xs text-secondary">
                  {{ isProviderConnected('apple') ? $t('profile.connected') : $t('profile.not_connected') }}
                </div>
              </div>
            </div>
            <AppButton
              v-if="isProviderConnected('apple')"
              variant="danger"
              size="sm"
              :loading="disconnecting === 'apple'"
              @click="disconnectProvider('apple')"
            >
              {{ $t('profile.disconnect') }}
            </AppButton>
            <AppButton
              v-else
              variant="secondary"
              size="sm"
              :loading="connecting === 'apple'"
              @click="connectApple"
            >
              {{ $t('profile.connect') }}
            </AppButton>
          </div>
        </div>

        <div v-if="socialState.error" class="alert alert-danger mt-4">
          {{ socialState.error }}
        </div>
        <div v-if="socialState.success" class="alert alert-success mt-4">
          {{ socialState.success }}
        </div>
      </AppCard>

      <!-- Password Update -->
      <AppCard>
        <template #header>{{ $t('profile.security_title') }}</template>
        <div v-if="passwordState.error" class="alert alert-danger mb-4">
          {{ passwordState.error }}
        </div>
        <div v-if="passwordState.success" class="alert alert-success mb-4">
          {{ passwordState.success }}
        </div>
        <p v-if="!hasPassword" class="text-secondary mb-4 text-sm">
          {{ $t('profile.set_password_hint') }}
        </p>
        <form @submit.prevent="updatePassword" class="max-w-md">
          <AppInput
            v-if="hasPassword"
            class="mb-4"
            v-model="passwordForm.current"
            :label="$t('profile.current_password')"
            type="password"
          />
          <AppInput
            v-model="passwordForm.new"
            :label="hasPassword ? $t('profile.new_password') : $t('profile.set_password')"
            type="password"
          />
          <AppButton type="submit" variant="secondary" :loading="isUpdatingPwd" class="mt-4">
            {{ hasPassword ? $t('profile.update_password') : $t('profile.set_password') }}
          </AppButton>
        </form>
      </AppCard>

      <!-- B2B Licenses -->
      <AppCard>
        <template #header>{{ $t('profile.b2b_licenses') }}</template>
        <p class="text-secondary mb-4 text-sm">
          {{ $t('profile.b2b_subtitle') }}
        </p>

        <div v-if="b2bLicenses.length === 0" class="p-4 bg-elevated rounded-lg border border-subtle text-secondary text-sm">
          {{ $t('common.no_data') }}
        </div>

        <div v-else class="flex flex-col gap-3">
          <div v-for="license in b2bLicenses" :key="license.id" class="p-4 bg-elevated rounded-lg border border-subtle flex justify-between items-center">
            <div>
              <div class="font-medium mb-1">{{ license.app_instance }}</div>
              <div class="text-xs text-secondary font-mono">{{ license.license_number }}</div>
            </div>
            <AppBadge variant="success">{{ $t('dashboard.active') }}</AppBadge>
          </div>
        </div>
      </AppCard>

      <!-- Communications -->
      <AppCard>
        <template #header>{{ $t('profile.communications') }}</template>
        <p class="text-secondary mb-4 text-sm">
          {{ $t('profile.communications_subtitle') }}
        </p>
        <div v-if="marketingState.error" class="alert alert-danger mb-4">
          {{ marketingState.error }}
        </div>
        <div v-if="marketingState.success" class="alert alert-success mb-4">
          {{ marketingState.success }}
        </div>
        <div class="toggle-row">
          <div>
            <div class="font-medium">{{ $t('profile.marketing_optin') }}</div>
            <div class="text-xs text-secondary">{{ $t('profile.marketing_optin_desc') }}</div>
          </div>
          <button
            class="toggle-switch"
            :class="{ active: marketingOptin }"
            :disabled="isUpdatingMarketing"
            @click="toggleMarketing"
            role="switch"
            :aria-checked="marketingOptin"
          >
            <span class="toggle-knob" />
          </button>
        </div>
      </AppCard>

      <!-- Delete Account -->
      <AppCard class="danger-card">
        <template #header>{{ $t('profile.delete_account') }}</template>
        <p class="text-secondary mb-4 text-sm">
          {{ $t('profile.delete_account_desc') }}
        </p>
        <div v-if="!canDeleteAccount" class="alert alert-warning mb-4">
          {{ $t('profile.delete_account_disabled') }}
        </div>
        <AppButton
          variant="danger"
          :disabled="!canDeleteAccount"
          @click="showDeleteModal = true"
        >
          {{ $t('profile.delete_account') }}
        </AppButton>
      </AppCard>

      <!-- Delete Account Modal -->
      <AppModal
        :is-open="showDeleteModal"
        @close="showDeleteModal = false; deleteEmail = ''; deleteState.error = ''"
        :title="$t('profile.delete_account_confirm')"
        size="sm"
      >
        <p class="text-secondary text-sm mb-4">
          {{ $t('profile.delete_account_confirm_text') }}
        </p>
        <AppInput
          v-model="deleteEmail"
          :label="$t('common.email')"
          type="email"
          :placeholder="$t('profile.type_email_to_confirm')"
        />
        <div v-if="deleteState.error" class="alert alert-danger mt-4">
          {{ deleteState.error }}
        </div>
        <template #footer>
          <AppButton variant="secondary" @click="showDeleteModal = false">{{ $t('common.cancel') }}</AppButton>
          <AppButton variant="danger" :loading="isDeleting" @click="handleDeleteAccount">{{ $t('common.delete') }}</AppButton>
        </template>
      </AppModal>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { authApi } from '@/api/auth'
import { useLocale } from '@/composables/useLocale'
import { useSocialAuth } from '@/composables/useSocialAuth'
import AppCard from '@/components/ui/AppCard.vue'
import AppInput from '@/components/ui/AppInput.vue'
import AppButton from '@/components/ui/AppButton.vue'
import AppBadge from '@/components/ui/AppBadge.vue'
import AppModal from '@/components/ui/AppModal.vue'
import { useRouter } from 'vue-router'

const auth = useAuthStore()
const { setLocale, availableLocales, locale, t } = useLocale()
const socialAuth = useSocialAuth()

const profileForm = ref({
  email: auth.user?.email || '',
  firstName: auth.user?.first_name || '',
  lastName: auth.user?.last_name || '',
  language: auth.user?.language || locale.value
})

const passwordForm = ref({
  current: '',
  new: ''
})

const isUpdating = ref(false)
const profileState = ref({ error: '', success: '', errors: {} })

const isUpdatingPwd = ref(false)
const passwordState = ref({ error: '', success: '' })

const b2bLicenses = ref([])
const socialAccounts = ref([])
const hasPassword = ref(true)
const connecting = ref(null)
const disconnecting = ref(null)
const socialState = ref({ error: '', success: '' })

const router = useRouter()

// Marketing opt-in
const marketingOptin = ref(false)
const isUpdatingMarketing = ref(false)
const marketingState = ref({ error: '', success: '' })

// Delete account
const canDeleteAccount = ref(true)
const showDeleteModal = ref(false)
const deleteEmail = ref('')
const isDeleting = ref(false)
const deleteState = ref({ error: '' })

const isProviderConnected = (provider) => {
  return socialAccounts.value.some(a => a.provider === provider)
}

onMounted(async () => {
  await auth.fetchUser()
  profileForm.value.email = auth.user?.email || ''
  profileForm.value.firstName = auth.user?.first_name || ''
  profileForm.value.lastName = auth.user?.last_name || ''
  profileForm.value.language = auth.user?.language || locale.value

  const res = await authApi.getMe()
  if (res.success) {
     b2bLicenses.value = res.b2b_licenses || []
     socialAccounts.value = res.user?.social_accounts || []
     hasPassword.value = res.has_password !== false
     marketingOptin.value = res.user?.marketing_optin || false
     canDeleteAccount.value = res.can_delete_account !== false
  }
})

const updateProfile = async () => {
  profileState.value.error = ''
  profileState.value.success = ''
  profileState.value.errors = {}
  isUpdating.value = true

  try {
    const res = await authApi.updateProfile({
      first_name: profileForm.value.firstName,
      last_name: profileForm.value.lastName,
      language: profileForm.value.language
    })

    if (res.success) {
      profileState.value.success = t('common.success')
      auth.user.first_name = profileForm.value.firstName
      auth.user.last_name = profileForm.value.lastName
      auth.user.language = profileForm.value.language

      // Update app locale
      await setLocale(profileForm.value.language)
    }
  } catch (err) {
    profileState.value.error = err.response?.data?.message || t('common.error')
    if (err.response?.data?.errors) {
      profileState.value.errors = err.response.data.errors
    }
  } finally {
    isUpdating.value = false
  }
}

const updatePassword = async () => {
  passwordState.value.error = ''
  passwordState.value.success = ''
  isUpdatingPwd.value = true

  try {
    const res = await authApi.updatePassword(
      hasPassword.value ? passwordForm.value.current : null,
      passwordForm.value.new
    )
    if (res.success) {
      passwordState.value.success = t('common.success')
      passwordForm.value.current = ''
      passwordForm.value.new = ''
      hasPassword.value = true
    }
  } catch (err) {
    passwordState.value.error = err.response?.data?.message || t('common.error')
  } finally {
    isUpdatingPwd.value = false
  }
}

const providerLabel = (provider) => provider === 'google' ? 'Google' : 'Apple'

const disconnectProvider = async (provider) => {
  socialState.value.error = ''
  socialState.value.success = ''
  disconnecting.value = provider

  try {
    const res = await authApi.disconnectSocial(provider)
    if (res.success) {
      socialAccounts.value = socialAccounts.value.filter(a => a.provider !== provider)
      socialState.value.success = t('profile.disconnected_success', { provider: providerLabel(provider) })
    }
  } catch (err) {
    socialState.value.error = err.response?.data?.error?.message || err.response?.data?.message || t('common.error')
  } finally {
    disconnecting.value = null
  }
}

const connectGoogle = async () => {
  socialState.value.error = ''
  socialState.value.success = ''
  connecting.value = 'google'
  try {
    const idToken = await socialAuth.getGoogleIdToken()
    const res = await authApi.connectSocial('google', idToken)
    if (res.success) {
      const me = await authApi.getMe()
      if (me.success) {
        socialAccounts.value = me.user?.social_accounts || []
      }
      socialState.value.success = t('profile.connected_success', { provider: 'Google' })
    }
  } catch (err) {
    const msg = err.response?.data?.error?.message || err.response?.data?.message || err.message
    if (msg && msg !== 'canceled') {
      socialState.value.error = msg
    }
  } finally {
    connecting.value = null
  }
}

const connectApple = async () => {
  socialState.value.error = ''
  socialState.value.success = ''
  connecting.value = 'apple'
  try {
    const { idToken } = await socialAuth.getAppleIdToken()
    const res = await authApi.connectSocial('apple', idToken)
    if (res.success) {
      const me = await authApi.getMe()
      if (me.success) {
        socialAccounts.value = me.user?.social_accounts || []
      }
      socialState.value.success = t('profile.connected_success', { provider: 'Apple' })
    }
  } catch (err) {
    if (err.error === 'popup_closed_by_user') return
    const msg = err.response?.data?.error?.message || err.response?.data?.message || err.message
    if (msg && msg !== 'canceled') {
      socialState.value.error = msg
    }
  } finally {
    connecting.value = null
  }
}

const toggleMarketing = async () => {
  marketingState.value.error = ''
  marketingState.value.success = ''
  isUpdatingMarketing.value = true

  try {
    const newValue = !marketingOptin.value
    const res = await authApi.updateProfile({ marketing_optin: newValue })
    if (res.success) {
      marketingOptin.value = newValue
      marketingState.value.success = t('common.success')
    }
  } catch (err) {
    marketingState.value.error = err.response?.data?.message || t('common.error')
  } finally {
    isUpdatingMarketing.value = false
  }
}

const handleDeleteAccount = async () => {
  deleteState.value.error = ''

  if (deleteEmail.value.toLowerCase() !== auth.user?.email?.toLowerCase()) {
    deleteState.value.error = t('profile.email_mismatch')
    return
  }

  isDeleting.value = true

  try {
    const res = await authApi.deleteAccount(deleteEmail.value)
    if (res.success) {
      auth.logout()
      router.push({ path: '/login', query: { deactivated: '1' } })
    }
  } catch (err) {
    deleteState.value.error = err.response?.data?.error?.message || err.response?.data?.message || t('common.error')
  } finally {
    isDeleting.value = false
  }
}
</script>

<style scoped>
.max-w-md { max-width: 28rem; }
.font-medium { font-weight: 500; }
.font-mono { font-family: monospace; }
.text-xs { font-size: 0.75rem; }
.text-sm { font-size: 0.875rem; }
.bg-elevated { background-color: var(--bg-elevated); }
.border { border: 1px solid var(--border-subtle); }
.rounded-lg { border-radius: 12px; }

.social-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 16px;
  background: var(--bg-elevated);
  border: 1px solid var(--border-subtle);
  border-radius: 12px;
}

.social-info {
  display: flex;
  align-items: center;
  gap: 12px;
}

.social-icon {
  flex-shrink: 0;
}

.toggle-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 16px;
  background: var(--bg-elevated);
  border: 1px solid var(--border-subtle);
  border-radius: 12px;
}

.toggle-switch {
  position: relative;
  width: 44px;
  height: 24px;
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.2);
  border: none;
  cursor: pointer;
  transition: background 0.2s;
  flex-shrink: 0;
}

.toggle-switch.active {
  background: var(--primary);
}

.toggle-switch:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.toggle-knob {
  position: absolute;
  top: 2px;
  left: 2px;
  width: 20px;
  height: 20px;
  border-radius: 50%;
  background: white;
  transition: transform 0.2s;
}

.toggle-switch.active .toggle-knob {
  transform: translateX(20px);
}

.danger-card {
  border: 1px solid rgba(255, 77, 77, 0.2);
}

.alert-warning {
  color: #FBBF24;
  font-size: 0.875rem;
  padding: 10px;
  background: rgba(251, 191, 36, 0.1);
  border-radius: 8px;
}

@media (max-width: 480px) {
  .grid-cols-2 {
    grid-template-columns: 1fr;
  }
}
</style>
