<script setup>
/**
 * Stat
 * ----
 * Single KPI tile with a tone-tinted icon medallion, value, optional hint,
 * and trend indicator.
 */
import { computed } from 'vue'
import Icon from './Icon.vue'

const props = defineProps({
  label: { type: String, required: true },
  value: { type: [String, Number], required: true },
  hint: { type: String, default: null },
  icon: { type: String, default: null },
  tone: {
    type: String,
    default: 'primary',
    validator: (v) =>
      ['primary', 'success', 'warning', 'danger', 'info', 'neutral'].includes(v),
  },
  trend: {
    type: String,
    default: null,
    validator: (v) => v === null || ['up', 'down', 'flat'].includes(v),
  },
  trendValue: { type: String, default: null },
})

const toneClass = {
  primary: 'bg-primary/10 text-primary',
  success: 'bg-success/10 text-success',
  warning: 'bg-warning/10 text-warning',
  danger: 'bg-error/10 text-error',
  info: 'bg-info/10 text-info',
  neutral: 'bg-base-200 text-muted',
}

const trendClass = computed(() => {
  if (props.trend === 'up') return 'text-success'
  if (props.trend === 'down') return 'text-error'
  return 'text-muted'
})

const trendIcon = computed(() => {
  if (props.trend === 'up') return 'arrow-up'
  if (props.trend === 'down') return 'arrow-down'
  return 'arrow-right'
})
</script>

<template>
  <div class="app-card p-4 flex items-start gap-3">
    <div
      v-if="icon"
      :class="['h-10 w-10 shrink-0 rounded-xl inline-flex items-center justify-center', toneClass[tone]]"
    >
      <Icon :name="icon" size="md" />
    </div>
    <div class="min-w-0 flex-1">
      <p class="text-xs uppercase tracking-wide text-muted font-medium">
        {{ label }}
      </p>
      <p class="text-2xl font-semibold text-base-content mt-1 leading-tight">
        {{ value }}
      </p>
      <div v-if="hint || trend" class="mt-1 flex items-center gap-2 text-xs">
        <span v-if="hint" class="text-muted">{{ hint }}</span>
        <span
          v-if="trend"
          :class="['inline-flex items-center gap-0.5 font-medium', trendClass]"
        >
          <Icon :name="trendIcon" size="xs" />
          <span>{{ trendValue }}</span>
        </span>
      </div>
    </div>
  </div>
</template>
