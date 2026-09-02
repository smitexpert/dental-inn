<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import PageHeader from '@/Components/ui/PageHeader.vue'
import FilterBar from '@/Components/ui/FilterBar.vue'
import Card from '@/Components/ui/Card.vue'
import Badge from '@/Components/ui/Badge.vue'
import Stat from '@/Components/ui/Stat.vue'
import EmptyState from '@/Components/ui/EmptyState.vue'
import Pagination from '@/Components/ui/Pagination.vue'
import Icon from '@/Components/ui/Icon.vue'

const props = defineProps({
  payments: Object,
  filters: Object,
  methods: Array,
  aggregates: Object,
})

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

function goPage(n) {
  router.get(
    `/billing/payments?page=${n}`,
    { q: q.value, method: method.value, date_from: dateFrom.value, date_to: dateTo.value },
    { preserveState: true },
  )
}
</script>

<template>
  <Head title="Payments" />
  <AuthenticatedLayout>
    <div class="space-y-5">
      <PageHeader
        title="Payments"
        subtitle="Track all payments received against issued invoices."
        icon="credit-card"
        eyebrow="Billing"
      />

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        <Stat
          label="Payments"
          :value="aggregates?.count ?? 0"
          :hint="`${aggregates?.distinct_invoices ?? 0} invoice${(aggregates?.distinct_invoices ?? 0) === 1 ? '' : 's'}`"
          icon="credit-card"
          tone="primary"
        />
        <Stat
          label="Total collected"
          :value="aggregates?.total_collected ?? '0.00'"
          icon="banknote"
          tone="success"
        />
        <Stat
          label="Avg. payment"
          :value="aggregates?.avg_payment ?? '0.00'"
          icon="calculator"
          tone="info"
        />
        <Stat
          label="Outstanding"
          :value="aggregates?.outstanding ?? '0.00'"
          :hint="`across ${aggregates?.distinct_invoices_seen ?? 0} invoice${(aggregates?.distinct_invoices_seen ?? 0) === 1 ? '' : 's'}`"
          icon="alert-circle"
          tone="warning"
        />
      </div>

      <FilterBar title="Filters">
        <template #aside>
          <Badge variant="primary">
            {{ payments.total }} {{ payments.total === 1 ? 'payment' : 'payments' }}
          </Badge>
        </template>
        <div class="md:col-span-2">
          <div class="relative">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-(--color-muted) pointer-events-none">
              <Icon name="search" size="sm" />
            </span>
            <input
              v-model="q"
              type="search"
              placeholder="Search by invoice or reference"
              class="input-shell h-10 pl-9"
            />
          </div>
        </div>
        <select v-model="method" class="input-shell h-10" @change="applyFilters">
          <option value="">All methods</option>
          <option v-for="m in methods" :key="m.value" :value="m.value">{{ m.label }}</option>
        </select>
        <input v-model="dateFrom" type="date" class="input-shell h-10" />
        <input v-model="dateTo" type="date" class="input-shell h-10" />
      </FilterBar>

      <Card :padded="false">
        <EmptyState
          v-if="payments.data.length === 0"
          icon="credit-card"
          title="No payments recorded yet"
          description="Record a payment against an invoice to see it here."
        />

        <div v-else class="overflow-x-auto">
          <table class="app-table">
            <thead>
              <tr>
                <th>Date</th>
                <th>Invoice</th>
                <th>Patient</th>
                <th class="text-right">Amount</th>
                <th>Method</th>
                <th>Reference</th>
                <th>Received by</th>
                <th class="w-24 text-right">Receipt</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="p in payments.data" :key="p.id">
                <td class="text-(--color-muted)">{{ p.payment_date }}</td>
                <td>
                  <div class="font-medium font-mono text-xs">{{ p.invoice?.invoice_number ?? '—' }}</div>
                </td>
                <td>{{ p.invoice?.patient?.name ?? '—' }}</td>
                <td class="text-right font-medium tabular-nums">{{ p.amount }}</td>
                <td>{{ p.method_label }}</td>
                <td class="text-(--color-muted)">{{ p.reference ?? '—' }}</td>
                <td>{{ p.receiver?.name ?? '—' }}</td>
                <td class="text-right">
                  <Link
                    :href="`/billing/payments/${p.id}/receipt`"
                    class="p-1.5 rounded-md text-(--color-muted) hover:bg-base-200 hover:text-primary transition-colors inline-flex"
                    aria-label="View receipt"
                    title="View receipt"
                  >
                    <Icon name="receipt-text" size="sm" />
                  </Link>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="px-5 pb-4">
          <Pagination
            :current-page="payments.current_page"
            :last-page="payments.last_page"
            :total="payments.total"
            :per-page="payments.per_page"
            @page="goPage"
          />
        </div>
      </Card>
    </div>
  </AuthenticatedLayout>
</template>
