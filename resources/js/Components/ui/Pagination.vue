<script setup>
import { computed } from 'vue'

const props = defineProps({
  rows: { type: Number, default: 10 },
  totalRecords: { type: Number, default: 0 },
  first: { type: Number, default: 0 },
  rowsPerPageOptions: { type: Array, default: () => [10, 25, 50, 100] },
})

const emit = defineEmits(['page', 'update:first'])

const currentPage = computed(() => Math.floor(props.first / props.rows) + 1)
const lastPage = computed(() => Math.max(1, Math.ceil(props.totalRecords / props.rows)))

const visiblePages = computed(() => {
  const arr = []
  const last = lastPage.value
  const cur = currentPage.value
  for (let i = 1; i <= last; i++) {
    if (i === 1 || i === last || Math.abs(i - cur) <= 1) arr.push(i)
    else if (arr[arr.length - 1] !== '…') arr.push('…')
  }
  return arr
})

function go(n) {
  if (n < 1 || n > lastPage.value) return
  emit('update:first', (n - 1) * props.rows)
  emit('page', { page: n, first: (n - 1) * props.rows, rows: props.rows })
}
</script>

<template>
  <div class="flex items-center justify-between gap-2 px-4 py-2 bg-base-100 border border-base-300 rounded-md">
    <div class="text-sm text-base-content/70">
      Page {{ currentPage }} of {{ lastPage }} ({{ totalRecords }} total)
    </div>
    <div class="join">
      <button
        type="button"
        class="join-item btn btn-sm"
        :disabled="currentPage <= 1"
        @click="go(currentPage - 1)"
      >«</button>
      <template v-for="(p, i) in visiblePages" :key="i">
        <span v-if="p === '…'" class="join-item btn btn-sm btn-disabled">…</span>
        <button
          v-else
          type="button"
          class="join-item btn btn-sm"
          :class="p === currentPage ? 'btn-primary' : ''"
          @click="go(p)"
        >{{ p }}</button>
      </template>
      <button
        type="button"
        class="join-item btn btn-sm"
        :disabled="currentPage >= lastPage"
        @click="go(currentPage + 1)"
      >»</button>
    </div>
  </div>
</template>
