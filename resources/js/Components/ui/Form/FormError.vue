<script setup>
import { computed } from 'vue'

const props = defineProps({
  errors: { type: Object, default: () => ({}) },
})

/**
 * Filter Laravel-style field errors down to entries the user can act on. We
 * skip entries whose key starts with `_` — those are application-level markers
 * (e.g. a `_summary` line we attach in `withValidator`) and would otherwise
 * duplicate the banner text.
 */
const fieldErrors = computed(() =>
  Object.fromEntries(
    Object.entries(props.errors).filter(([key]) => !key.startsWith('_')),
  ),
)
</script>

<template>
  <div
    v-if="Object.keys(fieldErrors).length"
    class="rounded-md border border-error/30 bg-error/10 p-3 text-sm text-error"
  >
    <p class="font-semibold mb-1">Please correct the following:</p>
    <ul class="list-disc list-inside space-y-0.5">
      <li v-for="(msg, field) in fieldErrors" :key="field">{{ msg }}</li>
    </ul>
  </div>
</template>
