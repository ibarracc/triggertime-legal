<template>
  <div class="container checkout-success-container">
    <AppCard class="checkout-card text-center">
      <div class="success-icon mb-4">🎉</div>
      <h1 class="mb-2">{{ $t('subscription.activated') }}</h1>
      <p class="text-secondary mb-8">
        {{ $t('subscription.activated_desc') }}
      </p>
      
      <AppButton @click="goToDashboard" class="w-full" size="lg">
        {{ $t('common.back') === 'Back' ? 'Return to Dashboard' : $t('common.back') }}
      </AppButton>
    </AppCard>
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import AppCard from '@/components/ui/AppCard.vue'
import AppButton from '@/components/ui/AppButton.vue'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()

onMounted(async () => {
  // Refresh user data so the new subscription status is reflected immediately
  if (authStore.isAuthenticated) {
    await authStore.fetchUser()
  }
})

const goToDashboard = () => {
  if (authStore.isAuthenticated) {
    router.push('/dashboard')
  } else {
    router.push('/login')
  }
}
</script>

<style scoped>
.checkout-success-container {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: calc(100vh - 160px);
  padding: 40px 20px;
}

.checkout-card {
  width: 100%;
  max-width: 500px;
  padding: 32px;
}

.success-icon {
  font-size: 4rem;
  line-height: 1;
}

.w-full { width: 100%; }
</style>
