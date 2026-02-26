<template>
  <div class="container auth-container">
    <AppCard class="auth-card">
      <div class="text-center mb-8">
        <h1 class="mb-2">{{ $t('auth.register_title') }}</h1>
        <p class="text-secondary">{{ $t('auth.register_subtitle') }}</p>
      </div>

      <form @submit.prevent="handleRegister">
        <div class="grid grid-cols-2 gap-4 mb-4">
          <AppInput
            v-model="firstName"
            :label="$t('common.first_name')"
            placeholder="John"
          />
          <AppInput
            v-model="lastName"
            :label="$t('common.last_name')"
            placeholder="Doe"
          />
        </div>

        <AppInput
          v-model="email"
          :label="$t('common.email')"
          type="email"
          :placeholder="$t('auth.email_placeholder')"
          required
        />

        <div class="mb-4">
          <label class="block text-sm font-medium text-secondary mb-1.5">{{ $t('common.language') }}</label>
          <select 
            v-model="language" 
            class="w-full bg-elevated border border-subtle rounded-lg p-2.5 text-sm text-primary focus:border-primary outline-none transition-all"
          >
            <option v-for="l in availableLocales" :key="l.code" :value="l.code">
              {{ l.flag }} {{ l.name }}
            </option>
          </select>
        </div>
        
        <AppInput
          v-model="password"
          :label="$t('common.password')"
          type="password"
          :placeholder="$t('auth.password_placeholder')"
          required
        />
        
        <AppInput
          v-model="confirmPassword"
          :label="$t('auth.confirm_password')"
          type="password"
          :placeholder="$t('auth.password_placeholder')"
          required
        />

        <div v-if="errorMsg" class="error-msg mb-4">
          {{ errorMsg }}
        </div>

        <AppButton
          type="submit"
          class="w-full mt-4"
          :loading="isLoading"
        >
          {{ $t('common.register') }}
        </AppButton>
      </form>

      <div class="text-center mt-6 text-sm text-secondary">
        {{ $t('auth.have_account') }} 
        <router-link to="/login" class="text-primary hover-underline">{{ $t('common.login') }}</router-link>
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
const { locale, availableLocales, setLocale, t } = useLocale()

const firstName = ref('')
const lastName = ref('')
const email = ref('')
const language = ref(locale.value)
const password = ref('')
const confirmPassword = ref('')
const isLoading = ref(false)
const errorMsg = ref('')

const handleRegister = async () => {
  if (password.value !== confirmPassword.value) {
    errorMsg.value = t('auth.password_mismatch')
    return
  }

  isLoading.value = true
  errorMsg.value = ''
  
  const result = await authStore.register(email.value, password.value, firstName.value, lastName.value, language.value)
  
  if (result.success) {
    // Sync local locale
    await setLocale(language.value)

    const upgradeToken = route.query.upgrade_token
    if (upgradeToken) {
        router.push(`/checkout/${upgradeToken}`)
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
  min-height: calc(100vh - 160px);
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
