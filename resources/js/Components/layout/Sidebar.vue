<script setup>
import SidebarItem from './SidebarItem.vue'
import { useNav } from '@/Composables/useNav.js'
import { usePage } from '@inertiajs/vue3'

const items = useNav()
const page = usePage()
const isActive = (href) => page.url === href || (href !== '/dashboard' && page.url.startsWith(href))
</script>

<template>
  <!--
    The drawer-toggle checkbox state controls daisyUI's `is-drawer-open`
    and `is-drawer-close` variant selectors below. When collapsed we
    shrink the aside to icon width; when open we restore the full 64-unit
    width.
  -->
  <aside
    class="bg-base-100 border-r border-base-300 flex flex-col h-full overflow-hidden w-14 transition-[width] duration-200 ease-in-out is-drawer-open:w-64"
  >
    <div class="px-4 py-4 border-b border-base-300 is-drawer-close:hidden">
      <h2 class="text-sm font-semibold text-base-content">Navigation</h2>
    </div>

    <nav class="flex-1 p-2 space-y-0.5 overflow-y-auto">
      <SidebarItem
        v-for="item in items"
        :key="item.href + item.label"
        :href="item.href"
        :label="item.label"
        :icon="item.icon || null"
        :active="isActive(item.href)"
        :coming-soon="!!item.comingSoon"
        :badge="item.badge || null"
        :children="item.children || []"
      />
    </nav>
  </aside>
</template>
