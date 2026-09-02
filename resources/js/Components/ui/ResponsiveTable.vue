<script setup>
/**
 * ResponsiveTable
 * ---------------
 * Renders a `<table>` on screens `>= md` and a card list on `< md`.
 * The card view uses the `card` slot, called once per row with the
 * `row` prop. The table view uses the existing `app-table` styling
 * so it picks up the global table look without extra props.
 */
import { onMounted, onUnmounted, ref } from 'vue'

const props = defineProps({
  columns: { type: Array, required: true }, // [{ key, label }]
  rows:    { type: Array, required: true },
})

const isNarrow = ref(false)
let mql = null
let handler = null

onMounted(() => {
  if (typeof window === 'undefined') return
  mql = window.matchMedia('(max-width: 767px)')
  isNarrow.value = mql.matches
  handler = (e) => { isNarrow.value = e.matches }
  if (mql.addEventListener) mql.addEventListener('change', handler)
  else mql.addListener(handler)
})

onUnmounted(() => {
  if (!mql) return
  if (mql.removeEventListener) mql.removeEventListener('change', handler)
  else mql.removeListener(handler)
})
</script>

<template>
  <div v-if="!isNarrow" class="overflow-x-auto">
    <table class="app-table">
      <thead>
        <tr>
          <th v-for="c in columns" :key="c.key">{{ c.label }}</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="row in rows" :key="row.id ?? JSON.stringify(row)">
          <td v-for="c in columns" :key="c.key">
            <slot :name="`cell:${c.key}`" :row="row" :value="row[c.key]">
              {{ row[c.key] }}
            </slot>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
  <ul v-else class="space-y-3">
    <li v-for="row in rows" :key="row.id ?? JSON.stringify(row)">
      <slot name="card" :row="row" />
    </li>
  </ul>
</template>