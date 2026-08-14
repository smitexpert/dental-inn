<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import UiCard from '@/Components/ui/Card.vue'
import UiBadge from '@/Components/ui/Badge.vue'
import UiButton from '@/Components/ui/Button.vue'
import UiInput from '@/Components/ui/Input.vue'
import UiSelect from '@/Components/ui/Select.vue'
import UiPagination from '@/Components/ui/Pagination.vue'
import UiEmptyState from '@/Components/ui/EmptyState.vue'
import { ref, watch } from 'vue'

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
      { q: q.value, status: status.value, category_id: categoryId.value, date_from: dateFrom.value, date_to: dateTo.value },
      { preserveState: true, replace: true },
    )
  }, 300)
}
watch([q, status, categoryId, dateFrom, dateTo], applyFilters)
</script>

<template>
  <Head title="Expenses" />
  <AuthenticatedLayout>
    <UiCard title="Expenses">
      <template #header>
        <div class="flex items-center justify-between gap-3">
          <h3 class="text-base font-semibold text-foreground">Expenses</h3>
          <Link href="/billing/expenses/create"><UiButton>Submit expense</UiButton></Link>
        </div>
      </template>

      <div class="grid grid-cols-5 gap-3 mb-4">
        <UiInput v-model="q" placeholder="Search vendor / description" />
        <UiSelect v-model="status" :options="[{ label: 'All statuses', value: '' }, ...statuses.map((s) => ({ label: s.label, value: s.value }))]" />
        <UiSelect v-model="categoryId" :options="[{ label: 'All categories', value: '' }, ...categories.map((c) => ({ label: c.name, value: c.id }))]" />
        <UiInput v-model="dateFrom" type="date" />
        <UiInput v-model="dateTo" type="date" />
      </div>

      <UiEmptyState v-if="expenses.data.length === 0" title="No expenses yet" />

      <div v-else class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-surface-muted text-muted-foreground">
            <tr>
              <th class="text-left px-4 py-2">Date</th>
              <th class="text-left px-4 py-2">Category</th>
              <th class="text-left px-4 py-2">Vendor</th>
              <th class="text-right px-4 py-2">Amount</th>
              <th class="text-left px-4 py-2">Method</th>
              <th class="text-left px-4 py-2">Status</th>
              <th class="text-right px-4 py-2">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="e in expenses.data" :key="e.id" class="border-b border-border last:border-b-0">
              <td class="px-4 py-2">{{ e.expense_date }}</td>
              <td class="px-4 py-2">{{ e.category.name }}</td>
              <td class="px-4 py-2">{{ e.vendor ?? '—' }}</td>
              <td class="px-4 py-2 text-right font-medium">{{ e.amount }} {{ e.currency_code }}</td>
              <td class="px-4 py-2">{{ e.payment_method_label }}</td>
              <td class="px-4 py-2">
                <UiBadge :variant="e.status_color">{{ e.status_label }}</UiBadge>
              </td>
              <td class="px-4 py-2 text-right">
                <Link :href="`/billing/expenses/${e.id}`" class="text-primary-600 hover:underline">View</Link>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="mt-4"><UiPagination :paginator="expenses" /></div>
    </UiCard>
  </AuthenticatedLayout>
</template>
