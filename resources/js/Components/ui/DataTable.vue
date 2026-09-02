<script setup>
import { computed, useSlots } from 'vue'

defineProps({
  value: { type: Array, required: true },
  paginator: { type: Boolean, default: true },
  rows: { type: Number, default: 10 },
  loading: { type: Boolean, default: false },
  striped: { type: Boolean, default: true },
  selectionMode: { type: String, default: null },
})

const emit = defineEmits(['row-click', 'sort', 'page'])
const slots = useSlots()

const columns = computed(() => {
  const nodes = slots.default?.() ?? []
  const cols = []
  for (const n of nodes) {
    if (n.type?.__name === 'Column' || n.type?.name === 'Column' || (typeof n.type === 'object' && n.props?.field)) {
      cols.push({ field: n.props.field, header: n.props.header ?? n.props.field, sortable: n.props.sortable })
    }
    // walk children for Fragment-wrapped Columns
    if (n.children && Array.isArray(n.children)) {
      for (const c of n.children) {
        if (typeof c === 'object' && c?.props?.field) {
          cols.push({ field: c.props.field, header: c.props.header ?? c.props.field, sortable: c.props.sortable })
        }
      }
    }
  }
  return cols
})
</script>

<template>
  <div class="card bg-base-100 border border-base-300 overflow-hidden">
    <table class="table w-full" :class="striped ? 'table-zebra' : ''">
      <thead>
        <tr>
          <th v-for="col in columns" :key="col.field">{{ col.header }}</th>
        </tr>
      </thead>
      <tbody>
        <tr v-if="loading">
          <td :colspan="columns.length" class="text-center py-8">
            <span class="loading loading-spinner loading-md" />
          </td>
        </tr>
        <tr v-else-if="value.length === 0">
          <td :colspan="columns.length" class="text-center text-base-content/60 py-8">No records found.</td>
        </tr>
        <tr
          v-for="(row, i) in value"
          v-else
          :key="i"
          class="hover:bg-base-200 cursor-pointer"
          @click="$emit('row-click', { data: row, index: i })"
        >
          <td v-for="col in columns" :key="col.field">{{ row[col.field] }}</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
