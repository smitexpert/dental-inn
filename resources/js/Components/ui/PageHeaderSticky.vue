<script setup>
/**
 * PageHeaderSticky
 * ----------------
 * Sticky page header that sits below the topbar (`top-16`) and renders
 * a breadcrumb above the H1 with optional meta/actions slots. The
 * parent layout must reserve `sticky-page-header-spacer` height below it.
 */
import Breadcrumbs from './Breadcrumbs.vue'

defineProps({
  title:      { type: String, required: true },
  breadcrumb: { type: Array,  default: () => [] }, // [{ label, href? }]
  meta:       { type: String, default: null },
})
</script>

<template>
  <header class="sticky-page-header flex items-center justify-between gap-4">
    <div class="min-w-0">
      <Breadcrumbs :items="breadcrumb" />
      <h1 class="text-2xl font-semibold text-base-content tracking-tight mt-1 truncate">
        {{ title }}
      </h1>
      <p v-if="meta" class="text-sm text-muted mt-0.5">{{ meta }}</p>
      <slot name="meta" v-else />
    </div>
    <div v-if="$slots.actions" class="flex items-center gap-2 shrink-0">
      <slot name="actions" />
    </div>
  </header>
</template>
