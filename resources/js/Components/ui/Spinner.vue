<script setup>
/**
 * Spinner
 * -------
 * DaisyUI `loading` spinner with three size variants. Wraps in a span so
 * it can sit inline next to text.
 */
defineProps({
  size: {
    type: String,
    default: 'md',
    validator: (v) => ['sm', 'md', 'lg'].includes(v),
  },
  label: { type: String, default: 'Loading…' },
})

const sizeClass = {
  sm: 'loading-sm',
  md: 'loading-md',
  lg: 'loading-lg',
}
</script>

<template>
  <span
    :class="['loading loading-spinner', sizeClass[size]]"
    role="status"
    :aria-label="label"
  />
</template>
