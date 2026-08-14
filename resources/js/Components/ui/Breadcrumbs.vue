<script setup>
import { Link } from '@inertiajs/vue3'

defineProps({
  items: { type: Array, required: true },
})
</script>

<template>
  <nav class="text-sm" aria-label="Breadcrumb">
    <ol class="flex items-center gap-1.5 flex-wrap">
      <li v-for="(item, i) in items" :key="i" class="flex items-center gap-1.5">
        <Link v-if="item.href && i < items.length - 1" :href="item.href" class="text-muted-foreground hover:text-foreground">
          {{ item.label }}
        </Link>
        <span v-else class="text-foreground font-medium">{{ item.label }}</span>
        <span v-if="i < items.length - 1" class="text-muted-foreground">/</span>
      </li>
    </ol>
  </nav>
</template>
