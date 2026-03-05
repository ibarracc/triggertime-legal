<template>
  <div class="container auth-container">
    <AppCard class="auth-card">
      <div class="text-center mb-8">
        <div class="email-icon mb-4">
          <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <rect x="2" y="4" width="20" height="16" rx="2"/>
            <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
          </svg>
        </div>
        <h1 class="mb-2">{{ $t('auth.verify_email_title') }}</h1>
        <p class="text-secondary">{{ $t('auth.verify_email_subtitle') }}</p>
      </div>

      <div v-if="authStore.user?.email" class="email-badge mb-6">
        {{ authStore.user.email }}
      </div>

      <div v-if="successMsg" class="success-msg mb-4">
        {{ successMsg }}
      </div>

      <div v-if="errorMsg" class="error-msg mb-4">
        {{ errorMsg }}
      </div>

      <AppButton
        class="w-full mb-4"
        :loading="isResending"
        :disabled="cooldown > 0"
        @click="handleResend"
      >
        {{ cooldown > 0 ? `${$t('auth.verify_email_resend')} (${cooldown}s)` : $t('auth.verify_email_resend') }}
      </AppButton>

      <p class="text-center text-sm text-secondary mb-6">
        {{ $t('auth.verify_email_check_spam') }}
      </p>

      <div v-if="showSubscribeCta" class="subscribe-cta">
        <hr class="divider mb-4" />
        <p class="text-center text-sm text-secondary mb-4">
          {{ $t('auth.verify_email_subscribe_cta') }}
        </p>
        <AppButton
          variant="secondary"
          class="w-full"
          @click="handleSubscribe"
        >
          {{ $t('dashboard.upgrade_pro') }}
        </AppButton>
      </div>

      <div class="text-center mt-6">
        <button class="logout-link" @click="handleLogout">
          {{ $t('common.logout') }}
        </button>
      </div>
    </AppCard>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { authApi } from '@/api/auth'
import subscriptionsApi from '@/api/subscriptions'
import AppCard from '@/components/ui/AppCard.vue'
import AppButton from '@/components/ui/AppButton.vue'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()

const isResending = ref(false)
const cooldown = ref(0)
const successMsg = ref('')
const errorMsg = ref('')
const showSubscribeCta = ref(!!route.query.intent || !!route.query.upgrade_token)

let cooldownTimer = null

const handleResend = async () => {
  if (cooldown.value > 0) return

  isResending.value = true
  errorMsg.value = ''
  successMsg.value = ''

  try {
    const response = await authApi.resendVerification()
    if (response.success) {
      successMsg.value = response.message
      startCooldown()
    }
  } catch (err) {
    errorMsg.value = err.response?.data?.message || 'Failed to resend verification email.'
  } finally {
    isResending.value = false
  }
}

const startCooldown = () => {
  cooldown.value = 60
  cooldownTimer = setInterval(() => {
    cooldown.value--
    if (cooldown.value <= 0) {
      clearInterval(cooldownTimer)
    }
  }, 1000)
}

const handleSubscribe = async () => {
  try {
    const upgradeToken = route.query.upgrade_token
    const response = await subscriptionsApi.createCheckout(upgradeToken || null)
    if (response.checkout_url) {
      window.location.href = response.checkout_url
    }
  } catch (err) {
    errorMsg.value = err.response?.data?.message || 'Failed to start checkout.'
  }
}

const handleLogout = () => {
  authStore.logout()
  router.push('/login')
}

onMounted(() => {
  // Start with initial cooldown to prevent immediate resend after registration
  startCooldown()
})
</script>

<style scoped>
.auth-container {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: calc(100vh - 160px);
  padding: 40px 20px;
}

.auth-card {
  width: 100%;
  max-width: 480px;
  padding: 40px 32px;
}

.email-icon {
  display: flex;
  justify-content: center;
}

.email-badge {
  text-align: center;
  padding: 10px 16px;
  background: var(--bg-elevated);
  border-radius: 8px;
  font-size: 0.9375rem;
  color: var(--text-primary);
  font-weight: 500;
}

.success-msg {
  color: var(--success);
  font-size: 0.875rem;
  text-align: center;
  padding: 10px;
  background: rgba(74, 222, 128, 0.1);
  border-radius: 8px;
}

.error-msg {
  color: var(--danger);
  font-size: 0.875rem;
  text-align: center;
  padding: 10px;
  background: rgba(255, 77, 77, 0.1);
  border-radius: 8px;
}

.divider {
  border: none;
  border-top: 1px solid var(--border-subtle);
}

.logout-link {
  background: none;
  border: none;
  color: var(--text-secondary);
  cursor: pointer;
  font-size: 0.875rem;
  text-decoration: underline;
}

.logout-link:hover {
  color: var(--text-primary);
}

.w-full { width: 100%; }
.text-center { text-align: center; }
.text-secondary { color: var(--text-secondary); }
</style>
