<template>
  <div class="container checkout-landing-container">
    <AppCard class="checkout-card">
      <!-- Loading State -->
      <div v-if="isLoading" class="text-center py-8">
        <div class="spinner mx-auto mb-4"></div>
        <p class="text-secondary">Validating secure token...</p>
      </div>

      <!-- Error State -->
      <div v-else-if="errorMsg" class="text-center py-8">
        <div class="state-icon text-warning mb-4">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-16 h-16 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
          </svg>
        </div>
        <h2 class="mb-2">Link Expired</h2>
        <p class="text-secondary mb-6">{{ errorMsg }}</p>
        <AppButton @click="goBackToApp" class="w-full">
          Return to App
        </AppButton>
      </div>

      <!-- Direct Link Success State -->
      <div v-else-if="linkSuccessMsg" class="text-center py-8">
        <div class="state-icon text-success mb-4">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-16 h-16 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
        <h2 class="mb-2">Device Linked Successfully</h2>
        <p class="text-secondary mb-8">{{ linkSuccessMsg }}</p>
        <AppButton @click="() => router.push('/dashboard/devices')" class="w-full" size="lg">
          Return to Dashboard
        </AppButton>
      </div>

      <!-- Success State -->
      <div v-else class="text-center">
        <div v-if="!authStore.isProPlus" class="inline-block mb-4">
          <AppBadge variant="primary">Upgrade to Pro+</AppBadge>
        </div>
        <h1 class="mb-2">Link Your Device</h1>
        <p class="text-secondary mb-8">
          You're linking <strong class="text-primary">{{ deviceInfo?.model || 'your device' }}</strong>
          to a Pro+ subscription.
        </p>

        <!-- EXISTING USER WITH ACTIVE PRO+ PLAN -->
        <div v-if="authStore.isProPlus">

          <div v-if="isAlreadyLinked">
            <div class="bg-elevated p-8 rounded-xl border border-subtle mb-6 text-center text-success bg-opacity-10 border-success">
              <h3 class="font-heading text-lg mb-2 text-success">Device Already Linked</h3>
              <p class="text-secondary m-0">This device is already connected to your TriggerTime account.</p>
            </div>
            <AppButton @click="() => router.push('/dashboard/devices')" class="w-full mb-4" size="lg">
              Return to Dashboard
            </AppButton>
          </div>

          <div v-else>
            <div class="bg-elevated p-8 rounded-xl border border-subtle mb-6 text-center">
              <h3 class="font-heading text-lg mb-2 text-primary">Active Subscription Found!</h3>
              <p class="text-secondary mb-0">You already have an active Pro+ subscription. Click below to securely link this device to your account.</p>

              <div v-if="linkErrorMsg" class="alert alert-danger mt-4 text-left">
                {{ linkErrorMsg }}
              </div>
            </div>
            <AppButton @click="linkDeviceDirectly" class="w-full mb-4" size="lg" :loading="isProcessing">
              Link Device Now
            </AppButton>
          </div>

        </div>

        <!-- NO SUBSCRIPTION / NOT AUTHENTICATED -->
        <div v-else>
          <div class="bg-elevated p-6 pt-6 rounded-lg border border-subtle mb-8 text-left">
            <h3 class="font-heading text-lg mb-4 text-center">Next Steps:</h3>
            <ol class="step-list text-secondary">
              <li>Create an account or log in to an existing one.</li>
              <li>Complete your Pro+ subscription checkout.</li>
              <li>Return to the app to automatically unlock premium features.</li>
            </ol>
          </div>

          <div v-if="authStore.isAuthenticated" class="flex flex-col gap-4">
            <AppButton @click="processCheckout" class="w-full" size="lg" :loading="isProcessing">
              Complete Pro+ Checkout
            </AppButton>
          </div>
          <div v-else class="flex flex-col gap-4">
            <AppButton @click="continueToRegister" class="w-full" size="lg">
              Create Account & Checkout
            </AppButton>
            <AppButton variant="ghost" @click="continueToLogin" class="w-full">
              I already have an account
            </AppButton>
          </div>
        </div>
      </div>
    </AppCard>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/api/index'
