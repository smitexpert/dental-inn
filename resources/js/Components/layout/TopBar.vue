<script setup>
import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import UserMenu from './UserMenu.vue'
import ColorModeToggle from './ColorModeToggle.vue'

const settings = computed(() => usePage().props.settings)
defineEmits(['toggle-sidebar'])
</script>

<template>
  <header class="h-14 px-4 border-b border-base-300 bg-base-100 flex items-center justify-between gap-3">
    <div class="flex items-center gap-3">
      <button
        type="button"
        class="lg:hidden h-9 w-9 inline-flex items-center justify-center rounded-md text-base-content hover:bg-base-200"
        @click="$emit('toggle-sidebar')"
        aria-label="Toggle sidebar"
      >
        <span class="i-lucide-menu w-5 h-5" />
      </button>
      <h1 class="text-base font-semibold text-base-content">{{ settings.clinic_name }}</h1>
    </div>
    <div class="flex items-center gap-2">
      <button
        type="button"
        class="relative h-9 w-9 inline-flex items-center justify-center rounded-md text-base-content hover:bg-base-200"
        aria-label="Notifications"
      >
        <span class="i-lucide-bell w-4 h-4" />
        <span class="absolute top-1 right-1 h-2 w-2 rounded-full bg-primary" />
      </button>
      <ColorModeToggle />
      <UserMenu />
    </div>
  </header>
</template>
