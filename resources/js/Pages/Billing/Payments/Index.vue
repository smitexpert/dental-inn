<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import UiCard from '@/Components/ui/Card.vue'
import UiInput from '@/Components/ui/Input.vue'
import UiSelect from '@/Components/ui/Select.vue'
import UiPagination from '@/Components/ui/Pagination.vue'
import UiEmptyState from '@/Components/ui/EmptyState.vue'
import { ref, watch } from 'vue'

const props = defineProps({ payments: Object, filters: Object, methods: Array })

const q = ref(props.filters.q ?? '')
const method = ref(props.filters.method ?? '')
const dateFrom = ref(props.filters.date_from ?? '')
const dateTo = ref(props.filters.date_to ?? '')

let t
function applyFilters() {
  clearTimeout(t)
  t = setTimeout(() => {
    router.get(
      '/billing/payments',
      { q: q.value, method: method.value, date_from: dateFrom.value, date_to: dateTo.value },
      { preserveState: true, replace: true },
    )
  }, 300)
}
watch([q, method, dateFrom, dateTo], applyFilters)
</script>

<template>
  <Head title="Payments" />
  <AuthenticatedLayout>
    <UiCard title="Payments">
      <div class="grid grid-cols-4 gap-3 mb-4">
        <UiInput v-model="q" placeholder="Search by invoice / reference" />
        <UiSelect v-model="method" :options="[{ label: 'All methods', value: '' }, ...methods.map((m) => ({ label: m.label, value: m.value }))]" />
        <UiInput v-model="dateFrom" type="date" />
        <UiInput v-model="dateTo" type="date" />
      </div>

      <UiEmptyState v-if="payments.data.length === 0" title="No payments recorded yet" />

      <div v-else class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-surface-muted text-muted-foreground">
            <tr>
              <th class="text-left px-4 py-2">Date</th>
              <th class="text-left px-4 py-2">Invoice</th>
              <th class="text-left px-4 py-2">Patient</th>
              <th class="text-right px-4 py-2">Amount</th>
              <th class="text-left px-4 py-2">Method</th>
              <th class="text-left px-4 py-2">Reference</th>
              <th class="text-left px-4 py-2">Received by</th>
              <th class="text-right px-4 py-2">Receipt</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="p in payments.data" :key="p.id" class="border-b border-border last:border-b-0">
              <td class="px-4 py-2">{{ p.payment_date }}</td>
              <td class="px-4 py-2 text-foreground font-mono text-xs">{{ p.invoice?.invoice_number ?? '—' }}</td>
              <td class="px-4 py-2">{{ p.invoice?.patient?.name ?? '—' }}</td>
              <td class="px-4 py-2 text-right font-medium">{{ p.amount }}</td>
              <td class="px-4 py-2">{{ p.method_label }}</td>
              <td class="px-4 py-2 text-muted-foreground">{{ p.reference ?? '—' }}</td>
              <td class="px-4 py-2">{{ p.receiver?.name ?? '—' }}</td>
              <td class="px-4 py-2 text-right">
                <Link :href="`/billing/payments/${p.id}/receipt`" class="text-primary-600 hover:underline">View</Link>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="mt-4"><UiPagination :paginator="payments" /></div>
    </UiCard>
  </AuthenticatedLayout>
</template>
