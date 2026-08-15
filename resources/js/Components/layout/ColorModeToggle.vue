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
    <svg
      v-if="mode === 'dark'"
      xmlns="http://www.w3.org/2000/svg"
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      stroke-width="2"
      stroke-linecap="round"
      stroke-linejoin="round"
      class="w-4 h-4"
      role="img"
      aria-hidden="true"
    ><circle cx="12" cy="12" r="4" /><path d="M12 2v2m0 16v2M4.93 4.93l1.41 1.41m11.32 11.32l1.41 1.41M2 12h2m16 0h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41" /></svg>
    <svg
      v-else
      xmlns="http://www.w3.org/2000/svg"
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      stroke-width="2"
      stroke-linecap="round"
      stroke-linejoin="round"
      class="w-4 h-4"
      role="img"
      aria-hidden="true"
    ><path d="M20.985 12.486a9 9 0 1 1-9.473-9.472c.405-.022.617.46.402.803a6 6 0 0 0 8.268 8.268c.344-.215.825-.004.803.401" /></svg>
  </button>
</template>