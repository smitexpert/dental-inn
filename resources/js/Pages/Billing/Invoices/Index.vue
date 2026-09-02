<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import PageHeader from '@/Components/ui/PageHeader.vue'
import FilterBar from '@/Components/ui/FilterBar.vue'
import Card from '@/Components/ui/Card.vue'
import Button from '@/Components/ui/Button.vue'
import Badge from '@/Components/ui/Badge.vue'
import EmptyState from '@/Components/ui/EmptyState.vue'
import Pagination from '@/Components/ui/Pagination.vue'
import Icon from '@/Components/ui/Icon.vue'

const props = defineProps({ invoices: Object, filters: Object, statuses: Array })

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

        <div v-else class="overflow-x-auto">
          <table class="app-table">
            <thead>
              <tr>
                <th>Number</th>
                <th>Patient</th>
                <th>Issued</th>
                <th>Due</th>
                <th class="text-right w-24 whitespace-nowrap">Total</th>
                <th class="text-right w-24 whitespace-nowrap">Balance</th>
                <th class="w-40 whitespace-nowrap">Status</th>
                <th class="w-20 text-right">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="i in invoices.data" :key="i.id">
                <td>
                  <div class="font-medium font-mono text-xs">{{ i.invoice_number }}</div>
                </td>
                <td>
                  <div class="font-medium">{{ i.patient?.name ?? '—' }}</div>
                  <div v-if="i.patient?.patient_code" class="text-xs text-muted">
                    {{ i.patient.patient_code }}
                  </div>
                </td>
                <td class="text-muted whitespace-nowrap">{{ i.invoice_date }}</td>
                <td class="text-muted whitespace-nowrap">{{ i.due_date || '—' }}</td>
                <td class="text-right font-medium tabular-nums whitespace-nowrap">{{ i.total }}</td>
                <td class="text-right font-medium tabular-nums whitespace-nowrap">{{ i.balance_due }}</td>
                <td>
                  <Badge :variant="statusBadge(i.status)" dot class="whitespace-nowrap">{{ i.status_label }}</Badge>
                </td>
                <td class="text-right">
                  <div class="inline-flex items-center gap-1">
                    <Link
                      :href="`/billing/invoices/${i.id}`"
                      class="p-1.5 rounded-md text-muted hover:bg-base-200 hover:text-primary transition-colors"
                      :aria-label="`View invoice ${i.invoice_number}`"
                      :title="`View invoice`"
                    >
                      <Icon name="eye" size="sm" />
                    </Link>
                    <Link
                      v-if="i.status === 'draft'"
                      :href="`/billing/invoices/${i.id}/edit`"
                      class="p-1.5 rounded-md text-muted hover:bg-base-200 hover:text-primary transition-colors"
                      :aria-label="`Edit invoice ${i.invoice_number}`"
                      :title="`Edit invoice`"
                    >
                      <Icon name="edit" size="sm" />
                    </Link>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

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
