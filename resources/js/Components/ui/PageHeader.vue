<script setup>
/**
 * PageHeader
 * ----------
 * Title block for the top of a page. Renders an optional eyebrow, a
 * gradient icon medallion, the title, an optional subtitle, and an
 * `actions` slot on the right.
 */
import Icon from './Icon.vue'

defineProps({
  title: { type: String, required: true },
  subtitle: { type: String, default: null },
  icon: { type: String, default: null },
  eyebrow: { type: String, default: null },
})
</script>

<template>
  <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
    <div class="min-w-0 flex items-start gap-3">
      <div
        v-if="icon"
        class="h-11 w-11 shrink-0 rounded-xl cta-gradient inline-flex items-center justify-center text-primary-content shadow-(--shadow-pop)"
      >
        <Icon :name="icon" size="lg" />
      </div>
      <div class="min-w-0">
        <p v-if="eyebrow" class="app-section-title">{{ eyebrow }}</p>
        <h1 class="text-2xl font-semibold tracking-tight text-base-content leading-tight">
          {{ title }}
        </h1>
        <p v-if="subtitle" class="text-sm text-(--color-muted) mt-1 max-w-2xl">
          {{ subtitle }}
        </p>
      </div>
    </div>
    <div v-if="$slots.actions" class="flex items-center gap-2 shrink-0 flex-wrap">
      <slot name="actions" />
    </div>
  </div>
</template>
