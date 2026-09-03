<script setup>
/**
 * EmptyState
 * ----------
 * Centered placeholder for empty lists, zero-result searches, etc. Renders
 * a tone-tinted icon medallion, title, optional description, and an
 * optional action slot.
 */
import { computed } from 'vue'
import Icon from './Icon.vue'

const props = defineProps({
  title: { type: String, required: true },
  description: { type: String, default: null },
  icon: { type: String, default: 'inbox' },
  tone: {
    type: String,
    default: 'neutral',
    validator: (v) => ['neutral', 'primary', 'success', 'warning', 'danger'].includes(v),
  },
})

const toneClass = {
  neutral: 'bg-base-200 text-muted',
  primary: 'bg-primary/10 text-primary',
  success: 'bg-success/10 text-success',
  warning: 'bg-warning/10 text-warning',
  danger: 'bg-error/10 text-error',
}

const iconTone = computed(() => toneClass[props.tone])
</script>

<template>
  <div class="app-card p-10 flex flex-col items-center justify-center text-center gap-3">
    <div class="h-14 w-14 rounded-2xl bg-base-200 text-base-content/70 inline-flex items-center justify-center">
      <slot name="icon">
        <Icon :name="icon" size="lg" />
      </slot>
    </div>
    <h3 class="text-base font-semibold text-base-content">{{ title }}</h3>
    <p v-if="description" class="text-sm text-muted max-w-md">{{ description }}</p>
    <div v-if="$slots.action" class="mt-2">
      <slot name="action" />
    </div>
  </div>
</template>
