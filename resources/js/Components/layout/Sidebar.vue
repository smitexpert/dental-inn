<script setup>
import SidebarItem from './SidebarItem.vue'
import { useNav } from '@/Composables/useNav.js'
import { usePage } from '@inertiajs/vue3'

const items = useNav()
const page = usePage()
const isActive = (href) => page.url === href || (href !== '/dashboard' && page.url.startsWith(href))
</script>

<template>
  <aside class="w-64 bg-base-100 border-r border-base-300 flex flex-col h-full">
    <div class="px-4 py-4 border-b border-base-300">
      <h2 class="text-sm font-semibold text-base-content">Navigation</h2>
    </div>
    <nav class="flex-1 p-2 space-y-0.5 overflow-y-auto">
      <SidebarItem
        v-for="item in items"
        :key="item.href + item.label"
        :href="item.href"
        :label="item.label"
        :icon="item.icon ? `i-lucide-${item.icon}` : null"
        :active="isActive(item.href)"
        :coming-soon="!!item.comingSoon"
        :badge="item.badge || null"
        :children="item.children || []"
      />
    </nav>
  </aside>
</template>
