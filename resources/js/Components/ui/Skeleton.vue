<script setup>
defineProps({
  width: { type: String, default: 'w-full' },
  height: { type: String, default: 'h-4' },
  rounded: { type: String, default: 'rounded' },
})
</script>

<template>
  <div :class="['animate-pulse bg-surface-muted', width, height, rounded]" />
</template>
