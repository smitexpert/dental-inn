<script setup>
import { useColorMode } from '@/Composables/useColorMode.js'
import Icon from '@/Components/ui/Icon.vue'

const { mode, toggle } = useColorMode()
</script>

<template>
  <button
    type="button"
    class="h-9 w-9 inline-flex items-center justify-center rounded-lg text-base-content hover:bg-base-200 transition-colors app-focus-ring"
    :aria-label="mode === 'caremint-dark' ? 'Switch to light mode' : 'Switch to dark mode'"
    :title="mode === 'caremint-dark' ? 'Light mode' : 'Dark mode'"
    @click="toggle"
  >
    <Transition
      mode="out-in"
      enter-active-class="transition duration-200 ease-out"
      leave-active-class="transition duration-150 ease-in"
      enter-from-class="opacity-0 -rotate-90"
      leave-to-class="opacity-0 rotate-90"
    >
      <Icon
        v-if="mode === 'caremint-dark'"
        key="sun"
        name="sun"
        size="md"
      />
      <Icon
        v-else
        key="moon"
        name="moon"
        size="md"
      />
    </Transition>
  </button>
</template>
