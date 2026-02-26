<template>
  <div class="admin-subscriptions">
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold font-heading">Subscriptions</h1>
    </div>

    <!-- Error State -->
    <div v-if="error" class="bg-red-500/10 border border-red-500/20 text-red-500 p-4 rounded-xl mb-6">
      {{ error }}
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center p-12">
      <div class="animate-spin h-8 w-8 border-4 border-[var(--primary)] border-t-transparent rounded-full"></div>
    </div>

    <!-- Subscriptions Table -->
    <div v-else class="table-card mt-6 mb-6">
      <div class="table-wrapper">
        <table class="admin-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Stripe Ext ID</th>
              <th>Plan</th>
              <th>Status</th>
              <th>Dates</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="sub in subscriptions" :key="sub.id" class="hover:bg-white/5 transition-colors group">
              <td>
                <div v-if="sub.user" class="font-medium text-sm text-primary">
                  <span v-if="sub.user.first_name || sub.user.last_name">{{ sub.user.first_name }} {{ sub.user.last_name }}</span>
                  <span v-else>{{ sub.user.email }}</span>
                </div>
                <div v-else class="font-medium text-sm text-primary">User ID: {{ sub.user_id }}</div>
                <div class="text-xs text-secondary opacity-50">{{ sub.id }}</div>
              </td>
              <td class="text-sm font-mono">{{ sub.stripe_subscription_id }}</td>
              <td>
                <span class="badge badge-purple uppercase tracking-wider">
                  {{ sub.plan }}
                </span>
              </td>
              <td>
                <span v-if="sub.status === 'active'" class="badge badge-green">{{ sub.status }}</span>
                <span v-else class="badge badge-gray">{{ sub.status }}</span>
              </td>
              <td class="text-xs tabular-nums text-secondary">
                <div>Start: {{ sub.current_period_start ? new Date(sub.current_period_start).toLocaleDateString() : 'N/A' }}</div>
                <div>End: {{ sub.current_period_end ? new Date(sub.current_period_end).toLocaleDateString() : (sub.valid_until ? new Date(sub.valid_until).toLocaleDateString() : 'Never') }}</div>
              </td>
            </tr>
            <tr v-if="subscriptions.length === 0">
              <td colspan="5" class="p-8 text-center text-secondary">No subscriptions found.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { adminApi } from '@/api/admin'

const loading = ref(true)
const error = ref('')
const subscriptions = ref([])

const loadSubscriptions = async () => {
  loading.value = true
  error.value = ''
  try {
    const response = await adminApi.getSubscriptions()
    if (response) {
      // CakePHP's default .all() might return [{}] directly if not wrapped in "success": true
      if (response.success) {
          subscriptions.value = response.subscriptions
      } else {
          subscriptions.value = response
      }
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to load subscriptions'
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadSubscriptions()
})
</script>
