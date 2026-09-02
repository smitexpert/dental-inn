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

const props = defineProps({ expenses: Object, filters: Object, statuses: Array, categories: Array })

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
</script>

<template>
  <Head title="Expenses" />
  <AuthenticatedLayout>
    <div class="space-y-5">
      <PageHeader
        title="Expenses"
        subtitle="Track clinic expenses, vendors, and approval status."
        icon="wallet"
        eyebrow="Billing"
      >
        <template #actions>
          <Link href="/billing/expenses/create">
            <Button icon="plus">Submit expense</Button>
          </Link>
        </template>
      </PageHeader>

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

        <div v-else class="overflow-x-auto">
          <table class="app-table">
            <thead>
              <tr>
                <th>Date</th>
                <th>Category</th>
                <th>Vendor</th>
                <th class="text-right">Amount</th>
                <th>Method</th>
                <th class="w-32">Status</th>
                <th class="w-20 text-right">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="e in expenses.data" :key="e.id">
                <td class="text-muted">{{ e.expense_date }}</td>
                <td class="font-medium">{{ e.category.name }}</td>
                <td class="text-muted">{{ e.vendor ?? '—' }}</td>
                <td class="text-right font-medium tabular-nums">{{ e.amount }} {{ e.currency_code }}</td>
                <td class="text-muted">{{ e.payment_method_label }}</td>
                <td>
                  <Badge :variant="e.status_color" dot>{{ e.status_label }}</Badge>
                </td>
                <td class="text-right">
                  <Link
                    :href="`/billing/expenses/${e.id}`"
                    class="p-1.5 rounded-md text-muted hover:bg-base-200 hover:text-primary transition-colors inline-flex"
                    aria-label="View expense"
                    title="View expense"
                  >
                    <Icon name="eye" size="sm" />
                  </Link>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

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
