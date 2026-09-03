<script setup>
import { useNav } from '@/Composables/useNav.js'
import { usePage } from '@inertiajs/vue3'
import SidebarItem from './SidebarItem.vue'
import Icon from '@/Components/ui/Icon.vue'

defineProps({
  collapsed: { type: Boolean, default: false },
})

const items = useNav()
const page = usePage()

// A parent group (e.g. /billing/invoices) is "active" when either its own
// href matches the current URL or any of its children's hrefs does. This
// keeps the sub-menu expanded when the user navigates to a child route.
function isActive(item) {
  if (!item) return false
  const url = page.url
  if (item.href && (url === item.href || url.startsWith(item.href + '/'))) return true
  if (item.children?.length) {
    return item.children.some((child) => isActive(child))
  }
  return false
}
</script>

<template>
  <aside
    class="bg-base-100 border-r border-(--color-line) flex flex-col h-screen transition-[width] duration-200 ease-(--ease-out-soft) overflow-hidden shadow-[1px_0_24px_rgba(15,23,42,0.04)] dark:shadow-[1px_0_24px_rgba(0,0,0,0.3)]"
    :class="collapsed ? 'w-16' : 'w-[260px]'"
  >
    <!-- Brand wordmark -->
    <div
      class="h-16 flex items-center gap-2.5 px-4 border-b border-(--color-line) shrink-0"
    >
      <span
        class="h-9 w-9 rounded-xl cta-gradient inline-flex items-center justify-center text-primary-content shadow-(--shadow-card)"
      >
        <Icon name="tooth" size="md" />
      </span>
      <span
        v-if="!collapsed"
        class="brand-gradient text-lg font-bold tracking-tight whitespace-nowrap"
      >
        Caremint
      </span>
    </div>

    <!-- Nav -->
    <nav class="flex-1 p-2 space-y-0.5 overflow-y-auto">
      <SidebarItem
        v-for="item in items"
        :key="item.href + item.label"
        :href="item.href"
        :label="item.label"
        :icon="item.icon || null"
        :active="isActive(item)"
        :coming-soon="!!item.comingSoon"
        :badge="item.badge || null"
        :children="item.children || []"
        :collapsed="collapsed"
      />
    </nav>

    <!-- Footer -->
    <div
      v-if="!collapsed"
      class="px-4 py-3 border-t border-(--color-line) text-xs text-(--color-subtle) shrink-0 flex items-center justify-between"
    >
      <span>v1.0 · Caremint Dental</span>
      <slot name="footer" />
    </div>
  </aside>
</template>
