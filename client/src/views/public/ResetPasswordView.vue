<template>
  <div class="container auth-container">
    <AppCard class="auth-card">

      <div v-if="successMsg" class="text-center">
        <div class="success-icon mb-4">
          <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"/>
            <path d="m9 12 2 2 4-4"/>
          </svg>
        </div>
        <h1 class="mb-2">{{ $t('auth.password_reset_title') }}</h1>
        <p class="text-secondary mb-8">{{ successMsg }}</p>
        <AppButton @click="$router.push('/login')" class="w-full">{{ $t('auth.back_to_login') }}</AppButton>
      </div>

      <template v-else>
      <div class="text-center mb-8">
        <h1 class="mb-2">{{ $t('auth.new_password_title') }}</h1>
        <p class="text-secondary">{{ $t('auth.new_password_subtitle') }}</p>
      </div>

      <form @submit.prevent="handleReset">
        <AppInput
          v-model="password"
          :label="$t('auth.new_password_title')"
          type="password"
          :placeholder="$t('auth.new_password_placeholder')"
          required
        />

        <AppInput
          v-model="confirmPassword"
          :label="$t('auth.confirm_password')"
          type="password"
          :placeholder="$t('auth.confirm_password_placeholder')"
          required
        />

        <div v-if="errorMsg" class="error-msg my-4">
          {{ errorMsg }}
        </div>

        <AppButton
          type="submit"
          class="w-full mt-4"
          :loading="isLoading"
        >
          {{ $t('auth.reset_password') }}
        </AppButton>
      </form>
      </template>
    </AppCard>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { authApi } from '@/api/auth'
import AppCard from '@/components/ui/AppCard.vue'
import AppInput from '@/components/ui/AppInput.vue'
import AppButton from '@/components/ui/AppButton.vue'

const { t } = useI18n()
const route = useRoute()

const password = ref('')
const confirmPassword = ref('')
const isLoading = ref(false)
const errorMsg = ref('')
const successMsg = ref('')

const handleReset = async () => {
  if (password.value !== confirmPassword.value) {
    errorMsg.value = t('auth.password_mismatch')
    return
  }

  const token = route.params.token
  if (!token) {
    errorMsg.value = t('auth.invalid_reset_token')
    return
  }

  isLoading.value = true
  errorMsg.value = ''
  
  try {
    const response = await authApi.resetPassword(token, password.value)
    if (response.success) {
      successMsg.value = response.message || 'Password has been set.'
    } else {
      errorMsg.value = t('auth.reset_password_failed')
    }
  } catch (err) {
    errorMsg.value = err.response?.data?.message || t('auth.reset_link_expired_error')
  } finally {
    isLoading.value = false
  }
}
</script>

<style scoped>
.auth-container {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: calc(100vh - 140px);
  padding: 2rem;
}

.auth-card {
  width: 100%;
  max-width: 440px;
  padding: 2.5rem;
}

.success-icon {
  display: flex;
  justify-content: center;
}

.error-msg {
  color: #ff6b6b;
  font-size: 0.875rem;
  background-color: rgba(255, 107, 107, 0.1);
  padding: 0.75rem;
  border-radius: 0.5rem;
}

h1 {
  font-size: 1.75rem;
  font-weight: 700;
  font-family: var(--font-heading);
}

.text-secondary {
  color: var(--text-secondary);
}
</style>
