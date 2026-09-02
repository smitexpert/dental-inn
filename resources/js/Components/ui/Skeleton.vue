<script setup>
/**
 * Skeleton
 * --------
 * Placeholder shimmer block using the `skeleton-shimmer` class from
 * `app.css`. Width/height can be controlled via utility classes.
 */
defineProps({
  width: { type: String, default: 'w-full' },
  height: { type: String, default: 'h-4' },
  rounded: { type: String, default: 'rounded-md' },
})
</script>

<template>
  <div :class="['skeleton-shimmer', width, height, rounded]" />
</template>
