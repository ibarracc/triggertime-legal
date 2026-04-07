<template>
  <div class="sessions-table-wrapper">
    <!-- Filters -->
    <div class="filters-row mb-6 flex flex-wrap gap-3 items-center">
      <select v-model="localSort" @change="$emit('sort', localSort)" class="filter-select">
        <option value="newest">{{ $t('sessions.sort_newest') }}</option>
        <option value="oldest">{{ $t('sessions.sort_oldest') }}</option>
      </select>
      <select v-model="localDiscipline" @change="$emit('filter-discipline', localDiscipline)" class="filter-select">
        <option value="">{{ $t('sessions.filter_discipline') }}</option>
        <option v-for="d in disciplines" :key="d" :value="d">{{ d }}</option>
      </select>
      <select v-model="localType" @change="$emit('filter-type', localType)" class="filter-select">
        <option value="">{{ $t('sessions.filter_type') }}</option>
        <option v-for="t in types" :key="t" :value="t">{{ t }}</option>
      </select>
    </div>

    <!-- Table -->
    <div class="table-container">
      <table class="sessions-table">
        <thead>
          <tr>
            <th>{{ $t('sessions.date') }}</th>
            <th>{{ $t('sessions.discipline') }}</th>
            <th>{{ $t('sessions.type') }}</th>
            <th>{{ $t('sessions.location') }}</th>
            <th class="text-right">{{ $t('sessions.total_score') }}</th>
            <th class="text-right">{{ $t('sessions.total_x_count') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="session in sessions"
            :key="session.id"
            class="session-row"
            @click="$emit('select', session.uuid)"
          >
            <td>{{ formatDate(session.session_date) }}</td>
            <td>{{ session.discipline_name || '-' }}</td>
            <td>{{ session.session_type || '-' }}</td>
            <td>{{ session.location || '-' }}</td>
            <td class="text-right font-bold">{{ session.total_score ?? '-' }}</td>
            <td class="text-right">{{ session.total_x_count ?? '-' }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div v-if="totalPages > 1" class="pagination mt-6 flex justify-center gap-2 items-center">
      <AppButton
        variant="secondary"
        size="sm"
        :disabled="currentPage <= 1"
        @click="$emit('page', currentPage - 1)"
      >
        &laquo;
      </AppButton>
      <span class="text-sm text-secondary px-3">
        {{ currentPage }} / {{ totalPages }}
      </span>
      <AppButton
        variant="secondary"
        size="sm"
        :disabled="currentPage >= totalPages"
        @click="$emit('page', currentPage + 1)"
      >
        &raquo;
      </AppButton>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import AppButton from '@/components/ui/AppButton.vue'

const { locale } = useI18n()

const props = defineProps({
  sessions: { type: Array, default: () => [] },
  disciplines: { type: Array, default: () => [] },
  types: { type: Array, default: () => [] },
  currentPage: { type: Number, default: 1 },
  totalPages: { type: Number, default: 1 },
  sort: { type: String, default: 'newest' },
  discipline: { type: String, default: '' },
  sessionType: { type: String, default: '' },
})

defineEmits(['select', 'sort', 'filter-discipline', 'filter-type', 'page'])

const localSort = ref(props.sort)
const localDiscipline = ref(props.discipline)
const localType = ref(props.sessionType)

watch(() => props.sort, (v) => { localSort.value = v })
watch(() => props.discipline, (v) => { localDiscipline.value = v })
watch(() => props.sessionType, (v) => { localType.value = v })

const formatDate = (dateStr) => {
  if (!dateStr) return '-'
  try {
    return new Date(dateStr).toLocaleDateString(locale.value)
  } catch {
    return dateStr
  }
}
</script>

<style scoped>
.filter-select {
  background: var(--bg-surface);
  border: 1px solid var(--border-subtle);
  border-radius: 8px;
  padding: 8px 12px;
  color: var(--text-primary);
  font-size: 0.875rem;
  cursor: pointer;
}

.filter-select:focus {
  outline: none;
  border-color: var(--primary);
}

.table-container {
  overflow-x: auto;
}

.sessions-table {
  width: 100%;
  border-collapse: collapse;
}

.sessions-table th {
  text-align: left;
  padding: 12px 16px;
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: var(--text-secondary);
  border-bottom: 1px solid var(--border-subtle);
  white-space: nowrap;
}

.sessions-table td {
  padding: 14px 16px;
  font-size: 0.875rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.04);
  color: var(--text-primary);
}

.session-row {
  cursor: pointer;
  transition: background 0.15s ease;
}

.session-row:hover {
  background: rgba(255, 255, 255, 0.04);
}

.text-right { text-align: right; }
.font-bold { font-weight: 700; }
.text-sm { font-size: 0.875rem; }
.text-secondary { color: var(--text-secondary); }
.px-3 { padding-left: 0.75rem; padding-right: 0.75rem; }
.mt-6 { margin-top: 1.5rem; }
.mb-6 { margin-bottom: 1.5rem; }
.flex { display: flex; }
.flex-wrap { flex-wrap: wrap; }
.gap-2 { gap: 0.5rem; }
.gap-3 { gap: 0.75rem; }
.items-center { align-items: center; }
.justify-center { justify-content: center; }
</style>