import AppCard from '@/components/ui/AppCard.vue'
import AppButton from '@/components/ui/AppButton.vue'
import AppBadge from '@/components/ui/AppBadge.vue'
import { useAuthStore } from '@/stores/auth'
import { devicesApi } from '@/api/devices'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const isLoading = ref(true)
const errorMsg = ref('')
const deviceInfo = ref(null)

const token = route.params.token
const isAlreadyLinked = ref(false)

onMounted(async () => {
  if (!token) {
    errorMsg.value = 'No token provided in the URL.'
    isLoading.value = false
    return
  }

  try {
    // API validation for the token
    const res = await api.get(`/web/tokens/${token}/verify`);
    if (res.success) {
        deviceInfo.value = {
            uuid: res.device_uuid
        }
        
        // If authenticated, check if they already have the device
        if (authStore.isAuthenticated) {
           const devicesRes = await devicesApi.getDevices()
           if (devicesRes.success && devicesRes.devices) {
               isAlreadyLinked.value = devicesRes.devices.some(d => d.device_uuid === res.device_uuid)
           }
        }
    }
  } catch (err) {
    errorMsg.value = err.response?.data?.message || 'The upgrade link has expired. Please generate a new one from the app.'
  } finally {
    isLoading.value = false
  }
})

const continueToRegister = () => {
  router.push({ path: '/register', query: { upgrade_token: token } })
}

const continueToLogin = () => {
  router.push({ path: '/login', query: { upgrade_token: token } })
}

const isProcessing = ref(false)
const processCheckout = async () => {
    isProcessing.value = true
    try {
        const res = await api.post('/web/subscriptions/checkout', { upgrade_token: token })
        if (res.success && res.url) {
            window.location.href = res.url // Redirect to Stripe
        }
    } catch (e) {
        errorMsg.value = 'Failed to initiate checkout. Please try again.'
    } finally {
        isProcessing.value = false
    }
}

const linkSuccessMsg = ref('')
const linkErrorMsg = ref('')

const linkDeviceDirectly = async () => {
    isProcessing.value = true
    linkErrorMsg.value = ''
    try {
        const res = await api.post('/web/devices/link-upgrade-token', { upgrade_token: token })
        if (res.success) {
            linkSuccessMsg.value = 'Your device has been linked to your account. You can now close this window or return to your dashboard.'
        }
    } catch (e) {
        linkErrorMsg.value = e.response?.data?.message || 'Failed to link device. Please try again.'
    } finally {
        isProcessing.value = false
    }
}

const goBackToApp = () => {
    // If we're on a mobile device, this deep link will open the app.
    // Otherwise, we fallback to just telling the user what to do, or close the page.
    window.location.href = 'triggertime://'
    setTimeout(() => {
        // Fallback for desktop/where deep link fails
        alert('Please close this window and generate a new link from the TriggerTime application.')
    }, 500)
}
</script>

<style scoped>
.checkout-landing-container {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: calc(100vh - 160px);
  padding: 40px 20px;
}

.checkout-card {
  width: 100%;
  max-width: 500px;
  padding: 24px 24px 24px;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 3px solid rgba(255,255,255,0.1);
  border-radius: 50%;
  border-top-color: var(--primary);
  animation: spin 1s ease-in-out infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.state-icon {
  display: flex;
  justify-content: center;
  align-items: center;
}

.text-warning { color: var(--warning); }
.text-success { color: var(--success); }

.step-list {
  padding-left: 2rem;
  line-height: 1.6;
  text-align: left;
}

.step-list li {
  margin-bottom: 1rem;
  padding-left: 0.5rem;
}

.inline-block { display: inline-block; }
.text-left { text-align: left; }
.w-full { width: 100%; }
.text-primary { color: var(--primary); }
.bg-elevated { background-color: var(--bg-elevated); }
.border { border: 1px solid var(--border-subtle); }
.rounded-lg { border-radius: 12px; }
.py-8 { padding-top: 2rem; padding-bottom: 2rem; }
</style>
