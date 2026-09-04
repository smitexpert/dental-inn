<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import PageHeader from '@/Components/ui/PageHeader.vue'
import KpiStrip from '@/Components/ui/KpiStrip.vue'
import FilterBar from '@/Components/ui/FilterBar.vue'
import Card from '@/Components/ui/Card.vue'
import Button from '@/Components/ui/Button.vue'
import Badge from '@/Components/ui/Badge.vue'
import EmptyState from '@/Components/ui/EmptyState.vue'
import Pagination from '@/Components/ui/Pagination.vue'
import ResponsiveTable from '@/Components/ui/ResponsiveTable.vue'
import Icon from '@/Components/ui/Icon.vue'

const props = defineProps({
  invoices: Object,
  filters: Object,
  statuses: Array,
  kpis: { type: Array, default: () => [] },
})

const q = ref(props.filters.q ?? '')
const status = ref(props.filters.status ?? '')
const dateFrom = ref(props.filters.date_from ?? '')
const dateTo = ref(props.filters.date_to ?? '')

let t
function applyFilters() {
  clearTimeout(t)
  t = setTimeout(() => {
    router.get(
      '/billing/invoices',
      { q: q.value, status: status.value, date_from: dateFrom.value, date_to: dateTo.value },
      { preserveState: true, replace: true },
    )
  }, 300)
}
watch([q, status, dateFrom, dateTo], applyFilters)

function goPage(n) {
  router.get(
    `/billing/invoices?page=${n}`,
    { q: q.value, status: status.value, date_from: dateFrom.value, date_to: dateTo.value },
    { preserveState: true },
  )
}

function statusBadge(s) {
  const m = {
    draft: 'neutral',
    issued: 'info',
    partially_paid: 'warning',
    paid: 'success',
    overdue: 'danger',
    cancelled: 'neutral',
    refunded: 'neutral',
  }
  return m[s] ?? 'neutral'
}

const columns = [
  { key: 'invoice_number', label: 'Number' },
  { key: 'patient', label: 'Patient' },
  { key: 'invoice_date', label: 'Issued' },
  { key: 'due_date', label: 'Due' },
  { key: 'total', label: 'Total' },
  { key: 'balance_due', label: 'Balance' },
  { key: 'status', label: 'Status' },
  { key: 'actions', label: '' },
]
</script>

<template>
  <Head title="Invoices" />
  <AuthenticatedLayout>
    <div class="space-y-5">
      <PageHeader
        title="Invoices"
        subtitle="Track issued invoices, balances, and collection status across all patients."
        icon="receipt-text"
        eyebrow="Billing"
      >
        <template #actions>
          <Link href="/billing/invoices/create">
            <Button icon="plus">New invoice</Button>
          </Link>
        </template>
      </PageHeader>
      <KpiStrip :tiles="kpis" />

      <FilterBar title="Filters">
        <template #aside>
          <Badge variant="primary">
            {{ invoices.total }} {{ invoices.total === 1 ? 'invoice' : 'invoices' }}
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
              placeholder="Search by invoice number or patient"
              class="input-shell h-10 pl-9 pr-9"
            />
          </div>
        </div>
        <select v-model="status" class="input-shell h-10" @change="applyFilters">
          <option value="">All statuses</option>
          <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
        </select>
        <input v-model="dateFrom" type="date" class="input-shell h-10" />
        <input v-model="dateTo" type="date" class="input-shell h-10" />
      </FilterBar>

      <Card :padded="false">
        <EmptyState
          v-if="invoices.data.length === 0"
          icon="receipt-text"
          title="No invoices yet"
          description="Create your first invoice to start tracking billing and payments."
        >
          <template #action>
            <Link href="/billing/invoices/create">
              <Button icon="plus">New invoice</Button>
            </Link>
          </template>
        </EmptyState>

        <ResponsiveTable v-else :columns="columns" :rows="invoices.data">
          <template #cell:invoice_number="{ row }">
            <div class="font-medium font-mono text-xs">{{ row.invoice_number }}</div>
          </template>
          <template #cell:patient="{ row }">
            <div class="font-medium">{{ row.patient?.name ?? '—' }}</div>
            <div v-if="row.patient?.patient_code" class="text-xs text-muted">
              {{ row.patient.patient_code }}
            </div>
          </template>
          <template #cell:invoice_date="{ row }">
            <span class="text-muted whitespace-nowrap">{{ row.invoice_date }}</span>
          </template>
          <template #cell:due_date="{ row }">
            <span class="text-muted whitespace-nowrap">{{ row.due_date || '—' }}</span>
          </template>
          <template #cell:total="{ row }">
            <div class="text-right font-medium tabular-nums whitespace-nowrap">{{ row.total }}</div>
          </template>
          <template #cell:balance_due="{ row }">
            <div class="text-right font-medium tabular-nums whitespace-nowrap">{{ row.balance_due }}</div>
          </template>
          <template #cell:status="{ row }">
            <Badge :variant="statusBadge(row.status)" dot class="whitespace-nowrap">{{ row.status_label }}</Badge>
          </template>
          <template #cell:actions="{ row }">
            <div class="inline-flex items-center gap-1 justify-end">
              <Link
                :href="`/billing/invoices/${row.id}`"
                class="p-1.5 rounded-md text-muted hover:bg-base-200 hover:text-primary transition-colors"
                :aria-label="`View invoice ${row.invoice_number}`"
                :title="`View invoice`"
              >
                <Icon name="eye" size="sm" />
              </Link>
              <Link
                v-if="row.status === 'draft'"
                :href="`/billing/invoices/${row.id}/edit`"
                class="p-1.5 rounded-md text-muted hover:bg-base-200 hover:text-primary transition-colors"
                :aria-label="`Edit invoice ${row.invoice_number}`"
                :title="`Edit invoice`"
              >
                <Icon name="edit" size="sm" />
              </Link>
            </div>
          </template>
          <template #card="{ row }">
            <div class="app-card p-4 space-y-2">
              <div class="font-medium font-mono text-xs">{{ row.invoice_number }}</div>
              <div class="font-medium">{{ row.patient?.name ?? '—' }}</div>
              <div class="text-sm text-muted">Issued {{ row.invoice_date }}</div>
              <div class="flex items-center justify-between">
                <Badge :variant="statusBadge(row.status)" dot class="whitespace-nowrap">{{ row.status_label }}</Badge>
                <span class="font-semibold tabular-nums">{{ row.balance_due }}</span>
              </div>
            </div>
          </template>
        </ResponsiveTable>

        <div class="px-5 pb-4">
          <Pagination
            :current-page="invoices.current_page"
            :last-page="invoices.last_page"
            :total="invoices.total"
            :per-page="invoices.per_page"
            @page="goPage"
          />
        </div>
      </Card>
    </div>
  </AuthenticatedLayout>
</template>
