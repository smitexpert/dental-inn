<script setup>
import { useColorMode } from '@/Composables/useColorMode.js'
const { mode, toggle } = useColorMode()
</script>

<template>
  <button
    type="button"
    @click="toggle"
    class="h-9 w-9 inline-flex items-center justify-center rounded-md text-base-content hover:bg-base-200 focus:outline-none focus:ring-2 focus:ring-primary"
    :aria-label="mode === 'dark' ? 'Switch to light mode' : 'Switch to dark mode'"
  >
    <span v-if="mode === 'dark'" class="i-lucide-sun w-4 h-4" />
    <span v-else class="i-lucide-moon w-4 h-4" />
  </button>
</template>
