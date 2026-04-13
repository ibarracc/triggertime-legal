<template>
  <div class="session-detail-view">
    <!-- Back Button -->
    <div class="mb-6">
      <AppButton variant="ghost" size="sm" @click="goBack">
        &larr; {{ $t('sessions.back_to_list') }}
      </AppButton>
    </div>

    <!-- Loading -->
    <div v-if="isLoading" class="loading-state text-center py-12">
      <div class="spinner-lg mb-4"></div>
      <p class="text-secondary">{{ $t('common.loading') }}</p>
    </div>

    <template v-else-if="session">
      <!-- Header -->
      <div class="header-section mb-8">
        <div class="flex items-center gap-3 flex-wrap mb-2">
          <h1 class="m-0">{{ session.discipline_name || $t('sessions.detail_title') }}</h1>
          <AppBadge v-if="session.type" variant="primary">{{ session.type }}</AppBadge>
          <AppBadge v-if="session.auto_closed" variant="warning">{{ $t('sessions.auto_closed') }}</AppBadge>
        </div>
        <p class="text-secondary m-0">{{ formatDate(session.date) }}</p>
      </div>

      <!-- Location & Notes -->
      <div v-if="session.location || session.notes" class="meta-section mb-6 flex flex-col gap-2">
        <div v-if="session.location" class="text-sm">
          <span class="text-secondary">{{ $t('sessions.location') }}:</span> {{ session.location }}
        </div>
        <div v-if="session.notes" class="text-sm">
          <span class="text-secondary">{{ $t('sessions.notes') }}:</span> {{ session.notes }}
        </div>
      </div>

      <!-- Score Summary -->
      <div class="score-cards mb-8 grid gap-4">
        <AppCard>
          <div class="score-block text-center">
            <div class="text-3xl font-heading font-bold">{{ session.total_score ?? '-' }}</div>
            <div class="text-xs text-secondary uppercase tracking-wide mt-1">{{ $t('sessions.total_score') }}</div>
          </div>
        </AppCard>
        <AppCard>
          <div class="score-block text-center">
            <div class="text-3xl font-heading font-bold">{{ session.total_x_count ?? '-' }}</div>
            <div class="text-xs text-secondary uppercase tracking-wide mt-1">{{ $t('sessions.total_x_count') }}</div>
          </div>
        </AppCard>
      </div>

      <!-- Scorecard -->
      <AppCard>
        <SessionScorecard
          :series="session.sync_series || []"
          :strings="session.sync_strings || []"
        />
      </AppCard>
    </template>

    <!-- Error / Not Found -->
    <AppCard v-else class="text-center py-12">
      <p class="text-secondary">{{ $t('common.no_data') }}</p>
    </AppCard>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { sessionsApi } from '@/api/sessions'
import AppButton from '@/components/ui/AppButton.vue'
import AppBadge from '@/components/ui/AppBadge.vue'
import AppCard from '@/components/ui/AppCard.vue'
import SessionScorecard from '@/components/dashboard/SessionScorecard.vue'

const route = useRoute()
const router = useRouter()
const { locale } = useI18n()

const session = ref(null)
const isLoading = ref(true)

const goBack = () => {
  router.push({ name: 'sessions' })
}

const formatDate = (dateStr) => {
  if (!dateStr) return '-'
  try {
    return new Date(dateStr).toLocaleDateString(locale.value, {
      year: 'numeric',
      month: 'long',
      day: 'numeric',
    })
  } catch {
    return dateStr
  }
}

const fetchSession = async () => {
  isLoading.value = true
  try {
    const uuid = route.params.uuid
    const response = await sessionsApi.getSession(uuid)
    if (response.success) {
      session.value = response.session
    }
  } finally {
    isLoading.value = false
  }
}

onMounted(fetchSession)
</script>

<style scoped>
.font-heading { font-family: var(--font-heading); }
.text-3xl { font-size: 1.875rem; }
.text-xs { font-size: 0.75rem; }
.text-sm { font-size: 0.875rem; }
.m-0 { margin: 0; }
.mt-1 { margin-top: 0.25rem; }
.mb-2 { margin-bottom: 0.5rem; }
.mb-4 { margin-bottom: 1rem; }
.mb-6 { margin-bottom: 1.5rem; }
.mb-8 { margin-bottom: 2rem; }
.py-12 { padding-top: 3rem; padding-bottom: 3rem; }
.font-bold { font-weight: 700; }
.tracking-wide { letter-spacing: 0.05em; }
.uppercase { text-transform: uppercase; }
.text-center { text-align: center; }
.text-secondary { color: var(--text-secondary); }
.flex { display: flex; }
.flex-col { flex-direction: column; }
.flex-wrap { flex-wrap: wrap; }
.items-center { align-items: center; }
.gap-2 { gap: 0.5rem; }
.gap-3 { gap: 0.75rem; }
.gap-4 { gap: 1rem; }

.score-cards {
  grid-template-columns: repeat(2, 1fr);
}

.grid {
  display: grid;
}

.spinner-lg {
  width: 40px;
  height: 40px;
  border: 4px solid rgba(255, 255, 255, 0.1);
  border-radius: 50%;
  border-top-color: var(--primary);
  animation: spin 1s ease-in-out infinite;
  margin: 0 auto;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

@media (max-width: 480px) {
  .score-cards {
    grid-template-columns: 1fr;
  }
}
</style>
