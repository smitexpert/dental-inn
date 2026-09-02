<script setup>
/**
 * Breadcrumbs
 * -----------
 * Renders a `nav` of `Link` items separated by a chevron. The final item
 * (no `href`) is treated as the current page and rendered as plain text.
 */
import { Link } from '@inertiajs/vue3'
import Icon from './Icon.vue'

defineProps({
  items: { type: Array, required: true },
})
</script>

<template>
  <nav class="text-sm" aria-label="Breadcrumb">
    <ol class="flex items-center gap-1.5 flex-wrap">
      <li
        v-for="(item, i) in items"
        :key="i"
        class="flex items-center gap-1.5"
      >
        <Link
          v-if="item.href && i < items.length - 1"
          :href="item.href"
          class="text-muted hover:text-base-content"
        >
          {{ item.label }}
        </Link>
        <span
          v-else
          :class="[
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
