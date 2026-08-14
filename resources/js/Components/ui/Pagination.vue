<script setup>
import Paginator from 'primevue/paginator'

const props = defineProps({
  rows: { type: Number, default: 10 },
  totalRecords: { type: Number, default: 0 },
  first: { type: Number, default: 0 },
  rowsPerPageOptions: { type: Array, default: () => [10, 25, 50, 100] },
})
defineEmits(['page', 'update:first'])

const pt = {
  root: { class: 'flex items-center justify-between gap-2 px-4 py-2 bg-surface border border-border rounded-md' },
  pageButton: ({ context }) => [
    'h-8 min-w-[2rem] px-2 rounded text-sm',
    context.active ? 'bg-primary-600 text-white' : 'text-foreground hover:bg-surface-muted',
  ],
  rowsPerPageDropdown: { class: 'h-8' },
}
</script>

<template>
  <Paginator
    :rows="rows"
    :total-records="totalRecords"
    :first="first"
    :rows-per-page-options="rowsPerPageOptions"
    :pt="pt"
    @page="$emit('page', $event)"
  />
</template>
