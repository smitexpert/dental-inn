<script setup>
/**
 * KpiTile (variant B)
 * -------------------
 * Single KPI card with label, value, optional sparkline (series) or
 * progress bar (bar), and an optional caption slot. Larger, softer than
 * the legacy Stat tile.
 */
import { computed } from 'vue'

const props = defineProps({
  label:  { type: String, required: true },
  value:  { type: [String, Number], required: true },
  series: { type: Array, default: null },           // number[] → sparkline
  bar:    { type: Object, default: null },          // { filled, total }
  format: { type: String, default: 'number' },      // 'number' | 'currency' | 'percent'
})

const sparkPath = computed(() => {
  if (!props.series || props.series.length < 2) return null
  const w = 120
  const h = 36
  const min = Math.min(...props.series)
  const max = Math.max(...props.series)
  const range = max - min || 1
  const step = w / (props.series.length - 1)
  return props.series
    .map((v, i) => {
      const x = i * step
      const y = h - ((v - min) / range) * h
      return `${i === 0 ? 'M' : 'L'}${x.toFixed(1)},${y.toFixed(1)}`
    })
    .join(' ')
})

const barPct = computed(() => {
  if (!props.bar || !props.bar.total) return 0
  return Math.min(100, Math.round((props.bar.filled / props.bar.total) * 100))
})
</script>

<template>
  <div class="kpi-tile">
    <p class="text-xs font-medium uppercase tracking-[0.06em] text-muted">
      {{ label }}
    </p>
    <p class="text-3xl font-semibold text-base-content leading-tight tracking-tight tabular-nums">
      {{ value }}
    </p>

    <svg
      v-if="sparkPath"
      :viewBox="`0 0 120 36`"
      class="w-full h-9 text-primary"
      preserveAspectRatio="none"
      aria-hidden="true"
    >
      <path :d="sparkPath" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
    </svg>

    <div
      v-else-if="bar"
      data-testid="kpi-bar"
      class="h-2 w-full rounded-full bg-base-200 overflow-hidden"
    >
      <div
        class="h-full rounded-full bg-primary transition-all duration-300"
        :style="{ width: `${barPct}%` }"
      />
    </div>

    <div v-if="$slots.caption" class="text-xs text-muted">
      <slot name="caption" />
    </div>
    <slot v-else />
  </div>
</template>
