<script setup>
import { Link } from '@inertiajs/vue3'
import { computed } from 'vue'
import Badge from '@/Components/ui/Badge.vue'

const props = defineProps({
  href: { type: String, required: true },
  label: { type: String, required: true },
  icon: { type: String, default: null },
  active: { type: Boolean, default: false },
  comingSoon: { type: Boolean, default: false },
})

const classes = computed(() => [
  'flex items-center gap-3 px-3 py-2 rounded-md text-sm transition-colors',
  props.comingSoon
    ? 'text-muted-foreground cursor-not-allowed'
    : props.active
      ? 'bg-primary-50 text-primary-700 font-medium'
      : 'text-foreground hover:bg-surface-muted',
])
</script>

<template>
  <Link v-if="!comingSoon" :href="href" :class="classes">
    <span v-if="icon" :class="icon" class="w-4 h-4 shrink-0" />
    <span class="flex-1 truncate">{{ label }}</span>
  </Link>
  <span v-else :class="classes">
    <span v-if="icon" :class="icon" class="w-4 h-4 shrink-0" />
    <span class="flex-1 truncate">{{ label }}</span>
    <Badge variant="neutral">Soon</Badge>
  </span>
</template>
