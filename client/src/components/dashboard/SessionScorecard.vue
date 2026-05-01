<template>
  <div class="scorecard">
    <!-- Series (with shots) -->
    <div v-if="series && series.length > 0" class="scorecard-section mb-6">
      <h3 class="section-title mb-4">{{ $t('sessions.series') }}</h3>
      <div class="series-list">
        <div
          v-for="(s, idx) in series"
          :key="s.id || idx"
          class="series-item mb-4"
        >
          <div class="series-header flex items-center gap-2 mb-2">
            <span class="series-label font-medium">
              {{ s.is_sighting ? $t('sessions.sighting') : `${$t('sessions.series')} ${s.series_number_within_phase || idx + 1}` }}
            </span>
            <AppBadge v-if="s.is_sighting" variant="neutral">{{ $t('sessions.sighting') }}</AppBadge>
            <span v-if="s.total_score != null" class="series-score ml-auto font-bold">{{ s.total_score }}</span>
          </div>
          <div v-if="s.sync_shots && s.sync_shots.length > 0" class="shots-row flex flex-wrap gap-2">
            <div
              v-for="(shot, sIdx) in s.sync_shots"
              :key="shot.id || sIdx"
              class="shot-circle"
              :class="{ 'shot-x': shot.is_x }"
              :title="shotLabel(shot)"
            >
              {{ shotDisplay(shot) }}
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Strings -->
    <div v-if="strings && strings.length > 0" class="scorecard-section">
      <h3 class="section-title mb-4">{{ $t('sessions.strings') }}</h3>
      <div class="strings-list">
        <div
          v-for="(str, idx) in strings"
          :key="str.id || idx"
          class="string-item flex items-center justify-between py-2 border-b"
        >
          <span class="text-sm">
            {{ str.is_sighting ? $t('sessions.sighting') : `${$t('sessions.strings')} ${str.string_number_within_phase || idx + 1}` }}
          </span>
          <span class="font-bold">{{ str.total_score ?? '-' }}</span>
        </div>
      </div>
    </div>

    <div v-if="(!series || series.length === 0) && (!strings || strings.length === 0)" class="text-secondary text-sm">
      {{ $t('common.no_data') }}
    </div>
  </div>
</template>

<script setup>
import AppBadge from '@/components/ui/AppBadge.vue'

defineProps({
  series: { type: Array, default: () => [] },
  strings: { type: Array, default: () => [] },
})

const shotDisplay = (shot) => {
  if (shot.is_x) return 'X'
  if (shot.value != null) return String(shot.value)
  return '-'
}

const shotLabel = (shot) => {
  let label = `${shot.value ?? '-'}`
  if (shot.is_x) label += ' (X)'
  return label
}
</script>

<style scoped>
.section-title {
  font-family: var(--font-heading);
  font-size: 1rem;
  font-weight: 600;
  color: var(--text-primary);
}

.series-label {
  font-size: 0.875rem;
  color: var(--text-secondary);
}

.series-score {
  font-size: 1rem;
  color: var(--text-primary);
}

.shot-circle {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.75rem;
  font-weight: 700;
  background: var(--bg-elevated);
  border: 1px solid var(--border-subtle);
  color: var(--text-primary);
}

.shot-x {
  background: var(--primary-a15);
  border-color: var(--primary-a40);
  color: var(--primary);
}

.string-item {
  border-bottom: 1px solid var(--border-subtle);
}

.flex { display: flex; }
.flex-wrap { flex-wrap: wrap; }
.items-center { align-items: center; }
.justify-between { justify-content: space-between; }
.gap-2 { gap: 0.5rem; }
.mb-2 { margin-bottom: 0.5rem; }
.mb-4 { margin-bottom: 1rem; }
.mb-6 { margin-bottom: 1.5rem; }
.ml-auto { margin-left: auto; }
.py-2 { padding-top: 0.5rem; padding-bottom: 0.5rem; }
.font-medium { font-weight: 500; }
.font-bold { font-weight: 700; }
.text-sm { font-size: 0.875rem; }
.text-secondary { color: var(--text-secondary); }
</style>
