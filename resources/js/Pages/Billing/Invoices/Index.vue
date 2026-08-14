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
    <UiCard title="Invoices">
      <template #header>
        <div class="flex items-center justify-between gap-3">
          <h3 class="text-base font-semibold text-base-content">Invoices</h3>
          <Link href="/billing/invoices/create"><UiButton>New invoice</UiButton></Link>
        </div>
      </template>

      <div class="grid grid-cols-4 gap-3 mb-4">
        <UiInput v-model="q" placeholder="Search invoice / patient" />
        <UiSelect
          v-model="status"
          :options="[{ label: 'All statuses', value: '' }, ...statuses.map((s) => ({ label: s.label, value: s.value }))]"
        />
        <UiInput v-model="dateFrom" type="date" />
        <UiInput v-model="dateTo" type="date" />
      </div>

      <UiEmptyState v-if="invoices.data.length === 0" title="No invoices yet" description="Create one to get started." />

      <div v-else class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-base-200 text-base-content/70">
            <tr>
              <th class="text-left px-4 py-2">Number</th>
              <th class="text-left px-4 py-2">Patient</th>
              <th class="text-left px-4 py-2">Date</th>
              <th class="text-left px-4 py-2">Due</th>
              <th class="text-right px-4 py-2">Total</th>
              <th class="text-right px-4 py-2">Balance</th>
              <th class="text-left px-4 py-2">Status</th>
              <th class="text-right px-4 py-2">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="i in invoices.data" :key="i.id" class="border-b border-base-300 last:border-b-0">
              <td class="px-4 py-2 text-base-content font-mono text-xs">{{ i.invoice_number }}</td>
              <td class="px-4 py-2 text-base-content">{{ i.patient?.name ?? '—' }}</td>
              <td class="px-4 py-2 text-base-content/70">{{ i.invoice_date }}</td>
              <td class="px-4 py-2 text-base-content/70">{{ i.due_date }}</td>
              <td class="px-4 py-2 text-right text-base-content font-medium">{{ i.total }}</td>
              <td class="px-4 py-2 text-right text-base-content">{{ i.balance_due }}</td>
              <td class="px-4 py-2">
                <UiBadge :variant="statusBadge(i.status)">{{ i.status_label }}</UiBadge>
              </td>
              <td class="px-4 py-2 text-right">
                <Link :href="`/billing/invoices/${i.id}`" class="text-primary hover:underline">View</Link>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="mt-4">
        <UiPagination :paginator="invoices" />
      </div>
    </UiCard>
  </AuthenticatedLayout>
</template>
