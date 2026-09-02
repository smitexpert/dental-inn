<script setup>
/**
 * FilterBar
 * ---------
 * Card-styled filter row. Renders a title (with a filter icon) on the
 * left, an `aside` slot on the right (counts, reset, etc.), and a
 * responsive grid for the default filter inputs.
 */
import Icon from './Icon.vue'

defineProps({
  title: { type: String, default: 'Filters' },
})
</script>

<template>
  <div class="app-card p-4">
    <div v-if="title || $slots.aside" class="flex items-center justify-between mb-3 gap-3">
      <div class="flex items-center gap-2 text-sm font-medium text-base-content min-w-0">
        <Icon name="filter" size="sm" class="text-muted" />
        <span class="truncate">{{ title }}</span>
        <slot name="title" />
      </div>
      <div v-if="$slots.aside" class="flex items-center gap-2 shrink-0">
        <slot name="aside" />
      </div>
    </div>
    <div class="grid gap-3 grid-cols-1 md:grid-cols-2 lg:grid-cols-4">
      <slot />
    </div>
  </div>
</template>
