<script setup>
import { computed } from 'vue'

const props = defineProps({
  src: { type: String, default: null },
  name: { type: String, default: '' },
  size: { type: String, default: 'md', validator: (v) => ['sm', 'md', 'lg', 'xl'].includes(v) },
})

const sizes = {
  sm: 'h-6 w-6 text-xs',
  md: 'h-8 w-8 text-sm',
  lg: 'h-10 w-10 text-base',
  xl: 'h-14 w-14 text-lg',
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
      'inline-flex items-center justify-center rounded-full bg-primary-100 text-primary-700 font-medium overflow-hidden',
      sizes[size],
    ]"
    :title="name"
  >
    <img v-if="src" :src="src" :alt="name" class="h-full w-full object-cover" />
    <span v-else>{{ initials }}</span>
  </div>
</template>
