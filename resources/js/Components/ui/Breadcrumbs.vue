<script setup>
/**
 * Breadcrumbs (refreshed)
 * -----------------------
 * Pill-shaped breadcrumb. Items with `href` (and not the last) render as
 * `<Link>`s; the final item is a stronger-weight span (current page).
 */
import { Link } from '@inertiajs/vue3'
import Icon from './Icon.vue'

defineProps({
  items: { type: Array, required: true }, // [{ label, href? }]
})
</script>

<template>
  <nav class="text-xs" aria-label="Breadcrumb">
    <ol class="flex items-center gap-1 flex-wrap">
      <li
        v-for="(item, i) in items"
        :key="i"
        class="flex items-center gap-1"
      >
        <Link
          v-if="item.href && i < items.length - 1"
          :href="item.href"
          class="px-2 py-0.5 rounded-full text-muted hover:text-base-content hover:bg-base-200 transition-colors"
        >
          {{ item.label }}
        </Link>
        <span
          v-else
          :class="[
            'px-2 py-0.5 rounded-full',
            i < items.length - 1 ? 'text-muted' : 'text-base-content font-medium',
          ]"
        >{{ item.label }}</span>
        <Icon
          v-if="i < items.length - 1"
          name="chevron-right"
          size="xs"
          class="text-(--color-subtle)"
        />
      </li>
    </ol>
  </nav>
</template>
