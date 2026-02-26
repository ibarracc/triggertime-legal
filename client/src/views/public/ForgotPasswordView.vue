<template>
  <div class="container auth-container">
    <AppCard class="auth-card">
      <div class="text-center mb-8">
        <h1 class="mb-2">Reset Password</h1>
        <p class="text-secondary">Enter your email address and we'll send you a link to reset your password.</p>
      </div>

      <div v-if="successMsg" class="bg-green-500/10 border border-green-500/20 text-green-400 p-4 rounded-xl mb-6 text-sm">
        {{ successMsg }}
      </div>

      <form v-else @submit.prevent="handleForgot">
        <AppInput
          v-model="email"
          label="Email Address"
          type="email"
          placeholder="you@example.com"
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
          Send Reset Link
        </AppButton>
      </form>

      <div class="text-center mt-6 text-sm text-secondary">
        Remember your password? 
        <router-link to="/login" class="text-primary hover-underline">Back to login</router-link>
      </div>
    </AppCard>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { authApi } from '@/api/auth'
import AppCard from '@/components/ui/AppCard.vue'
import AppInput from '@/components/ui/AppInput.vue'
import AppButton from '@/components/ui/AppButton.vue'

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
      errorMsg.value = 'Failed to send reset link.'
    }
  } catch (err) {
    errorMsg.value = err.response?.data?.message || 'An error occurred. Please try again.'
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

.text-primary {
  color: var(--primary);
}

.hover-underline:hover {
  text-decoration: underline;
}
</style>
