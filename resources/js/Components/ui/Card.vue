<script setup>
defineProps({
  title: { type: String, default: null },
  padded: { type: Boolean, default: true },
})
</script>

<template>
  <div class="bg-surface border border-border rounded-lg shadow-sm">
    <div v-if="title || $slots.header" class="px-4 py-3 border-b border-border flex items-center justify-between">
      <slot name="header">
        <h3 class="text-base font-semibold text-foreground">{{ title }}</h3>
      </slot>
    </div>
    <div :class="padded ? 'p-4' : ''">
      <slot />
    </div>
    <div v-if="$slots.footer" class="px-4 py-3 border-t border-border bg-surface-muted">
      <slot name="footer" />
    </div>
  </div>
</template>
