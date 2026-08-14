<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import UiCard from '@/Components/ui/Card.vue'
import UiButton from '@/Components/ui/Button.vue'
import UiInput from '@/Components/ui/Input.vue'
import UiSelect from '@/Components/ui/Select.vue'
import UiBadge from '@/Components/ui/Badge.vue'
import UiPagination from '@/Components/ui/Pagination.vue'
import UiEmptyState from '@/Components/ui/EmptyState.vue'
import { ref, watch } from 'vue'

const props = defineProps({
  movements: { type: Object, required: true },
  filters: { type: Object, default: () => ({}) },
  types: { type: Array, default: () => [] },
  currencies: { type: Array, default: () => [] },
})

const q = ref(props.filters.q ?? '')
const type = ref(props.filters.type ?? '')
const currency = ref(props.filters.currency_code ?? '')
const dateFrom = ref(props.filters.date_from ?? '')
const dateTo = ref(props.filters.date_to ?? '')

let t
function applyFilters() {
  clearTimeout(t)
  t = setTimeout(() => {
    router.get(
      '/exchange/movements',
      {
        q: q.value,
        type: type.value,
        currency_code: currency.value,
        date_from: dateFrom.value,
        date_to: dateTo.value,
      },
      { preserveState: true, replace: true },
    )
  }, 300)
}
watch([q, type, currency, dateFrom, dateTo], applyFilters)

function variantFor(type) {
  const m = {
    cash_in: 'success',
    cash_out: 'danger',
    exchange_in: 'success',
    exchange_out: 'danger',
    sale: 'success',
    expense: 'danger',
    refund: 'success',
    adjustment: 'warning',
  }
  return m[type] ?? 'neutral'
}

function formatDate(value) {
  if (!value) return '—'
  return new Date(value).toLocaleString()
}

function findCurrency(code) {
  return props.currencies.find((c) => c.code === code)
}
</script>

<template>
  <Head title="Cash movements" />
  <AuthenticatedLayout>
    <UiCard title="Cash movements">
      <template #header>
        <div class="flex items-center justify-between gap-3">
          <h3 class="text-base font-semibold text-base-content">Cash movements</h3>
          <Link href="/exchange/movements/create"><UiButton>New movement</UiButton></Link>
        </div>
      </template>

      <div class="grid grid-cols-5 gap-3 mb-4">
        <UiInput v-model="q" placeholder="Search reason / reference" />
        <UiSelect
          v-model="type"
          :options="[{ label: 'All types', value: '' }, ...types.map((t) => ({ label: t.label, value: t.value }))]"
        />
        <UiSelect
          v-model="currency"
          :options="[{ label: 'All currencies', value: '' }, ...currencies.map((c) => ({ label: c.code, value: c.code }))]"
        />
        <UiInput v-model="dateFrom" type="date" />
        <UiInput v-model="dateTo" type="date" />
      </div>

      <UiEmptyState v-if="movements.data.length === 0" title="No movements" description="Record one above to start tracking." />

      <div v-else class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-base-200 text-base-content/70">
            <tr>
              <th class="text-left px-4 py-2">Date</th>
              <th class="text-left px-4 py-2">Type</th>
              <th class="text-left px-4 py-2">Currency</th>
              <th class="text-right px-4 py-2">Amount</th>
              <th class="text-right px-4 py-2">Counter</th>
              <th class="text-left px-4 py-2">Reason</th>
              <th class="text-left px-4 py-2">By</th>
              <th class="text-right px-4 py-2">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="m in movements.data" :key="m.id" class="border-b border-base-300 last:border-b-0">
              <td class="px-4 py-2 text-base-content/70 text-xs">{{ formatDate(m.created_at) }}</td>
              <td class="px-4 py-2">
                <UiBadge :variant="variantFor(m.type)">{{ m.type_label }}</UiBadge>
              </td>
              <td class="px-4 py-2 text-base-content font-mono text-xs">
                {{ findCurrency(m.currency_code)?.symbol ?? '' }} {{ m.currency_code }}
              </td>
              <td class="px-4 py-2 text-right text-base-content">{{ m.amount }}</td>
              <td class="px-4 py-2 text-right text-base-content/70">
                <template v-if="m.counter_amount">
                  {{ findCurrency(m.counter_currency_code)?.symbol ?? '' }} {{ m.counter_amount }}
                </template>
                <span v-else>—</span>
              </td>
              <td class="px-4 py-2 text-base-content/70">{{ m.reason ?? '—' }}</td>
              <td class="px-4 py-2 text-base-content/70 text-xs">{{ m.performer?.name ?? '—' }}</td>
              <td class="px-4 py-2 text-right">
                <Link :href="`/exchange/movements/${m.id}`" class="text-primary hover:underline">View</Link>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="mt-4">
        <UiPagination :paginator="movements" />
      </div>
    </UiCard>
  </AuthenticatedLayout>
</template>
