<script setup>
/**
 * Pagination
 * ----------
 * Range-based pager accepting `currentPage`, `lastPage`, `total`, `perPage`
 * and emitting `page` (the new page number). Renders a "X–Y of Z" indicator
 * plus a button row with prev/next and a windowed numeric range with
 * ellipsis for long ranges.
 */
import { computed } from 'vue'
import Icon from './Icon.vue'

const props = defineProps({
  currentPage: { type: Number, default: 1 },
  lastPage: { type: Number, default: 1 },
  total: { type: Number, default: 0 },
  perPage: { type: Number, default: 10 },
})

const emit = defineEmits(['page'])

const from = computed(() => {
  if (props.total === 0) return 0
  return (props.currentPage - 1) * props.perPage + 1
})
const to = computed(() =>
  Math.min(props.currentPage * props.perPage, props.total),
)

const visiblePages = computed(() => {
  const arr = []
  const last = props.lastPage
  const cur = props.currentPage
  if (last <= 7) {
    for (let i = 1; i <= last; i++) arr.push(i)
    return arr
  }
  for (let i = 1; i <= last; i++) {
    if (
      i === 1 ||
      i === last ||
      Math.abs(i - cur) <= 1 ||
      (cur <= 4 && i <= 5) ||
      (cur >= last - 3 && i >= last - 4)
    ) {
      arr.push(i)
    } else if (arr[arr.length - 1] !== '…') {
      arr.push('…')
    }
  }
  return arr
})

function go(n) {
  if (typeof n !== 'number') return
  if (n < 1 || n > props.lastPage) return
  if (n === props.currentPage) return
  emit('page', n)
}
</script>

<template>
  <div
    class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 px-4 py-3 app-card"
  >
    <div class="text-sm text-(--color-muted)">
      <template v-if="total > 0">
        Showing <span class="font-medium text-base-content">{{ from }}</span>–<span class="font-medium text-base-content">{{ to }}</span>
        of <span class="font-medium text-base-content">{{ total }}</span>
      </template>
      <template v-else>No results</template>
    </div>
    <nav class="join" aria-label="Pagination">
      <button
        type="button"
        class="join-item btn btn-sm btn-ghost"
        :disabled="currentPage <= 1"
        @click="go(currentPage - 1)"
      >
        <Icon name="chevron-left" size="sm" />
      </button>
      <template v-for="(p, i) in visiblePages" :key="i">
        <span
          v-if="p === '…'"
          class="join-item btn btn-sm btn-disabled bg-transparent border-(--color-line)"
        >…</span>
        <button
          v-else
          type="button"
          :class="['join-item btn btn-sm', p === currentPage ? 'btn-primary' : 'btn-ghost']"
          @click="go(p)"
        >{{ p }}</button>
      </template>
      <button
        type="button"
        class="join-item btn btn-sm btn-ghost"
        :disabled="currentPage >= lastPage"
        @click="go(currentPage + 1)"
      >
        <Icon name="chevron-right" size="sm" />
      </button>
    </nav>
  </div>
</template>
