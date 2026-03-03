<template>
  <div class="container auth-container">
    <AppCard class="auth-card">
      <div class="text-center mb-8">
        <h1 class="mb-2">{{ $t('auth.login_title') }}</h1>
        <p class="text-secondary">{{ $t('auth.login_subtitle') }}</p>
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

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()
const { setLocale } = useLocale()

const email = ref('')
const password = ref('')
const isLoading = ref(false)
const errorMsg = ref('')

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
</script>

<style scoped>
.auth-container {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: calc(100vh - 160px); /* viewport minus header and footer avg */
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
</style>
