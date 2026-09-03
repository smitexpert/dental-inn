<script setup>
/**
 * Avatar
 * ------
 * Circular avatar. Shows an image when `src` is provided, otherwise falls
 * back to initials derived from `name`. The `tone` prop colors the initial
 * background using a translucent tint of the chosen color.
 */
import { computed } from 'vue'

const props = defineProps({
  src: { type: String, default: null },
  name: { type: String, default: '' },
  size: {
    type: String,
    default: 'md',
    validator: (v) => ['sm', 'md', 'lg', 'xl'].includes(v),
  },
  tone: {
    type: String,
    default: 'primary',
    validator: (v) => ['primary', 'secondary', 'success', 'warning', 'info'].includes(v),
  },
})

const sizes = {
  sm: 'h-6 w-6 text-xs',
  md: 'h-8 w-8 text-sm',
  lg: 'h-10 w-10 text-base',
  xl: 'h-14 w-14 text-lg',
}

const toneClass = {
  primary: 'bg-gradient-to-br from-primary/15 to-primary/25 text-primary',
  secondary: 'bg-gradient-to-br from-secondary/15 to-secondary/25 text-secondary',
  success: 'bg-gradient-to-br from-success/15 to-success/25 text-success',
  warning: 'bg-gradient-to-br from-warning/15 to-warning/25 text-warning',
  info: 'bg-gradient-to-br from-info/15 to-info/25 text-info',
}

const initials = computed(() => {
  if (!props.name) return '?'
  return props.name
    .split(' ')
    .map((p) => p[0])
    .filter(Boolean)
    .slice(0, 2)
    .join('')
    .toUpperCase()
})
</script>

<template>
  <div
    :class="[
      'inline-flex items-center justify-center rounded-full font-semibold overflow-hidden',
      sizes[size],
      toneClass[tone],
    ]"
    :title="name"
  >
    <img v-if="src" :src="src" :alt="name" class="h-full w-full object-cover" />
    <span v-else>{{ initials }}</span>
  </div>
</template>
