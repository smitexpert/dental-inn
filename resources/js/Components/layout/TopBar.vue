<script setup>
import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import UserMenu from './UserMenu.vue'
import ColorModeToggle from './ColorModeToggle.vue'
import Icon from '@/Components/ui/Icon.vue'

const props = defineProps({
  collapsed: { type: Boolean, default: false },
  isMobileOpen: { type: Boolean, default: false },
})

defineEmits(['toggle-sidebar'])

const settings = computed(() => usePage().props.settings)
</script>

<template>
  <header class="app-header">
    <div class="h-full px-4 flex items-center justify-between gap-3">
      <div class="flex items-center gap-3 min-w-0">
        <button
          type="button"
          class="h-9 w-9 inline-flex items-center justify-center rounded-lg text-base-content hover:bg-base-200 transition-colors app-focus-ring"
          :aria-label="collapsed ? 'Expand sidebar' : 'Collapse sidebar'"
          :title="collapsed ? 'Expand sidebar' : 'Collapse sidebar'"
          @click="$emit('toggle-sidebar')"
        >
          <Icon
            :name="collapsed ? 'panel-left-open' : 'panel-left-close'"
            size="md"
          />
        </button>
        <h1 class="text-base font-semibold text-base-content truncate">
          {{ settings.clinic_name }}
        </h1>
      </div>
      <div class="flex items-center gap-1.5">
        <button
          type="button"
          class="relative h-9 w-9 inline-flex items-center justify-center rounded-lg text-base-content hover:bg-base-200 transition-colors app-focus-ring"
          aria-label="Notifications"
        >
          <Icon name="bell" size="md" />
          <span
            class="absolute top-2 right-2 h-2 w-2 rounded-full bg-error ring-2 ring-base-100"
          />
        </button>
        <ColorModeToggle />
        <UserMenu />
      </div>
    </div>
  </header>
</template>
