<script setup>
/**
 * Badge
 * -----
 * Small inline label with optional leading dot or icon. Six tonal variants
 * (neutral, primary, success, warning, danger, info).
 */
import { computed } from 'vue'
import Icon from './Icon.vue'

const props = defineProps({
  variant: {
    type: String,
    default: 'neutral',
    validator: (v) => ['neutral', 'primary', 'success', 'warning', 'danger', 'info'].includes(v),
  },
  size: {
    type: String,
    default: 'md',
    validator: (v) => ['sm', 'md'].includes(v),
  },
  dot: { type: Boolean, default: false },
  icon: { type: String, default: null },
})

const variantClass = {
  neutral: 'bg-base-200 text-base-content/70 border-(--color-line)',
  primary: 'bg-primary/10 text-primary border-primary/35',
  success: 'bg-success/10 text-success border-success/35',
  warning: 'bg-warning/10 text-warning border-warning/35',
  danger: 'bg-error/10 text-error border-error/35',
  info: 'bg-info/10 text-info border-info/35',
}

const dotClass = {
  neutral: 'bg-base-content/40',
  primary: 'bg-primary',
  success: 'bg-success',
  warning: 'bg-warning',
  danger: 'bg-error',
  info: 'bg-info',
}

const sizeClass = computed(() =>
  props.size === 'sm' ? 'text-[11px] px-2 py-0.5' : 'text-xs px-2.5 py-0.5',
)
</script>

<template>
  <span
    :class="[
      'inline-flex items-center gap-1.5 rounded-full font-medium border',
      sizeClass,
      variantClass[variant],
    ]"
  >
    <span v-if="dot" :class="['h-1.5 w-1.5 rounded-full', dotClass[variant]]" />
    <Icon v-else-if="icon" :name="icon" size="xs" />
    <slot />
  </span>
</template>
