<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import PageHeaderSticky from '@/Components/ui/PageHeaderSticky.vue'
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
  expenses: Object,
  filters: Object,
  statuses: Array,
  categories: Array,
  kpis: { type: Array, default: () => [] },
})

const q = ref(props.filters.q ?? '')
const status = ref(props.filters.status ?? '')
const categoryId = ref(props.filters.category_id ?? '')
const dateFrom = ref(props.filters.date_from ?? '')
const dateTo = ref(props.filters.date_to ?? '')

let t
function applyFilters() {
  clearTimeout(t)
  t = setTimeout(() => {
    router.get(
      '/billing/expenses',
      {
        q: q.value,
        status: status.value,
        category_id: categoryId.value,
        date_from: dateFrom.value,
        date_to: dateTo.value,
      },
      { preserveState: true, replace: true },
    )
  }, 300)
}
watch([q, status, categoryId, dateFrom, dateTo], applyFilters)

function goPage(n) {
  router.get(
    `/billing/expenses?page=${n}`,
    {
      q: q.value,
      status: status.value,
      category_id: categoryId.value,
      date_from: dateFrom.value,
      date_to: dateTo.value,
    },
    { preserveState: true },
  )
}

const columns = [
  { key: 'expense_date', label: 'Date' },
  { key: 'category', label: 'Category' },
  { key: 'vendor', label: 'Vendor' },
  { key: 'amount', label: 'Amount' },
  { key: 'payment_method_label', label: 'Method' },
  { key: 'status', label: 'Status' },
  { key: 'actions', label: '' },
]
</script>

<template>
  <Head title="Expenses" />
  <AuthenticatedLayout>
    <template #pageHeader>
      <PageHeaderSticky
        title="Expenses"
        :breadcrumb="[{ label: 'Billing' }, { label: 'Expenses' }]"
        meta="Track clinic expenses, vendors, and approval status."
      >
        <template #actions>
          <Link href="/billing/expenses/create">
            <Button icon="plus">Submit expense</Button>
          </Link>
        </template>
      </PageHeaderSticky>
    </template>

    <div class="space-y-5 pt-5">
      <KpiStrip :tiles="kpis" />

      <FilterBar title="Filters">
        <template #aside>
          <Badge variant="primary">
            {{ expenses.total }} {{ expenses.total === 1 ? 'expense' : 'expenses' }}
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
              placeholder="Search by vendor or description"
              class="input-shell h-10 pl-9 pr-9"
            />
          </div>
        </div>
        <select v-model="status" class="input-shell h-10" @change="applyFilters">
          <option value="">All statuses</option>
          <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
        </select>
        <select v-model="categoryId" class="input-shell h-10" @change="applyFilters">
          <option value="">All categories</option>
          <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
        </select>
        <input v-model="dateFrom" type="date" class="input-shell h-10" />
        <input v-model="dateTo" type="date" class="input-shell h-10" />
      </FilterBar>

      <Card :padded="false">
        <EmptyState
          v-if="expenses.data.length === 0"
          icon="wallet"
          title="No expenses yet"
          description="Submit your first clinic expense to start tracking."
        >
          <template #action>
            <Link href="/billing/expenses/create">
              <Button icon="plus">Submit expense</Button>
            </Link>
          </template>
        </EmptyState>

        <ResponsiveTable v-else :columns="columns" :rows="expenses.data">
          <template #cell:expense_date="{ row }">
            <span class="text-muted">{{ row.expense_date }}</span>
          </template>
          <template #cell:category="{ row }">
            <span class="font-medium">{{ row.category.name }}</span>
          </template>
          <template #cell:vendor="{ row }">
            <span class="text-muted">{{ row.vendor ?? '—' }}</span>
          </template>
          <template #cell:amount="{ row }">
            <div class="text-right font-medium tabular-nums">{{ row.amount }} {{ row.currency_code }}</div>
          </template>
          <template #cell:payment_method_label="{ row }">
            <span class="text-muted">{{ row.payment_method_label }}</span>
          </template>
          <template #cell:status="{ row }">
            <Badge :variant="row.status_color" dot>{{ row.status_label }}</Badge>
          </template>
          <template #cell:actions="{ row }">
            <div class="text-right">
              <Link
                :href="`/billing/expenses/${row.id}`"
                class="p-1.5 rounded-md text-muted hover:bg-base-200 hover:text-primary transition-colors inline-flex"
                aria-label="View expense"
                title="View expense"
              >
                <Icon name="eye" size="sm" />
              </Link>
            </div>
          </template>
          <template #card="{ row }">
            <div class="app-card p-4 space-y-2">
              <div class="text-sm text-muted">{{ row.expense_date }}</div>
              <div class="font-medium">{{ row.category.name }}</div>
              <div class="text-sm text-muted">{{ row.vendor ?? '—' }}</div>
              <div class="flex items-center justify-between">
                <Badge :variant="row.status_color" dot>{{ row.status_label }}</Badge>
                <span class="font-semibold tabular-nums">{{ row.amount }} {{ row.currency_code }}</span>
              </div>
            </div>
          </template>
        </ResponsiveTable>

        <div class="px-5 pb-4">
          <Pagination
            :current-page="expenses.current_page"
            :last-page="expenses.last_page"
            :total="expenses.total"
            :per-page="expenses.per_page"
            @page="goPage"
          />
        </div>
      </Card>
    </div>
  </AuthenticatedLayout>
</template>
