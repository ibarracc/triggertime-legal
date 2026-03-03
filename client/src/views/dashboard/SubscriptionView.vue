<template>
  <div class="subscription-view">
    <div class="header-section mb-8">
      <h1 class="mb-2">{{ $t('nav.subscription') }}</h1>
      <p class="text-secondary">{{ $t('subscription.subtitle') }}</p>
    </div>

    <AppCard class="mb-8">
      <template #header>
        <div class="flex items-center gap-3">
          <span class="plan-icon">💎</span>
          {{ $t('subscription.current_plan') }}
        </div>
      </template>

      <div class="plan-details flex justify-between items-center mb-4">
        <div>
          <div class="flex items-center gap-3 mb-2">
            <h2 class="m-0">{{ auth.isProPlus ? 'Pro+' : $t('dashboard.free_plan') }}</h2>
            <AppBadge :variant="auth.isProPlus ? 'success' : 'neutral'">
              {{ auth.isProPlus ? $t('dashboard.active') : $t('subscription.current_plan') }}
            </AppBadge>
          </div>
          <p class="text-secondary m-0">
            {{ 
              auth.isProPlus 
                ? (auth.subscription?.current_period_end ? `${$t('subscription.next_billing')}: ${formatDate(auth.subscription.current_period_end)}.` : `${$t('dashboard.subscription_status')}: ${$t('dashboard.active')}`)
                : $t('subscription.subtitle') 
            }}
          </p>
        </div>
        
        <div class="text-right">
          <div class="price font-heading text-2xl font-bold">
            {{ auth.isProPlus ? '$4.99' : '$0.00' }}<span class="text-sm text-secondary font-body font-normal">/{{ $t('subscription.per_month') }}</span>
          </div>
        </div>
      </div>

      <div v-if="auth.isProPlus && auth.subscription?.cancel_at_period_end" class="cancel-banner mb-6">
        <div class="cancel-banner-icon">⚠️</div>
        <div class="cancel-banner-content">
          <div class="cancel-banner-title">{{ $t('subscription.canceled_title') }}</div>
          <div class="cancel-banner-desc">
            {{ $t('subscription.canceled_desc', { date: auth.subscription?.current_period_end ? formatDate(auth.subscription.current_period_end) : '...' }) }}
          </div>
        </div>
      </div>

      <div v-if="errorMessage" class="alert alert-danger mb-6">
        <span>❌</span>
        <span>{{ errorMessage }}</span>
      </div>

      <div class="border-t border-subtle pt-6 flex justify-end">
        <AppButton 
          v-if="auth.isProPlus" 
          variant="secondary" 
          @click="openCustomerPortal"
          :loading="isLoading"
        >
          {{ $t('subscription.manage_billing') }}
        </AppButton>
        <AppButton 
          v-else 
          variant="primary" 
          @click="startCheckout"
          :loading="isLoading"
        >
          {{ $t('subscription.upgrade_now') }}
        </AppButton>
      </div>
    </AppCard>

    <div class="features-list glass-card p-6 rounded-lg">
      <h3 class="mb-4">{{ $t('subscription.pro_features') }}</h3>
      <ul class="check-list text-secondary">
        <li>{{ $t('subscription.multi_device_sync') }}</li>
        <li>{{ $t('subscription.advanced_analytics') }}</li>
        <li>{{ $t('subscription.priority_support') }}</li>
        <li>{{ $t('subscription.unlimited_sessions') }}</li>
      </ul>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useI18n } from 'vue-i18n'
import AppCard from '@/components/ui/AppCard.vue'
import AppBadge from '@/components/ui/AppBadge.vue'
import AppButton from '@/components/ui/AppButton.vue'
import subscriptionsApi from '@/api/subscriptions'

const auth = useAuthStore()
const { t, locale } = useI18n()
const isLoading = ref(false)
const errorMessage = ref('')

const formatDate = (dateString) => {
  if (!dateString) return ''
  const date = new Date(dateString)
  return date.toLocaleDateString(locale.value, {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const openCustomerPortal = async () => {
  isLoading.value = true
  errorMessage.value = ''
  try {
    const res = await subscriptionsApi.getPortalUrl()
    if (res.success && res.url) {
      window.location.href = res.url
    } else {
      errorMessage.value = t('common.error')
    }
  } catch (error) {
    console.error('Portal Error:', error)
    errorMessage.value = t('common.error')
  } finally {
    isLoading.value = false
  }
}

const startCheckout = async () => {
  isLoading.value = true
  errorMessage.value = ''
  try {
    const res = await subscriptionsApi.createCheckout()
    if (res.success && res.url) {
      window.location.href = res.url
    } else {
      errorMessage.value = t('common.error')
    }
  } catch (error) {
    console.error('Checkout Error:', error)
    errorMessage.value = t('common.error')
  } finally {
    isLoading.value = false
  }
}
</script>

<style scoped>
.plan-icon { font-size: 1.5rem; }
.border-t { border-top: 1px solid var(--border-subtle); }
.pt-6 { padding-top: 1.5rem; }
.m-0 { margin: 0; }
.text-sm { font-size: 0.875rem; }
.text-2xl { font-size: 1.5rem; border-color: transparent;}
.font-normal { font-weight: 400; }
.font-body { font-family: var(--font-body); }
.font-heading { font-family: var(--font-heading); }

.check-list {
  list-style: none;
  padding: 0;
}
.check-list li {
  position: relative;
  padding-left: 28px;
  margin-bottom: 12px;
}
.check-list li::before {
  content: '✓';
  position: absolute;
  left: 0;
  color: var(--primary);
  font-weight: bold;
}

.cancel-banner {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 14px 18px;
  border-radius: 10px;
  background: rgba(251, 191, 36, 0.08);
  border: 1px solid rgba(251, 191, 36, 0.25);
  font-size: 0.875rem;
  line-height: 1.5;
  color: var(--text-secondary);
}

.cancel-banner-icon {
  font-size: 1.15rem;
  flex-shrink: 0;
  margin-top: 1px;
}

.cancel-banner-title {
  color: #FBBF24;
  font-weight: 600;
  margin-bottom: 2px;
}

.cancel-banner-desc {
  word-wrap: break-word; /* Ensure unwrappable text doesn't burst out */
  overflow-wrap: break-word;
}

.cancel-banner-content strong {
  color: var(--text-primary);
}

@media (max-width: 640px) {
  .plan-details {
    flex-direction: column;
    align-items: flex-start !important;
    gap: 16px;
  }
  .plan-details .text-right {
    text-align: left;
  }
}
</style>
