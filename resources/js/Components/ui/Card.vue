<script setup>
/**
 * Card
 * ----
 * Surface container with header, body, footer, and four visual variants.
 * Slots follow conventional naming (`header`, `actions`, `footer`, default).
 */
import { computed } from 'vue'

const props = defineProps({
  variant: {
    type: String,
    default: 'default',
    validator: (v) => ['default', 'elevated', 'outlined', 'accent'].includes(v),
  },
  padded: { type: Boolean, default: true },
  title: { type: String, default: null },
  subtitle: { type: String, default: null },
})

const wrapperClass = computed(() => {
  switch (props.variant) {
    case 'elevated':
      return 'app-card shadow-(--shadow-pop)'
    case 'outlined':
      return 'rounded-xl border border-(--color-line-strong) bg-transparent'
    case 'accent':
      return 'app-card border-primary/30 bg-primary/5'
    default:
      return 'app-card'
  }
})
</script>

<template>
  <div :class="wrapperClass">
    <div
      v-if="title || $slots.header || $slots.actions"
      class="px-4 py-3 border-b border-(--color-line) flex items-start justify-between gap-3"
    >
      <slot name="header">
        <div>
          <h3 class="app-section-title text-base-content">{{ title }}</h3>
          <p v-if="subtitle" class="text-xs text-muted mt-0.5">{{ subtitle }}</p>
        </div>
      </slot>
      <div v-if="$slots.actions" class="flex items-center gap-2 shrink-0">
        <slot name="actions" />
      </div>
    </div>
    <div :class="padded ? 'p-4' : ''">
      <slot />
    </div>
    <div
      v-if="$slots.footer"
      class="px-4 py-3 border-t border-(--color-line) bg-base-200/40"
    >
      <slot name="footer" />
    </div>
  </div>
</template>
