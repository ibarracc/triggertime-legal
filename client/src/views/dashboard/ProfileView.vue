<template>
  <div class="profile-view">
    <div class="header-section mb-8">
      <h1 class="mb-2">{{ $t('profile.title') }}</h1>
      <p class="text-secondary">{{ $t('profile.info_subtitle') }}</p>
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
          
          <div class="mb-4">
            <label class="block text-sm font-medium text-secondary mb-1.5">{{ $t('common.language') }}</label>
            <select 
              v-model="profileForm.language" 
              class="w-full bg-elevated border border-subtle rounded-lg p-2.5 text-sm text-primary focus:border-primary outline-none transition-all"
            >
              <option v-for="l in availableLocales" :key="l.code" :value="l.code">
                {{ l.flag }} {{ l.name }}
              </option>
            </select>
          </div>

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

      <!-- Password Update -->
      <AppCard>
        <template #header>{{ $t('profile.security_title') }}</template>
        <div v-if="passwordState.error" class="alert alert-danger mb-4">
          {{ passwordState.error }}
        </div>
        <div v-if="passwordState.success" class="alert alert-success mb-4">
          {{ passwordState.success }}
        </div>
        <form @submit.prevent="updatePassword" class="max-w-md">
          <AppInput
            class="mb-4"
            v-model="passwordForm.current"
            :label="$t('profile.current_password')"
            type="password"
          />
          <AppInput
            v-model="passwordForm.new"
            :label="$t('profile.new_password')"
            type="password"
          />
          <AppButton type="submit" variant="secondary" :loading="isUpdatingPwd" class="mt-4">
            {{ $t('profile.update_password') }}
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
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { authApi } from '@/api/auth'
import { useLocale } from '@/composables/useLocale'
import AppCard from '@/components/ui/AppCard.vue'
import AppInput from '@/components/ui/AppInput.vue'
import AppButton from '@/components/ui/AppButton.vue'
import AppBadge from '@/components/ui/AppBadge.vue'

const auth = useAuthStore()
const { setLocale, availableLocales, locale, t } = useLocale()

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

onMounted(async () => {
  await auth.fetchUser()
  profileForm.value.email = auth.user?.email || ''
  profileForm.value.firstName = auth.user?.first_name || ''
  profileForm.value.lastName = auth.user?.last_name || ''
  profileForm.value.language = auth.user?.language || locale.value
  
  // Fetch full details which includes B2B licenses
  const res = await authApi.getMe()
  if (res.success) {
     b2bLicenses.value = res.b2b_licenses || []
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
    const res = await authApi.updatePassword(passwordForm.value.current, passwordForm.value.new)
    if (res.success) {
      passwordState.value.success = t('common.success')
      passwordForm.value.current = ''
      passwordForm.value.new = ''
    }
  } catch (err) {
    passwordState.value.error = err.response?.data?.message || t('common.error')
  } finally {
    isUpdatingPwd.value = false
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

@media (max-width: 480px) {
  .grid-cols-2 {
    grid-template-columns: 1fr;
  }
}
</style>
