<template>
  <div class="container auth-container">
    <AppCard class="auth-card">
      <div class="text-center mb-8">
        <h1 class="mb-2">{{ $t('auth.login_title') }}</h1>
        <p class="text-secondary">{{ $t('auth.login_subtitle') }}</p>
        <div v-if="route.query.deactivated" class="alert alert-info mb-4">
          {{ $t('auth.account_deactivated') }}
        </div>
      </div>

      <form @submit.prevent="handleLogin">
        <AppInput
          v-model="email"
          :label="$t('common.email')"
          type="email"
          :placeholder="$t('auth.email_placeholder')"
          required
        />
        
        <AppInput
          v-model="password"
          :label="$t('common.password')"
          type="password"
          :placeholder="$t('auth.password_placeholder')"
          required
        />

        <div v-if="errorMsg" class="error-msg mb-4">
          {{ errorMsg }}
        </div>

        <div class="flex justify-end mb-4">
          <router-link to="/forgot-password" class="text-sm text-primary hover-underline">{{ $t('auth.forgot_password') }}</router-link>
        </div>

        <AppButton
          type="submit"
          class="w-full mt-4"
          :loading="isLoading"
        >
          {{ $t('common.login') }}
        </AppButton>
      </form>

      <div class="sso-divider">
        <span>{{ $t('auth.or_continue_with') }}</span>
      </div>

      <div class="sso-buttons">
        <button
          type="button"
          class="sso-btn sso-btn-google"
          :disabled="socialAuth.isLoading.value"
          @click="handleGoogleLogin"
        >
          <svg class="sso-icon" viewBox="0 0 24 24" width="20" height="20">
            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z"/>
            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
          </svg>
          {{ $t('auth.sign_in_google') }}
        </button>

        <button
          type="button"
          class="sso-btn sso-btn-apple"
          :disabled="socialAuth.isLoading.value"
          @click="handleAppleLogin"
        >
          <svg class="sso-icon" viewBox="0 0 24 24" width="20" height="20">
            <path fill="currentColor" d="M17.05 20.28c-.98.95-2.05.88-3.08.4-1.09-.5-2.08-.48-3.24 0-1.44.62-2.2.44-3.06-.4C2.79 15.25 3.51 7.59 9.05 7.31c1.35.07 2.29.74 3.08.8 1.18-.24 2.31-.93 3.57-.84 1.51.12 2.65.72 3.4 1.8-3.12 1.87-2.38 5.98.48 7.13-.57 1.5-1.31 2.99-2.54 4.09zM12.03 7.25c-.15-2.23 1.66-4.07 3.74-4.25.29 2.58-2.34 4.5-3.74 4.25z"/>
          </svg>
          {{ $t('auth.sign_in_apple') }}
        </button>
      </div>

      <div v-if="socialAuth.error.value" class="error-msg mb-4">
        {{ socialAuth.error.value }}
      </div>

      <div class="text-center mt-6 text-sm text-secondary">
        {{ $t('auth.no_account') }} 
        <router-link to="/register" class="text-primary hover-underline">{{ $t('common.register') }}</router-link>
      </div>
    </AppCard>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useLocale } from '@/composables/useLocale'
import AppCard from '@/components/ui/AppCard.vue'
import AppInput from '@/components/ui/AppInput.vue'
import AppButton from '@/components/ui/AppButton.vue'
import { useRoute } from 'vue-router'
import { useSocialAuth } from '@/composables/useSocialAuth'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()
const { setLocale } = useLocale()

const email = ref('')
const password = ref('')
const isLoading = ref(false)
const errorMsg = ref('')
const socialAuth = useSocialAuth()

const handleLogin = async () => {
  isLoading.value = true
  errorMsg.value = ''
  
  const result = await authStore.login(email.value, password.value)
  
  if (result.success) {
    // Sync language if user has a preference
    if (authStore.user?.language) {
      await setLocale(authStore.user.language)
    }

    const upgradeToken = route.query.upgrade_token
    const redirectUrl = route.query.redirect
    
    if (upgradeToken) {
        router.push(`/checkout/${upgradeToken}`)
    } else if (redirectUrl) {
        router.push(redirectUrl)
    } else {
        router.push('/dashboard')
    }
  } else {
    errorMsg.value = result.error
    isLoading.value = false
  }
}

const handleGoogleLogin = async () => {
  errorMsg.value = ''
  const result = await socialAuth.loginWithGoogle()
  if (result?.success) {
    if (authStore.user?.language) {
      await setLocale(authStore.user.language)
    }
    const upgradeToken = route.query.upgrade_token
    const redirectUrl = route.query.redirect
    if (upgradeToken) {
      router.push(`/checkout/${upgradeToken}`)
    } else if (redirectUrl) {
      router.push(redirectUrl)
    } else {
      router.push('/dashboard')
    }
  }
}

const handleAppleLogin = async () => {
  errorMsg.value = ''
  const result = await socialAuth.loginWithApple()
  if (result?.success) {
    if (authStore.user?.language) {
      await setLocale(authStore.user.language)
    }
    const upgradeToken = route.query.upgrade_token
    const redirectUrl = route.query.redirect
    if (upgradeToken) {
      router.push(`/checkout/${upgradeToken}`)
    } else if (redirectUrl) {
      router.push(redirectUrl)
    } else {
      router.push('/dashboard')
    }
  }
}
</script>

<style scoped>
.auth-container {
  flex: 1;
  display: flex;
  justify-content: center;
  align-items: safe center;
  padding: 40px 20px;
}

.auth-card {
  width: 100%;
  max-width: 440px;
  padding: 16px;
}

.error-msg {
  color: var(--danger);
  font-size: 0.875rem;
  text-align: center;
  padding: 10px;
  background: rgba(255, 77, 77, 0.1);
  border-radius: 8px;
}

.w-full {
  width: 100%;
}

.text-primary { color: var(--primary); }
.hover-underline:hover { text-decoration: underline; }

.sso-divider {
  display: flex;
  align-items: center;
  margin: 24px 0;
  gap: 12px;
}

.sso-divider::before,
.sso-divider::after {
  content: '';
  flex: 1;
  height: 1px;
  background: var(--border);
}

.sso-divider span {
  font-size: 0.875rem;
  color: var(--text-secondary);
  white-space: nowrap;
}

.sso-buttons {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.sso-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  width: 100%;
  padding: 12px 16px;
  border-radius: 8px;
  font-size: 0.9375rem;
  font-weight: 500;
  cursor: pointer;
  transition: background 0.2s, border-color 0.2s, box-shadow 0.2s;
}

.sso-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.sso-btn-google,
.sso-btn-apple {
  background: var(--bg-elevated, var(--surface));
  border: 1px solid var(--border-subtle, var(--border));
  color: var(--text);
}

.sso-btn-google:hover:not(:disabled),
.sso-btn-apple:hover:not(:disabled) {
  background: var(--surface-hover, rgba(255, 255, 255, 0.08));
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.sso-icon {
  flex-shrink: 0;
}

.alert-info {
  color: var(--primary);
  font-size: 0.875rem;
  text-align: center;
  padding: 10px;
  background: rgba(59, 130, 246, 0.1);
  border-radius: 8px;
}
</style>
