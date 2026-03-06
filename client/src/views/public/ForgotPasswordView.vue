<template>
  <div class="container auth-container">
    <AppCard class="auth-card">

      <div v-if="successMsg" class="text-center">
        <div class="sent-icon mb-4">
          <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <rect x="2" y="4" width="20" height="16" rx="2"/>
            <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
          </svg>
        </div>
        <h1 class="mb-2">{{ $t('auth.check_inbox_title') }}</h1>
        <p class="text-secondary mb-8">{{ successMsg }}</p>
        <router-link to="/login" class="text-primary hover-underline text-sm">{{ $t('auth.back_to_login') }}</router-link>
      </div>

      <template v-else>
        <div class="text-center mb-8">
          <h1 class="mb-2">{{ $t('auth.reset_password') }}</h1>
          <p class="text-secondary">{{ $t('auth.forgot_password_subtitle') }}</p>
        </div>

        <form @submit.prevent="handleForgot">
          <AppInput
            v-model="email"
            :label="$t('common.email')"
            type="email"
            :placeholder="$t('auth.email_placeholder')"
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
            {{ $t('auth.send_reset_link') }}
          </AppButton>
        </form>

        <div class="text-center mt-6 text-sm text-secondary">
          {{ $t('auth.remember_password') }}
          <router-link to="/login" class="text-primary hover-underline">{{ $t('auth.back_to_login') }}</router-link>
        </div>
      </template>
    </AppCard>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { authApi } from '@/api/auth'
import AppCard from '@/components/ui/AppCard.vue'
import AppInput from '@/components/ui/AppInput.vue'
import AppButton from '@/components/ui/AppButton.vue'

const { t } = useI18n()

const email = ref('')
const isLoading = ref(false)
const errorMsg = ref('')
const successMsg = ref('')

const handleForgot = async () => {
  if (!email.value) return
  
  isLoading.value = true
  errorMsg.value = ''
  
  try {
    const response = await authApi.forgotPassword(email.value)
    if (response.success) {
      successMsg.value = response.message || 'Check your email for the reset link.'
    } else {
      errorMsg.value = t('auth.forgot_password_failed')
    }
  } catch (err) {
    errorMsg.value = err.response?.data?.message || t('auth.error_try_again')
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

.sent-icon {
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

.text-primary {
  color: var(--primary);
}

.hover-underline:hover {
  text-decoration: underline;
}
</style>
