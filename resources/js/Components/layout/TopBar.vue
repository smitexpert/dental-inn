<script setup>
import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import UserMenu from './UserMenu.vue'
import ColorModeToggle from './ColorModeToggle.vue'

defineProps({
  open: { type: Boolean, default: true },
})

const settings = computed(() => usePage().props.settings)
</script>

<template>
  <header class="h-14 px-4 border-b border-base-300 bg-base-100 flex items-center justify-between gap-3">
    <div class="flex items-center gap-3">
      <!-- Mobile: opens the overlay drawer below `lg`. -->
      <label
        for="app-sidebar-drawer"
        aria-label="Open menu"
        class="lg:hidden h-9 w-9 inline-flex items-center justify-center rounded-md text-base-content hover:bg-base-200 cursor-pointer"
      >
        <svg
          xmlns="http://www.w3.org/2000/svg"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="2"
          stroke-linecap="round"
          stroke-linejoin="round"
          class="w-5 h-5"
          role="img"
          aria-hidden="true"
        ><path d="M4 5h16M4 12h16M4 19h16" /></svg>
      </label>
      <!--
        Desktop (`lg+`): the drawer is pinned open by `lg:drawer-open`, so
        `drawer-button` flips the same checkbox to collapse/expand. The
        icon swaps based on whether the drawer is currently open.
      -->
      <label
        for="app-sidebar-drawer"
        :aria-label="open ? 'Collapse sidebar' : 'Expand sidebar'"
        :title="open ? 'Collapse sidebar' : 'Expand sidebar'"
        class="hidden lg:inline-flex h-9 w-9 items-center justify-center rounded-md text-base-content hover:bg-base-200 cursor-pointer drawer-button"
      >
        <svg
          v-if="open"
          xmlns="http://www.w3.org/2000/svg"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="2"
          stroke-linecap="round"
          stroke-linejoin="round"
          class="w-5 h-5"
          role="img"
          aria-hidden="true"
        ><rect width="18" height="18" x="3" y="3" rx="2" /><path d="M9 3v18m7-6l-3-3l3-3" /></svg>
        <svg
          v-else
          xmlns="http://www.w3.org/2000/svg"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="2"
          stroke-linecap="round"
          stroke-linejoin="round"
          class="w-5 h-5"
          role="img"
          aria-hidden="true"
        ><rect width="18" height="18" x="3" y="3" rx="2" /><path d="M9 3v18m5-12l3 3l-3 3" /></svg>
      </label>
      <h1 class="text-base font-semibold text-base-content">{{ settings.clinic_name }}</h1>
    </div>
    <div class="flex items-center gap-2">
      <button
        type="button"
        class="relative h-9 w-9 inline-flex items-center justify-center rounded-md text-base-content hover:bg-base-200"
        aria-label="Notifications"
      >
        <svg
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
        ><path d="M10.268 21a2 2 0 0 0 3.464 0m-10.47-5.674A1 1 0 0 0 4 17h16a1 1 0 0 0 .74-1.673C19.41 13.956 18 12.499 18 8A6 6 0 0 0 6 8c0 4.499-1.411 5.956-2.738 7.326" /></svg>
        <span class="absolute top-1 right-1 h-2 w-2 rounded-full bg-primary" />
      </button>
      <ColorModeToggle />
      <UserMenu />
    </div>
  </header>
</template>