<script setup>
defineProps({
  label: { type: String, required: true },
  value: { type: [String, Number], required: true },
  hint: { type: String, default: null },
  trend: { type: String, default: null, validator: (v) => v === null || ['up', 'down', 'flat'].includes(v) },
  trendValue: { type: String, default: null },
})
</script>

<template>
  <div class="bg-base-100 border border-base-300 rounded-lg p-4">
    <p class="text-xs uppercase tracking-wide text-base-content/70 font-medium">{{ label }}</p>
    <p class="text-2xl font-semibold text-base-content mt-2">{{ value }}</p>
    <p v-if="hint" class="text-xs text-base-content/70 mt-1">{{ hint }}</p>
    <p
      v-if="trend"
      :class="[
        'text-xs mt-2 font-medium inline-flex items-center gap-1',
        trend === 'up' ? 'text-success' : trend === 'down' ? 'text-error' : 'text-base-content/70',
      ]"
    >
      <span v-if="trend === 'up'">↑</span>
      <span v-else-if="trend === 'down'">↓</span>
      <span v-else>→</span>
      {{ trendValue }}
    </p>
  </div>
</template>
