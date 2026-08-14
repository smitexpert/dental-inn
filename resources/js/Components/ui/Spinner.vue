<script setup>
defineProps({
  size: { type: String, default: 'md', validator: (v) => ['sm', 'md', 'lg'].includes(v) },
  label: { type: String, default: 'Loading…' },
})

const sizes = { sm: 'h-4 w-4 border-2', md: 'h-6 w-6 border-2', lg: 'h-10 w-10 border-4' }
</script>

<template>
  <span
    :class="['inline-block rounded-full border-primary-600 border-t-transparent animate-spin', sizes[size]]"
    role="status"
    :aria-label="label"
  />
</template>
