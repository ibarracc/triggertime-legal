<template>
  <div class="container auth-container">
    <AppCard class="auth-card">
      <div class="text-center mb-8">
        <h1 class="mb-2">New Password</h1>
        <p class="text-secondary">Enter your new secure password below.</p>
      </div>

      <div v-if="successMsg" class="bg-green-500/10 border border-green-500/20 text-green-400 p-4 rounded-xl mb-6 text-sm text-center">
        <p class="mb-4">{{ successMsg }}</p>
        <AppButton @click="$router.push('/login')" class="w-full">Go to Login</AppButton>
      </div>

      <form v-else @submit.prevent="handleReset">
        <AppInput
          v-model="password"
          label="New Password"
          type="password"
          placeholder="Create a strong password"
          required
        />
        
        <AppInput
          v-model="confirmPassword"
          label="Confirm Password"
          type="password"
          placeholder="Repeat your new password"
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
          Reset Password
        </AppButton>
      </form>
    </AppCard>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { authApi } from '@/api/auth'
import AppCard from '@/components/ui/AppCard.vue'
import AppInput from '@/components/ui/AppInput.vue'
import AppButton from '@/components/ui/AppButton.vue'

const router = useRouter()
const route = useRoute()

const password = ref('')
const confirmPassword = ref('')
const isLoading = ref(false)
const errorMsg = ref('')
const successMsg = ref('')

const handleReset = async () => {
  if (password.value !== confirmPassword.value) {
    errorMsg.value = 'Passwords do not match.'
    return
  }
  
  const token = route.params.token
  if (!token) {
    errorMsg.value = 'Invalid or missing reset token.'
    return
  }

  isLoading.value = true
  errorMsg.value = ''
  
  try {
    const response = await authApi.resetPassword(token, password.value)
    if (response.success) {
      successMsg.value = response.message || 'Password has been set.'
    } else {
      errorMsg.value = 'Failed to reset password.'
    }
  } catch (err) {
    errorMsg.value = err.response?.data?.message || 'An error occurred. Check if the link expired.'
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
