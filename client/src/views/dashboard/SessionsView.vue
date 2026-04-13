<template>
  <div class="sessions-view">
    <div class="header-section mb-8 flex justify-between items-end">
      <div>
        <h1 class="mb-2">{{ $t('sessions.title') }}</h1>
        <p class="text-secondary m-0">{{ $t('sessions.subtitle') }}</p>
      </div>
      <div v-if="!isLoading && sessions.length > 0" class="session-count text-right">
        <div class="text-2xl font-heading font-bold">{{ totalCount }}</div>
        <div class="text-xs text-secondary uppercase tracking-wide">{{ $t('nav.sessions') }}</div>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="isLoading" class="loading-state text-center py-12">
      <div class="spinner-lg mb-4"></div>
      <p class="text-secondary">{{ $t('common.loading') }}</p>
    </div>

    <!-- Empty State -->
    <AppCard v-else-if="sessions.length === 0 && !isLoading" class="empty-state text-center py-12">
      <div class="empty-icon text-4xl mb-4">&#127919;</div>
      <p class="text-secondary">{{ $t('sessions.empty') }}</p>
    </AppCard>

    <!-- Sessions Table -->
    <SessionsTable
      v-else
      :sessions="sessions"
      :disciplines="disciplines"
      :types="types"
      :current-page="currentPage"
      :total-pages="totalPages"
      :sort="sort"
      :discipline="filterDiscipline"
      :session-type="filterType"
      @select="goToSession"
      @sort="handleSort"
      @filter-discipline="handleFilterDiscipline"
      @filter-type="handleFilterType"
      @page="handlePage"
    />
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { sessionsApi } from '@/api/sessions'
import AppCard from '@/components/ui/AppCard.vue'
import SessionsTable from '@/components/dashboard/SessionsTable.vue'

const router = useRouter()

const sessions = ref([])
const isLoading = ref(true)
const currentPage = ref(1)
const totalPages = ref(1)
const totalCount = ref(0)
const sort = ref('newest')
const filterDiscipline = ref('')
const filterType = ref('')

const disciplines = computed(() => {
  const set = new Set()
  sessions.value.forEach(s => {
    if (s.discipline_name) set.add(s.discipline_name)
  })
  return Array.from(set).sort()
})

const types = computed(() => {
  const set = new Set()
  sessions.value.forEach(s => {
    if (s.type) set.add(s.type)
  })
  return Array.from(set).sort()
})

const fetchSessions = async () => {
  isLoading.value = true
  try {
    const params = {
      page: currentPage.value,
      sort: sort.value === 'newest' ? 'newest' : 'oldest',
    }
    if (filterDiscipline.value) params.discipline = filterDiscipline.value
    if (filterType.value) params.type = filterType.value

    const response = await sessionsApi.getSessions(params)
    if (response.success) {
      sessions.value = response.sessions || []
      totalCount.value = response.pagination?.total || sessions.value.length
      totalPages.value = response.pagination?.pages || 1
    }
  } finally {
    isLoading.value = false
  }
}

const goToSession = (uuid) => {
  router.push({ name: 'session-detail', params: { uuid } })
}

const handleSort = (val) => {
  sort.value = val
  currentPage.value = 1
  fetchSessions()
}

const handleFilterDiscipline = (val) => {
  filterDiscipline.value = val
  currentPage.value = 1
  fetchSessions()
}

const handleFilterType = (val) => {
  filterType.value = val
  currentPage.value = 1
  fetchSessions()
}

const handlePage = (page) => {
  currentPage.value = page
  fetchSessions()
}

onMounted(fetchSessions)
</script>

<style scoped>
.font-heading { font-family: var(--font-heading); }
.text-2xl { font-size: 1.5rem; }
.text-xs { font-size: 0.75rem; }
.text-4xl { font-size: 2.25rem; }
.m-0 { margin: 0; }
.mb-2 { margin-bottom: 0.5rem; }
.mb-4 { margin-bottom: 1rem; }
.mb-8 { margin-bottom: 2rem; }
.py-12 { padding-top: 3rem; padding-bottom: 3rem; }
.font-bold { font-weight: 700; }
.tracking-wide { letter-spacing: 0.05em; }
.uppercase { text-transform: uppercase; }
.text-center { text-align: center; }
.text-secondary { color: var(--text-secondary); }
.flex { display: flex; }
.justify-between { justify-content: space-between; }
.items-end { align-items: flex-end; }

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

@media (max-width: 640px) {
  .header-section {
    flex-direction: column;
    align-items: flex-start !important;
    gap: 16px;
  }
  .session-count {
    text-align: left !important;
  }
}
</style>
