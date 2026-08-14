<script setup>
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'

defineProps({
  value: { type: Array, required: true },
  paginator: { type: Boolean, default: true },
  rows: { type: Number, default: 10 },
  loading: { type: Boolean, default: false },
  striped: { type: Boolean, default: true },
  selectionMode: { type: String, default: null },
  vModel: { type: [Array, Object], default: undefined },
})
defineEmits(['update:selection', 'row-click', 'sort', 'page'])

const pt = {
  root: { class: 'bg-surface border border-border rounded-lg overflow-hidden' },
  table: { class: 'w-full text-sm' },
  thead: { class: 'bg-surface-muted text-muted-foreground' },
  headerCell: { class: 'px-4 py-2 text-left font-medium border-b border-border' },
  bodyRow: ({ context }) => [
    'border-b border-border last:border-b-0',
    context.selected ? 'bg-primary-50' : 'hover:bg-surface-muted',
    context.striped && context.even ? 'bg-surface-muted/50' : '',
  ],
  bodyCell: { class: 'px-4 py-2 text-foreground' },
  paginator: { class: 'flex items-center justify-between px-4 py-2 border-t border-border bg-surface' },
  pageButton: ({ context }) => [
    'h-8 px-2 rounded text-sm',
    context.active ? 'bg-primary-600 text-white' : 'text-foreground hover:bg-surface-muted',
  ],
}
</script>

<template>
  <DataTable
    :value="value"
    :paginator="paginator"
    :rows="rows"
    :loading="loading"
    :selection-mode="selectionMode"
    :pt="pt"
    @row-click="(e) => $emit('row-click', e)"
    @page="(e) => $emit('page', e)"
    @sort="(e) => $emit('sort', e)"
  >
    <slot />
  </DataTable>
</template>
