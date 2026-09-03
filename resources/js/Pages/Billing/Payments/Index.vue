<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import PageHeaderSticky from '@/Components/ui/PageHeaderSticky.vue'
import KpiStrip from '@/Components/ui/KpiStrip.vue'
import FilterBar from '@/Components/ui/FilterBar.vue'
import Card from '@/Components/ui/Card.vue'
import Badge from '@/Components/ui/Badge.vue'
import EmptyState from '@/Components/ui/EmptyState.vue'
import Pagination from '@/Components/ui/Pagination.vue'
import ResponsiveTable from '@/Components/ui/ResponsiveTable.vue'
import Icon from '@/Components/ui/Icon.vue'

const props = defineProps({
  payments: Object,
  filters: Object,
  methods: Array,
  aggregates: Object,
  kpis: { type: Array, default: () => [] },
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

const columns = [
  { key: 'payment_date', label: 'Date' },
  { key: 'invoice', label: 'Invoice' },
  { key: 'patient', label: 'Patient' },
  { key: 'amount', label: 'Amount' },
  { key: 'method_label', label: 'Method' },
  { key: 'reference', label: 'Reference' },
  { key: 'receiver', label: 'Received by' },
  { key: 'actions', label: '' },
]
</script>

<template>
  <Head title="Payments" />
  <AuthenticatedLayout>
    <template #pageHeader>
      <PageHeaderSticky
        title="Payments"
        :breadcrumb="[{ label: 'Billing' }, { label: 'Payments' }]"
        meta="Track all payments received against issued invoices."
      />
    </template>

    <div class="space-y-5 pt-5">
      <KpiStrip :tiles="kpis" />

      <FilterBar title="Filters">
        <template #aside>
          <Badge variant="primary">
            {{ payments.total }} {{ payments.total === 1 ? 'payment' : 'payments' }}
          </Badge>
        </template>
        <div class="md:col-span-2">
          <div class="relative">
            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-muted pointer-events-none">
              <Icon name="search" size="sm" />
            </span>
            <input
              v-model="q"
              type="search"
              placeholder="Search by invoice or reference"
              class="input-shell h-10 pl-9 pr-9"
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

        <ResponsiveTable v-else :columns="columns" :rows="payments.data">
          <template #cell:payment_date="{ row }">
            <span class="text-muted">{{ row.payment_date }}</span>
          </template>
          <template #cell:invoice="{ row }">
            <div class="font-medium font-mono text-xs">{{ row.invoice?.invoice_number ?? '—' }}</div>
          </template>
          <template #cell:patient="{ row }">
            <span>{{ row.invoice?.patient?.name ?? '—' }}</span>
          </template>
          <template #cell:amount="{ row }">
            <div class="text-right font-medium tabular-nums">{{ row.amount }}</div>
          </template>
          <template #cell:method_label="{ row }">
            <span>{{ row.method_label }}</span>
          </template>
          <template #cell:reference="{ row }">
            <span class="text-muted">{{ row.reference ?? '—' }}</span>
          </template>
          <template #cell:receiver="{ row }">
            <span>{{ row.receiver?.name ?? '—' }}</span>
          </template>
          <template #cell:actions="{ row }">
            <div class="text-right">
              <Link
                :href="`/billing/payments/${row.id}/receipt`"
                class="p-1.5 rounded-md text-muted hover:bg-base-200 hover:text-primary transition-colors inline-flex"
                aria-label="View receipt"
                title="View receipt"
              >
                <Icon name="receipt-text" size="sm" />
              </Link>
            </div>
          </template>
          <template #card="{ row }">
            <div class="app-card p-4 space-y-2">
              <div class="font-medium font-mono text-xs">{{ row.invoice?.invoice_number ?? '—' }}</div>
              <div class="text-sm text-muted">{{ row.payment_date }}</div>
              <div class="flex items-center justify-between">
                <span class="text-sm">{{ row.method_label }}</span>
                <span class="font-semibold tabular-nums">{{ row.amount }}</span>
              </div>
            </div>
          </template>
        </ResponsiveTable>

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
